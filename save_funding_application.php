<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

auth_require_user();
$auth = auth_user();
$uid = (int) $auth['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify($_POST['_csrf'] ?? null)) {
    flash_set('error', 'Invalid request.');
    redirect('user/apply_funding.php');
}

if (!canUserApplyForFunding($uid)) {
    flash_set('error', 'You are currently not eligible to apply for funding.');
    redirect('user/funding_overview.php');
}

$pdo = db();
$settings = mfund_settings_map($pdo);
$minAmount = (float) ($settings['min_funding_amount'] ?? 50000);
$maxAmount = (float) ($settings['max_funding_amount'] ?? 20000000);

$type = clean_string($_POST['application_type'] ?? 'loan');
$requested = (float) ($_POST['requested_amount'] ?? 0);
$purpose = clean_string($_POST['purpose'] ?? '');
$businessName = clean_string($_POST['business_name'] ?? '');
$businessSector = clean_string($_POST['business_sector'] ?? '');
$monthlyRevenue = $_POST['monthly_revenue_estimate'] !== '' ? (float) $_POST['monthly_revenue_estimate'] : null;
$repaymentCapacity = $_POST['repayment_capacity'] !== '' ? (float) $_POST['repayment_capacity'] : null;
$proposedMonths = $_POST['proposed_repayment_period'] !== '' ? (int) $_POST['proposed_repayment_period'] : null;
$businessDescription = clean_string($_POST['business_description'] ?? '');
$requestReason = clean_string($_POST['request_reason'] ?? '');
$supportingNotes = clean_string($_POST['supporting_notes'] ?? '');
$declaration = (int) ($_POST['declaration'] ?? 0) === 1;

if (!in_array($type, ['loan', 'grant', 'support'], true) || $requested < $minAmount || $requested > $maxAmount || $purpose === '' || $businessName === '' || $businessSector === '' || $requestReason === '' || !$declaration) {
    flash_set('error', 'Please complete all required fields with valid values.');
    redirect('user/apply_funding.php');
}

$supportPath = null;
$supportName = null;
if (isset($_FILES['supporting_document']) && (int) ($_FILES['supporting_document']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    try {
        $uploaded = mfund_store_supporting_file($_FILES['supporting_document'], $uid);
        $supportPath = $uploaded['path'];
        $supportName = $uploaded['original_name'];
    } catch (Throwable $e) {
        flash_set('error', $e->getMessage());
        redirect('user/apply_funding.php');
    }
}

try {
    $reference = mfund_generate_reference($pdo);
    $stmt = $pdo->prepare('
      INSERT INTO funding_applications (
        user_id, m_id, reference_number, application_type, requested_amount, purpose, business_name, business_sector,
        monthly_revenue_estimate, repayment_capacity, proposed_repayment_period, business_description, request_reason,
        supporting_notes, supporting_document_path, supporting_document_name, status, submitted_at, updated_at
      ) VALUES (
        :user_id, :m_id, :reference_number, :application_type, :requested_amount, :purpose, :business_name, :business_sector,
        :monthly_revenue_estimate, :repayment_capacity, :proposed_repayment_period, :business_description, :request_reason,
        :supporting_notes, :supporting_document_path, :supporting_document_name, "submitted", NOW(), NOW()
      )
    ');
    $stmt->execute([
        'user_id' => $uid,
        'm_id' => (string) $auth['m_id'],
        'reference_number' => $reference,
        'application_type' => $type,
        'requested_amount' => $requested,
        'purpose' => $purpose,
        'business_name' => $businessName,
        'business_sector' => $businessSector,
        'monthly_revenue_estimate' => $monthlyRevenue,
        'repayment_capacity' => $repaymentCapacity,
        'proposed_repayment_period' => $proposedMonths,
        'business_description' => $businessDescription !== '' ? $businessDescription : null,
        'request_reason' => $requestReason,
        'supporting_notes' => $supportingNotes !== '' ? $supportingNotes : null,
        'supporting_document_path' => $supportPath,
        'supporting_document_name' => $supportName,
    ]);
    $appId = (int) $pdo->lastInsertId();
    mfund_log_status($pdo, $appId, null, $uid, null, 'submitted', 'Application submitted by user.');

    createNotification(
        $uid,
        'M-FUND application received',
        'We received your application (' . $reference . '). You can track status under My applications.',
        'success',
        'mfund',
        $appId,
        url('user/funding_application_detail.php?id=' . $appId)
    );

    flash_set('success', 'Funding application submitted successfully. Reference: ' . $reference);
    redirect('user/my_funding_applications.php');
} catch (Throwable $e) {
    flash_set('error', 'Could not submit application: ' . $e->getMessage());
    redirect('user/apply_funding.php');
}
