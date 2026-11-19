-- ============================================================
--  RACEL SASH FACTORY MANAGEMENT SYSTEM  -  Database
--  Import this file in phpMyAdmin (Import tab) or run it in the SQL tab.
--  After importing, open  http://localhost/racel-sash-factory/setup.php
--  to create the first admin account.
-- ============================================================

CREATE DATABASE IF NOT EXISTS racel_sash
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE racel_sash;

-- ---------- USERS (admin / employee / customer) ----------
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(120) NOT NULL,
  email         VARCHAR(150) NOT NULL UNIQUE,
  phone         VARCHAR(30)  DEFAULT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('admin','employee','customer') NOT NULL DEFAULT 'customer',
  is_active     TINYINT(1) NOT NULL DEFAULT 1,
  skills        VARCHAR(255) DEFAULT NULL,
  experience    VARCHAR(120) DEFAULT NULL,
  assigned_responsibilities TEXT DEFAULT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- PRODUCT CATALOG ----------
CREATE TABLE IF NOT EXISTS products (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name         VARCHAR(120) NOT NULL,
  category     VARCHAR(60)  NOT NULL,
  base_price   DECIMAL(12,2) NOT NULL DEFAULT 0,
  default_days INT NOT NULL DEFAULT 7,
  description  VARCHAR(255) DEFAULT NULL,
  is_active    TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

-- ---------- CUSTOMER ORDERS ----------
CREATE TABLE IF NOT EXISTS orders (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_no       INT UNIQUE DEFAULT NULL,            -- 1001, 1002 ... (sorted list for binary search)
  order_code     VARCHAR(20) UNIQUE DEFAULT NULL,    -- ORD-1001 (key for the hash table)
  customer_id    INT UNSIGNED NOT NULL,
  product_id     INT UNSIGNED NOT NULL,
  manager_id     INT UNSIGNED DEFAULT NULL,
  quantity       INT NOT NULL DEFAULT 1,
  width_cm       DECIMAL(8,2) DEFAULT NULL,
  height_cm      DECIMAL(8,2) DEFAULT NULL,
  wood_type      VARCHAR(60) DEFAULT NULL,
  notes          TEXT DEFAULT NULL,
  agreed_price   DECIMAL(12,2) NOT NULL DEFAULT 0,
  status         ENUM('pending','confirmed','in_production','finishing','ready','completed','cancelled')
                 NOT NULL DEFAULT 'pending',
  progress       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  est_days       INT DEFAULT NULL,
  est_completion DATE DEFAULT NULL,
  admin_seen     TINYINT(1) NOT NULL DEFAULT 0,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  confirmed_at   DATETIME DEFAULT NULL,
  completed_at   DATETIME DEFAULT NULL,
  FOREIGN KEY (customer_id) REFERENCES users(id),
  FOREIGN KEY (product_id)  REFERENCES products(id),
  FOREIGN KEY (manager_id)  REFERENCES users(id)
) ENGINE=InnoDB;

-- ---------- EMPLOYEES ASSIGNED TO AN ORDER ----------
CREATE TABLE IF NOT EXISTS order_assignments (
  order_id    INT UNSIGNED NOT NULL,
  employee_id INT UNSIGNED NOT NULL,
  assigned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (order_id, employee_id),
  FOREIGN KEY (order_id)    REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (employee_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- PROGRESS TIMELINE SHOWN TO THE CUSTOMER ----------
CREATE TABLE IF NOT EXISTS order_updates (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id   INT UNSIGNED NOT NULL,
  status     VARCHAR(30) NOT NULL,
  message    VARCHAR(500) NOT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- PAYMENTS (these are the sales records) ----------
CREATE TABLE IF NOT EXISTS payments (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT UNSIGNED NOT NULL,
  amount      DECIMAL(12,2) NOT NULL,
  type        ENUM('down_payment','partial','full') NOT NULL DEFAULT 'partial',
  method      VARCHAR(30) NOT NULL DEFAULT 'Cash',
  note        VARCHAR(255) DEFAULT NULL,
  paid_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  recorded_by INT UNSIGNED DEFAULT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- EXPENSES ----------
CREATE TABLE IF NOT EXISTS expenses (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  description  VARCHAR(200) NOT NULL,
  category     VARCHAR(60)  NOT NULL DEFAULT 'Materials',
  amount       DECIMAL(12,2) NOT NULL,
  expense_date DATE NOT NULL,
  created_by   INT UNSIGNED DEFAULT NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------- RAW MATERIAL INVENTORY ----------
CREATE TABLE IF NOT EXISTS materials (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  item_code     VARCHAR(40) NOT NULL UNIQUE,
  name          VARCHAR(120) NOT NULL,
  unit          VARCHAR(20) NOT NULL DEFAULT 'pcs',
  quantity      DECIMAL(12,2) NOT NULL DEFAULT 0,
  reorder_level DECIMAL(12,2) NOT NULL DEFAULT 0,
  unit_cost     DECIMAL(12,2) NOT NULL DEFAULT 0,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;


-- ---------- MATERIALS USED FOR EACH ORDER (ERD bridge entity) ----------
CREATE TABLE IF NOT EXISTS order_materials (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id    INT UNSIGNED NOT NULL,
  material_id INT UNSIGNED NOT NULL,
  quantity_used DECIMAL(12,2) NOT NULL DEFAULT 0,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (material_id) REFERENCES materials(id)
) ENGINE=InnoDB;

-- ---------- PRODUCT TRACKING (ERD entity; mirrors customer-facing timeline) ----------
CREATE TABLE IF NOT EXISTS product_tracking (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  production_status VARCHAR(30) NOT NULL,
  date_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  remarks VARCHAR(500) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------- STARTER DATA ----------
INSERT INTO products (name, category, base_price, default_days, description) VALUES
 ('Wooden Window Sash',   'Windows',   4500.00, 10, 'Custom-fit sliding or casement sash in solid wood.'),
 ('Louver Window',        'Windows',   3800.00,  8, 'Adjustable wooden louver panels for airflow and privacy.'),
 ('Wooden Door',          'Doors',     6500.00, 14, 'Solid panel or flush door finished to your specification.'),
 ('Door Frame (Jamb)',    'Doors',     2800.00,  7, 'Measured door jamb and trim set.'),
 ('Wooden Cabinet',       'Cabinets',  8500.00, 20, 'Built-in or standing cabinets with custom dimensions.'),
 ('Custom Wood Furniture','Furniture', 5000.00, 18, 'Tables, chairs, and shelving made to your design.');

INSERT INTO materials (item_code, name, unit, quantity, reorder_level, unit_cost) VALUES
 ('MHG-001', 'Mahogany Wood',   'board ft', 400, 100,  95.00),
 ('NRA-001', 'Narra Wood',      'board ft', 250,  80, 140.00),
 ('PIN-001', 'Pine Wood',       'board ft', 600, 150,  45.00),
 ('NAL-001', 'Nails (box)',     'box',       40,  10, 120.00),
 ('HNG-001', 'Hinges',          'pcs',      150,  40,  35.00),
 ('VRN-001', 'Varnish',         'liter',     30,  10, 320.00),
 ('GLU-001', 'Wood Glue',       'liter',     20,   6, 210.00),
 ('SND-001', 'Sandpaper Sheets','pcs',      200,  50,   8.00),
 ('GLS-001', 'Glass Panel',     'pcs',       60,  15, 260.00);

-- NOTE: No admin account is inserted here on purpose.
-- Create it safely through setup.php so the password is hashed.
