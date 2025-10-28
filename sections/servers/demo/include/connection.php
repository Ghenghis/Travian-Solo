<?php
/**
 * Demo World Connection Configuration
 * Speed: 5x
 * Round Length: 180 days
 */

global $connection;
$connection = [
    // Game Settings
    'speed' => '5',                // 5x game speed
    'round_length' => '180',       // 180 days per round
    'worldId' => 'demo',
    'title' => 'Demo Server 5x',
    'serverName' => 'Demo Server',
    'version' => 'T4.6',
    
    // URLs
    'gameWorldUrl' => 'http://demo.travian.local/',
    'indexUrl' => 'http://demo.travian.local/',
    
    // Security
    'secure_hash_code' => md5(uniqid(rand(), true)),
    
    // Auto-reinstall settings
    'auto_reinstall' => '0',
    'auto_reinstall_start_after' => '86400',
    'engine_filename' => 'demo.service',
    
    // Database Configuration
    'database' => [
        'hostname' => 'mysql',
        'username' => 'travian_user',
        'password' => 'travian_password123',
        'database' => 'travian_demo',
        'charset' => 'utf8mb4',
        'port' => '3306',
    ],
    
    // Game World Specific Settings
    'settings' => [
        'protection_hours' => 72,
        'max_players' => 10000,
        'tribe_limit' => [1 => 3000, 2 => 3000, 3 => 3000, 4 => 1000],
        'registration_open' => true,
        'activation_required' => true,
        'email_activation' => true,
        'plus_account' => true,
        'gold_enabled' => true,
        'artifacts_enabled' => true,
        'ww_enabled' => true,
        'natars_enabled' => false, // Disabled for demo
    ],
    
    // Paths
    'paths' => [
        'root' => '/var/www/html/sections/servers/demo/',
        'public' => '/var/www/html/sections/servers/demo/public/',
        'logs' => '/var/www/html/sections/servers/demo/logs/',
        'cache' => '/var/www/html/sections/servers/demo/cache/',
    ],
    
    // Logging
    'logging' => [
        'enabled' => true,
        'level' => getenv('APP_DEBUG') === 'true' ? 'debug' : 'error',
        'file' => '/var/www/html/sections/servers/demo/logs/world.log',
    ],
];
