<?php
/**
 * Test Activation Flow
 * Tests the activation process after registration
 */

echo "=== ACTIVATION FLOW TEST ===\n\n";

// Get the most recent registered user
$pdo = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$stmt = $pdo->query("SELECT * FROM activation WHERE used = 0 ORDER BY id DESC LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "❌ NO REGISTERED USERS FOUND!\n";
    exit(1);
}

echo "📋 User Details:\n";
echo "  ID: {$user['id']}\n";
echo "  Username: {$user['name']}\n";
echo "  Email: {$user['email']}\n";
echo "  WorldId: {$user['worldId']}\n";
echo "  Token: {$user['token']}\n";
echo "  Used: " . ($user['used'] ? 'YES' : 'NO') . "\n\n";

// Check if activate.php exists in the world directory
$worldPaths = [
    '/var/www/html/sections/servers/testworld/public/activate.php',
    '/var/www/html/sections/servers/testworld/activate.php',
    '/var/www/html/activate.php',
];

echo "=== Checking for activate.php ===\n";
$activateFound = false;
foreach ($worldPaths as $path) {
    if (file_exists($path)) {
        echo "✓ Found: $path\n";
        $activateFound = true;
        break;
    } else {
        echo "✗ Not found: $path\n";
    }
}

if (!$activateFound) {
    echo "\n⚠️ activate.php not found in expected locations!\n";
    echo "This needs to be created or the world schema needs to be imported.\n\n";
}

// Test 1: Check if ActivateHandler class exists
echo "\n=== TEST 1: Check ActivateHandler Class ===\n";
if (class_exists('Core\ActivateHandler')) {
    echo "✓ ActivateHandler class exists\n";
} else {
    if (file_exists('/var/www/html/sections/api/include/Core/ActivateHandler.php')) {
        echo "✓ ActivateHandler.php file exists\n";
        require_once '/var/www/html/sections/api/include/Core/ActivateHandler.php';
    } else {
        echo "✗ ActivateHandler class not found!\n";
    }
}

// Test 2: Check world database exists and has users table
echo "\n=== TEST 2: Check World Database ===\n";
try {
    $worldDb = new PDO(
        'mysql:host=mysql;port=3306;dbname=travian_testworld;charset=utf8mb4',
        'travian_user',
        'travian_password123',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✓ Connected to travian_testworld\n";
    
    // Check if users table exists
    $tables = $worldDb->query("SHOW TABLES LIKE 'users'")->fetchAll();
    if (count($tables) > 0) {
        echo "✓ users table exists\n";
        
        // Count users
        $count = $worldDb->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "  Current users in table: $count\n";
    } else {
        echo "✗ users table NOT FOUND!\n";
        echo "  World schema needs to be imported (90+ tables)\n";
        
        // Show what tables do exist
        $existingTables = $worldDb->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "  Existing tables: " . count($existingTables) . "\n";
        if (count($existingTables) > 0) {
            echo "  First few: " . implode(', ', array_slice($existingTables, 0, 5)) . "...\n";
        }
    }
} catch (PDOException $e) {
    echo "✗ Could not connect to travian_testworld\n";
    echo "  Error: " . $e->getMessage() . "\n";
}

// Test 3: Simulate activation process
echo "\n=== TEST 3: Simulate Activation ===\n";
echo "Token to use: {$user['token']}\n";

// Check if we can find the activation record by token
$stmt = $pdo->prepare("SELECT * FROM activation WHERE token = :token AND used = 0");
$stmt->execute(['token' => $user['token']]);
$activation = $stmt->fetch(PDO::FETCH_ASSOC);

if ($activation) {
    echo "✓ Activation record found by token\n";
    echo "  Username: {$activation['name']}\n";
    echo "  Email: {$activation['email']}\n";
    echo "  WorldId: {$activation['worldId']}\n";
} else {
    echo "✗ Could not find activation record by token\n";
}

// Test 4: Check what happens when we try to activate
echo "\n=== TEST 4: Check Activation Requirements ===\n";
$requirements = [
    'Activation record exists' => isset($activation),
    'Token matches' => isset($activation) && $activation['token'] === $user['token'],
    'Not already used' => isset($activation) && $activation['used'] == 0,
    'World database exists' => isset($worldDb),
    'Users table exists' => isset($tables) && count($tables) > 0,
];

foreach ($requirements as $req => $met) {
    echo ($met ? "✓" : "✗") . " $req\n";
}

$allMet = !in_array(false, $requirements, true);
if ($allMet) {
    echo "\n🎉 All requirements met! Ready to activate.\n";
} else {
    echo "\n⚠️ Some requirements not met. Activation may fail.\n";
}

echo "\n=== SUMMARY ===\n";
echo "User registered: YES\n";
echo "Token generated: YES\n";
echo "Activation record: " . (isset($activation) ? "FOUND" : "NOT FOUND") . "\n";
echo "World DB ready: " . (isset($worldDb) ? "YES" : "NO") . "\n";
echo "Users table exists: " . (isset($tables) && count($tables) > 0 ? "YES" : "NO - NEED TO IMPORT SCHEMA") . "\n";

if (!isset($tables) || count($tables) == 0) {
    echo "\n⚡ NEXT STEP: Import world database schema\n";
    echo "Run: docker-compose exec -T mysql mysql -u root -proot_password123 travian_testworld < docker/mysql/init/02-world-schema.sql\n";
}
