<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

auth_require_admin();
$admin = auth_admin();
$adminId = (int) $admin['admin_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['_csrf'] ?? null)) {
    flash_set('error', __('error.invalid_request'));
    redirect('admin/admin_funding_applications.php');
}

$appId = (int) ($_POST['application_id'] ?? 0);
$amount = (float) ($_POST['disbursed_amount'] ?? 0);
$date = clean_string($_POST['disbursement_date'] ?? '');
$method = clean_string($_POST['disbursement_method'] ?? '');
$note = clean_string($_POST['reference_note'] ?? '');

if ($appId <= 0 || $amount <= 0 || $date === '' || $method === '') {
    flash_set('error', __('fund.disburse.bad_input'));
    redirect('admin/admin_funding_applications.php');
}

$pdo = db();
$appStmt = $pdo->prepare('SELECT id, user_id, reference_number, status FROM funding_applications WHERE id = :id LIMIT 1');
$appStmt->execute(['id' => $appId]);
$app = $appStmt->fetch();
if (!$app) {
    flash_set('error', __('fund.repay.app_not_found'));
    redirect('admin/admin_funding_applications.php');
}

$pdo->beginTransaction();
try {
    $ins = $pdo->prepare('
      INSERT INTO funding_disbursements (application_id, disbursed_amount, disbursement_date, disbursement_method, reference_note, recorded_by_admin_id, created_at)
      VALUES (:application_id, :disbursed_amount, :disbursement_date, :disbursement_method, :reference_note, :recorded_by_admin_id, NOW())
    ');
    $ins->execute([
        'application_id' => $appId,
        'disbursed_amount' => $amount,
        'disbursement_date' => $date,
        'disbursement_method' => $method,
        'reference_note' => $note !== '' ? $note : null,
        'recorded_by_admin_id' => $adminId,
    ]);

    $old = (string) $app['status'];
    $up = $pdo->prepare('UPDATE funding_applications SET status = "disbursed", updated_at = NOW() WHERE id = :id');
    $up->execute(['id' => $appId]);
    mfund_log_status($pdo, $appId, $adminId, null, $old, 'disbursed', 'Disbursement recorded.');
    admin_log($pdo, $adminId, (int) $app['user_id'], 'mfund_disbursement', 'Disbursement recorded for ' . $app['reference_number']);

    $pdo->commit();
    flash_set('success', __('fund.disburse.recorded'));
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('error', __('fund.disburse.fail', ['msg' => $e->getMessage()]));
}

redirect('admin/admin_funding_review.php?id=' . $appId);
