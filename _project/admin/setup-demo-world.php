<?php
/**
 * Setup Demo World - Copy schema from testworld to demo
 */

echo "=== DEMO WORLD SETUP ===\n\n";

// Connect to testworld
$testworld = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_testworld;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Connect to demo
$demo = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_demo;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "Step 1: Check current state\n";

// Get testworld tables
$testTables = $testworld->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "  Testworld tables: " . count($testTables) . "\n";

// Get demo tables
$demoTables = $demo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "  Demo tables: " . count($demoTables) . "\n\n";

if (count($demoTables) >= count($testTables)) {
    echo "✓ Demo world already has schema!\n";
    echo "  No action needed.\n";
    exit(0);
}

echo "Step 2: Copy schema from testworld to demo\n";
echo "  This will copy table structures (not data)\n\n";

$copied = 0;
$errors = [];

foreach ($testTables as $table) {
    try {
        // Check if table already exists in demo
        $exists = in_array($table, $demoTables);
        
        if ($exists) {
            echo "  - $table (already exists, skipping)\n";
            continue;
        }
        
        // Get CREATE TABLE statement
        $result = $testworld->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $createStmt = $result['Create Table'];
        
        // Execute in demo
        $demo->exec($createStmt);
        
        echo "  ✓ $table (copied)\n";
        $copied++;
        
    } catch (PDOException $e) {
        $errors[] = "$table: " . $e->getMessage();
        echo "  ✗ $table (error)\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Tables copied: $copied\n";
echo "Errors: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\nErrors encountered:\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}

// Verify final count
$demoTablesAfter = $demo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "\nFinal demo tables: " . count($demoTablesAfter) . "\n";

if (count($demoTablesAfter) == count($testTables)) {
    echo "✅ Demo world schema complete!\n";
} else {
    echo "⚠️ Demo world schema incomplete\n";
    echo "Expected: " . count($testTables) . ", Got: " . count($demoTablesAfter) . "\n";
}
