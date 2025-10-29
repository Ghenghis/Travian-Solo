<?php
/**
 * Test what Database\DB is actually trying to connect to
 */

require_once __DIR__ . '/sections/globalConfig.php';

echo "Testing Database\DB connection details...\n";

echo "Constants:\n";
echo "  DB_HOST: " . DB_HOST . "\n";
echo "  DB_PORT: " . DB_PORT . "\n";
echo "  DB_USERNAME: " . DB_USERNAME . "\n";
echo "  DB_PASSWORD: " . DB_PASSWORD . "\n";
echo "  DB_DATABASE: " . DB_DATABASE . "\n";

echo "\nGlobal config dataSources:\n";
global $globalConfig;
$globalDb = $globalConfig['dataSources']['globalDB'];
echo "  Host: " . $globalDb['hostname'] . "\n";
echo "  Port: " . $globalDb['port'] . "\n";
echo "  User: " . $globalDb['username'] . "\n";
echo "  DB: " . $globalDb['database'] . "\n";
echo "  Pass: " . $globalDb['password'] . "\n";

// Test connection
try {
    require_once __DIR__ . '/sections/api/include/Database/DB.php';
    $db = Database\DB::getInstance();
    echo "\n✓ Database\DB connection successful!\n";
} catch (Exception $e) {
    echo "\n✗ Database\DB connection failed: " . $e->getMessage() . "\n";
}
