<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

auth_require_login();
$actor = auth_actor();
$docId = (int) ($_GET['id'] ?? 0);
$download = (int) ($_GET['download'] ?? 0) === 1;

if ($docId <= 0) {
    http_response_code(404);
    exit('Document not found.');
}

$pdo = db();
$stmt = $pdo->prepare('
    SELECT d.*
    FROM user_documents d
    WHERE d.id = :id
    LIMIT 1
');
$stmt->execute(['id' => $docId]);
$doc = $stmt->fetch();
if (!$doc) {
    http_response_code(404);
    exit('Document not found.');
}

$isAdmin = ((string) ($actor['account_type'] ?? '') === 'admin');
if (!$isAdmin && (int) $doc['user_id'] !== (int) ($actor['user_id'] ?? 0)) {
    http_response_code(403);
    exit('Access denied.');
}

$path = (string) $doc['file_path'];
if (!is_file($path)) {
    http_response_code(404);
    exit('Stored file missing.');
}

$mime = (string) $doc['mime_type'];
$filename = (string) $doc['original_file_name'];

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header('Content-Disposition: ' . ($download ? 'attachment' : 'inline') . '; filename="' . rawurlencode($filename) . '"');
readfile($path);
exit;
