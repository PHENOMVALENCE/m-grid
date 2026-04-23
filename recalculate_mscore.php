<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

auth_require_login();
$actor = auth_actor();

$userId = (int) ($_GET['user_id'] ?? 0);
$from = clean_string($_GET['from'] ?? '');
$isAdmin = ((string) ($actor['account_type'] ?? '') === 'admin');

if (!$isAdmin) {
    $userId = (int) ($actor['user_id'] ?? 0);
}
if ($userId <= 0) {
    flash_set('error', __('mscore.recalc.invalid'));
    redirect($isAdmin ? 'admin/admin_mscores.php' : 'user/my_mscore.php');
}

try {
    calculateUserMScore($userId);
    flash_set('success', __('mscore.user.ok'));
} catch (Throwable $e) {
    flash_set('error', __('mscore.recalc.fail', ['msg' => $e->getMessage()]));
}

if ($isAdmin && $from === 'admin_detail') {
    redirect('admin/admin_mscore_detail.php?user_id=' . $userId);
}
if ($isAdmin) {
    redirect('admin/admin_mscores.php');
}
redirect('user/my_mscore.php');
