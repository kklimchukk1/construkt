<?php
/**
 * Database Configuration
 */

namespace Construkt\Config;

class Database {
    private static $instance = null;
    private $connection;

    private function __construct() {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = $_ENV['DB_DATABASE'] ?? 'construkt';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? 'root';

        try {
            // Create mysqli connection
            $this->connection = new \mysqli($host, $username, $password, $database, $port);
            
            // Check for connection errors
            if ($this->connection->connect_error) {
                throw new \Exception("Connection failed: " . $this->connection->connect_error);
            }
            
            // Set charset to utf8mb4
            $this->connection->set_charset('utf8mb4');
        } catch (\Exception $e) {
            // Log error but don't expose details in production
            if ($_ENV['APP_ENV'] === 'development') {
                throw new \Exception("Database connection failed: " . $e->getMessage());
            } else {
                throw new \Exception("Database connection failed. Please try again later.");
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }
}
