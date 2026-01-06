<?php
// Simple database connection test

// Define base paths
define('BASE_PATH', dirname(__DIR__));

// Load environment variables from .env file if available
if (file_exists(BASE_PATH . '/.env')) {
    $env = parse_ini_file(BASE_PATH . '/.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Get database credentials from environment or use defaults
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'construkt';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? 'root';

try {
    // Try PDO connection
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Test query
    $stmt = $pdo->query('SELECT 1 as test');
    $result = $stmt->fetch();
    
    echo "PDO connection successful! Test result: ";
    print_r($result);
    
} catch (PDOException $e) {
    echo "PDO Connection failed: " . $e->getMessage();
}

echo "<hr>";

// Also try mysqli connection as a fallback
try {
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "MySQLi connection successful!";
    $mysqli->close();
    
} catch (Exception $e) {
    echo "MySQLi Connection failed: " . $e->getMessage();
}
