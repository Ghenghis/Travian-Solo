<?php
/**
 * Test database connection directly
 */

require_once __DIR__ . '/sections/globalConfig.php';

echo "Testing database connection...\n";

try {
    $pdo = new PDO(
        'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
        'travian_user',
        'travian_password123'
    );

    echo "✓ PDO connection successful!\n";

    $stmt = $pdo->query("SELECT COUNT(*) as count FROM activation");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Query successful! Activation table has {$result['count']} records\n";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Database has " . count($tables) . " tables\n";

} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}
