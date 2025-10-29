<?php
global $connection;
$connection = [
    'speed' => '100',
    'round_length' => '365',
    'worldId' => 'testworld',
    'title' => 'Test Server 100x',
    'serverName' => 'Test Server',
    'version' => 'T4.6',
    'gameWorldUrl' => 'http://testworld.travian.local/',
    'secure_hash_code' => md5('testworld_' . time()),
    'auto_reinstall' => '0',
    'auto_reinstall_start_after' => '86400',
    'engine_filename' => 'testworld.service',
    'database' => [
        'hostname' => getenv('DB_HOST') ?: 'mysql',
        'username' => getenv('DB_USERNAME') ?: 'travian_user',
        'password' => getenv('DB_PASSWORD') ?: 'travian_password123',
        'database' => 'travian_testworld',
        'charset'  => 'utf8mb4',
    ],
];
if (empty($connection['database']['password'])) {
    error_log('ERROR: Database password not configured for testworld');
}