<?php
global $connection;
$connection = [
    'speed' => '5',
    'round_length' => '180',
    'worldId' => 'demo',
    'title' => 'Demo Server 5x',
    'serverName' => 'Demo Server',
    'version' => 'T4.6',
    'gameWorldUrl' => 'http://demo.travian.local/',
    'secure_hash_code' => md5('demo_' . time()),
    'auto_reinstall' => '0',
    'auto_reinstall_start_after' => '86400',
    'engine_filename' => 'demo.service',
    'database' => [
        'hostname' => getenv('DB_HOST') ?: 'mysql',
        'username' => getenv('DB_USERNAME') ?: 'travian_user',
        'password' => getenv('DB_PASSWORD') ?: 'travian_password123',
        'database' => 'travian_demo',
        'charset'  => 'utf8mb4',
    ],
];
if (empty($connection['database']['password'])) {
    error_log('ERROR: Database password not configured for demo');
}