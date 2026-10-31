# Rama Aurantha storefront

## MySQL and PHP setup

The storefront reads product listings and inventory from MySQL through `api.php`. Product images uploaded in admin are written to the `uploads/` directory; the database stores their paths. PHP 8.1+ with PDO MySQL enabled is required; YouPI payments also require PHP cURL.

1. In phpMyAdmin, select the database named `rama`, open the **SQL** tab, and run `schema.sql`. It creates the `products`, `payment_orders`, `payment_order_items`, and `admin_users` tables.

If you already created the old `products`/`payment_orders` tables, `CREATE TABLE IF NOT EXISTS` does not add new columns. In phpMyAdmin, run these migration statements **once** (skip any column already present):

```sql
ALTER TABLE products
  ADD COLUMN stock_quantity INT UNSIGNED NOT NULL DEFAULT 0;

ALTER TABLE payment_orders
  MODIFY status ENUM('PENDING', 'COD_PLACED', 'AWAITING_RECONCILIATION', 'MANUAL_REVIEW', 'PAID', 'FAILED', 'EXPIRED', 'CANCELLED', 'UNVERIFIED_SUCCESS', 'CREATE_FAILED') NOT NULL DEFAULT 'PENDING',
  ADD COLUMN payment_method ENUM('UPI', 'COD') NOT NULL DEFAULT 'UPI',
  ADD COLUMN stock_released BOOLEAN NOT NULL DEFAULT FALSE;

CREATE TABLE IF NOT EXISTS payment_order_items (
  order_id VARCHAR(40) NOT NULL,
  product_id VARCHAR(100) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  PRIMARY KEY (order_id, product_id),
  KEY payment_order_items_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Existing products start with zero available units. Sign in to Product admin and set the real stock quantity for every product before enabling checkout. New orders reserve the requested quantity atomically; failed, expired, or cancelled UPI checkouts restore it. COD orders reduce stock when placed.

2. Keep the existing private `config.php`; add the payment settings below to it. For a fresh install, copy `config.example.php` to `config.php` first:

```sh
cp config.example.php config.php
```

Edit `config.php` with the MySQL username and password you use in phpMyAdmin. Generate an admin password hash in Terminal:

```sh
php -r 'echo password_hash("CHOOSE_A_STRONG_PASSWORD", PASSWORD_DEFAULT), PHP_EOL;'
```

Replace `CHOOSE_A_STRONG_PASSWORD` locally, run the command, and paste its output into `admin_password_hash` in `config.php`. Do not paste the plain password or hash into chat. `config.php` and uploaded images are excluded from Git.

3. Configure YouPI in the private `config.php`:

```php
'storefront_url' => 'https://your-store-domain.example',
'youpi_api_url' => 'https://your-payment-api-domain.example',
'youpi_merchant_id' => 'YOUR_MERCHANT_ID',
'youpi_merchant_key' => 'YOUR_PRIVATE_MERCHANT_API_KEY',
'youpi_webhook_secret' => 'YOUR_WEBHOOK_SECRET',
```

Deploy YouPI separately on its own Node.js host with HTTPS. Configure the merchant's receiving UPI account and observer in YouPI's admin panel, then set the merchant webhook URL to `https://your-store-domain.example/api.php?resource=payments&action=webhook`. The storefront sends payment creation requests from PHP, so the merchant key and webhook secret are never put in browser JavaScript. Keep these values private; do not send passwords, keys, bank login details, or OTPs in chat.

The YouPI repository identifies itself as an experimental research project, not a certified or production-ready gateway. Its UPI observer alert is not settlement proof: this storefront only marks an order paid after a signed `RECONCILED` webhook matching the amount and payment ID. You must still reconcile the payment against the official bank statement. Obtain permission for the repository's commercial license before publishing, and use a regulated payment provider for real customer payments unless you have independently verified the legal, bank, and compliance requirements.

4. For local testing, start PHP in the project folder:

```sh
php -S 127.0.0.1:8000
```

Open `http://127.0.0.1:8000`. Open **Product admin** in the footer or visit `http://127.0.0.1:8000/#/admin`. The owner login uses `admin_username` (defaults to `admin`) and the password whose hash is in `admin_password_hash`. Generate the hash locally:

```sh
php -r 'echo password_hash("YOUR_NEW_PASSWORD", PASSWORD_DEFAULT), PHP_EOL;'
```

After signing in, use **Add a co-admin** to create a separate user ID and password (at least 12 characters). Share those credentials privately. Co-admins can add and edit products, but only the owner can delete products or create other co-admin accounts. The first owner sign-in imports the catalogue currently stored in that browser if the database catalogue is empty. Run the updated `schema.sql` first; it creates the `admin_users` table used for co-admin accounts.

For deployment, upload the PHP files and `uploads/` directory to a PHP-enabled host, configure the database credentials there, and use HTTPS. The web server must be allowed to write to `uploads/`. Do not deploy the example config as your active config; use a strong unique admin password.
