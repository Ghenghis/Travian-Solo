<?php
/**
 * Check activation settings for game servers
 */

try {
    $pdo = new PDO(
        'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
        'travian_user',
        'travian_password123'
    );

    echo "Checking game server activation settings...\n\n";

    $stmt = $pdo->query("SELECT id, worldId, name, activation FROM gameServers");
    $servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($servers as $server) {
        echo "Server: {$server['name']} (worldId: {$server['worldId']})\n";
        echo "Activation: " . ($server['activation'] == 1 ? 'ENABLED (saves to global DB)' : 'DISABLED (saves to world DB)') . "\n";
        echo "---\n";
    }

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
