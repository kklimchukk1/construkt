<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

echo "<h2>Fixing database...</h2>";

$fixes = [
    "ALTER TABLE `products` MODIFY COLUMN `supplier_id` INT UNSIGNED NULL",
    "ALTER TABLE `products` MODIFY COLUMN `category_id` INT UNSIGNED NULL",
];

foreach ($fixes as $sql) {
    try {
        $db->exec($sql);
        echo "<p style='color:green'>OK: $sql</p>";
    } catch (PDOException $e) {
        echo "<p style='color:orange'>Skip: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Done! <a href='/manager.php'>Go to Manager</a></h3>";
