<?php
/**
 * Create newsletter table in global database
 */

echo "=== CREATE NEWSLETTER TABLE ===\n\n";

$pdo = new PDO(
    'mysql:host=mysql;port=3306;dbname=travian_global;charset=utf8mb4',
    'travian_user',
    'travian_password123',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Check if table exists
$exists = $pdo->query("SHOW TABLES LIKE 'newsletter'")->rowCount() > 0;

if ($exists) {
    echo "ℹ️  Newsletter table already exists\n";
} else {
    echo "Creating newsletter table...\n";
    
    $sql = "CREATE TABLE `newsletter` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `email` varchar(255) NOT NULL,
        `private_key` varchar(50) NOT NULL,
        `subscribed` tinyint(1) NOT NULL DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`),
        KEY `private_key` (`private_key`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "✓ Newsletter table created successfully\n";
}

// Verify table structure
echo "\nTable structure:\n";
$columns = $pdo->query("DESCRIBE newsletter")->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "  - {$col['Field']} ({$col['Type']})\n";
}

// Count current subscribers
$count = $pdo->query("SELECT COUNT(*) FROM newsletter")->fetchColumn();
echo "\nCurrent subscribers: $count\n";

echo "\n✅ Newsletter table ready!\n";
