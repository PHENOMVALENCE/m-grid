<?php

declare(strict_types=1);

/**
 * Cross-cutting helpers: escaping, URLs, redirects, CSRF, validation.
 */

require_once __DIR__ . '/config.php';

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build an absolute-from-site-root URL for links and redirects. */
function url(string $path): string
{
    $path = ltrim($path, '/');
    if (MGRID_URL === '') {
        return '/' . $path;
    }
    return MGRID_URL . '/' . $path;
}

/** Static asset URL (served from /assets relative to project root). */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . url($path));
    }
    exit;
}

/** Trim and normalise user-supplied strings. */
function clean_string(?string $s): string
{
    return trim((string) $s);
}

/** Normalise phone for comparison (strip spaces); extend later for E.164. */
function normalise_phone(string $phone): string
{
    return preg_replace('/\s+/', '', $phone) ?? $phone;
}

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    $t = csrf_token();
    return '<input type="hidden" name="_csrf" value="' . e($t) . '">';
}

function csrf_verify(?string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    if ($token === null || $token === '') {
        return false;
    }
    return hash_equals((string) ($_SESSION['_csrf_token'] ?? ''), $token);
}

/** Flash message for next request (PRG-friendly). */
function flash_set(string $key, string $message): void
{
    $_SESSION['_flash'][$key] = $message;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $m = (string) $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $m;
}

/** Optional audit entry for administrator actions. */
function admin_log(PDO $pdo, int $adminId, ?int $targetUserId, string $actionType, string $description): void
{
    $st = $pdo->prepare('
        INSERT INTO admin_logs (admin_id, target_user_id, action_type, description)
        VALUES (:aid, :tid, :atype, :descr)
    ');
    $st->execute([
        'aid' => $adminId,
        'tid' => $targetUserId,
        'atype' => $actionType,
        'descr' => $description,
    ]);
}
