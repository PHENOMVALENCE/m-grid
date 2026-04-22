<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

auth_require_user();
$auth = auth_user();
$uid = (int) $auth['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('user/my_documents.php');
}
if (!csrf_verify($_POST['_csrf'] ?? null)) {
    flash_set('error', 'Invalid security token.');
    redirect('user/my_documents.php');
}

$pdo = db();
$mode = clean_string($_POST['mode'] ?? 'upload');
$title = clean_string($_POST['title'] ?? '');
$description = clean_string($_POST['description'] ?? '');
$typeId = (int) ($_POST['document_type_id'] ?? 0);
$parentId = (int) ($_POST['parent_document_id'] ?? 0);

if ($title === '' || $typeId <= 0) {
    flash_set('error', 'Document type and title are required.');
    redirect($mode === 'reupload' ? 'user/reupload_document.php?id=' . $parentId : 'user/upload_document.php');
}
if (!isset($_FILES['document_file'])) {
    flash_set('error', 'Please select a file.');
    redirect($mode === 'reupload' ? 'user/reupload_document.php?id=' . $parentId : 'user/upload_document.php');
}

$typeStmt = $pdo->prepare('SELECT id, slug FROM document_types WHERE id = :id AND is_active = 1 LIMIT 1');
$typeStmt->execute(['id' => $typeId]);
$type = $typeStmt->fetch();
if (!$type) {
    flash_set('error', 'Selected document type is invalid.');
    redirect('user/upload_document.php');
}

$versionNumber = 1;
if ($mode === 'reupload') {
    $parentDoc = mgrid_document_find_for_user($pdo, $parentId, $uid);
    if ($parentDoc === null) {
        flash_set('error', 'Parent document not found.');
        redirect('user/my_documents.php');
    }
    if (!mgrid_document_can_reupload((string) $parentDoc['status'])) {
        flash_set('error', 'This document cannot be re-uploaded.');
        redirect('user/my_documents.php');
    }
    $typeId = (int) $parentDoc['document_type_id'];
    $versionNumber = (int) $parentDoc['version_number'] + 1;
}

try {
    $upload = mgrid_document_store_upload($_FILES['document_file'], $uid, (string) $type['slug']);
    $ins = $pdo->prepare('
        INSERT INTO user_documents (
          user_id, m_id, document_type_id, title, description,
          original_file_name, stored_file_name, file_path, file_extension, file_size, mime_type,
          version_number, parent_document_id, status, uploaded_at, updated_at
        ) VALUES (
          :user_id, :m_id, :document_type_id, :title, :description,
          :original_file_name, :stored_file_name, :file_path, :file_extension, :file_size, :mime_type,
          :version_number, :parent_document_id, "pending", NOW(), NOW()
        )
    ');
    $ins->execute([
        'user_id' => $uid,
        'm_id' => (string) $auth['m_id'],
        'document_type_id' => $typeId,
        'title' => $title,
        'description' => $description !== '' ? $description : null,
        'original_file_name' => $upload['original_file_name'],
        'stored_file_name' => $upload['stored_file_name'],
        'file_path' => $upload['file_path'],
        'file_extension' => $upload['file_extension'],
        'file_size' => $upload['file_size'],
        'mime_type' => $upload['mime_type'],
        'version_number' => $versionNumber,
        'parent_document_id' => $mode === 'reupload' ? $parentId : null,
    ]);
    $newId = (int) $pdo->lastInsertId();
    mgrid_document_log_action($pdo, $newId, null, $mode === 'reupload' ? 'reuploaded' : 'uploaded', $description);

    flash_set('success', $mode === 'reupload' ? 'Document re-uploaded and sent for review.' : 'Document uploaded successfully and marked pending review.');
    redirect('user/my_documents.php');
} catch (Throwable $e) {
    flash_set('error', $e->getMessage() !== '' ? $e->getMessage() : 'Could not upload document.');
    redirect($mode === 'reupload' ? 'user/reupload_document.php?id=' . $parentId : 'user/upload_document.php');
}
