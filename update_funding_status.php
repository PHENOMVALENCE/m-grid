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
$newStatus = clean_string($_POST['new_status'] ?? '');
$remarks = clean_string($_POST['remarks'] ?? '');
$approvedAmount = $_POST['approved_amount'] !== '' ? (float) $_POST['approved_amount'] : null;
$interestRate = $_POST['interest_rate'] !== '' ? (float) $_POST['interest_rate'] : null;
$repayMonths = $_POST['repayment_duration_months'] !== '' ? (int) $_POST['repayment_duration_months'] : null;
$repayStart = clean_string($_POST['repayment_start_date'] ?? '');
$partner = clean_string($_POST['funding_partner_name'] ?? '');

if ($appId <= 0 || !in_array($newStatus, ['under_review','more_info_requested','approved','rejected','disbursed','active_repayment','completed','defaulted','cancelled'], true) || $remarks === '') {
    flash_set('error', 'Missing required review inputs.');
    redirect('admin/admin_funding_applications.php');
}

$pdo = db();
$appStmt = $pdo->prepare('SELECT * FROM funding_applications WHERE id = :id LIMIT 1');
$appStmt->execute(['id' => $appId]);
$app = $appStmt->fetch();
if (!$app) {
    flash_set('error', 'Funding application not found.');
    redirect('admin/admin_funding_applications.php');
}
$oldStatus = (string) $app['status'];

$pdo->beginTransaction();
try {
    $up = $pdo->prepare('UPDATE funding_applications SET status = :status, current_admin_remark = :remark, updated_at = NOW() WHERE id = :id');
    $up->execute(['status' => $newStatus, 'remark' => $remarks, 'id' => $appId]);

    $review = $pdo->prepare('
      INSERT INTO funding_reviews (
        application_id, admin_id, action, previous_status, new_status, remarks,
        approved_amount, interest_rate, repayment_duration_months, repayment_start_date, funding_partner_name, action_at
      ) VALUES (
        :application_id, :admin_id, :action, :previous_status, :new_status, :remarks,
        :approved_amount, :interest_rate, :repayment_duration_months, :repayment_start_date, :funding_partner_name, NOW()
      )
    ');
    $review->execute([
        'application_id' => $appId,
        'admin_id' => $adminId,
        'action' => 'status_update',
        'previous_status' => $oldStatus,
        'new_status' => $newStatus,
        'remarks' => $remarks,
        'approved_amount' => $approvedAmount,
        'interest_rate' => $interestRate,
        'repayment_duration_months' => $repayMonths,
        'repayment_start_date' => $repayStart !== '' ? $repayStart : null,
        'funding_partner_name' => $partner !== '' ? $partner : null,
    ]);

    if ($newStatus === 'active_repayment' && $approvedAmount !== null && $approvedAmount > 0 && $repayMonths !== null && $repayMonths > 0 && $repayStart !== '') {
        generateRepaymentSchedule($pdo, $appId, $approvedAmount, $repayMonths, $repayStart);
    }

    mfund_log_status($pdo, $appId, $adminId, null, $oldStatus, $newStatus, $remarks);
    admin_log($pdo, $adminId, (int) $app['user_id'], 'mfund_status_update', 'Funding ' . $app['reference_number'] . ' moved from ' . $oldStatus . ' to ' . $newStatus);

    $pdo->commit();
    flash_set('success', 'Funding status updated successfully.');

    $uidF = (int) $app['user_id'];
    $refF = (string) $app['reference_number'];
    $typeF = match ($newStatus) {
        'approved', 'disbursed', 'completed' => 'success',
        'rejected', 'defaulted' => 'alert',
        'more_info_requested' => 'warning',
        default => 'info',
    };
    createNotification(
        $uidF,
        'M-FUND: ' . $refF,
        'Your funding application status is now ' . $newStatus . '. Review the latest admin note in your application.',
        $typeF,
        'mfund',
        $appId,
        url('user/funding_application_detail.php?id=' . $appId)
    );
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('error', 'Could not update status: ' . $e->getMessage());
}

redirect('admin/admin_funding_review.php?id=' . $appId);
