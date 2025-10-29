<?php
/**
 * Test ServerDB connection to testworld
 */

require_once __DIR__ . '/sections/globalConfig.php';

echo "Testing ServerDB connection to testworld...\n";

$configFile = '/var/www/html/sections/servers/testworld/include/connection.php';
echo "Config file: $configFile\n";

if (file_exists($configFile)) {
    echo "✓ Config file exists\n";
    require_once $configFile;
    if (isset($connection)) {
        echo "✓ Connection array loaded\n";
        echo "  Host: " . $connection['database']['hostname'] . "\n";
        echo "  Port: " . $connection['database']['port'] . "\n";
        echo "  User: " . $connection['database']['username'] . "\n";
        echo "  Pass: " . $connection['database']['password'] . "\n";
        echo "  DB: " . $connection['database']['database'] . "\n";
        
        require_once __DIR__ . '/sections/api/include/Database/ServerDB.php';
        try {
            $serverDB = Database\ServerDB::getInstance($configFile);
            echo "✓ ServerDB connection successful!\n";
        } catch (Exception $e) {
            echo "✗ ServerDB connection failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ Connection array not loaded\n";
    }
} else {
    echo "✗ Config file not found\n";
}
