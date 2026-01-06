<?php
/**
 * Database configuration - MySQL
 */

// MySQL connection settings
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');  // No password
define('DB_NAME', 'construkt');
define('DB_PORT', 3306);

// Chatbot API URL
define('CHATBOT_API_URL', 'http://localhost:5000');

// Site settings
define('SITE_NAME', 'Construkt');
define('SITE_URL', 'http://localhost:8000');

/**
 * Get PDO database connection (MySQL)
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        // First connect without database to create it if needed
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];

            $tempPdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Create database if not exists
            $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $tempPdo = null;
        } catch (PDOException $e) {
            die('MySQL connection failed: ' . $e->getMessage());
        }

        // Now connect to the database
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

            // Initialize database tables
            initDatabase($pdo);
        } catch (PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    return $pdo;
}

/**
 * Initialize MySQL database with tables and sample data
 */
function initDatabase(PDO $pdo): void {
    // Check if tables exist
    $result = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($result->fetch()) {
        return; // Already initialized
    }

    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            phone VARCHAR(50),
            address TEXT,
            role ENUM('customer', 'manager', 'supplier', 'admin') DEFAULT 'customer',
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            image VARCHAR(255),
            image_url VARCHAR(500)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            company_name VARCHAR(255) NOT NULL,
            description TEXT,
            phone VARCHAR(50),
            address TEXT,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            stock INT DEFAULT 0,
            unit VARCHAR(50) DEFAULT 'pc',
            sku VARCHAR(100),
            image VARCHAR(255),
            image_url VARCHAR(500),
            category_id INT,
            supplier_id INT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            shipping_address TEXT,
            phone VARCHAR(50),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS support_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            manager_id INT,
            message TEXT NOT NULL,
            is_from_customer TINYINT(1) DEFAULT 1,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS conversation_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(100),
            user_message TEXT,
            bot_response TEXT,
            intent VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Insert sample data

    // Users (password: 'password')
    $hash = password_hash('password', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin@construkt.com', $hash, 'Admin', 'User', 'admin']);
    $stmt->execute(['manager@construkt.com', $hash, 'Manager', 'User', 'manager']);
    $stmt->execute(['supplier1@test.com', $hash, 'John', 'Manager', 'supplier']);
    $stmt->execute(['customer1@test.com', $hash, 'Bob', 'Smith', 'customer']);

    // Categories
    $pdo->exec("
        INSERT INTO categories (name, description) VALUES
        ('Bricks', 'Construction bricks of various types'),
        ('Cement', 'Cement and dry mixes'),
        ('Sand & Gravel', 'Aggregate materials'),
        ('Lumber', 'Wood and timber products'),
        ('Roofing', 'Roofing materials'),
        ('Paints', 'Paints and varnishes'),
        ('Tools', 'Construction tools'),
        ('Electrical', 'Electrical supplies')
    ");

    // Suppliers
    $pdo->exec("
        INSERT INTO suppliers (company_name, description, phone) VALUES
        ('BuildMart', 'Major construction materials supplier', '+1-555-0101'),
        ('BrickPro', 'Brick manufacturer', '+1-555-0102'),
        ('TimberTrade', 'Wholesale lumber', '+1-555-0103')
    ");

    // Products
    $pdo->exec("
        INSERT INTO products (name, description, price, stock, unit, category_id, supplier_id) VALUES
        ('Red Brick M-100', 'Ceramic construction brick', 0.85, 10000, 'pc', 1, 2),
        ('White Silicate Brick', 'Silicate brick M-150', 0.75, 8000, 'pc', 1, 2),
        ('Portland Cement 50kg', 'Portland cement PC-400', 12.50, 500, 'bag', 2, 1),
        ('Premium Cement 50kg', 'High-strength portland cement PC-500', 14.00, 300, 'bag', 2, 1),
        ('River Sand', 'Construction sand', 45.00, 100, 'ton', 3, 1),
        ('Granite Gravel 5-20mm', 'Crushed granite 5-20mm fraction', 55.00, 80, 'ton', 3, 1),
        ('Pine Board 2x6', 'Pine lumber 6ft', 8.50, 200, 'pc', 4, 3),
        ('Pine Beam 4x4', 'Pine beam 6ft', 15.00, 150, 'pc', 4, 3),
        ('Metal Roofing Tile', 'Steel roofing tile', 12.00, 500, 'sqft', 5, 1),
        ('Onduline Sheet', 'Bitumen roofing sheet', 18.00, 300, 'sheet', 5, 1),
        ('White Exterior Paint', 'Acrylic paint 2.5gal', 85.00, 100, 'bucket', 6, 1),
        ('Floor Varnish', 'Polyurethane varnish 1gal', 62.00, 50, 'can', 6, 1),
        ('Bosch Hammer Drill', 'Professional hammer drill 800W', 249.00, 20, 'pc', 7, 1),
        ('Makita Cordless Drill', 'Makita 18V cordless drill', 189.00, 30, 'pc', 7, 1),
        ('Copper Wire 12AWG', 'Copper electrical wire 100ft', 89.00, 50, 'roll', 8, 1),
        ('Circuit Breaker 15A', 'Automatic circuit breaker', 12.00, 200, 'pc', 8, 1)
    ");

    echo "<!-- Database initialized with sample data -->\n";
}

/**
 * Get product image URL (checks thumbnail first, then local file)
 */
function getProductImage($product): string {
    if (!empty($product['thumbnail'])) {
        return $product['thumbnail'];
    }
    return '/images/products/' . $product['id'] . '.jpg';
}

/**
 * Get category image URL
 */
function getCategoryImage($category): string {
    if (!empty($category['image'])) {
        return $category['image'];
    }
    return '/images/categories/' . $category['id'] . '.jpg';
}
