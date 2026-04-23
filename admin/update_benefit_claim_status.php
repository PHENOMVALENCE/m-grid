<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/admin_benefit_claims.php');
}

$token = $_POST['_csrf'] ?? null;
if (!csrf_verify(is_string($token) ? $token : null)) {
    flash_set('error', __('settings.error.token'));
    redirect('admin/admin_benefit_claims.php');
}

$pdo = db();
$admin = auth_admin();
if ($admin === null) {
    redirect('admin/admin_benefit_claims.php');
}
$adminId = (int) $admin['admin_id'];

$claimId = (int) ($_POST['claim_id'] ?? 0);
$newStatus = clean_string($_POST['new_status'] ?? '');
$remarks = clean_string($_POST['admin_remarks'] ?? '');

if (!mbenefits_module_ready($pdo) || $claimId <= 0 || !in_array($newStatus, ['pending', 'approved', 'rejected', 'redeemed', 'cancelled'], true)) {
    flash_set('error', __('error.invalid_request'));
    redirect('admin/admin_benefit_claims.php');
}

$st = $pdo->prepare('
    SELECT c.id, c.status, c.user_id, c.claim_reference, o.title AS offer_title
    FROM benefit_claims c
    INNER JOIN benefit_offers o ON o.id = c.benefit_offer_id
    WHERE c.id = :id LIMIT 1
');
$st->execute(['id' => $claimId]);
$row = $st->fetch();
if (!$row) {
    flash_set('error', __('claim.admin.not_found'));
    redirect('admin/admin_benefit_claims.php');
}

$old = (string) $row['status'];

try {
    $pdo->beginTransaction();
    $up = $pdo->prepare('UPDATE benefit_claims SET status = :st, admin_remarks = :rm WHERE id = :id LIMIT 1');
    $up->execute([
        'st' => $newStatus,
        'rm' => $remarks !== '' ? $remarks : null,
        'id' => $claimId,
    ]);
    mbenefits_log_claim_change($pdo, $claimId, $adminId, null, $old, $newStatus, $remarks !== '' ? $remarks : 'Status updated.');
    $pdo->commit();
    flash_set('success', __('claim.admin.updated'));

    $uidB = (int) $row['user_id'];
    $typeB = match ($newStatus) {
        'approved', 'redeemed' => 'success',
        'rejected', 'cancelled' => 'warning',
        default => 'info',
    };
    $msgB = 'Benefit claim ' . (string) $row['claim_reference'] . ' for "' . (string) $row['offer_title'] . '" is now ' . $newStatus . '.';
    if ($remarks !== '') {
        $msgB .= ' Admin note: ' . $remarks;
    }
    createNotification(
        $uidB,
        'M-Benefits claim update',
        $msgB,
        $typeB,
        'mbenefits',
        $claimId,
        url('user/benefit_claim_detail.php?id=' . $claimId)
    );
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('error', __('claim.admin.update_failed'));
}

redirect('admin/admin_benefit_claims.php');
