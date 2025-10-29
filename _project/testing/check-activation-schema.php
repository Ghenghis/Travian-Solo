<?php
/**
 * Check activation table schema
 */

try {
    $pdo = new PDO(
        'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
        'travian_user',
        'travian_password123'
    );

    echo "Checking activation table schema...\n\n";

    $stmt = $pdo->query("DESCRIBE activation");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Current columns in activation table:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }

    // Check if worldId column exists
    $hasWorldId = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'worldId') {
            $hasWorldId = true;
            break;
        }
    }

    echo "\nworldId column exists: " . ($hasWorldId ? 'YES' : 'NO') . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
