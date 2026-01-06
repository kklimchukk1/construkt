-- Migration: Update orders and order_items tables to full schema
-- This migration adds missing columns and updates existing structure

-- Update orders table status ENUM to include 'confirmed'
ALTER TABLE `orders`
  MODIFY COLUMN `status` ENUM('pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending';

-- Add missing columns to orders table (ignore errors if they already exist)
ALTER TABLE `orders`
  ADD COLUMN `order_number` VARCHAR(20) NOT NULL AFTER `user_id`,
  ADD UNIQUE INDEX `order_number_UNIQUE` (`order_number`);

ALTER TABLE `orders`
  ADD COLUMN `shipping_city` VARCHAR(100) NOT NULL DEFAULT '' AFTER `shipping_address`;

ALTER TABLE `orders`
  ADD COLUMN `shipping_state` VARCHAR(100) NOT NULL DEFAULT '' AFTER `shipping_city`;

ALTER TABLE `orders`
  ADD COLUMN `shipping_postal_code` VARCHAR(20) NOT NULL DEFAULT '' AFTER `shipping_state`;

ALTER TABLE `orders`
  ADD COLUMN `shipping_country` VARCHAR(100) NOT NULL DEFAULT 'United States' AFTER `shipping_postal_code`;

ALTER TABLE `orders`
  ADD COLUMN `shipping_method` VARCHAR(100) NULL AFTER `shipping_country`;

ALTER TABLE `orders`
  ADD COLUMN `shipping_cost` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `shipping_method`;

ALTER TABLE `orders`
  ADD COLUMN `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `shipping_cost`;

ALTER TABLE `orders`
  ADD COLUMN `payment_method` VARCHAR(50) NULL AFTER `phone`;

ALTER TABLE `orders`
  ADD COLUMN `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') NOT NULL DEFAULT 'pending' AFTER `payment_method`;

-- Update total_amount precision
ALTER TABLE `orders`
  MODIFY COLUMN `total_amount` DECIMAL(12,2) NOT NULL;

-- Add status index if not exists
ALTER TABLE `orders`
  ADD INDEX `status_idx` (`status`);

-- Update order_items table - rename price to unit_price and add subtotal
ALTER TABLE `order_items`
  CHANGE COLUMN `price` `unit_price` DECIMAL(10,2) NOT NULL;

ALTER TABLE `order_items`
  ADD COLUMN `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`;

-- Update subtotal values for existing records
UPDATE `order_items` SET `subtotal` = `unit_price` * `quantity` WHERE `subtotal` = 0;

-- Generate order numbers for existing orders without them
UPDATE `orders` SET `order_number` = CONCAT('ORD-', LPAD(id, 8, '0')) WHERE `order_number` = '' OR `order_number` IS NULL;

SELECT 'Orders migration completed!' AS Status;
