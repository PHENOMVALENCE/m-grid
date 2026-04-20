<?php

declare(strict_types=1);

/**
 * Front controller include: configuration, database, helpers, session.
 * Every public PHP entry point should require this file first.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/m_id_generator.php';

auth_start_session();

/**
 * Backward-compatible schema guard for admin roles.
 * Adds admins.role if missing and promotes earliest admin to super_admin.
 */
function mgrid_ensure_admin_role_schema(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $pdo = db();
    $col = $pdo->query("SHOW COLUMNS FROM admins LIKE 'role'")->fetch();
    if ($col) {
        return;
    }

    $pdo->exec("ALTER TABLE admins ADD COLUMN role ENUM('super_admin','admin') NOT NULL DEFAULT 'admin' AFTER password_hash");
    $pdo->exec("UPDATE admins SET role = 'super_admin' WHERE id = (SELECT min_id FROM (SELECT MIN(id) AS min_id FROM admins) AS t)");
}

mgrid_ensure_admin_role_schema();
