<?php
/**
 * Database Initialization Script
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
    $pdo = new PDO("mysql:host=$host;port=$port", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL server successfully.\n";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database '$database' created or already exists.\n";
    
    // Select the database
    $pdo->exec("USE `$database`");
    echo "Using database '$database'.\n\n";
    
    // Apply schema
    echo "Applying database schema...\n";
    
    // Read schema file
    $schemaFile = file_get_contents(__DIR__ . '/schema.sql');
    
    // Split schema file into individual statements
    $statements = explode(';', $schemaFile);
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            // Skip SOURCE commands as we'll handle them differently
            if (strpos($statement, 'SOURCE') === 0) {
                $sourceFile = trim(str_replace('SOURCE', '', $statement));
                echo "Including schema file: $sourceFile\n";
                
                // Read and execute the source file
                $sourceContent = file_get_contents(__DIR__ . '/' . $sourceFile);
                $sourceStatements = explode(';', $sourceContent);
                
                foreach ($sourceStatements as $sourceStatement) {
                    $sourceStatement = trim($sourceStatement);
                    if (!empty($sourceStatement)) {
                        $pdo->exec($sourceStatement);
                    }
                }
            } else {
                $pdo->exec($statement);
            }
        }
    }
    
    echo "Schema applied successfully.\n\n";
    
    // Apply seed data
    echo "Applying seed data...\n";
    
    // Read seed file
    $seedFile = file_get_contents(__DIR__ . '/seed.sql');
    
    // Split seed file into individual statements
    $statements = explode(';', $seedFile);
    
    // Execute each statement
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            // Skip SOURCE commands as we'll handle them differently
            if (strpos($statement, 'SOURCE') === 0) {
                $sourceFile = trim(str_replace('SOURCE', '', $statement));
                echo "Including seed file: $sourceFile\n";
                
                // Read and execute the source file
                $sourceContent = file_get_contents(__DIR__ . '/' . $sourceFile);
                $sourceStatements = explode(';', $sourceContent);
                
                foreach ($sourceStatements as $sourceStatement) {
                    $sourceStatement = trim($sourceStatement);
                    if (!empty($sourceStatement) && strpos($sourceStatement, '--') !== 0) {
                        try {
                            $pdo->exec($sourceStatement);
                        } catch (PDOException $e) {
                            echo "Error executing statement: " . $e->getMessage() . "\n";
                            echo "Statement: " . $sourceStatement . "\n";
                        }
                    }
                }
            } else {
                if (strpos($statement, '--') !== 0) { // Skip comments
                    $pdo->exec($statement);
                }
            }
        }
    }
    
    echo "Seed data applied successfully.\n\n";
    
    // Verify tables were created
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Created tables: " . implode(', ', $tables) . "\n\n";
    
    // Verify data was inserted
    echo "Data verification:\n";
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $categoryCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $supplierCount = $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn();
    $productCount = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    
    echo "- Users: $userCount records\n";
    echo "- Categories: $categoryCount records\n";
    echo "- Suppliers: $supplierCount records\n";
    echo "- Products: $productCount records\n";
    
    echo "\nDatabase initialization completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
