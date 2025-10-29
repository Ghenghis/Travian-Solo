<?php
/**
 * Check what databases exist
 */

try {
    $pdo = new PDO(
        'mysql:host=mysql;port=3306;charset=utf8mb4',
        'travian_user',
        'travian_password123'
    );

    echo "✓ Connected to MySQL\n";

    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "Available databases:\n";
    foreach ($databases as $db) {
        echo "  - $db\n";
    }

    // Check travian_testworld specifically
    if (in_array('travian_testworld', $databases)) {
        echo "\n✓ travian_testworld database exists\n";

        $pdo->exec("USE travian_testworld");
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo "Tables in travian_testworld: " . count($tables) . "\n";
        if (count($tables) > 0) {
            echo "First 10 tables:\n";
            foreach (array_slice($tables, 0, 10) as $table) {
                echo "  - $table\n";
            }
        } else {
            echo "⚠️  No tables found - schema not imported yet\n";
        }
    } else {
        echo "\n✗ travian_testworld database does not exist\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
