<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

auth_require_login();
$actor = auth_actor();
$appId = (int) ($_GET['application_id'] ?? 0);

if ($appId <= 0) {
    http_response_code(404);
    exit('Document not found.');
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, user_id, supporting_document_path, supporting_document_name FROM funding_applications WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $appId]);
$app = $stmt->fetch();
if (!$app || empty($app['supporting_document_path'])) {
    http_response_code(404);
    exit('Document not found.');
}

$isAdmin = ((string) ($actor['account_type'] ?? '') === 'admin');
if (!$isAdmin && (int) $app['user_id'] !== (int) ($actor['user_id'] ?? 0)) {
    http_response_code(403);
    exit('Access denied.');
}

$path = (string) $app['supporting_document_path'];
if (!is_file($path)) {
    http_response_code(404);
    exit('File missing.');
}

$mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
    http_response_code(403);
    exit('File type not allowed.');
}

$download = (int) ($_GET['download'] ?? 0) === 1;
$name = (string) ($app['supporting_document_name'] ?? ('supporting_' . $appId));
header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . rawurlencode($name) . '"');
readfile($path);
exit;
