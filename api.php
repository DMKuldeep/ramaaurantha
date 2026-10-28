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

function requireAdmin(bool $checkCsrf = true): void
{
    if (empty($_SESSION['admin'])) {
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
    $rows = $pdo->query('SELECT id, code, name, category, price, tags, image_url AS image FROM products ORDER BY created_at, code')->fetchAll();
    foreach ($rows as &$row) {
        $row['price'] = (int) $row['price'];
        $row['tags'] = json_decode($row['tags'], true) ?: [];
    }
    return $rows;
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
    $tags = $product['tags'] ?? [];
    $image = trim((string) ($product['image'] ?? ''));

    if ($id === '' || strlen($id) > 100 || $code === '' || strlen($code) > 24 || $name === '' || strlen($name) > 90) {
        respond(422, ['error' => 'Product code, name, or identifier is missing or too long.']);
    }
    if (!in_array($category, ['urli', 'soy'], true) || $price === false || $price < 1 || $price > 10000000) {
        respond(422, ['error' => 'Choose a collection and enter a valid product price.']);
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
            'INSERT INTO products (id, code, name, category, price, tags, image_url) VALUES (:id, :code, :name, :category, :price, :tags, :image) '
            . 'ON DUPLICATE KEY UPDATE code = VALUES(code), name = VALUES(name), category = VALUES(category), price = VALUES(price), tags = VALUES(tags), image_url = VALUES(image_url)',
        );
        $statement->execute([
            'id' => $id,
            'code' => $code,
            'name' => $name,
            'category' => $category,
            'price' => $price,
            'tags' => json_encode($tags, JSON_THROW_ON_ERROR),
            'image' => $imageUrl,
        ]);
    } catch (PDOException $error) {
        if ((string) $error->getCode() === '23000') {
            respond(409, ['error' => 'That product code is already in use.']);
        }
        throw $error;
    }

    return ['id' => $id, 'code' => $code, 'name' => $name, 'category' => $category, 'price' => $price, 'tags' => $tags, 'image' => $imageUrl];
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

if ($resource === 'admin' && $action === 'session' && $method === 'GET') {
    respond(200, ['authenticated' => !empty($_SESSION['admin']), 'csrf' => $_SESSION['csrf'] ?? null]);
}

if ($resource === 'admin' && $action === 'login' && $method === 'POST') {
    $passwordHash = (string) ($config['admin_password_hash'] ?? '');
    if ($passwordHash === '' || $passwordHash === 'replace_with_a_password_hash') {
        respond(503, ['error' => 'Set admin_password_hash in config.php before signing in.']);
    }
    $body = readJson();
    if (!password_verify((string) ($body['password'] ?? ''), $passwordHash)) {
        respond(401, ['error' => 'Incorrect admin password.']);
    }
    session_regenerate_id(true);
    $_SESSION['admin'] = true;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    respond(200, ['authenticated' => true, 'csrf' => $_SESSION['csrf']]);
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
        $statement = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $statement->execute(['id' => (string) ($body['id'] ?? '')]);
        respond(200, ['deleted' => $statement->rowCount() > 0]);
    }
    if ($action === 'import') {
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