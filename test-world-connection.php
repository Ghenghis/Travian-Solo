<?php
/**
 * Test World Database Connections
 * Tests connectivity to testworld and demo databases
 */

require_once __DIR__ . '/sections/api/include/Database/ServerDB.php';

use Database\ServerDB;

echo "=== Testing World Database Connections ===\n\n";

// Test testworld
echo "Testing testworld connection...\n";
try {
    $configPath = __DIR__ . '/sections/servers/testworld/include/connection.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("Config file not found: {$configPath}");
    }
    
    $db = ServerDB::getInstance($configPath);
    $result = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
    echo "✓ Testworld connected! Found {$result['count']} users\n";
    
    // Check table count
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Testworld has " . count($tables) . " tables\n";
    
    // Verify critical tables exist
    $criticalTables = ['users', 'villages', 'alliances', 'activation', 'marketplace'];
    foreach ($criticalTables as $table) {
        $exists = $db->query("SHOW TABLES LIKE '{$table}'")->fetch();
        if ($exists) {
            echo "  ✓ {$table} table exists\n";
        } else {
            echo "  ✗ {$table} table MISSING!\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Testworld failed: " . $e->getMessage() . "\n";
}

echo "\n";

// Test demo
echo "Testing demo connection...\n";
try {
    $configPath = __DIR__ . '/sections/servers/demo/include/connection.php';
    
    if (!file_exists($configPath)) {
        throw new Exception("Config file not found: {$configPath}");
    }
    
    $db = ServerDB::getInstance($configPath);
    $result = $db->query("SELECT COUNT(*) as count FROM users")->fetch();
    echo "✓ Demo connected! Found {$result['count']} users\n";
    
    // Check table count
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Demo has " . count($tables) . " tables\n";
    
    // Verify critical tables exist
    $criticalTables = ['users', 'villages', 'alliances', 'activation', 'marketplace'];
    foreach ($criticalTables as $table) {
        $exists = $db->query("SHOW TABLES LIKE '{$table}'")->fetch();
        if ($exists) {
            echo "  ✓ {$table} table exists\n";
        } else {
            echo "  ✗ {$table} table MISSING!\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Demo failed: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== World Database Connectivity Test Complete ===\n";
