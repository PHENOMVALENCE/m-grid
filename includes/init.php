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
require_once __DIR__ . '/document_helpers.php';
require_once __DIR__ . '/mscore_helper.php';
require_once __DIR__ . '/mscore_engine.php';
require_once __DIR__ . '/mfund_helper.php';
require_once __DIR__ . '/mfund_eligibility_helper.php';
require_once __DIR__ . '/repayment_helper.php';
require_once __DIR__ . '/mbenefits_helper.php';
require_once __DIR__ . '/opportunities_helper.php';
require_once __DIR__ . '/trainings_helper.php';
require_once __DIR__ . '/application_status_helper.php';
require_once __DIR__ . '/training_completion_helper.php';
require_once __DIR__ . '/notification_helper.php';
require_once __DIR__ . '/announcement_helper.php';
require_once __DIR__ . '/analytics_helper.php';
require_once __DIR__ . '/reporting_helper.php';

auth_start_session();

require_once __DIR__ . '/i18n.php';
mgrid_i18n_bootstrap();

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

/**
 * Backward-compatible schema guard for richer admin profiles.
 * Adds optional descriptive columns to admins table if missing.
 */
function mgrid_ensure_admin_extended_profile_schema(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $pdo = db();
    $columns = [
        'phone' => "ALTER TABLE admins ADD COLUMN phone VARCHAR(32) NULL AFTER email",
        'title' => "ALTER TABLE admins ADD COLUMN title VARCHAR(120) NULL AFTER phone",
        'department' => "ALTER TABLE admins ADD COLUMN department VARCHAR(120) NULL AFTER title",
    ];

    foreach ($columns as $name => $sql) {
        $col = $pdo->query("SHOW COLUMNS FROM admins LIKE '{$name}'")->fetch();
        if (!$col) {
            $pdo->exec($sql);
        }
    }
}

mgrid_ensure_admin_extended_profile_schema();

/**
 * Prefer Swahili as default for new rows (existing installs: bump DB default when still English).
 */
function mgrid_ensure_users_preferred_language_default_sw(): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;
    try {
        $pdo = db();
        $row = $pdo->query("SHOW COLUMNS FROM users LIKE 'preferred_language'")->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return;
        }
        $defRaw = isset($row['Default']) ? strtolower(trim((string) $row['Default'], " '\"")) : '';
        if ($defRaw === '' || $defRaw === 'null' || $defRaw === 'en') {
            $pdo->exec("ALTER TABLE users MODIFY preferred_language VARCHAR(16) NOT NULL DEFAULT 'sw'");
        }
    } catch (Throwable $e) {
        // Non-fatal: older MySQL or permissions may block ALTER
    }
}

mgrid_ensure_users_preferred_language_default_sw();
