<?php

/**
 * Application base paths
 */
define('PATH_ROOT', realpath(__DIR__ . '/..') . '/');
define('PATH_CONTROLLER', PATH_ROOT . 'controllers/');
define('PATH_MODEL', PATH_ROOT . 'models/');
define('PATH_VIEW', PATH_ROOT . 'views/');
define('PATH_ASSETS', PATH_ROOT . 'assets/');
define('PATH_ASSETS_UPLOADS', PATH_ASSETS . 'uploads/');

/**
 * URL configuration
 * Automatically detect the current base URL, but allow overriding
 */
if (!defined('BASE_URL')) {
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] === '443');
    $scheme = $isSecure ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        $scriptDir = '';
    }
    $basePath = $scriptDir === '' ? '/' : rtrim($scriptDir, '/') . '/';
    define('BASE_URL', sprintf('%s://%s%s', $scheme, $host, $basePath));
}

define('BASE_ASSETS', rtrim(BASE_URL, '/') . '/assets/');
define('BASE_ASSETS_UPLOADS', BASE_ASSETS . 'uploads/');

/**
 * Database configuration
 */
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'bonbon_shop');

define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);


