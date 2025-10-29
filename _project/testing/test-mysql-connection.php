<?php
/**
 * MySQL Connection Test Script
 * Tests the global database connection
 */

require_once __DIR__ . '/sections/globalConfig.php';
require_once __DIR__ . '/sections/api/include/Database/DB.php';

use Database\DB;

echo "=" . str_repeat("=", 60) . "\n";
echo "MySQL Connection Test\n";
echo "=" . str_repeat("=", 60) . "\n\n";

try {
    echo "Testing MySQL connection...\n";
    
    // Test connection
    $db = DB::getInstance();
    echo "✓ MySQL Connection Successful!\n";
    
    // Test basic query
    $result = $db->query("SELECT 1 as test")->fetch();
    if ($result['test'] == 1) {
        echo "✓ Basic query test passed\n";
    }
    
    // Check if global database exists
    $result = $db->query("SELECT DATABASE() as db_name")->fetch();
    echo "✓ Connected to database: " . $result['db_name'] . "\n";
    
    // Check tables
    echo "\nChecking tables...\n";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredTables = [
        'gameServers',
        'activation', 
        'configurations',
        'banIP',
        'email_blacklist',
        'mailserver',
        'passwordRecovery'
    ];
    
    foreach ($requiredTables as $table) {
        if (in_array($table, $tables)) {
            echo "  ✓ Table '{$table}' exists\n";
        } else {
            echo "  ✗ Table '{$table}' MISSING\n";
        }
    }
    
    // Check game servers
    echo "\nChecking game servers...\n";
    $servers = $db->query("SELECT worldId, name, speed FROM gameServers")->fetchAll();
    if (count($servers) > 0) {
        echo "✓ Found " . count($servers) . " game servers:\n";
        foreach ($servers as $server) {
            echo "  - {$server['name']} ({$server['worldId']}) - {$server['speed']}x speed\n";
        }
    } else {
        echo "✗ No game servers found\n";
    }
    
    // Check configurations
    echo "\nChecking configurations...\n";
    $configs = $db->query("SELECT `key`, `value` FROM configurations")->fetchAll();
    if (count($configs) > 0) {
        echo "✓ Found " . count($configs) . " configuration entries:\n";
        foreach ($configs as $config) {
            echo "  - {$config['key']}: {$config['value']}\n";
        }
    } else {
        echo "✗ No configurations found\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✓ MySQL Connection Test PASSED\n";
    echo str_repeat("=", 60) . "\n";
    
} catch (Exception $e) {
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✗ MySQL Connection Test FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo str_repeat("=", 60) . "\n";
    exit(1);
}
