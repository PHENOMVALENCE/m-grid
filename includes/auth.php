<?php

declare(strict_types=1);

/**
 * Session-based authentication and role checks.
 */

require_once __DIR__ . '/functions.php';

function auth_start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    session_name(MGRID_SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** @return array<string, mixed>|null */
function auth_user(): ?array
{
    if (empty($_SESSION['user_id']) || (string) ($_SESSION['account_type'] ?? '') !== 'user') {
        return null;
    }
    $pref = (string) ($_SESSION['preferred_language'] ?? 'sw');
    if (!in_array($pref, ['en', 'sw'], true)) {
        $pref = 'sw';
    }

    return [
        'user_id' => (int) $_SESSION['user_id'],
        'full_name' => (string) $_SESSION['full_name'],
        'm_id' => (string) $_SESSION['m_id'],
        'email' => (string) ($_SESSION['email'] ?? ''),
        'phone' => (string) ($_SESSION['phone'] ?? ''),
        'preferred_language' => $pref,
        'account_type' => 'user',
    ];
}

/** @return array<string, mixed>|null */
function auth_admin(): ?array
{
    if (empty($_SESSION['admin_id']) || (string) ($_SESSION['account_type'] ?? '') !== 'admin') {
        return null;
    }
    $role = (string) ($_SESSION['admin_role'] ?? 'admin');
    if (!in_array($role, ['super_admin', 'admin'], true)) {
        $role = 'admin';
    }
    return [
        'admin_id' => (int) $_SESSION['admin_id'],
        'full_name' => (string) $_SESSION['full_name'],
        'admin_code' => (string) $_SESSION['admin_code'],
        'email' => (string) ($_SESSION['email'] ?? ''),
        'role' => $role,
        'preferred_language' => in_array((string) ($_SESSION['preferred_language'] ?? 'sw'), ['en', 'sw'], true)
            ? (string) $_SESSION['preferred_language']
            : 'sw',
        'account_type' => 'admin',
    ];
}

/** @return array<string, mixed>|null */
function auth_actor(): ?array
{
    $admin = auth_admin();
    if ($admin !== null) {
        return $admin;
    }
    return auth_user();
}

/**
 * @param array<string, mixed> $row users table row (must include id, full_name, m_id, email, phone)
 */
function auth_login_user(array $row): void
{
    $_SESSION['account_type'] = 'user';
    $_SESSION['user_id'] = (int) $row['id'];
    $_SESSION['full_name'] = (string) $row['full_name'];
    $_SESSION['m_id'] = (string) $row['m_id'];
    $_SESSION['email'] = (string) $row['email'];
    $_SESSION['phone'] = (string) $row['phone'];
    $pref = (string) ($row['preferred_language'] ?? 'sw');
    $_SESSION['preferred_language'] = in_array($pref, ['en', 'sw'], true) ? $pref : 'sw';
    session_regenerate_id(true);
}

/**
 * @param array<string, mixed> $row admins table row (must include id, admin_id, full_name, email)
 */
function auth_login_admin(array $row): void
{
    $role = (string) ($row['role'] ?? 'admin');
    if (!in_array($role, ['super_admin', 'admin'], true)) {
        $role = 'admin';
    }
    $_SESSION['account_type'] = 'admin';
    $_SESSION['admin_id'] = (int) $row['id'];
    $_SESSION['full_name'] = (string) $row['full_name'];
    $_SESSION['admin_code'] = (string) $row['admin_id'];
    $_SESSION['email'] = (string) $row['email'];
    $_SESSION['admin_role'] = $role;
    $sessLang = (string) ($_SESSION['preferred_language'] ?? 'sw');
    $_SESSION['preferred_language'] = in_array($sessLang, ['en', 'sw'], true) ? $sessLang : 'sw';
    session_regenerate_id(true);
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool) $p['secure'], (bool) $p['httponly']);
    }
    session_destroy();
}

function auth_require_login(): void
{
    if (auth_actor() === null) {
        flash_set('error', function_exists('__') ? __('flash.sign_in_required') : 'Please sign in to continue.');
        redirect('login.php');
    }
}

function auth_require_user(): void
{
    auth_require_login();
    $u = auth_user();
    if ($u === null) {
        http_response_code(403);
        echo function_exists('__') ? __('error.access_denied') : 'Access denied.';
        exit;
    }
}

function auth_require_admin(): void
{
    auth_require_login();
    $a = auth_admin();
    if ($a === null) {
        http_response_code(403);
        echo function_exists('__') ? __('error.access_denied') : 'Access denied.';
        exit;
    }
}

function auth_is_super_admin(): bool
{
    $a = auth_admin();
    return $a !== null && (string) ($a['role'] ?? '') === 'super_admin';
}

function auth_require_super_admin(): void
{
    auth_require_admin();
    if (!auth_is_super_admin()) {
        http_response_code(403);
        echo function_exists('__') ? __('error.super_admin_required') : 'Super admin access required.';
        exit;
    }
}
