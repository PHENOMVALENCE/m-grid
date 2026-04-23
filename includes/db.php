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

    $port = defined('MGRID_DB_PORT') ? (int) MGRID_DB_PORT : 3306;
    if ($port < 1 || $port > 65535) {
        $port = 3306;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        MGRID_DB_HOST,
        $port,
        MGRID_DB_NAME,
        MGRID_DB_CHARSET
    );

    try {
        $pdo = new PDO($dsn, MGRID_DB_USER, MGRID_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        $code = (string) $e->getCode();
        $msg = $e->getMessage();
        if ($code === '2002' || str_contains($msg, '2002') || str_contains($msg, 'actively refused')) {
            throw new PDOException(
                'Database connection refused. Start MySQL in XAMPP Control Panel (Apache can run while MySQL is stopped), '
                . 'or set MGRID_DB_HOST / MGRID_DB_PORT if your server uses another host or port. '
                . "Tried: host=" . MGRID_DB_HOST . ", port={$port}, database=" . MGRID_DB_NAME . '.',
                (int) $e->getCode() ?: 2002,
                $e
            );
        }
        throw $e;
    }

    return $pdo;
}
