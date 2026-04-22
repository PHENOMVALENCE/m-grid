<?php

declare(strict_types=1);

/**
 * Document module helpers: type loading, upload validation, storage, badges, logs.
 */

function mgrid_document_allowed_map(): array
{
    return [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];
}

function mgrid_document_status_badge(string $status): string
{
    return match ($status) {
        'verified' => 'success',
        'rejected' => 'danger',
        'resubmission_requested' => 'info',
        default => 'warning',
    };
}

function mgrid_document_status_label(string $status): string
{
    return match ($status) {
        'verified' => 'Verified',
        'rejected' => 'Rejected',
        'resubmission_requested' => 'Resubmission Requested',
        default => 'Pending',
    };
}

function mgrid_document_can_reupload(string $status): bool
{
    return in_array($status, ['rejected', 'resubmission_requested'], true);
}

function mgrid_document_types(PDO $pdo): array
{
    $stmt = $pdo->query('SELECT id, name, slug FROM document_types WHERE is_active = 1 ORDER BY name ASC');
    return $stmt->fetchAll() ?: [];
}

function mgrid_document_storage_root(): string
{
    $root = rtrim(MGRID_STORAGE_ROOT, '/\\') . DIRECTORY_SEPARATOR . 'documents';
    if (!is_dir($root)) {
        mkdir($root, 0770, true);
    }
    return $root;
}

function mgrid_document_user_dir(int $userId, string $docTypeSlug): string
{
    $safeSlug = preg_replace('/[^a-z0-9_\\-]+/i', '_', strtolower($docTypeSlug)) ?: 'other';
    $dir = mgrid_document_storage_root() . DIRECTORY_SEPARATOR . 'user_' . $userId . DIRECTORY_SEPARATOR . $safeSlug;
    if (!is_dir($dir)) {
        mkdir($dir, 0770, true);
    }
    return $dir;
}

/**
 * @param array<string,mixed> $file $_FILES[...] item
 * @return array{stored_file_name:string,file_path:string,file_extension:string,file_size:int,mime_type:string,original_file_name:string}
 */
function mgrid_document_store_upload(array $file, int $userId, string $docTypeSlug): array
{
    if (!isset($file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed. Please try again.');
    }
    if (!isset($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
        throw new RuntimeException('Invalid upload source.');
    }

    $original = (string) ($file['name'] ?? 'file');
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0) {
        throw new RuntimeException('Uploaded file is empty.');
    }
    if ($size > MGRID_DOCUMENT_MAX_BYTES) {
        throw new RuntimeException('File exceeds maximum size of 8MB.');
    }

    $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allow = mgrid_document_allowed_map();
    if (!isset($allow[$extension])) {
        throw new RuntimeException('Only PDF, JPG, JPEG and PNG files are allowed.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file((string) $file['tmp_name']);
    if (!in_array($mime, $allow[$extension], true)) {
        throw new RuntimeException('File content does not match the selected extension.');
    }

    $storedName = date('Ymd_His') . '_' . bin2hex(random_bytes(12)) . '.' . $extension;
    $dir = mgrid_document_user_dir($userId, $docTypeSlug);
    $fullPath = $dir . DIRECTORY_SEPARATOR . $storedName;
    if (!move_uploaded_file((string) $file['tmp_name'], $fullPath)) {
        throw new RuntimeException('Could not save uploaded file.');
    }

    return [
        'stored_file_name' => $storedName,
        'file_path' => $fullPath,
        'file_extension' => $extension,
        'file_size' => $size,
        'mime_type' => $mime,
        'original_file_name' => $original,
    ];
}

function mgrid_document_log_action(PDO $pdo, int $documentId, ?int $adminId, string $action, ?string $remark): void
{
    $stmt = $pdo->prepare('
        INSERT INTO document_verification_logs (document_id, admin_id, action, remark, action_at)
        VALUES (:document_id, :admin_id, :action, :remark, NOW())
    ');
    $stmt->execute([
        'document_id' => $documentId,
        'admin_id' => $adminId,
        'action' => $action,
        'remark' => $remark !== null && $remark !== '' ? $remark : null,
    ]);
}

/**
 * @return array<string,mixed>|null
 */
function mgrid_document_find_for_user(PDO $pdo, int $documentId, int $userId): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM user_documents WHERE id = :id AND user_id = :user_id LIMIT 1');
    $stmt->execute(['id' => $documentId, 'user_id' => $userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * @return array<string,mixed>|null
 */
function mgrid_document_find_for_admin(PDO $pdo, int $documentId): ?array
{
    $stmt = $pdo->prepare('
        SELECT d.*, u.full_name, u.m_id, u.email, u.phone, dt.name AS type_name, dt.slug AS type_slug
        FROM user_documents d
        INNER JOIN users u ON u.id = d.user_id
        INNER JOIN document_types dt ON dt.id = d.document_type_id
        WHERE d.id = :id
        LIMIT 1
    ');
    $stmt->execute(['id' => $documentId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * @return array<int,array<string,mixed>>
 */
function mgrid_document_versions(PDO $pdo, int $documentId): array
{
    $seen = [];
    $ids = [];
    $current = $documentId;
    while ($current > 0 && !isset($seen[$current])) {
        $seen[$current] = true;
        $stmt = $pdo->prepare('SELECT id, parent_document_id FROM user_documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $current]);
        $row = $stmt->fetch();
        if (!$row) {
            break;
        }
        $ids[] = (int) $row['id'];
        $current = (int) ($row['parent_document_id'] ?? 0);
    }

    if ($ids === []) {
        return [];
    }
    $in = implode(',', array_fill(0, count($ids), '?'));
    $query = '
        SELECT d.*, dt.name AS type_name
        FROM user_documents d
        INNER JOIN document_types dt ON dt.id = d.document_type_id
        WHERE d.id IN (' . $in . ')
        ORDER BY d.version_number DESC, d.uploaded_at DESC
    ';
    $stmtList = $pdo->prepare($query);
    $stmtList->execute($ids);
    return $stmtList->fetchAll() ?: [];
}
