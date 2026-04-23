<?php

declare(strict_types=1);

/**
 * Member-only bootstrap: session + database + role = user. No HTML output.
 */

require_once __DIR__ . '/../../includes/init.php';

auth_require_login();
$u = auth_user();
if ($u === null) {
    http_response_code(403);
    echo function_exists('__') ? __('member.error.auth_required') : 'This area is for registered members.';
    exit;
}

$statusStmt = db()->prepare('SELECT status FROM users WHERE id = :id LIMIT 1');
$statusStmt->execute(['id' => (int) $u['user_id']]);
$statusRow = $statusStmt->fetch();
$accountStatus = (string) ($statusRow['status'] ?? 'pending');

$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$verificationPage = 'verify-id.php';
$allowedPendingPages = [
    'verify-id.php',
    'my_documents.php',
    'upload_document.php',
    'reupload_document.php',
    'save_document.php',
    'document_view.php',
];
if ($accountStatus !== 'active' && !in_array($currentScript, $allowedPendingPages, true)) {
    flash_set('error', __('member.flash.verify_pending'));
    redirect('user/verify-id.php');
}
