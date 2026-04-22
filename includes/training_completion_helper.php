<?php

declare(strict_types=1);

/**
 * Training completion audit log + optional sync to user_training_records for M-SCORE.
 */

function ot_log_training_completion(
    PDO $pdo,
    int $registrationId,
    ?int $adminId,
    ?string $participationFrom,
    ?string $participationTo,
    ?string $certificateFrom,
    ?string $certificateTo,
    ?string $note
): void {
    if (!mscore_table_exists($pdo, 'training_completion_logs')) {
        return;
    }
    $st = $pdo->prepare('
        INSERT INTO training_completion_logs (
          training_registration_id, admin_id, participation_from, participation_to,
          certificate_from, certificate_to, note
        ) VALUES (:rid, :aid, :pf, :pt, :cf, :ct, :n)
    ');
    $st->execute([
        'rid' => $registrationId,
        'aid' => $adminId,
        'pf' => $participationFrom,
        'pt' => $participationTo,
        'cf' => $certificateFrom,
        'ct' => $certificateTo,
        'n' => $note,
    ]);
}

/**
 * Creates or updates user_training_records when a registration is fully eligible for M-SCORE.
 * Returns true if a link was created or refreshed.
 */
function ot_sync_training_registration_to_mscore(PDO $pdo, int $registrationId): bool
{
    if (!mscore_table_exists($pdo, 'user_training_records') || !mscore_table_exists($pdo, 'training_registrations')) {
        return false;
    }

    $st = $pdo->prepare('
        SELECT r.*, p.title AS program_title, p.provider_name
        FROM training_registrations r
        INNER JOIN training_programs p ON p.id = r.training_program_id
        WHERE r.id = :id LIMIT 1
    ');
    $st->execute(['id' => $registrationId]);
    $reg = $st->fetch();
    if (!$reg) {
        return false;
    }

    $part = (string) ($reg['participation_status'] ?? '');
    $cert = (string) ($reg['certificate_status'] ?? '');
    $approved = (string) ($reg['status'] ?? '') === 'approved';

    if (!$approved || $part !== 'completed') {
        return false;
    }

    $verified = false;
    if ($cert === 'verified' || $cert === 'none') {
        $verified = true;
    }
    $pendingVerify = in_array($cert, ['issued', 'pending_verification'], true);

    $docId = $reg['certificate_document_id'] !== null && $reg['certificate_document_id'] !== ''
        ? (int) $reg['certificate_document_id']
        : null;

    $existingId = $reg['user_training_record_id'] !== null && $reg['user_training_record_id'] !== ''
        ? (int) $reg['user_training_record_id']
        : null;

    if ($existingId) {
        $vs = $verified ? 'verified' : ($pendingVerify ? 'pending' : 'rejected');
        $up = $pdo->prepare('
            UPDATE user_training_records SET
              completion_status = "completed",
              verified_status = :vs,
              certificate_document_id = :cid,
              completed_at = COALESCE(completed_at, NOW())
            WHERE id = :tid LIMIT 1
        ');
        $up->execute(['vs' => $vs, 'cid' => $docId, 'tid' => $existingId]);
        return true;
    }

    if (!$verified && !$pendingVerify) {
        return false;
    }

    $vsInsert = $verified ? 'verified' : 'pending';
    $ins = $pdo->prepare('
        INSERT INTO user_training_records (
          user_id, training_title, provider_name, completion_status, verified_status,
          certificate_document_id, completed_at
        ) VALUES (:uid, :title, :prov, "completed", :vs, :cid, NOW())
    ');
    try {
        $ins->execute([
            'uid' => (int) $reg['user_id'],
            'title' => (string) $reg['program_title'],
            'prov' => (string) $reg['provider_name'],
            'vs' => $vsInsert,
            'cid' => $docId,
        ]);
    } catch (Throwable $e) {
        try {
            $ins->execute([
                'uid' => (int) $reg['user_id'],
                'title' => (string) $reg['program_title'],
                'prov' => (string) $reg['provider_name'],
                'vs' => $vsInsert,
                'cid' => null,
            ]);
        } catch (Throwable $e2) {
            return false;
        }
    }
    $tid = (int) $pdo->lastInsertId();
    if ($tid <= 0) {
        return false;
    }
    $pdo->prepare('UPDATE training_registrations SET user_training_record_id = :tid WHERE id = :id LIMIT 1')
        ->execute(['tid' => $tid, 'id' => $registrationId]);
    return true;
}
