<?php

declare(strict_types=1);

/**
 * Persists UI language (EN/SW) for logged-in members or admins.
 * POST JSON: { "lang": "en"|"sw", "_csrf": "..." }
 */

require_once __DIR__ . '/includes/init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = auth_user();
$admin = auth_admin();
if ($user === null && $admin === null) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Not signed in']);
    exit;
}

$raw = (string) file_get_contents('php://input');
$body = $raw !== '' ? json_decode($raw, true) : null;
if (!is_array($body)) {
    $body = $_POST;
}

$lang = (string) ($body['lang'] ?? '');
$token = (string) ($body['_csrf'] ?? '');

if (!csrf_verify($token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid security token']);
    exit;
}

if (!in_array($lang, ['en', 'sw'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid language']);
    exit;
}

$_SESSION['preferred_language'] = $lang;

if ($user !== null) {
    $uid = (int) ($user['user_id'] ?? 0);
    if ($uid > 0) {
        $st = db()->prepare('UPDATE users SET preferred_language = :lang WHERE id = :id LIMIT 1');
        $st->execute(['lang' => $lang, 'id' => $uid]);
    }
}

echo json_encode(['ok' => true, 'lang' => $lang]);
