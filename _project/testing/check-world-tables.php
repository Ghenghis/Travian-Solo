<?php
/**
 * Check world database table count
 */

echo "=== WORLD DATABASE TABLE CHECK ===\n\n";

$worldDb = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_testworld;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Get all tables
$stmt = $worldDb->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "📊 Total tables in travian_testworld: " . count($tables) . "\n\n";

if (count($tables) > 0) {
    echo "Tables found:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
} else {
    echo "❌ No tables found!\n";
    echo "World schema needs to be imported.\n";
}

echo "\n";

// Check critical tables
$criticalTables = [
    'users',
    'activation',
    'login_handshake',
    'village',
    'fdata',
    'odata',
    'wdata'
];

echo "📋 Checking critical tables:\n";
foreach ($criticalTables as $table) {
    $exists = in_array($table, $tables);
    echo ($exists ? "✓" : "✗") . " $table\n";
}

// Expected table count for T4.4 schema
$expectedCount = 90;
echo "\n📈 Expected tables: ~$expectedCount\n";
echo "Current tables: " . count($tables) . "\n";

if (count($tables) < $expectedCount) {
    echo "\n⚠️ WORLD SCHEMA INCOMPLETE!\n";
    echo "Need to import: docker/mysql/init/02-world-schema.sql\n";
} else {
    echo "\n✅ WORLD SCHEMA COMPLETE!\n";
}
