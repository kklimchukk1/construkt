-- Add cart and orders tables to existing database
-- Run this if tables don't exist yet

USE `construkt`;

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

-- Add foreign key constraints (ignore errors if they exist)
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `cart_items`
  ADD CONSTRAINT `fk_cart_items_user`
  FOREIGN KEY (`user_id`)
  REFERENCES `users` (`id`)
  ON DELETE CASCADE
  ON UPDATE CASCADE;

ALTER TABLE `cart_items`
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
  ON UPDATE CASCADE;

ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_order_items_product`
  FOREIGN KEY (`product_id`)
  REFERENCES `products` (`id`)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Cart and Orders tables created successfully!' AS 'Status';
