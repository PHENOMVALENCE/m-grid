<?php

declare(strict_types=1);

function mfund_settings_map(PDO $pdo): array
{
    $rows = $pdo->query('SELECT setting_key, setting_value FROM mfund_settings')->fetchAll() ?: [];
    $map = [];
    foreach ($rows as $r) {
        $map[(string) $r['setting_key']] = (string) $r['setting_value'];
    }
    return $map;
}

function mfund_setting(PDO $pdo, string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = mfund_settings_map($pdo);
    }
    return $cache[$key] ?? $default;
}

function mfund_status_badge(string $status): string
{
    return match ($status) {
        'approved', 'disbursed', 'completed' => 'success',
        'under_review', 'active_repayment' => 'warning',
        'more_info_requested' => 'info',
        'rejected', 'defaulted', 'cancelled' => 'danger',
        default => 'secondary',
    };
}

function mfund_status_label(string $status): string
{
    return match ($status) {
        'under_review' => 'Under Review',
        'more_info_requested' => 'More Info Requested',
        'active_repayment' => 'Active Repayment',
        default => ucwords(str_replace('_', ' ', $status)),
    };
}

function mfund_generate_reference(PDO $pdo): string
{
    $year = (int) date('Y');
    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare('SELECT year, last_number FROM funding_reference_counters WHERE year = :y FOR UPDATE');
        $lock->execute(['y' => $year]);
        $row = $lock->fetch();
        if (!$row) {
            $ins = $pdo->prepare('INSERT INTO funding_reference_counters (year, last_number) VALUES (:y, 0)');
            $ins->execute(['y' => $year]);
            $last = 0;
        } else {
            $last = (int) $row['last_number'];
        }
        $next = $last + 1;
        $up = $pdo->prepare('UPDATE funding_reference_counters SET last_number = :n WHERE year = :y');
        $up->execute(['n' => $next, 'y' => $year]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return sprintf('MF-%d-%04d', $year, $next);
}

function mfund_storage_root(): string
{
    $root = rtrim(MGRID_STORAGE_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'funding';
    if (!is_dir($root)) {
        mkdir($root, 0770, true);
    }
    return $root;
}

/**
 * @param array<string,mixed> $file
 * @return array{path:string,original_name:string}
 */
function mfund_store_supporting_file(array $file, int $userId): array
{
    if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Supporting document upload failed.');
    }
    if (!isset($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
        throw new RuntimeException('Invalid supporting file upload source.');
    }
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > MGRID_FUNDING_MAX_BYTES) {
        throw new RuntimeException('Supporting file must be 1 byte to 8MB.');
    }
    $original = (string) ($file['name'] ?? 'document');
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allow = ['pdf', 'jpg', 'jpeg', 'png'];
    if (!in_array($ext, $allow, true)) {
        throw new RuntimeException('Only PDF/JPG/JPEG/PNG supporting files are allowed.');
    }
    $mime = (string) (new finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
    $validMime = in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true);
    if (!$validMime) {
        throw new RuntimeException('Supporting file MIME type is invalid.');
    }

    $dir = mfund_storage_root() . DIRECTORY_SEPARATOR . 'user_' . $userId;
    if (!is_dir($dir)) {
        mkdir($dir, 0770, true);
    }
    $name = date('Ymd_His') . '_' . bin2hex(random_bytes(10)) . '.' . $ext;
    $path = $dir . DIRECTORY_SEPARATOR . $name;
    if (!move_uploaded_file((string) $file['tmp_name'], $path)) {
        throw new RuntimeException('Could not save supporting file.');
    }
    return ['path' => $path, 'original_name' => $original];
}

function mfund_log_status(PDO $pdo, int $applicationId, ?int $adminId, ?int $userId, ?string $oldStatus, string $newStatus, ?string $note): void
{
    $stmt = $pdo->prepare('
        INSERT INTO funding_status_logs (application_id, admin_id, user_id, old_status, new_status, note, created_at)
        VALUES (:application_id, :admin_id, :user_id, :old_status, :new_status, :note, NOW())
    ');
    $stmt->execute([
        'application_id' => $applicationId,
        'admin_id' => $adminId,
        'user_id' => $userId,
        'old_status' => $oldStatus,
        'new_status' => $newStatus,
        'note' => $note,
    ]);
}

function mfund_open_statuses(): array
{
    return ['submitted', 'under_review', 'more_info_requested', 'approved', 'disbursed', 'active_repayment'];
}
