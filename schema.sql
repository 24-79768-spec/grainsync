CREATE DATABASE IF NOT EXISTS rice_moto_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rice_moto_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS activity_logs, stock_movements, order_items, orders, menu_recipes, ingredients, menu_items, categories, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('customer','owner','staff','rider') NOT NULL DEFAULT 'customer',
  phone VARCHAR(30) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE categories (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE menu_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(140) NOT NULL,
  description TEXT NULL,
  price DECIMAL(10,2) NOT NULL,
  image_url VARCHAR(500) NULL,
  is_available TINYINT(1) NOT NULL DEFAULT 1,
  is_featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_menu_category FOREIGN KEY (category_id) REFERENCES categories(id),
  INDEX idx_menu_category_available (category_id, is_available)
) ENGINE=InnoDB;

CREATE TABLE ingredients (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  unit VARCHAR(30) NOT NULL,
  stock_quantity DECIMAL(12,3) NOT NULL DEFAULT 0,
  low_stock_threshold DECIMAL(12,3) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE menu_recipes (
  menu_item_id BIGINT UNSIGNED NOT NULL,
  ingredient_id BIGINT UNSIGNED NOT NULL,
  quantity_required DECIMAL(12,3) NOT NULL,
  PRIMARY KEY (menu_item_id, ingredient_id),
  CONSTRAINT fk_recipe_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
  CONSTRAINT fk_recipe_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE orders (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  public_id VARCHAR(24) NOT NULL UNIQUE,
  customer_id BIGINT UNSIGNED NULL,
  created_by BIGINT UNSIGNED NULL,
  rider_id BIGINT UNSIGNED NULL,
  customer_name VARCHAR(120) NOT NULL,
  customer_phone VARCHAR(30) NULL,
  order_type ENUM('delivery','takeout') NOT NULL,
  status ENUM('Pending','Preparing','Ready','Out for Delivery','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  payment_method ENUM('cash','online') NOT NULL DEFAULT 'cash',
  payment_status ENUM('unpaid','paid','sandbox_paid') NOT NULL DEFAULT 'unpaid',
  delivery_address VARCHAR(500) NULL,
  latitude DECIMAL(10,7) NULL,
  longitude DECIMAL(10,7) NULL,
  distance_km DECIMAL(8,2) NOT NULL DEFAULT 0,
  delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
  subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
  total DECIMAL(10,2) NOT NULL DEFAULT 0,
  notes VARCHAR(1000) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_order_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_order_rider FOREIGN KEY (rider_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_order_status_created (status, created_at),
  INDEX idx_order_customer_created (customer_id, created_at)
) ENGINE=InnoDB;

CREATE TABLE order_items (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT UNSIGNED NOT NULL,
  menu_item_id BIGINT UNSIGNED NULL,
  item_name VARCHAR(140) NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  quantity INT UNSIGNED NOT NULL,
  options_json JSON NULL,
  CONSTRAINT fk_order_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_order_item_menu FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL,
  INDEX idx_order_item_menu (menu_item_id)
) ENGINE=InnoDB;

CREATE TABLE stock_movements (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ingredient_id BIGINT UNSIGNED NOT NULL,
  user_id BIGINT UNSIGNED NULL,
  order_id BIGINT UNSIGNED NULL,
  quantity_change DECIMAL(12,3) NOT NULL,
  reason VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_movement_ingredient FOREIGN KEY (ingredient_id) REFERENCES ingredients(id),
  CONSTRAINT fk_movement_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_movement_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
  INDEX idx_movement_created (created_at)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NULL,
  action VARCHAR(120) NOT NULL,
  details TEXT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_log_created (created_at),
  INDEX idx_log_action (action)
) ENGINE=InnoDB;

INSERT INTO users (name,email,password_hash,role,phone) VALUES
('Demo Customer','customer@ricemoto.test','$2y$10$r/9YXcUL7RqxJqoCx.Rn8.w2KpRS6HrbqX3Eqj8omI6.YVnsMpZl6','customer','09171234567'),
('Demo Owner','owner@ricemoto.test','$2y$10$r/9YXcUL7RqxJqoCx.Rn8.w2KpRS6HrbqX3Eqj8omI6.YVnsMpZl6','owner','09170000001'),
('Demo Kitchen Staff','staff@ricemoto.test','$2y$10$r/9YXcUL7RqxJqoCx.Rn8.w2KpRS6HrbqX3Eqj8omI6.YVnsMpZl6','staff','09170000002'),
('Demo Rider','rider@ricemoto.test','$2y$10$r/9YXcUL7RqxJqoCx.Rn8.w2KpRS6HrbqX3Eqj8omI6.YVnsMpZl6','rider','09170000003');

INSERT INTO categories (name,description,sort_order) VALUES
('Rice Bowls','Rice meals made fresh to order',1),('Combos','Complete meals with a side or drink',2),('Add-ons','Extras for your meal',3);

INSERT INTO menu_items (category_id,name,description,price,is_featured) VALUES
(1,'Chicken Inasal Rice','Grilled chicken, garlic rice, atchara, and house inasal glaze.',149,1),
(1,'Pork Adobo Rice','Slow-braised pork adobo over steaming rice.',139,1),
(1,'Beef Tapa Rice','Savory beef tapa, garlic rice, and egg.',169,1),
(2,'Crispy Chicken Combo','Crispy chicken rice bowl with a refreshing drink.',189,1),
(3,'Extra Garlic Rice','A generous serving of garlic rice.',35,0),
(3,'Fried Egg','Sunny-side-up egg.',20,0);

INSERT INTO ingredients (name,unit,stock_quantity,low_stock_threshold) VALUES
('Rice','kg',40,8),('Chicken','kg',18,4),('Pork','kg',15,3),('Beef','kg',10,2),
('Garlic rice seasoning','kg',4,1),('Atchara','kg',6,1),('Eggs','pcs',120,24),
('House sauce','L',5,1),('Takeout packaging','pcs',200,40),('Beverage','pcs',80,15);

INSERT INTO menu_recipes (menu_item_id,ingredient_id,quantity_required) VALUES
(1,1,0.18),(1,2,0.20),(1,5,0.015),(1,6,0.03),(1,8,0.02),(1,9,1),
(2,1,0.18),(2,3,0.18),(2,5,0.015),(2,8,0.02),(2,9,1),
(3,1,0.18),(3,4,0.16),(3,5,0.015),(3,7,1),(3,9,1),
(4,1,0.18),(4,2,0.20),(4,5,0.015),(4,10,1),(4,9,1),
(5,1,0.18),(5,5,0.015),(5,9,1),(6,7,1),(6,9,1);

INSERT INTO orders (public_id,customer_id,created_by,customer_name,customer_phone,order_type,status,payment_method,payment_status,subtotal,total,notes,created_at) VALUES
('RM-DEMO-1001',1,1,'Demo Customer','09171234567','takeout','Completed','cash','paid',149,149,'Less spicy',NOW() - INTERVAL 2 DAY),
('RM-DEMO-1002',1,1,'Demo Customer','09171234567','delivery','Preparing','cash','unpaid',343,383,'Please call on arrival',NOW() - INTERVAL 1 HOUR);
INSERT INTO order_items (order_id,menu_item_id,item_name,unit_price,quantity,options_json) VALUES
(1,1,'Chicken Inasal Rice',149,1,'{"spice":"medium"}'),
(2,2,'Pork Adobo Rice',139,1,'{}'),(2,5,'Extra Garlic Rice',35,1,'{}'),(2,6,'Fried Egg',20,1,'{}'),(2,1,'Chicken Inasal Rice',149,1,'{}');
UPDATE orders SET delivery_fee=40,distance_km=2.66 WHERE public_id='RM-DEMO-1002';

INSERT INTO activity_logs (user_id,action,details,ip_address) VALUES
(2,'seed','Demo data initialized',NULL),(1,'order_created','Sample takeout order RM-DEMO-1001',NULL),
(1,'order_created','Sample delivery order RM-DEMO-1002',NULL);
