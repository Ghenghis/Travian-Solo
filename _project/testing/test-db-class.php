<?php
/**
 * Test Database\DB class directly
 */

require_once __DIR__ . '/sections/globalConfig.php';

echo "Testing Database\DB class...\n";

try {
    require_once __DIR__ . '/sections/api/include/Database/DB.php';
    $db = Database\DB::getInstance();
    echo "✓ Database connection successful!\n";

    $stmt = $db->query("SELECT COUNT(*) as count FROM activation");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Query successful! Activation table has {$result['count']} records\n";

} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
