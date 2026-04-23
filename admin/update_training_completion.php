<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/admin_training_registrations.php');
}

$token = $_POST['_csrf'] ?? null;
if (!csrf_verify(is_string($token) ? $token : null)) {
    flash_set('error', __('settings.error.token'));
    redirect('admin/admin_training_registrations.php');
}

$pdo = db();
$admin = auth_admin();
$adminId = $admin !== null ? (int) $admin['admin_id'] : null;

$rid = (int) ($_POST['registration_id'] ?? 0);
$status = clean_string($_POST['status'] ?? '');
$part = clean_string($_POST['participation_status'] ?? '');
$cert = clean_string($_POST['certificate_status'] ?? '');
$notes = clean_string($_POST['admin_notes'] ?? '');
$docRaw = $_POST['certificate_document_id'] ?? '';
$docId = $docRaw !== '' && is_numeric($docRaw) ? (int) $docRaw : null;

$validReg = ['pending', 'approved', 'rejected', 'waitlisted', 'cancelled'];
$validPart = ['registered', 'attended', 'completed', 'no_show', 'excused'];
$validCert = ['none', 'issued', 'pending_verification', 'verified', 'rejected'];

if (!trainings_module_ready($pdo) || $rid <= 0
    || !in_array($status, $validReg, true)
    || !in_array($part, $validPart, true)
    || !in_array($cert, $validCert, true)) {
    flash_set('error', __('train.admin.bad_data'));
    redirect('admin/admin_training_registrations.php');
}

$st = $pdo->prepare('
    SELECT r.*, p.title AS program_title
    FROM training_registrations r
    INNER JOIN training_programs p ON p.id = r.training_program_id
    WHERE r.id = :id LIMIT 1
');
$st->execute(['id' => $rid]);
$reg = $st->fetch();
if (!$reg) {
    flash_set('error', __('train.admin.reg_not_found'));
    redirect('admin/admin_training_registrations.php');
}

$oldPart = (string) $reg['participation_status'];
$oldCert = (string) $reg['certificate_status'];
$oldSt = (string) $reg['status'];

try {
    $pdo->beginTransaction();
    $pdo->prepare('
        UPDATE training_registrations SET
          status = :st,
          participation_status = :part,
          certificate_status = :cert,
          certificate_document_id = :doc,
          admin_notes = :notes
        WHERE id = :id LIMIT 1
    ')->execute([
        'st' => $status,
        'part' => $part,
        'cert' => $cert,
        'doc' => $docId,
        'notes' => $notes !== '' ? $notes : null,
        'id' => $rid,
    ]);

    if (mscore_table_exists($pdo, 'training_completion_logs')) {
        ot_log_training_completion(
            $pdo,
            $rid,
            $adminId,
            $oldPart !== $part ? $oldPart : null,
            $oldPart !== $part ? $part : null,
            $oldCert !== $cert ? $oldCert : null,
            $oldCert !== $cert ? $cert : null,
            trim($oldSt !== $status ? ('Status ' . $oldSt . '→' . $status . '. ') : '') . ($notes !== '' ? $notes : null)
        );
    }

    $pdo->commit();
    ot_sync_training_registration_to_mscore($pdo, $rid);
    flash_set('success', __('train.admin.updated'));

    if ($oldSt !== $status || $oldPart !== $part || $oldCert !== $cert) {
        $uidT = (int) $reg['user_id'];
        $titleT = (string) ($reg['program_title'] ?? 'Training');
        $msgT = 'Your registration for "' . $titleT . '" was updated. Status: ' . $status . ', participation: ' . $part . ', certificate: ' . $cert . '.';
        if ($notes !== '') {
            $msgT .= ' Note: ' . $notes;
        }
        createNotification(
            $uidT,
            'Training update',
            $msgT,
            $part === 'completed' ? 'success' : 'info',
            'trainings',
            $rid,
            url('user/my_trainings.php')
        );
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash_set('error', __('train.admin.update_failed'));
}

redirect('admin/admin_training_registrations.php');
