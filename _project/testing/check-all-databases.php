<?php
/**
 * Check All Databases
 * Verifies what databases exist and their tables
 */

echo "===========================================\n";
echo "  DATABASE VERIFICATION TOOL\n";
echo "===========================================\n\n";

// Database connection settings
$host = getenv('DB_HOST') ?: 'mysql';
$username = 'root';
$password = 'root_password123'; // From MYSQL_ROOT_PASSWORD in .env

try {
    // Connect without specifying database
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to MySQL server\n\n";
    
    // Get all databases
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📊 DATABASES FOUND:\n";
    echo "-------------------------------------------\n";
    
    foreach ($databases as $dbName) {
        // Skip system databases
        if (in_array($dbName, ['information_schema', 'performance_schema', 'mysql', 'sys'])) {
            continue;
        }
        
        echo "\n🗄️  Database: $dbName\n";
        
        // Switch to database and count tables
        $pdo->exec("USE `$dbName`");
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $tableCount = count($tables);
        
        if ($tableCount > 0) {
            echo "   ✅ Tables: $tableCount\n";
            
            // Show first 10 tables
            echo "   📋 Sample tables:\n";
            $sampleTables = array_slice($tables, 0, 10);
            foreach ($sampleTables as $table) {
                echo "      - $table\n";
            }
            if ($tableCount > 10) {
                echo "      ... and " . ($tableCount - 10) . " more\n";
            }
        } else {
            echo "   ⚠️  NO TABLES (empty database)\n";
        }
    }
    
    echo "\n===========================================\n";
    echo "  WORLD DATABASE CHECK\n";
    echo "===========================================\n\n";
    
    // Check for world databases
    $worldDatabases = ['travian_testworld', 'travian_demo'];
    
    foreach ($worldDatabases as $worldDb) {
        if (in_array($worldDb, $databases)) {
            echo "✅ $worldDb EXISTS\n";
            
            // Check table count
            $pdo->exec("USE `$worldDb`");
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $tableCount = count($tables);
            
            if ($tableCount >= 90) {
                echo "   ✅ Has $tableCount tables (COMPLETE!)\n";
            } elseif ($tableCount > 0) {
                echo "   ⚠️  Has only $tableCount tables (INCOMPLETE - needs 90+)\n";
            } else {
                echo "   ❌ NO TABLES (needs import!)\n";
            }
        } else {
            echo "❌ $worldDb DOES NOT EXIST (needs creation!)\n";
        }
    }
    
    echo "\n===========================================\n";
    echo "  SUMMARY\n";
    echo "===========================================\n\n";
    
    $globalExists = in_array('travian_global', $databases);
    $testworldExists = in_array('travian_testworld', $databases);
    $demoExists = in_array('travian_demo', $databases);
    
    if ($globalExists) {
        echo "✅ Global database: EXISTS\n";
    } else {
        echo "❌ Global database: MISSING\n";
    }
    
    if ($testworldExists) {
        echo "✅ Testworld database: EXISTS\n";
    } else {
        echo "❌ Testworld database: NEEDS CREATION\n";
    }
    
    if ($demoExists) {
        echo "✅ Demo database: EXISTS\n";
    } else {
        echo "❌ Demo database: NEEDS CREATION\n";
    }
    
    echo "\n";
    
    if ($globalExists && $testworldExists && $demoExists) {
        echo "🎉 ALL DATABASES EXIST!\n";
        echo "   Next: Verify table counts\n";
    } else {
        echo "⚠️  MISSING DATABASES!\n";
        echo "   Next: Create missing databases\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n===========================================\n";
echo "Done!\n";
