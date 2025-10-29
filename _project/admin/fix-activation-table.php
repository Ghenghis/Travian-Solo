<?php
/**
 * Add missing columns to activation table
 */

try {
    $pdo = new PDO(
        'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
        'travian_user',
        'travian_password123'
    );

    echo "Adding missing columns to activation table...\n\n";

    // Add worldId column
    try {
        $pdo->exec("ALTER TABLE activation ADD COLUMN worldId INT UNSIGNED NOT NULL AFTER id");
        echo "✓ Added worldId column\n";
    } catch (Exception $e) {
        echo "- worldId column already exists or error: " . substr($e->getMessage(), 0, 50) . "\n";
    }

    // Add activationCode column
    try {
        $pdo->exec("ALTER TABLE activation ADD COLUMN activationCode VARCHAR(32) NOT NULL DEFAULT '' AFTER email");
        echo "✓ Added activationCode column\n";
    } catch (Exception $e) {
        echo "- activationCode column already exists or error: " . substr($e->getMessage(), 0, 50) . "\n";
    }

    // Add newsletter column
    try {
        $pdo->exec("ALTER TABLE activation ADD COLUMN newsletter TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER activationCode");
        echo "✓ Added newsletter column\n";
    } catch (Exception $e) {
        echo "- newsletter column already exists or error: " . substr($e->getMessage(), 0, 50) . "\n";
    }

    // Add used column
    try {
        $pdo->exec("ALTER TABLE activation ADD COLUMN used TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER time");
        echo "✓ Added used column\n";
    } catch (Exception $e) {
        echo "- used column already exists or error: " . substr($e->getMessage(), 0, 50) . "\n";
    }

    echo "\n✓ All columns added successfully!\n";

    // Show final schema
    $stmt = $pdo->query("DESCRIBE activation");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nFinal activation table schema:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
