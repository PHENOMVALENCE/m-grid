<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

auth_require_admin();
$admin = auth_admin();
$adminId = (int) $admin['admin_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['_csrf'] ?? null)) {
    flash_set('error', 'Invalid request.');
    redirect('admin/admin_funding_applications.php');
}

$appId = (int) ($_POST['application_id'] ?? 0);
$scheduleId = $_POST['schedule_id'] !== '' ? (int) $_POST['schedule_id'] : null;
$amount = (float) ($_POST['amount_paid'] ?? 0);
$paymentDate = clean_string($_POST['payment_date'] ?? '');
$method = clean_string($_POST['payment_method'] ?? '');
$ref = clean_string($_POST['reference_note'] ?? '');
$remarks = clean_string($_POST['remarks'] ?? '');

if ($appId <= 0 || $amount <= 0 || $paymentDate === '' || $method === '') {
    flash_set('error', 'Invalid repayment input.');
    redirect('admin/manage_repayments.php?application_id=' . $appId);
}

$pdo = db();
$appStmt = $pdo->prepare('SELECT id, user_id, reference_number, status FROM funding_applications WHERE id = :id LIMIT 1');
$appStmt->execute(['id' => $appId]);
$app = $appStmt->fetch();
if (!$app) {
    flash_set('error', 'Application not found.');
    redirect('admin/admin_funding_applications.php');
}

$pdo->beginTransaction();
try {
    $ins = $pdo->prepare('
      INSERT INTO funding_repayment_logs (application_id, schedule_id, amount_paid, payment_date, payment_method, reference_note, recorded_by_admin_id, remarks, created_at)
      VALUES (:application_id, :schedule_id, :amount_paid, :payment_date, :payment_method, :reference_note, :recorded_by_admin_id, :remarks, NOW())
    ');
    $ins->execute([
        'application_id' => $appId,
        'schedule_id' => $scheduleId,
        'amount_paid' => $amount,
        'payment_date' => $paymentDate,
        'payment_method' => $method,
        'reference_note' => $ref !== '' ? $ref : null,
        'recorded_by_admin_id' => $adminId,
        'remarks' => $remarks !== '' ? $remarks : null,
    ]);

    if ($scheduleId !== null && $scheduleId > 0) {
        $up = $pdo->prepare('UPDATE funding_repayment_schedules SET paid_amount = paid_amount + :amt, updated_at = NOW() WHERE id = :id AND application_id = :app');
        $up->execute(['amt' => $amount, 'id' => $scheduleId, 'app' => $appId]);
        updateRepaymentScheduleStatus($pdo, $scheduleId);
    }

    $totals = fundingRepaymentTotals($pdo, $appId);
    $oldStatus = (string) $app['status'];
    if ($totals['balance'] <= 0.009) {
        $newStatus = 'completed';
    } else {
        $newStatus = 'active_repayment';
    }
    if ($newStatus !== $oldStatus) {
        $upApp = $pdo->prepare('UPDATE funding_applications SET status = :s, updated_at = NOW() WHERE id = :id');
        $upApp->execute(['s' => $newStatus, 'id' => $appId]);
        mfund_log_status($pdo, $appId, $adminId, null, $oldStatus, $newStatus, 'Repayment record updated.');
    }

    admin_log($pdo, $adminId, (int) $app['user_id'], 'mfund_repayment_recorded', 'Repayment recorded for ' . $app['reference_number']);
    $pdo->commit();
    flash_set('success', 'Repayment recorded successfully.');
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('error', 'Failed to record repayment: ' . $e->getMessage());
}

redirect('admin/manage_repayments.php?application_id=' . $appId);
