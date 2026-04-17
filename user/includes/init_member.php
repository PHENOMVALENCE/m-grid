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
    echo 'This area is for registered members.';
    exit;
}

$statusStmt = db()->prepare('SELECT status FROM users WHERE id = :id LIMIT 1');
$statusStmt->execute(['id' => (int) $u['user_id']]);
$statusRow = $statusStmt->fetch();
$accountStatus = (string) ($statusRow['status'] ?? 'pending');

$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$verificationPage = 'verify-id.php';
if ($accountStatus !== 'active' && $currentScript !== $verificationPage) {
    flash_set('error', 'Your account is pending verification. Upload your National ID to continue.');
    redirect('user/verify-id.php');
}
