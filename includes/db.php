<?php

declare(strict_types=1);

/**
 * Shared PDO instance (singleton-style) for prepared statements across the app.
 */

require_once __DIR__ . '/config.php';

/**
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        MGRID_DB_HOST,
        MGRID_DB_NAME,
        MGRID_DB_CHARSET
    );

    $pdo = new PDO($dsn, MGRID_DB_USER, MGRID_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
