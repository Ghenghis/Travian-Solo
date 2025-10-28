<?php
/**
 * Test World Connection Configuration
 * Speed: 100x
 * Round Length: 365 days
 */

global $connection;
$connection = [
    // Game Settings
    'speed' => '100',              // 100x game speed
    'round_length' => '365',       // 365 days per round
    'worldId' => 'testworld',
    'title' => 'Test Server 100x',
    'serverName' => 'Test Server',
    'version' => 'T4.6',
    
    // URLs
    'gameWorldUrl' => 'http://testworld.travian.local/',
    'indexUrl' => 'http://testworld.travian.local/',
    
    // Security
    'secure_hash_code' => md5(uniqid(rand(), true)),
    
    // Auto-reinstall settings
    'auto_reinstall' => '0',
    'auto_reinstall_start_after' => '86400',
    'engine_filename' => 'testworld.service',
    
    // Database Configuration
    'database' => [
        'hostname' => 'mysql',
        'username' => 'travian_user',
        'password' => 'travian_password123',
        'database' => 'travian_testworld',
        'charset' => 'utf8mb4',
        'port' => '3306',
    ],
    
    // Game World Specific Settings
    'settings' => [
        'protection_hours' => 72,
        'max_players' => 50000,
        'tribe_limit' => [1 => 15000, 2 => 15000, 3 => 15000, 4 => 5000],
        'registration_open' => true,
        'activation_required' => true,
        'email_activation' => true,
        'plus_account' => true,
        'gold_enabled' => true,
        'artifacts_enabled' => true,
        'ww_enabled' => true,
        'natars_enabled' => true,
    ],
    
    // Paths
    'paths' => [
        'root' => '/var/www/html/sections/servers/testworld/',
        'public' => '/var/www/html/sections/servers/testworld/public/',
        'logs' => '/var/www/html/sections/servers/testworld/logs/',
        'cache' => '/var/www/html/sections/servers/testworld/cache/',
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'level' => getenv('APP_DEBUG') === 'true' ? 'debug' : 'error',
        'file' => '/var/www/html/sections/servers/testworld/logs/world.log',
    ],
];
