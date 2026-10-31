<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

$isHttps = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'secure' => $isHttps,
    'samesite' => 'Strict',
    'path' => '/',
]);
session_start();

function respond(int $status, array $data): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

set_exception_handler(static function (Throwable $error): never {
    error_log('Store API request failed: ' . $error->getMessage());
    respond(500, ['error' => 'The server could not complete this request. Check the PHP error log.']);
});

function readJson(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        respond(400, ['error' => 'Request body must be valid JSON.']);
    }
    return $data;
}

function requireAdmin(bool $checkCsrf = true, array $roles = ['owner', 'co_admin']): void
{
    if (!in_array($_SESSION['admin_role'] ?? '', $roles, true)) {
        respond(401, ['error' => 'Please sign in as an administrator.']);
    }
    if ($checkCsrf) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!is_string($token) || !hash_equals($_SESSION['csrf'] ?? '', $token)) {
            respond(403, ['error' => 'Your session expired. Sign in again.']);
        }
    }
}

function database(array $config): PDO
{
    try {
        return new PDO(
            sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_port'], $config['db_name']),
            $config['db_user'],
            $config['db_password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    } catch (PDOException $error) {
        error_log('Store database connection failed: ' . $error->getMessage());
        respond(503, ['error' => 'Could not connect to the store database. Check config.php and the MySQL service.']);
    }
}

function fetchProducts(PDO $pdo): array
{
    $rows = $pdo->query('SELECT id, code, name, category, price, stock_quantity AS stockQuantity, tags, image_url AS image FROM products ORDER BY created_at, code')->fetchAll();
    foreach ($rows as &$row) {
        $row['price'] = (int) $row['price'];
        $row['stockQuantity'] = (int) $row['stockQuantity'];
        $row['tags'] = json_decode($row['tags'], true) ?: [];
    }
    return $rows;
}

function reserveOrderInventory(PDO $pdo, array $items, string $paymentMethod): array
{
    $requested = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            throw new InvalidArgumentException('The checkout cart is invalid.');
        }
        $id = trim((string) ($item['id'] ?? ''));
        $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
        if ($id === '' || strlen($id) > 100 || $quantity === false || $quantity < 1 || $quantity > 99
            || isset($requested[$id])) {
            throw new InvalidArgumentException('The checkout cart contains an invalid product or quantity.');
        }
        $requested[$id] = $quantity;
    }

    $orderId = 'RA-' . strtoupper(bin2hex(random_bytes(12)));
    $statusToken = bin2hex(random_bytes(32));
    $productsById = [];
    $subtotalPaise = 0;
    $pdo->beginTransaction();
    try {
        $select = $pdo->prepare('SELECT id, name, price, stock_quantity FROM products WHERE id = :id FOR UPDATE');
        $lockIds = array_keys($requested);
        sort($lockIds, SORT_STRING);
        foreach ($lockIds as $id) {
            $select->execute(['id' => $id]);
            $product = $select->fetch();
            if (!$product) {
                throw new InvalidArgumentException('A product in your cart is no longer available. Refresh the catalogue and try again.');
            }
            if ((int) $product['stock_quantity'] < $requested[$id]) {
                throw new InvalidArgumentException(sprintf(
                    'Only %d unit(s) of %s are currently in stock.',
                    (int) $product['stock_quantity'],
                    $product['name'],
                ));
            }
            $productsById[$id] = $product;
        }

        $decrement = $pdo->prepare(
            'UPDATE products SET stock_quantity = stock_quantity - :quantity '
            . 'WHERE id = :id AND stock_quantity >= :available_quantity',
        );
        $insertItem = $pdo->prepare(
            'INSERT INTO payment_order_items (order_id, product_id, quantity) VALUES (:order_id, :product_id, :quantity)',
        );
        $orderItems = [];
        foreach ($requested as $id => $quantity) {
            $product = $productsById[$id];
            $decrement->execute([
                'quantity' => $quantity,
                'id' => $id,
                'available_quantity' => $quantity,
            ]);
            if ($decrement->rowCount() !== 1) {
                throw new InvalidArgumentException(sprintf('The available stock for %s has changed. Please refresh and try again.', $product['name']));
            }
            $insertItem->execute(['order_id' => $orderId, 'product_id' => $id, 'quantity' => $quantity]);
            $price = (int) $product['price'];
            $subtotalPaise += $price * $quantity * 100;
            $orderItems[] = [
                'id' => $id,
                'name' => $product['name'],
                'price' => $price,
                'quantity' => $quantity,
            ];
        }

        $deliveryPaise = $subtotalPaise === 0 || $subtotalPaise >= 149900 ? 0 : 9900;
        $amountPaise = $subtotalPaise + $deliveryPaise;
        if ($amountPaise < 1) {
            throw new InvalidArgumentException('Your order total is invalid.');
        }

        $insertOrder = $pdo->prepare(
            'INSERT INTO payment_orders (order_id, payment_id, amount_paise, status, payment_method, status_token_hash) '
            . 'VALUES (:order_id, :payment_id, :amount_paise, :status, :payment_method, :token_hash)',
        );
        $insertOrder->execute([
            'order_id' => $orderId,
            'payment_id' => $paymentMethod === 'COD' ? 'cod-' . $orderId : 'pending-' . $orderId,
            'amount_paise' => $amountPaise,
            'status' => $paymentMethod === 'COD' ? 'COD_PLACED' : 'PENDING',
            'payment_method' => $paymentMethod,
            'token_hash' => hash('sha256', $statusToken),
        ]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }

    return [
        'orderId' => $orderId,
        'statusToken' => $statusToken,
        'items' => $orderItems,
        'subtotalPaise' => $subtotalPaise,
        'deliveryPaise' => $deliveryPaise,
        'amountPaise' => $amountPaise,
    ];
}

function releaseOrderInventory(PDO $pdo, string $orderId, string $status): void
{
    $pdo->beginTransaction();
    try {
        $statement = $pdo->prepare(
            'SELECT status, stock_released FROM payment_orders WHERE order_id = :order_id FOR UPDATE',
        );
        $statement->execute(['order_id' => $orderId]);
        $order = $statement->fetch();
        if (!$order || (bool) $order['stock_released'] || $order['status'] === 'PAID') {
            $pdo->commit();
            return;
        }

        $items = $pdo->prepare('SELECT product_id, quantity FROM payment_order_items WHERE order_id = :order_id');
        $items->execute(['order_id' => $orderId]);
        $restore = $pdo->prepare('UPDATE products SET stock_quantity = stock_quantity + :quantity WHERE id = :id');
        foreach ($items->fetchAll() as $item) {
            $restore->execute(['quantity' => $item['quantity'], 'id' => $item['product_id']]);
        }
        $update = $pdo->prepare(
            'UPDATE payment_orders SET status = :status, stock_released = TRUE WHERE order_id = :order_id',
        );
        $update->execute(['status' => $status, 'order_id' => $orderId]);
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

function isAllowedPaymentUrl(string $url, bool $allowLocalHttp): bool
{
    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        return false;
    }

    $parts = parse_url($url);
    if (!is_array($parts) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        return false;
    }
    if (($parts['scheme'] ?? '') === 'https') {
        return true;
    }

    return $allowLocalHttp
        && ($parts['scheme'] ?? '') === 'http'
        && in_array(strtolower($parts['host']), ['localhost', '127.0.0.1', '[::1]', '::1'], true);
}

function isLocalPaymentHost(string $url): bool
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true);
}

function createYoupiPayment(PDO $pdo, array $config, string $orderId, int $amountPaise, string $returnUrl): array
{
    $apiUrl = rtrim((string) ($config['youpi_api_url'] ?? ''), '/');
    $merchantId = trim((string) ($config['youpi_merchant_id'] ?? ''));
    $merchantKey = trim((string) ($config['youpi_merchant_key'] ?? ''));
    $allowLocalHttp = isLocalPaymentHost($apiUrl);

    if ($apiUrl === '' || $merchantId === '' || $merchantKey === ''
        || trim((string) ($config['youpi_webhook_secret'] ?? '')) === ''
        || !isAllowedPaymentUrl($apiUrl, true)) {
        respond(503, ['error' => 'YouPI payment settings are incomplete. Configure the API URL and merchant credentials in config.php.']);
    }
    if (!function_exists('curl_init')) {
        respond(503, ['error' => 'PHP cURL is required to connect to the payment gateway.']);
    }

    $payload = json_encode([
        'orderId' => $orderId,
        'amount' => sprintf('%d.%02d', intdiv($amountPaise, 100), $amountPaise % 100),
        'currency' => 'INR',
        'returnUrl' => $returnUrl,
        'metadata' => ['store' => 'Rama Aurantha'],
    ], JSON_THROW_ON_ERROR);

    $curl = curl_init($apiUrl . '/api/v1/payments');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Merchant-Id: ' . $merchantId,
            'X-Merchant-Key: ' . $merchantKey,
            'Idempotency-Key: ' . $orderId,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS | ($allowLocalHttp ? CURLPROTO_HTTP : 0),
    ]);

    $responseBody = curl_exec($curl);
    $httpStatus = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if ($responseBody === false || $httpStatus < 200 || $httpStatus >= 300) {
        error_log('YouPI payment creation failed; HTTP status ' . $httpStatus . ($curlError !== '' ? ', cURL: ' . $curlError : ''));
        releaseOrderInventory($pdo, $orderId, 'CREATE_FAILED');
        respond(502, ['error' => 'The payment gateway could not create this checkout. Your cart has not been cleared; please try again.']);
    }

    $result = json_decode($responseBody, true);
    $paymentId = is_array($result) ? trim((string) ($result['paymentId'] ?? '')) : '';
    $checkoutUrl = is_array($result) ? trim((string) ($result['checkoutUrl'] ?? '')) : '';
    $responseAmountPaise = is_array($result) ? $result['amountPaise'] ?? null : null;
    if ($paymentId === '' || strlen($paymentId) > 100
        || ($result['orderId'] ?? '') !== $orderId
        || (string) $responseAmountPaise !== (string) $amountPaise
        || ($result['currency'] ?? '') !== 'INR'
        || !isAllowedPaymentUrl($checkoutUrl, $allowLocalHttp)) {
        error_log('YouPI payment creation returned an invalid checkout response.');
        releaseOrderInventory($pdo, $orderId, 'CREATE_FAILED');
        respond(502, ['error' => 'The payment gateway returned an invalid checkout response. Your cart has not been cleared.']);
    }

    return ['payment_id' => $paymentId, 'checkout_url' => $checkoutUrl];
}

function storeImage(string $image, array $config): string
{
    if (preg_match('#^https://#i', $image)) {
        if (filter_var($image, FILTER_VALIDATE_URL) === false) {
            respond(422, ['error' => 'Enter a valid HTTPS image URL.']);
        }
        return $image;
    }

    if (!preg_match('#^data:image/(png|jpeg|webp);base64,([A-Za-z0-9+/=]+)$#', $image, $matches)) {
        respond(422, ['error' => 'Upload a PNG, JPEG, or WebP image, or use an HTTPS image URL.']);
    }
    $bytes = base64_decode($matches[2], true);
    if ($bytes === false || strlen($bytes) > 5 * 1024 * 1024) {
        respond(422, ['error' => 'Image must be smaller than 5 MB after resizing.']);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($bytes);
    $extensions = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        respond(422, ['error' => 'The uploaded file is not a supported image.']);
    }

    $directory = $config['upload_dir'];
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        respond(500, ['error' => 'Could not create the image upload directory.']);
    }
    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (file_put_contents($directory . DIRECTORY_SEPARATOR . $filename, $bytes, LOCK_EX) === false) {
        respond(500, ['error' => 'Could not save the uploaded image.']);
    }
    return rtrim($config['upload_url'], '/') . '/' . $filename;
}

function saveProduct(PDO $pdo, array $config, array $product): array
{
    $id = trim((string) ($product['id'] ?? ''));
    $code = trim((string) ($product['code'] ?? ''));
    $name = trim((string) ($product['name'] ?? ''));
    $category = $product['category'] ?? '';
    $price = filter_var($product['price'] ?? null, FILTER_VALIDATE_INT);
    $stockQuantity = filter_var($product['stockQuantity'] ?? 0, FILTER_VALIDATE_INT);
    $tags = $product['tags'] ?? [];
    $image = trim((string) ($product['image'] ?? ''));

    if ($id === '' || strlen($id) > 100 || $code === '' || strlen($code) > 24 || $name === '' || strlen($name) > 90) {
        respond(422, ['error' => 'Product code, name, or identifier is missing or too long.']);
    }
    if (!in_array($category, ['urli', 'soy'], true) || $price === false || $price < 1 || $price > 10000000) {
        respond(422, ['error' => 'Choose a collection and enter a valid product price.']);
    }
    if ($stockQuantity === false || $stockQuantity < 0 || $stockQuantity > 1000000) {
        respond(422, ['error' => 'Enter available inventory as a whole number from 0 to 1,000,000.']);
    }
    if (!is_array($tags) || count($tags) > 20) {
        respond(422, ['error' => 'Product tags are invalid.']);
    }
    $tags = array_values(array_filter(array_map(static fn ($tag) => trim((string) $tag), $tags), static fn ($tag) => $tag !== ''));
    if ($image === '') {
        respond(422, ['error' => 'A product image is required.']);
    }

    $imageUrl = storeImage($image, $config);
    try {
        $statement = $pdo->prepare(
            'INSERT INTO products (id, code, name, category, price, stock_quantity, tags, image_url) VALUES (:id, :code, :name, :category, :price, :stock_quantity, :tags, :image) '
            . 'ON DUPLICATE KEY UPDATE code = VALUES(code), name = VALUES(name), category = VALUES(category), price = VALUES(price), stock_quantity = VALUES(stock_quantity), tags = VALUES(tags), image_url = VALUES(image_url)',
        );
        $statement->execute([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'category' => $category,
            'price' => $price,
            'stock_quantity' => $stockQuantity,
            'tags' => json_encode($tags, JSON_THROW_ON_ERROR),
            'image' => $imageUrl,
        ]);
    } catch (PDOException $error) {
        if ((string) $error->getCode() === '23000') {
            respond(409, ['error' => 'That product code is already in use.']);
        }
        throw $error;
    }

    return ['id' => $id, 'code' => $code, 'name' => $name, 'category' => $category, 'price' => $price, 'stockQuantity' => $stockQuantity, 'tags' => $tags, 'image' => $imageUrl];
}

$configPath = __DIR__ . '/config.php';
if (!is_file($configPath)) {
    respond(503, ['error' => 'Backend setup is incomplete. Copy config.example.php to config.php and add your local settings.']);
}
$config = require $configPath;
if (!is_array($config)) {
    respond(500, ['error' => 'config.php must return a configuration array.']);
}

$resource = $_GET['resource'] ?? '';
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($resource === 'payments' && $action === 'create' && $method === 'POST') {
    $body = readJson();
    $items = $body['items'] ?? null;
    if (!is_array($items) || count($items) < 1 || count($items) > 50) {
        respond(422, ['error' => 'Choose between 1 and 50 products for checkout.']);
    }

    $siteUrl = rtrim(trim((string) ($config['storefront_url'] ?? '')), '/');
    $apiUrl = trim((string) ($config['youpi_api_url'] ?? ''));
    $allowLocalHttp = isLocalPaymentHost($apiUrl);
    if (trim((string) ($config['youpi_merchant_id'] ?? '')) === ''
        || trim((string) ($config['youpi_merchant_key'] ?? '')) === ''
        || trim((string) ($config['youpi_webhook_secret'] ?? '')) === ''
        || $apiUrl === '' || !isAllowedPaymentUrl($apiUrl, true)) {
        respond(503, ['error' => 'YouPI payment settings are incomplete. Configure the API URL, merchant credentials, and webhook secret in config.php.']);
    }
    if (!function_exists('curl_init')) {
        respond(503, ['error' => 'PHP cURL is required to connect to the payment gateway.']);
    }
    if ($siteUrl === '' || !isAllowedPaymentUrl($siteUrl, $allowLocalHttp)
        || parse_url($siteUrl, PHP_URL_QUERY) !== null
        || parse_url($siteUrl, PHP_URL_FRAGMENT) !== null
        || !in_array((string) parse_url($siteUrl, PHP_URL_PATH), ['', '/'], true)) {
        respond(503, ['error' => 'Set storefront_url to the public HTTPS origin of this website in config.php.']);
    }

    $pdo = database($config);
    try {
        $order = reserveOrderInventory($pdo, $items, 'UPI');
    } catch (InvalidArgumentException $error) {
        respond(422, ['error' => $error->getMessage()]);
    }

    $gateway = createYoupiPayment(
        $pdo,
        $config,
        $order['orderId'],
        $order['amountPaise'],
        $siteUrl . '/#/confirmation',
    );
    $update = $pdo->prepare(
        'UPDATE payment_orders SET payment_id = :payment_id WHERE order_id = :order_id',
    );
    $update->execute(['payment_id' => $gateway['payment_id'], 'order_id' => $order['orderId']]);

    respond(201, [
        'orderId' => $order['orderId'],
        'statusToken' => $order['statusToken'],
        'paymentId' => $gateway['payment_id'],
        'checkoutUrl' => $gateway['checkout_url'],
        'items' => $order['items'],
        'subtotalPaise' => $order['subtotalPaise'],
        'deliveryPaise' => $order['deliveryPaise'],
        'amountPaise' => $order['amountPaise'],
    ]);
}

if ($resource === 'orders' && $action === 'cod' && $method === 'POST') {
    $body = readJson();
    $items = $body['items'] ?? null;
    if (!is_array($items) || count($items) < 1 || count($items) > 50) {
        respond(422, ['error' => 'Choose between 1 and 50 products for checkout.']);
    }
    try {
        $order = reserveOrderInventory(database($config), $items, 'COD');
    } catch (InvalidArgumentException $error) {
        respond(422, ['error' => $error->getMessage()]);
    }
    respond(201, $order + ['status' => 'COD_PLACED']);
}

if ($resource === 'payments' && $action === 'status' && $method === 'POST') {
    $body = readJson();
    $orderId = trim((string) ($body['orderId'] ?? ''));
    $statusToken = (string) ($body['token'] ?? '');
    if ($orderId === '' || strlen($orderId) > 40 || !preg_match('/^[a-f0-9]{64}$/', $statusToken)) {
        respond(404, ['error' => 'Payment order not found.']);
    }

    $statement = database($config)->prepare(
        'SELECT status_token_hash, status FROM payment_orders WHERE order_id = :order_id',
    );
    $statement->execute(['order_id' => $orderId]);
    $order = $statement->fetch();
    if (!$order || !hash_equals((string) $order['status_token_hash'], hash('sha256', $statusToken))) {
        respond(404, ['error' => 'Payment order not found.']);
    }

    respond(200, ['status' => $order['status']]);
}

if ($resource === 'payments' && $action === 'webhook' && $method === 'POST') {
    $secret = trim((string) ($config['youpi_webhook_secret'] ?? ''));
    if ($secret === '') {
        respond(503, ['error' => 'The payment webhook is not configured.']);
    }
    $rawBody = file_get_contents('php://input', false, null, 0, 65537);
    if ($rawBody === false || strlen($rawBody) > 65536) {
        respond(413, ['error' => 'Webhook payload is invalid or too large.']);
    }
    $signature = $_SERVER['HTTP_X_GATEWAY_SIGNATURE'] ?? '';
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $rawBody, $secret);
    if (!is_string($signature) || !hash_equals($expectedSignature, $signature)) {
        respond(401, ['error' => 'Invalid payment webhook signature.']);
    }

    $payload = json_decode($rawBody, true);
    if (!is_array($payload)) {
        respond(400, ['error' => 'Webhook payload must be valid JSON.']);
    }
    $orderId = trim((string) ($payload['orderId'] ?? ''));
    $paymentId = trim((string) ($payload['paymentId'] ?? ''));
    $event = strtoupper(trim((string) ($payload['event'] ?? '')));
    $amountPaise = $payload['amountPaise'] ?? null;
    if ($orderId === '' || $paymentId === ''
        || (!is_string($amountPaise) && !is_int($amountPaise))
        || !preg_match('/^[0-9]+$/', (string) $amountPaise)) {
        respond(400, ['error' => 'Webhook payment details are invalid.']);
    }

    $statusByEvent = [
        'OBSERVED_SUCCESS' => 'AWAITING_RECONCILIATION',
        'RECONCILED' => 'PAID',
        'MANUAL_REVIEW' => 'MANUAL_REVIEW',
        'FAILED' => 'FAILED',
        'EXPIRED' => 'EXPIRED',
        'CANCELLED' => 'CANCELLED',
        'SUCCESS' => 'UNVERIFIED_SUCCESS',
    ];
    if (!isset($statusByEvent[$event])) {
        respond(400, ['error' => 'Webhook event is not supported.']);
    }
    if (($payload['currency'] ?? '') !== 'INR'
        || (string) ($payload['method'] ?? '') !== 'UPI'
        || ($event === 'RECONCILED' && ($payload['settlementState'] ?? '') !== 'RECONCILED')) {
        respond(422, ['error' => 'Webhook does not represent a reconciled UPI payment.']);
    }

    $pdo = database($config);
    $statement = $pdo->prepare(
        'SELECT payment_id, amount_paise, payment_method FROM payment_orders WHERE order_id = :order_id',
    );
    $statement->execute(['order_id' => $orderId]);
    $order = $statement->fetch();
    if (!$order || $order['payment_method'] !== 'UPI' || !hash_equals((string) $order['payment_id'], $paymentId)
        || (string) $order['amount_paise'] !== (string) $amountPaise) {
        error_log('YouPI webhook did not match a stored payment order.');
        respond(422, ['error' => 'Webhook does not match a stored payment order.']);
    }

    $nextStatus = $statusByEvent[$event];
    if (in_array($event, ['FAILED', 'EXPIRED', 'CANCELLED'], true)) {
        releaseOrderInventory($pdo, $orderId, $nextStatus);
    } else {
        $update = $pdo->prepare(
            'UPDATE payment_orders SET status = CASE WHEN status = \'PAID\' THEN status ELSE :status END, '
            . 'gateway_event = :event WHERE order_id = :order_id',
        );
        $update->execute([
            'status' => $nextStatus,
            'event' => $event,
            'order_id' => $orderId,
        ]);
    }
    respond(200, ['received' => true]);
}

if ($resource === 'admin' && $action === 'session' && $method === 'GET') {
    $role = $_SESSION['admin_role'] ?? null;
    respond(200, [
        'authenticated' => in_array($role, ['owner', 'co_admin'], true),
        'role' => $role,
        'username' => $_SESSION['admin_username'] ?? null,
        'csrf' => $_SESSION['csrf'] ?? null,
    ]);
}

if ($resource === 'admin' && $action === 'login' && $method === 'POST') {
    $body = readJson();
    $username = trim((string) ($body['username'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $username)) {
        respond(401, ['error' => 'Incorrect username or password.']);
    }

    $ownerUsername = trim((string) ($config['admin_username'] ?? 'admin'));
    $passwordHash = (string) ($config['admin_password_hash'] ?? '');
    if (strcasecmp($username, $ownerUsername) === 0) {
        if ($passwordHash === '' || $passwordHash === 'replace_with_a_password_hash') {
            respond(503, ['error' => 'Set admin_password_hash in config.php before signing in.']);
        }
        $authenticated = password_verify((string) ($body['password'] ?? ''), $passwordHash);
        $role = 'owner';
    } else {
        $pdo = database($config);
        $statement = $pdo->prepare(
            'SELECT password_hash FROM admin_users WHERE username = :username AND active = 1',
        );
        $statement->execute(['username' => $username]);
        $coAdmin = $statement->fetch();
        $authenticated = is_array($coAdmin)
            && password_verify((string) ($body['password'] ?? ''), (string) $coAdmin['password_hash']);
        $role = 'co_admin';
    }

    if (!$authenticated) {
        respond(401, ['error' => 'Incorrect username or password.']);
    }

    session_regenerate_id(true);
    $_SESSION['admin_role'] = $role;
    $_SESSION['admin_username'] = $username;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    respond(200, [
        'authenticated' => true,
        'username' => $username,
        'role' => $role,
        'csrf' => $_SESSION['csrf'],
    ]);
}

if ($resource === 'admin' && $action === 'create-user' && $method === 'POST') {
    requireAdmin(true, ['owner']);
    $body = readJson();
    $username = trim((string) ($body['username'] ?? ''));
    $password = (string) ($body['password'] ?? '');
    $ownerUsername = trim((string) ($config['admin_username'] ?? 'admin'));

    if (!preg_match('/^[A-Za-z0-9._-]{3,40}$/', $username)) {
        respond(422, ['error' => 'User ID must be 3–40 characters: letters, numbers, dot, underscore, or hyphen.']);
    }
    if (strcasecmp($username, $ownerUsername) === 0) {
        respond(409, ['error' => 'That user ID is reserved for the store owner.']);
    }
    if (strlen($password) < 12 || strlen($password) > 200) {
        respond(422, ['error' => 'Set a co-admin password between 12 and 200 characters.']);
    }

    try {
        $statement = database($config)->prepare(
            'INSERT INTO admin_users (username, password_hash) VALUES (:username, :password_hash)',
        );
        $statement->execute([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);
    } catch (PDOException $error) {
        if ((string) $error->getCode() === '23000') {
            respond(409, ['error' => 'That co-admin user ID already exists.']);
        }
        throw $error;
    }

    respond(201, ['created' => true, 'username' => $username, 'role' => 'co_admin']);
}

if ($resource === 'admin' && $action === 'logout' && $method === 'POST') {
    requireAdmin();
    $_SESSION = [];
    session_destroy();
    respond(200, ['authenticated' => false]);
}

if ($resource === 'products' && $method === 'GET') {
    respond(200, ['products' => fetchProducts(database($config))]);
}

if ($resource === 'products' && $method === 'POST') {
    requireAdmin();
    $pdo = database($config);
    $body = readJson();

    if ($action === 'save') {
        respond(200, ['product' => saveProduct($pdo, $config, $body)]);
    }
    if ($action === 'delete') {
        requireAdmin(true, ['owner']);
        $statement = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute(['id' => (string) ($body['id'] ?? '')]);
        respond(200, ['deleted' => $statement->rowCount() > 0]);
    }
    if ($action === 'import') {
        requireAdmin(true, ['owner']);
        $incoming = $body['products'] ?? null;
        if (!is_array($incoming) || count($incoming) > 500) {
            respond(422, ['error' => 'The catalogue import is invalid.']);
        }
        $pdo->beginTransaction();
        try {
            foreach ($incoming as $product) {
                if (!is_array($product)) {
                    throw new InvalidArgumentException('Invalid product in import.');
                }
                saveProduct($pdo, $config, $product);
            }
            $pdo->commit();
        } catch (Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $error;
        }
        respond(200, ['products' => fetchProducts($pdo)]);
    }
}

respond(404, ['error' => 'API route not found.']);