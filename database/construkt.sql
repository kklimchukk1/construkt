CREATE DATABASE IF NOT EXISTS `construkt` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE `construkt`;

-- Disable foreign key checks during setup
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables if they exist to ensure clean setup
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `chat_logs`;
DROP TABLE IF EXISTS `support_messages`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;

-- Create Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `role` ENUM('customer', 'supplier', 'admin') NOT NULL DEFAULT 'customer',
  `company_name` VARCHAR(255) NULL,
  `phone` VARCHAR(20) NULL,
  `address` TEXT NULL,
  `city` VARCHAR(100) NULL,
  `state` VARCHAR(100) NULL,
  `postal_code` VARCHAR(20) NULL,
  `country` VARCHAR(100) NULL DEFAULT 'United States',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `email_UNIQUE` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` INT UNSIGNED NULL COMMENT 'Self-reference to parent category',
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(120) NOT NULL COMMENT 'URL-friendly version of the name',
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `slug_UNIQUE` (`slug`),
  INDEX `fk_categories_parent_idx` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Suppliers Table
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL COMMENT 'Reference to user account',
  `company_name` VARCHAR(255) NOT NULL,
  `contact_name` VARCHAR(100) NOT NULL,
  `contact_title` VARCHAR(100) NULL,
  `email` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `website` VARCHAR(255) NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'United States',
  `description` TEXT NULL,
  `logo` VARCHAR(255) NULL,
  `business_license` VARCHAR(100) NULL,
  `tax_id` VARCHAR(50) NULL,
  `year_established` YEAR NULL,
  `num_employees` INT NULL,
  `service_regions` TEXT NULL COMMENT 'Comma-separated list of service regions',
  `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `rating` DECIMAL(3,2) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `user_id_UNIQUE` (`user_id`),
  INDEX `company_name_idx` (`company_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `supplier_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `unit` VARCHAR(20) NOT NULL COMMENT 'e.g., kg, ton, piece, m², m³',
  `stock_quantity` INT NOT NULL DEFAULT 0,
  `dimensions` JSON NULL COMMENT 'Product dimensions and material type in JSON format',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `thumbnail` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_products_category_idx` (`category_id`),
  INDEX `fk_products_supplier_idx` (`supplier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Product Images Table
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `display_order` INT NOT NULL DEFAULT 0,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_product_images_product_idx` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Chat Logs Table
CREATE TABLE IF NOT EXISTS `chat_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `session_id` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `is_bot` TINYINT(1) NOT NULL DEFAULT 0,
  `intent` VARCHAR(100) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_chat_logs_user_idx` (`user_id`),
  INDEX `session_id_idx` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Support Messages Table
CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NOT NULL,
  `manager_id` INT UNSIGNED NULL,
  `message` TEXT NOT NULL,
  `is_from_customer` TINYINT(1) NOT NULL DEFAULT 1,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_support_customer_idx` (`customer_id`),
  INDEX `fk_support_manager_idx` (`manager_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Cart Items Table
CREATE TABLE IF NOT EXISTS `cart_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_cart_user_idx` (`user_id`),
  INDEX `fk_cart_product_idx` (`product_id`),
  UNIQUE INDEX `user_product_unique` (`user_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `order_number` VARCHAR(20) NOT NULL,
  `status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
  `total_amount` DECIMAL(12,2) NOT NULL,
  `shipping_address` TEXT NOT NULL,
  `shipping_city` VARCHAR(100) NOT NULL,
  `shipping_state` VARCHAR(100) NOT NULL,
  `shipping_postal_code` VARCHAR(20) NOT NULL,
  `shipping_country` VARCHAR(100) NOT NULL DEFAULT 'United States',
  `shipping_method` VARCHAR(100) NULL,
  `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `phone` VARCHAR(20) NOT NULL,
  `payment_method` VARCHAR(50) NULL,
  `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
  `notes` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `order_number_UNIQUE` (`order_number`),
  INDEX `fk_orders_user_idx` (`user_id`),
  INDEX `status_idx` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` INT UNSIGNED NOT NULL,
  `product_id` INT UNSIGNED NOT NULL,
  `quantity` INT NOT NULL,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `fk_order_items_order_idx` (`order_id`),
  INDEX `fk_order_items_product_idx` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add foreign key constraints
ALTER TABLE `categories`
  ADD CONSTRAINT `fk_categories_parent`
  FOREIGN KEY (`parent_id`)
  REFERENCES `categories` (`id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_suppliers_user`
  FOREIGN KEY (`user_id`)
  REFERENCES `users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category`
  FOREIGN KEY (`category_id`)
  REFERENCES `categories` (`id`)
  ON DELETE RESTRICT
  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_products_supplier`
  FOREIGN KEY (`supplier_id`)
  REFERENCES `suppliers` (`id`)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

ALTER TABLE `product_images`
  ADD CONSTRAINT `fk_product_images_product`
  FOREIGN KEY (`product_id`)
  REFERENCES `products` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `chat_logs`
  ADD CONSTRAINT `fk_chat_logs_user`
  FOREIGN KEY (`user_id`)
  REFERENCES `users` (`id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

ALTER TABLE `support_messages`
  ADD CONSTRAINT `fk_support_customer`
  FOREIGN KEY (`customer_id`)
  REFERENCES `users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_support_manager`
  FOREIGN KEY (`manager_id`)
  REFERENCES `users` (`id`)
  ON DELETE SET NULL
  ON UPDATE CASCADE;

ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_user`
  FOREIGN KEY (`user_id`)
  REFERENCES `users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cart_items_product`
  FOREIGN KEY (`product_id`)
  REFERENCES `products` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user`
  FOREIGN KEY (`user_id`)
  REFERENCES `users` (`id`)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_order`
  FOREIGN KEY (`order_id`)
  REFERENCES `orders` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_order_items_product`
  FOREIGN KEY (`product_id`)
  REFERENCES `products` (`id`)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

-- Insert parent categories
INSERT INTO `categories` (`id`, `parent_id`, `name`, `slug`, `description`, `display_order`) VALUES
(1, NULL, 'Building Materials', 'building-materials', 'Essential materials for construction projects', 10),
(2, NULL, 'Hardware', 'hardware', 'Tools and hardware for construction', 20),
(3, NULL, 'Flooring', 'flooring', 'Flooring materials and accessories', 30),
(4, NULL, 'Plumbing', 'plumbing', 'Plumbing supplies and fixtures', 40),
(5, NULL, 'Electrical', 'electrical', 'Electrical supplies and components', 50),
(6, NULL, 'HVAC', 'hvac', 'Heating, ventilation, and air conditioning', 60),
(7, NULL, 'Painting & Supplies', 'painting-supplies', 'Paint and painting accessories', 70),
(8, NULL, 'Landscaping', 'landscaping', 'Outdoor and landscaping materials', 80);

-- Insert subcategories for Building Materials
INSERT INTO `categories` (`parent_id`, `name`, `slug`, `description`, `display_order`) VALUES
(1, 'Concrete & Cement', 'concrete-cement', 'Concrete, cement, and related products', 11),
(1, 'Bricks & Blocks', 'bricks-blocks', 'Bricks, blocks, and masonry products', 12),
(1, 'Lumber & Composites', 'lumber-composites', 'Wood and composite building materials', 13),
(1, 'Drywall', 'drywall', 'Drywall panels and accessories', 14),
(1, 'Insulation', 'insulation', 'Thermal and acoustic insulation materials', 15),
(1, 'Roofing', 'roofing', 'Roofing materials and accessories', 16),
(1, 'Siding', 'siding', 'Exterior siding and cladding materials', 17),
(1, 'Windows & Doors', 'windows-doors', 'Windows, doors, and related hardware', 18);

-- Insert subcategories for Hardware
INSERT INTO `categories` (`parent_id`, `name`, `slug`, `description`, `display_order`) VALUES
(2, 'Hand Tools', 'hand-tools', 'Manual tools for construction', 21),
(2, 'Power Tools', 'power-tools', 'Electric and battery-powered tools', 22),
(2, 'Fasteners', 'fasteners', 'Nails, screws, bolts, and other fasteners', 23),
(2, 'Safety Equipment', 'safety-equipment', 'Personal protective equipment and safety gear', 24);

-- Insert subcategories for Flooring
INSERT INTO `categories` (`parent_id`, `name`, `slug`, `description`, `display_order`) VALUES
(3, 'Tile', 'tile', 'Ceramic, porcelain, and stone tiles', 31),
(3, 'Hardwood', 'hardwood', 'Solid and engineered hardwood flooring', 32),
(3, 'Laminate', 'laminate', 'Laminate flooring options', 33),
(3, 'Vinyl', 'vinyl', 'Vinyl and luxury vinyl flooring', 34),
(3, 'Carpet', 'carpet', 'Carpet and carpet tiles', 35);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Confirmation message
SELECT 'Database setup completed successfully!' AS 'Status';