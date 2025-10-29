<?php
/**
 * PHPUnit Bootstrap File
 * Loads the real production codebase for testing
 */

// Start session for CSRF testing
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Set testing environment
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';
$_SERVER['APP_ENV'] = 'testing';

// Define base path
define('BASE_PATH', dirname(__DIR__));

// Autoloader for production code - USES REAL CODEBASE
spl_autoload_register(function ($class) {
    // Convert namespace to file path
    $class = str_replace('\\', '/', $class);
    
    // Map namespaces to directories in REAL codebase
    $paths = [
        'Core/' => BASE_PATH . '/sections/api/include/Core/',
        'Api/Ctrl/' => BASE_PATH . '/sections/api/include/Api/Ctrl/',
        'Api/' => BASE_PATH . '/sections/api/include/Api/',
        'Middleware/' => BASE_PATH . '/sections/api/include/Middleware/',
        'Model/' => BASE_PATH . '/sections/api/include/Model/',
    ];
    
    foreach ($paths as $namespace => $dir) {
        if (strpos($class, $namespace) === 0) {
            $file = $dir . substr($class, strlen($namespace)) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
    
    // Try direct path for non-namespaced classes
    $file = BASE_PATH . '/sections/api/include/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load global config for database constants
require_once BASE_PATH . '/sections/globalConfig.php';

// Load global helper functions used by namespaced classes (e.g., get_random_string)
if (file_exists(BASE_PATH . '/sections/api/include/functions.php')) {
    require_once BASE_PATH . '/sections/api/include/functions.php';
}

// Debug output removed to keep headers intact during tests
