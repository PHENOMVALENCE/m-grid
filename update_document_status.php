<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

auth_require_admin();
$admin = auth_admin();
$adminId = (int) $admin['admin_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/admin_documents.php');
}
if (!csrf_verify($_POST['_csrf'] ?? null)) {
    flash_set('error', 'Invalid security token.');
    redirect('admin/admin_documents.php');
}

$docId = (int) ($_POST['document_id'] ?? 0);
$action = clean_string($_POST['action'] ?? '');
$remark = clean_string($_POST['remark'] ?? '');

if ($docId <= 0 || !in_array($action, ['verified', 'rejected', 'resubmission_requested'], true)) {
    flash_set('error', 'Invalid review action.');
    redirect('admin/admin_documents.php');
}

$pdo = db();
$doc = mgrid_document_find_for_admin($pdo, $docId);
if ($doc === null) {
    flash_set('error', 'Document not found.');
    redirect('admin/admin_documents.php');
}

if ($action !== 'verified' && $remark === '') {
    flash_set('error', 'Please provide remark for reject/resubmission actions.');
    redirect('admin/review_document.php?id=' . $docId);
}

$up = $pdo->prepare('
    UPDATE user_documents
    SET status = :status,
        admin_remark = :remark,
        reviewed_by = :reviewed_by,
        reviewed_at = NOW(),
        updated_at = NOW()
    WHERE id = :id
    LIMIT 1
');
$up->execute([
    'status' => $action,
    'remark' => $remark !== '' ? $remark : null,
    'reviewed_by' => $adminId,
    'id' => $docId,
]);

mgrid_document_log_action($pdo, $docId, $adminId, $action, $remark);
admin_log($pdo, $adminId, (int) $doc['user_id'], 'document_' . $action, 'Document #' . $docId . ' marked as ' . $action);

$uidDoc = (int) $doc['user_id'];
$typeMap = ['verified' => 'success', 'rejected' => 'alert', 'resubmission_requested' => 'warning'];
$typeN = $typeMap[$action] ?? 'info';
$msgDoc = 'Your document "' . (string) ($doc['title'] ?? 'upload') . '" was marked as ' . str_replace('_', ' ', $action) . '.';
if ($remark !== '') {
    $msgDoc .= ' Note from reviewer: ' . $remark;
}
createNotification(
    $uidDoc,
    'Document update',
    $msgDoc,
    $typeN,
    'documents',
    $docId,
    url('user/my_documents.php')
);

flash_set('success', 'Document status updated successfully.');
redirect('admin/review_document.php?id=' . $docId);
