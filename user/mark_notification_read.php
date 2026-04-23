<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('user/notifications.php');
}

if (!csrf_verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
    flash_set('error', __('session.invalid'));
    redirect('user/notifications.php');
}

$uid = (int) auth_user()['user_id'];
$nid = (int) ($_POST['notification_id'] ?? 0);
$redir = clean_string($_POST['redirect'] ?? '');
if ($nid > 0) {
    markNotificationAsRead($nid, $uid);
}

if ($redir !== '' && str_starts_with($redir, '/') && !str_starts_with($redir, '//')) {
    header('Location: ' . $redir);
    exit;
}
redirect('user/notifications.php');
