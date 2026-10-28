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