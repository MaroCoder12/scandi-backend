<?php
// One-off migration script to add `attributes` column to `cart` table if it doesn't exist
// Usage (CLI): php scand_pro/scripts/add_cart_attributes_column.php

require_once __DIR__ . '/../config/database.php';

function columnExists(PDO $pdo, string $dbName, string $table, string $column): bool {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :table AND COLUMN_NAME = :column");
    $stmt->execute([':db' => $dbName, ':table' => $table, ':column' => $column]);
    return (int)$stmt->fetchColumn() > 0;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (!$pdo) {
        throw new Exception('Could not obtain DB connection. Check DB_HOST/DB_NAME/DB_USER/DB_PASS environment variables.');
    }

    // Extract dbname from DSN used in Database class
    $dbName = getenv('DB_NAME') ?: 'scand_test';

    $table = 'cart';
    $column = 'attributes';

    echo "Checking for column `$column` on `$table` in DB `$dbName`...\n";
    if (columnExists($pdo, $dbName, $table, $column)) {
        echo "Column already exists. Nothing to do.\n";
        exit(0);
    }

    echo "Adding column...\n";
    $sql = "ALTER TABLE `$table` ADD COLUMN `$column` TEXT NULL AFTER `product_id`";
    $pdo->exec($sql);

    if (columnExists($pdo, $dbName, $table, $column)) {
        echo "Success: column `$column` added to `$table`.\n";
        exit(0);
    }

    throw new Exception('Column addition did not take effect.');
} catch (Throwable $e) {
    fwrite(STDERR, "Migration failed: " . $e->getMessage() . "\n");
    exit(1);
}

