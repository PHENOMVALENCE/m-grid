<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

auth_require_admin();
$admin = auth_admin();
$adminId = (int) ($admin['admin_id'] ?? 0);

$pdo = db();
$users = $pdo->query('SELECT id FROM users ORDER BY id ASC')->fetchAll() ?: [];
$ok = 0;
$failed = 0;
foreach ($users as $u) {
    try {
        calculateUserMScore((int) $u['id']);
        $ok++;
    } catch (Throwable) {
        $failed++;
    }
}

admin_log($pdo, $adminId, null, 'mscore_bulk_recalculate', 'Bulk recalculated M-SCORE for users. Success: ' . $ok . ', failed: ' . $failed);
flash_set('success', 'Bulk recalculation finished. Success: ' . $ok . ', failed: ' . $failed . '.');
redirect('admin/admin_mscores.php');
