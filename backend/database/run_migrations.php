<?php
/**
 * Database Migration Runner
 * 
 * This script runs all SQL migration files in the migrations directory.
 * It helps maintain database schema changes over time.
 */

// Load environment variables
require_once __DIR__ . '/../bootstrap.php';

// Get database credentials from environment
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'construkt';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? 'root';

// Connect to database
try {
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    // Check for connection errors
    if ($mysqli->connect_error) {
        throw new Exception("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "Connected to database successfully.\n";
    
    // Create migrations table if it doesn't exist
    $createMigrationsTable = "
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `migration` varchar(255) NOT NULL,
            `executed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `migration` (`migration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    if ($mysqli->query($createMigrationsTable) === false) {
        throw new Exception("Error creating migrations table: " . $mysqli->error);
    }
    
    // Get all migration files
    $migrationsDir = __DIR__ . '/migrations';
    $migrationFiles = scandir($migrationsDir);
    
    // Filter out . and .. directories
    $migrationFiles = array_filter($migrationFiles, function($file) {
        return $file !== '.' && $file !== '..' && pathinfo($file, PATHINFO_EXTENSION) === 'sql';
    });
    
    // Sort migration files
    sort($migrationFiles);
    
    // Get executed migrations
    $executedMigrations = [];
    $result = $mysqli->query("SELECT migration FROM migrations");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $executedMigrations[] = $row['migration'];
        }
    }
    
    // Run migrations
    foreach ($migrationFiles as $migrationFile) {
        // Skip if already executed
        if (in_array($migrationFile, $executedMigrations)) {
            echo "Migration {$migrationFile} already executed. Skipping.\n";
            continue;
        }
        
        // Read migration file
        $migrationContent = file_get_contents($migrationsDir . '/' . $migrationFile);
        
        // Split migration into individual statements
        $statements = explode(';', $migrationContent);
        
        // Begin transaction
        $mysqli->begin_transaction();
        
        try {
            // Execute each statement
            foreach ($statements as $statement) {
                $statement = trim($statement);
                
                if (empty($statement)) {
                    continue;
                }
                
                if ($mysqli->query($statement) === false) {
                    throw new Exception("Error executing statement: " . $mysqli->error);
                }
            }
            
            // Record migration
            $stmt = $mysqli->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->bind_param("s", $migrationFile);
            $stmt->execute();
            
            // Commit transaction
            $mysqli->commit();
            
            echo "Migration {$migrationFile} executed successfully.\n";
        } catch (Exception $e) {
            // Rollback transaction
            $mysqli->rollback();
            
            echo "Error executing migration {$migrationFile}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "All migrations completed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
