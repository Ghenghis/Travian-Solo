<?php
/**
 * Check game servers configuration
 */

require_once __DIR__ . '/sections/globalConfig.php';

echo "Checking game servers configuration...\n";

try {
    require_once __DIR__ . '/sections/api/include/Database/DB.php';
    $db = Database\DB::getInstance();

    $stmt = $db->query("SELECT id, worldId, configFileLocation, registerClosed FROM gameServers LIMIT 5");
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Found " . count($servers) . " game servers:\n";
    foreach ($servers as $server) {
        echo "  ID: {$server['id']}, WorldId: {$server['worldId']}, Config: {$server['configFileLocation']}, Closed: {$server['registerClosed']}\n";
    }

    // Check what server ID 1 looks like
    $stmt = $db->prepare("SELECT * FROM gameServers WHERE id = ?");
    $stmt->execute([1]);
    $server1 = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($server1) {
        echo "\nServer ID 1 details:\n";
        print_r($server1);
    } else {
        echo "\nServer ID 1 not found!\n";
    }

} catch (Exception $e) {
    echo "✗ Query failed: " . $e->getMessage() . "\n";
}
