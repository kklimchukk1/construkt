"""
Create MySQL database and tables for Construkt
"""
import mysql.connector
from mysql.connector import Error
from dotenv import load_dotenv
import os

# Load environment variables
env_path = os.path.join(os.path.dirname(__file__), '..', 'chatbot', '.env')
print(f"Loading env from: {env_path}")
load_dotenv(env_path)


def create_tables():
    """Create the database and all tables"""
    try:
        host = os.getenv('DB_HOST', 'localhost')
        port = int(os.getenv('DB_PORT', 3306))
        user = os.getenv('DB_USER', 'root')
        password = os.getenv('DB_PASSWORD', '')
        database = os.getenv('DB_NAME', 'construkt')

        print(f"Connecting to {host}:{port} as {user}")

        # First connect WITHOUT database to create it
        connection = mysql.connector.connect(
            host=host,
            port=port,
            user=user,
            password=password,
            charset='utf8mb4'
        )

        cursor = connection.cursor()
        print(f"Creating database '{database}' if not exists...")
        cursor.execute(f"CREATE DATABASE IF NOT EXISTS `{database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")
        connection.commit()
        cursor.close()
        connection.close()

        print(f"Connecting to {host}:{port}/{database}")

        # Now connect to the database
        connection = mysql.connector.connect(
            host=host,
            port=port,
            user=user,
            password=password,
            database=database,
            charset='utf8mb4'
        )

        cursor = connection.cursor()

        # Create Users Table
        print("Creating users table...")
        cursor.execute("""
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
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        # Create Categories Table
        print("Creating categories table...")
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS categories (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                parent_id INT DEFAULT NULL,
                image VARCHAR(255),
                image_url VARCHAR(500),
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        # Create Suppliers Table
        print("Creating suppliers table...")
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS suppliers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                company_name VARCHAR(255) NOT NULL,
                description TEXT,
                contact_name VARCHAR(255),
                contact_title VARCHAR(100),
                phone VARCHAR(50),
                address TEXT,
                city VARCHAR(100),
                state VARCHAR(100),
                postal_code VARCHAR(20),
                country VARCHAR(100) DEFAULT 'United States',
                is_verified TINYINT(1) DEFAULT 0,
                is_featured TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        # Create Products Table
        print("Creating products table...")
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS products (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                description TEXT,
                price DECIMAL(10,2) NOT NULL,
                stock_quantity INT DEFAULT 0,
                unit VARCHAR(50) DEFAULT 'pc',
                sku VARCHAR(100),
                thumbnail VARCHAR(500),
                image VARCHAR(255),
                image_url VARCHAR(500),
                category_id INT,
                supplier_id INT,
                is_active TINYINT(1) DEFAULT 1,
                is_featured TINYINT(1) DEFAULT 0,
                calculation_type VARCHAR(50) DEFAULT 'unit',
                dimensions TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
                FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        # Create Cart Items Table
        print("Creating cart_items table...")
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS cart_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                UNIQUE KEY unique_cart_item (user_id, product_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        # Create Orders Table
        print("Creating orders table...")
        cursor.execute("""
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        # Create Order Items Table
        print("Creating order_items table...")
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        # Create Support Messages Table
        print("Creating support_messages table...")
        cursor.execute("""
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        # Create Conversation Logs Table
        print("Creating conversation_logs table...")
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS conversation_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id VARCHAR(100),
                user_message TEXT,
                bot_response TEXT,
                intent VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        """)

        connection.commit()

        # Verify tables exist
        cursor.execute("SHOW TABLES")
        tables = cursor.fetchall()
        print("\nAll tables in database:")
        for table in tables:
            print(f"  - {table[0]}")

        cursor.close()
        connection.close()

        print("\nAll tables created successfully!")
        return True

    except Error as e:
        print(f"Error creating tables: {e}")
        return False


if __name__ == "__main__":
    create_tables()
