<?php
/**
 * List All Tables in World Databases
 */

$host = getenv('DB_HOST') ?: 'mysql';
$username = 'root';
$password = 'root_password123';

try {
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $database = 'travian_testworld';
    $pdo->exec("USE `$database`");
    
    echo "===========================================\n";
    echo "  ALL TABLES IN: $database\n";
    echo "===========================================\n\n";
    
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Total tables: " . count($tables) . "\n\n";
    
    // Group tables by category
    $categories = [
        'User & Auth' => ['users', 'activation', 'activation_progress', 'online', 'mdata', 'hero', 'profile'],
        'Village' => ['vdata', 'fdata', 'odata', 'wdata', 'abdata', 'bdata'],
        'Resources' => ['movement', 'market', 'raidlist', 'send', 'stockmarket'],
        'Military' => ['units', 'enforcement', 'a2b', 'attacks', 'farmlist', 'prisoners'],
        'Alliance' => ['alidata', 'ali_invite', 'ali_log', 'ali_permission', 'diplomacy'],
        'Reports' => ['report', 'ndata', 'mdata', 'battle_report'],
        'Technology' => ['research', 'training', 'tdata'],
        'Events' => ['deleting', 'demolish', 'destroy', 'artefacts', 'banlist'],
    ];
    
    // Find uncategorized tables
    $categorized = [];
    foreach ($categories as $tables_in_cat) {
        $categorized = array_merge($categorized, $tables_in_cat);
    }
    
    echo "📋 TABLES BY CATEGORY:\n";
    echo "-------------------------------------------\n\n";
    
    foreach ($categories as $category => $keywords) {
        $found = [];
        foreach ($tables as $table) {
            foreach ($keywords as $keyword) {
                if (stripos($table, $keyword) !== false || $table === $keyword) {
                    if (!in_array($table, $found)) {
                        $found[] = $table;
                    }
                }
            }
        }
        
        if (!empty($found)) {
            echo "## $category\n";
            foreach ($found as $table) {
                // Get row count
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                $status = $count > 0 ? "($count rows)" : "(empty)";
                echo "   - $table $status\n";
            }
            echo "\n";
        }
    }
    
    echo "📄 ALL TABLES (Alphabetical):\n";
    echo "-------------------------------------------\n";
    sort($tables);
    $column = 0;
    foreach ($tables as $table) {
        echo str_pad($table, 25);
        $column++;
        if ($column % 3 == 0) {
            echo "\n";
        }
    }
    echo "\n\n";
    
    echo "===========================================\n";
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
