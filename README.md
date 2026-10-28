# Rama Aurantha storefront

## MySQL and PHP setup

The storefront reads product listings from MySQL through `api.php`. Product images uploaded in admin are written to the `uploads/` directory; the database stores their paths. PHP 8.1+ with PDO MySQL enabled is required.

1. In phpMyAdmin, select the database named `rama`, open the **SQL** tab, and paste the contents of `schema.sql` (also shown below). Run it to create the `products` table.

```sql
USE rama;

CREATE TABLE IF NOT EXISTS products (
	id VARCHAR(100) NOT NULL,
	code VARCHAR(24) NOT NULL,
	name VARCHAR(90) NOT NULL,
	category ENUM('urli', 'soy') NOT NULL,
	price INT UNSIGNED NOT NULL,
	tags JSON NOT NULL,
	image_url TEXT NOT NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (id),
	UNIQUE KEY products_code_unique (code),
	KEY products_category_idx (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

2. In the project folder, make a private config copy:

```sh
cp config.example.php config.php
```

Edit `config.php` with the MySQL username and password you use in phpMyAdmin. Generate an admin password hash in Terminal:

```sh
php -r 'echo password_hash("CHOOSE_A_STRONG_PASSWORD", PASSWORD_DEFAULT), PHP_EOL;'
```

Replace `CHOOSE_A_STRONG_PASSWORD` locally, run the command, and paste its output into `admin_password_hash` in `config.php`. Do not paste the plain password or hash into chat. `config.php` and uploaded images are excluded from Git.

3. For local testing, start PHP in the project folder:

```sh
php -S 127.0.0.1:8000
```

Open `http://127.0.0.1:8000`. Open **Product admin** in the footer and sign in with the password you chose. If the `products` table is empty, the first successful admin sign-in imports the catalogue currently stored in that browser. After that, product changes are shared through MySQL.

For deployment, upload the PHP files and `uploads/` directory to a PHP-enabled host, configure the database credentials there, and use HTTPS. The web server must be allowed to write to `uploads/`. Do not deploy the example config as your active config; use a strong unique admin password.
