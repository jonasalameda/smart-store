<?php

declare(strict_types=1);

//Settings for  development (dev) environment

// App-specific config.
define('APP_DEBUG_MODE', true);
define('APP_ASSETS_DIR', '/public/assets');

// Base URL follows the request (port 80, 8080, 127.0.0.1, etc.). Set APP_BASE_URL in the
// environment to override (e.g. CLI or when HTTP_HOST is not available).
if (!defined('APP_BASE_URL')) {
    $envBase = getenv('APP_BASE_URL');
    if (is_string($envBase) && $envBase !== '') {
        define('APP_BASE_URL', rtrim($envBase, '/'));
    } elseif (PHP_SAPI !== 'cli' && isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '') {
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $base = $scheme . '://' . $_SERVER['HTTP_HOST'] . '/' . APP_ROOT_DIR_NAME;
        define('APP_BASE_URL', rtrim($base, '/'));
    } else {
        define('APP_BASE_URL', rtrim('http://localhost/' . APP_ROOT_DIR_NAME, '/'));
    }
}

define('APP_ASSETS_DIR_URL', APP_BASE_URL . APP_ASSETS_DIR);
define('APP_ASSETS_DIR_PATH', realpath(APP_BASE_DIR_PATH . '/' . APP_ASSETS_DIR));

// Update the cache busting token upon new deployments.
define('CACHE_BUSTING_TOKEN', 'YV954');

function myCustomErrorHandler(int $error_no, string $error_message, string $file, int $line_number)
{
    echo sprintf(
        "<strong>Error:</strong> %s <br><strong>Message:</strong> %s <br> <strong> occurred in:</strong> [%s] <strong> at line:</strong> [%s] <br>",
        getErrorName($error_no),
        $error_message,
        $file,
        $line_number
    );
}

set_error_handler('myCustomErrorHandler');

return function (array $settings): array {
    // Error reporting
    // Enable all error reporting for dev environment.
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');

    $settings['error']['display_error_details'] = true;

    // Skip admin gate on protected routes; home (/) goes to dashboard instead of login.
    $settings['features']['customer_auth_enabled'] = false;

    // Database
    $settings['db']['database'] = 'smart-store-db';
    $settings['db']['hostname'] = 'root';
    $settings['db']['port'] = '3306';

    return $settings;
};
