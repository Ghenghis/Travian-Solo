<?php
/**
 * Global Configuration - MySQL Version with Environment Variables
 * Updated for production-ready deployment
 */

// Error Reporting (Production)
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../storage/logs/php-error.log');

// Ensure storage/logs directory exists
$logDir = __DIR__ . '/../storage/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// MySQL Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'travian_user');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');
define('DB_DATABASE', getenv('DB_DATABASE') ?: 'travian_global');

// Validate database configuration
if (empty(DB_PASSWORD)) {
    error_log('ERROR: DB_PASSWORD not configured');
    if (getenv('APP_DEBUG') === 'true') {
        die('Database configuration error. Check logs.');
    }
}

// Redis Configuration
define('REDIS_HOST', getenv('REDIS_HOST') ?: 'redis');
define('REDIS_PORT', getenv('REDIS_PORT') ?: 6379);
define('REDIS_PASSWORD', getenv('REDIS_PASSWORD') ?: '');

// Application Settings
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('DOMAIN', parse_url(APP_URL, PHP_URL_HOST));
define('DEBUG_MODE', getenv('APP_DEBUG') === 'true');
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Security
define('SECURE_HASH_SALT', getenv('SECURE_HASH_SALT') ?: 'CHANGE_THIS_SALT_IN_PRODUCTION');
define('SESSION_LIFETIME', (int)getenv('SESSION_LIFETIME') ?: 86400);
define('COOKIE_SECURE', getenv('COOKIE_SECURE') === 'true');
define('COOKIE_DOMAIN', getenv('COOKIE_DOMAIN') ?: '');

// Email Settings
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', (int)getenv('SMTP_PORT') ?: 587);
define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: '');
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION') ?: 'tls');
define('SMTP_FROM_ADDRESS', getenv('SMTP_FROM_ADDRESS') ?: 'noreply@travian.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Travian');

// reCAPTCHA
define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY') ?: '');
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY') ?: '');

// Game Configuration
define('DEFAULT_GAME_SPEED', (int)getenv('DEFAULT_GAME_SPEED') ?: 100);
define('DEFAULT_ROUND_LENGTH', (int)getenv('DEFAULT_ROUND_LENGTH') ?: 365);
define('PROTECTION_HOURS', (int)getenv('PROTECTION_HOURS') ?: 72);

// Paths
define('ROOT_PATH', __DIR__);
define('INCLUDE_PATH', ROOT_PATH . '/api/include');

// Composer Autoload
if (file_exists(INCLUDE_PATH . '/vendor/autoload.php')) {
    require_once INCLUDE_PATH . '/vendor/autoload.php';
}

// Session Configuration
ini_set('session.save_handler', 'files'); // Change to 'redis' when Redis is available
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', COOKIE_SECURE ? '1' : '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_lifetime', SESSION_LIFETIME);

// Global configuration array for backward compatibility
global $globalConfig;
$globalConfig = [];
$globalConfig['staticParameters'] = [];
$globalConfig['staticParameters']['default_language'] = 'us';
$globalConfig['staticParameters']['default_timezone'] = 'UTC';
$globalConfig['staticParameters']['default_direction'] = 'LTR';
$globalConfig['staticParameters']['default_dateFormat'] = 'y.m.d';
$globalConfig['staticParameters']['default_timeFormat'] = 'H:i';
$globalConfig['staticParameters']['indexUrl'] = APP_URL . '/';
$globalConfig['staticParameters']['forumUrl'] = getenv('FORUM_URL') ?: APP_URL . '/forum/';
$globalConfig['staticParameters']['answersUrl'] = 'https://answers.travian.com/index.php';
$globalConfig['staticParameters']['helpUrl'] = getenv('HELP_URL') ?: APP_URL . '/help/';
$globalConfig['staticParameters']['adminEmail'] = getenv('ADMIN_EMAIL') ?: '';
$globalConfig['staticParameters']['session_timeout'] = SESSION_LIFETIME;
$globalConfig['staticParameters']['default_payment_location'] = 2;
$globalConfig['staticParameters']['global_css_class'] = getenv('CSS_CLASS') ?: 'travian';
$globalConfig['staticParameters']['gpacks'] = file_exists(__DIR__ . '/gpack/gpack.php') ? require(__DIR__ . '/gpack/gpack.php') : [];
$globalConfig['staticParameters']['recaptcha_public_key'] = RECAPTCHA_SITE_KEY;
$globalConfig['staticParameters']['recaptcha_private_key'] = RECAPTCHA_SECRET_KEY;

// Caching Servers
$globalConfig['cachingServers'] = [
    'memcached' => [
        [REDIS_HOST, REDIS_PORT],
    ],
];

// Data Sources
$globalConfig['dataSources'] = [];
$globalConfig['dataSources']['globalDB']['hostname'] = DB_HOST;
$globalConfig['dataSources']['globalDB']['username'] = DB_USERNAME;
$globalConfig['dataSources']['globalDB']['password'] = DB_PASSWORD;
$globalConfig['dataSources']['globalDB']['database'] = DB_DATABASE;
$globalConfig['dataSources']['globalDB']['charset'] = 'utf8mb4';
$globalConfig['dataSources']['globalDB']['port'] = DB_PORT;

// Logging configuration
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// Validate critical configuration
$criticalVars = ['DB_HOST', 'DB_USERNAME', 'DB_PASSWORD', 'DB_DATABASE'];
foreach ($criticalVars as $var) {
    if (empty(constant($var))) {
        error_log("CRITICAL: Missing configuration for {$var}");
        if (DEBUG_MODE) {
            die("Configuration error: {$var} is not set");
        }
    }
}
