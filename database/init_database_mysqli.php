<?php
/**
 * Database Initialization Script using MySQLi
 * This script creates the database and applies the schema and seed data
 */

// Database connection parameters
$host = 'localhost';
$port = '3306';
$username = 'root';
$password = 'root';
$database = 'construkt';

echo "=== Construction Materials Marketplace Database Initialization ===\n\n";

try {
    // Connect to MySQL server (without selecting a database)
    $mysqli = new mysqli($host, $username, $password, '', $port);
    
    // Check for connection errors
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "Connected to MySQL server successfully.\n";
    
    // Create database if it doesn't exist
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$database' created or already exists.\n";
    
    // Select the database
    $mysqli->select_db($database);
    echo "Using database '$database'.\n\n";
    
    // Function to execute SQL file
    function executeSqlFile($mysqli, $filePath) {
        if (!file_exists($filePath)) {
            echo "File not found: $filePath\n";
            return false;
        }
        
        $sql = file_get_contents($filePath);
        
        // Remove comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        
        // Split into statements
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                // Skip SOURCE commands
                if (strpos($statement, 'SOURCE') === 0) {
                    continue;
                }
                
                if (!$mysqli->query($statement)) {
                    echo "Error executing statement: " . $mysqli->error . "\n";
                    echo "Statement: $statement\n";
                }
            }
        }
        
        return true;
    }
    
    // Apply schema files
    echo "Applying database schema...\n";
    executeSqlFile($mysqli, __DIR__ . '/schema/users.sql');
    echo "- Created users table\n";
    
    executeSqlFile($mysqli, __DIR__ . '/schema/categories.sql');
    echo "- Created categories table\n";
    
    executeSqlFile($mysqli, __DIR__ . '/schema/suppliers.sql');
    echo "- Created suppliers table\n";
    
    executeSqlFile($mysqli, __DIR__ . '/schema/products.sql');
    echo "- Created products table\n";
    
    // Apply additional tables from schema.sql
    echo "- Creating additional tables...\n";
    
    // Product Images Table
    $mysqli->query("
    CREATE TABLE IF NOT EXISTS `product_images` (
      `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
      `product_id` INT UNSIGNED NOT NULL,
      `image_url` VARCHAR(255) NOT NULL,
      `display_order` INT NOT NULL DEFAULT 0,
      `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      INDEX `fk_product_images_product_idx` (`product_id`),
      CONSTRAINT `fk_product_images_product` 
        FOREIGN KEY (`product_id`) 
        REFERENCES `products` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Orders Table
    $mysqli->query("
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
      INDEX `status_idx` (`status`),
      CONSTRAINT `fk_orders_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`id`)
        ON DELETE RESTRICT
        ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Order Items Table
    $mysqli->query("
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
      INDEX `fk_order_items_product_idx` (`product_id`),
      CONSTRAINT `fk_order_items_order` 
        FOREIGN KEY (`order_id`) 
        REFERENCES `orders` (`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
      CONSTRAINT `fk_order_items_product` 
        FOREIGN KEY (`product_id`) 
        REFERENCES `products` (`id`) 
        ON DELETE RESTRICT 
        ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Chat Logs Table
    $mysqli->query("
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
      INDEX `session_id_idx` (`session_id`),
      CONSTRAINT `fk_chat_logs_user` 
        FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) 
        ON DELETE SET NULL 
        ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    echo "Schema applied successfully.\n\n";
    
    // Apply foreign key constraints
    echo "Applying foreign key constraints...\n";
    
    // Products to Categories relationship
    $mysqli->query("
    ALTER TABLE `products` 
      ADD CONSTRAINT `fk_products_category` 
      FOREIGN KEY (`category_id`) 
      REFERENCES `categories` (`id`) 
      ON DELETE RESTRICT 
      ON UPDATE CASCADE;
    ");
    
    // Products to Suppliers relationship
    $mysqli->query("
    ALTER TABLE `products` 
      ADD CONSTRAINT `fk_products_supplier` 
      FOREIGN KEY (`supplier_id`) 
      REFERENCES `suppliers` (`id`) 
      ON DELETE RESTRICT 
      ON UPDATE CASCADE;
    ");
    
    // Suppliers to Users relationship
    $mysqli->query("
    ALTER TABLE `suppliers` 
      ADD CONSTRAINT `fk_suppliers_user` 
      FOREIGN KEY (`user_id`) 
      REFERENCES `users` (`id`) 
      ON DELETE CASCADE 
      ON UPDATE CASCADE;
    ");
    
    echo "Foreign key constraints applied successfully.\n\n";
    
    // Apply seed data
    echo "Applying seed data...\n";
    
    // Apply users seed data
    executeSqlFile($mysqli, __DIR__ . '/seed/users.sql');
    echo "- Inserted users data\n";
    
    // Apply categories seed data
    executeSqlFile($mysqli, __DIR__ . '/seed/categories.sql');
    echo "- Inserted categories data\n";
    
    // Apply suppliers seed data
    executeSqlFile($mysqli, __DIR__ . '/seed/suppliers.sql');
    echo "- Inserted suppliers data\n";
    
    // Apply products seed data
    executeSqlFile($mysqli, __DIR__ . '/seed/products.sql');
    echo "- Inserted products data\n";
    
    echo "Seed data applied successfully.\n\n";
    
    // Verify tables were created
    $result = $mysqli->query("SHOW TABLES");
    $tables = [];
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    echo "Created tables: " . implode(', ', $tables) . "\n\n";
    
    // Verify data was inserted
    echo "Data verification:\n";
    $userCount = $mysqli->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
    $categoryCount = $mysqli->query("SELECT COUNT(*) FROM categories")->fetch_row()[0];
    $supplierCount = $mysqli->query("SELECT COUNT(*) FROM suppliers")->fetch_row()[0];
    $productCount = $mysqli->query("SELECT COUNT(*) FROM products")->fetch_row()[0];
    
    echo "- Users: $userCount records\n";
    echo "- Categories: $categoryCount records\n";
    echo "- Suppliers: $supplierCount records\n";
    echo "- Products: $productCount records\n";
    
    echo "\nDatabase initialization completed successfully!\n";
    
    // Close the connection
    $mysqli->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
