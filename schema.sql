USE rama;

CREATE TABLE IF NOT EXISTS products (
  id VARCHAR(100) NOT NULL,
  code VARCHAR(24) NOT NULL,
  name VARCHAR(90) NOT NULL,
  category ENUM('urli', 'soy') NOT NULL,
  price INT UNSIGNED NOT NULL,
  stock_quantity INT UNSIGNED NOT NULL DEFAULT 0,
  tags JSON NOT NULL,
  image_url TEXT NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY products_code_unique (code),
  KEY products_category_idx (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_orders (
  order_id VARCHAR(40) NOT NULL,
  payment_id VARCHAR(100) NOT NULL,
  amount_paise BIGINT UNSIGNED NOT NULL,
  status ENUM('PENDING', 'COD_PLACED', 'AWAITING_RECONCILIATION', 'MANUAL_REVIEW', 'PAID', 'FAILED', 'EXPIRED', 'CANCELLED', 'UNVERIFIED_SUCCESS', 'CREATE_FAILED') NOT NULL DEFAULT 'PENDING',
  payment_method ENUM('UPI', 'COD') NOT NULL DEFAULT 'UPI',
  stock_released BOOLEAN NOT NULL DEFAULT FALSE,
  status_token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  gateway_event VARCHAR(32) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (order_id),
  UNIQUE KEY payment_orders_payment_id_unique (payment_id),
  KEY payment_orders_status_idx (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_order_items (
  order_id VARCHAR(40) NOT NULL,
  product_id VARCHAR(100) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  PRIMARY KEY (order_id, product_id),
  KEY payment_order_items_product_idx (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS admin_users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(40) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('co_admin') NOT NULL DEFAULT 'co_admin',
  active BOOLEAN NOT NULL DEFAULT TRUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY admin_users_username_unique (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;