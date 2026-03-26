<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Slim\App;

require realpath(__DIR__ . '/../vendor/autoload.php');

// Load the app's global constants.
require_once realpath(__DIR__ . '/constants.php');
// Include the global functions that will be used across the app's various components.
require realpath(__DIR__ . '/functions.php');

// Ensure PHP sessions have a writable directory on Windows/Wamp.
// Without this, PHP may try to use a default Linux path like /var/lib/php/sessions,
// which can cause "Failed to read session data" warnings/errors.
if (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionDir = APP_BASE_DIR_PATH . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'sessions';
    if (!is_dir($sessionDir)) {
        @mkdir($sessionDir, 0777, true);
    }

    // Set session location before session_start().
    if (is_dir($sessionDir)) {
        session_save_path($sessionDir);
    }

    // Match the app's intended session name (see config/defaults.php).
    if (!empty($_COOKIE[session_name()])) {
        // no-op; keep current session name if already set by PHP
    }
    session_name('app_session');

    session_start();
}

// Configure the DI container and load dependencies.
$definitions = require realpath(__DIR__ . '/container.php');

// Build DI container instance
//@see https://php-di.org/
$container = (new ContainerBuilder())
    ->addDefinitions($definitions)
    ->build();
// Create App instance
return $container->get(App::class);
