<?php

declare(strict_types=1);

/**
 * Application configuration (M-GRID).
 * Copy or adjust database settings for your XAMPP / hosting environment.
 */

if (!defined('MGRID_ROOT')) {
    define('MGRID_ROOT', dirname(__DIR__));
}

/** Public URL path of this project (no trailing slash), e.g. "/m-grid" under http://localhost/m-grid/ */
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '') ?: '';
$projectRoot = realpath(MGRID_ROOT) ?: MGRID_ROOT;
if ($documentRoot !== '' && str_starts_with(str_replace('\\', '/', $projectRoot), str_replace('\\', '/', $documentRoot))) {
    $relative = substr(str_replace('\\', '/', $projectRoot), strlen(str_replace('\\', '/', $documentRoot)));
    define('MGRID_URL', rtrim($relative, '/'));
} else {
    define('MGRID_URL', '');
}

define('MGRID_DB_HOST', getenv('MGRID_DB_HOST') ?: '127.0.0.1');
define('MGRID_DB_NAME', getenv('MGRID_DB_NAME') ?: 'm_grid');
define('MGRID_DB_USER', getenv('MGRID_DB_USER') ?: 'root');
define('MGRID_DB_PASS', getenv('MGRID_DB_PASS') ?: '');
define('MGRID_DB_CHARSET', 'utf8mb4');

/** Session cookie name (avoid clashing with other PHP apps on same host) */
define('MGRID_SESSION_NAME', 'MGRIDSESSID');
