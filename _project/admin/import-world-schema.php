<?php
/**
 * Import world schema into travian_testworld database
 */

echo "Importing world schema into travian_testworld...\n";

try {
    // Connect to travian_testworld
    $pdo = new PDO(
        'mysql:host=mysql;port=3306;dbname=travian_testworld;charset=utf8mb4',
        'travian_user',
        'travian_password123'
    );

    echo "✓ Connected to travian_testworld\n";

    // Read the schema file
    $schemaFile = __DIR__ . '/docker/mysql/init/02-world-schema.sql';
    if (!file_exists($schemaFile)) {
        die("✗ Schema file not found: $schemaFile\n");
    }

    $schema = file_get_contents($schemaFile);
    echo "✓ Read schema file (" . strlen($schema) . " bytes)\n";

    // Split into individual statements
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $statement) {
        if (empty($statement)) continue;

        try {
            $pdo->exec($statement);
            $successCount++;
        } catch (Exception $e) {
            echo "⚠️  Statement failed: " . substr($statement, 0, 50) . "... - " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }

    echo "✓ Import complete: $successCount successful, $errorCount errors\n";

    // Check final table count
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Final table count: " . count($tables) . "\n";

} catch (Exception $e) {
    echo "✗ Import failed: " . $e->getMessage() . "\n";
}
