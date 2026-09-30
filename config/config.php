<?php
/**
 * Application Configuration
 * Hostinger Shared Hosting Compatible
 */

// Prevent multiple inclusions
if (defined('CONFIG_LOADED')) {
    return;
}
define('CONFIG_LOADED', true);

// Start PHP session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

/**
 * Load environment variables from .env file (parent or current directory)
 */
function load_env($filePath) {
    if (!file_exists($filePath)) {
        return;
    }
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Strip surrounding quotes
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Check root directory and config directory for .env
$rootPath = dirname(__DIR__);
load_env($rootPath . '/.env');
load_env(__DIR__ . '/.env');

/**
 * Helper to retrieve environment variable with fallback
 */
function env($key, $default = null) {
    $val = getenv($key);
    if ($val === false) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    if ($val === 'true' || $val === '(true)') return true;
    if ($val === 'false' || $val === '(false)') return false;
    if ($val === 'empty' || $val === '(empty)') return '';
    if ($val === 'null' || $val === '(null)') return null;
    return $val;
}

// App Settings
define('APP_ENV', env('APP_ENV', 'production'));
define('APP_URL', rtrim(env('APP_URL', (isset($_SERVER['HTTP_HOST']) ? ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']) : 'http://localhost')), '/'));
define('APP_SECRET', env('APP_SECRET', 'carouselfy_default_secret_key_change_me'));

// Database Credentials
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', ''));
define('DB_USER', env('DB_USER', ''));
define('DB_PASSWORD', env('DB_PASSWORD', ''));

// AI API Keys (Stored server-side only)
define('OPENAI_API_KEY', env('OPENAI_API_KEY', ''));
define('OPENAI_MODEL', env('OPENAI_MODEL', 'gpt-4o-mini'));
define('LOVABLE_API_KEY', env('LOVABLE_API_KEY', ''));

// Meta / Instagram Graph API Settings
define('INSTAGRAM_APP_ID', env('INSTAGRAM_APP_ID', ''));
define('INSTAGRAM_APP_SECRET', env('INSTAGRAM_APP_SECRET', ''));
define('INSTAGRAM_REDIRECT_URI', env('INSTAGRAM_REDIRECT_URI', APP_URL . '/api/instagram/callback.php'));
define('INSTAGRAM_GRAPH_VERSION', env('INSTAGRAM_GRAPH_VERSION', 'v20.0'));

// Cron Secret Token
define('CRON_SECRET', env('CRON_SECRET', 'change_this_cron_token_for_security'));

// Directory Paths
define('ROOT_PATH', $rootPath);
define('PUBLIC_PATH', $rootPath . '/public_html');
define('UPLOADS_PATH', $rootPath . '/public_html/uploads');

// Error reporting settings
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}
