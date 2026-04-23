<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$auth = auth_user();
$uid = (int) $auth['user_id'];
$docId = (int) ($_GET['id'] ?? 0);

$doc = $docId > 0 ? mgrid_document_find_for_user($pdo, $docId, $uid) : null;
if ($doc === null) {
    flash_set('error', __('doc.not_found'));
    redirect('user/my_documents.php');
}
if (!mgrid_document_can_reupload((string) $doc['status'])) {
    flash_set('error', __('doc.reupload.blocked'));
    redirect('user/my_documents.php');
}

$typeStmt = $pdo->prepare('SELECT id, name FROM document_types WHERE id = :id LIMIT 1');
$typeStmt->execute(['id' => (int) $doc['document_type_id']]);
$type = $typeStmt->fetch();

$mgrid_page_title = mgrid_title('title.reupload_doc');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-card">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title"><i class="ti ti-refresh-dot"></i> Re-upload Document</h1>
    <a href="<?= e(url('user/my_documents.php')) ?>" class="btn-mgrid btn-mgrid-ghost">Back to My Documents</a>
  </div>
  <div class="mgrid-card-body">
    <div class="mgrid-alert mgrid-alert-warning">
      Current status: <strong><?= e(mgrid_document_status_label((string) $doc['status'])) ?></strong>.
      <?= !empty($doc['admin_remark']) ? ' Admin remark: ' . e((string) $doc['admin_remark']) : '' ?>
    </div>

    <form method="post" action="<?= e(url('save_document.php')) ?>" enctype="multipart/form-data" class="row g-3" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="mode" value="reupload">
      <input type="hidden" name="parent_document_id" value="<?= (int) $doc['id'] ?>">
      <input type="hidden" name="document_type_id" value="<?= (int) $doc['document_type_id'] ?>">
      <div class="col-md-6">
        <label class="mgrid-form-label">Document type</label>
        <input class="mgrid-form-control" value="<?= e((string) ($type['name'] ?? 'Unknown')) ?>" disabled>
      </div>
      <div class="col-md-6">
        <label class="mgrid-form-label" for="title">Document title</label>
        <input class="mgrid-form-control" type="text" id="title" name="title" maxlength="180" required value="<?= e((string) $doc['title']) ?>">
      </div>
      <div class="col-12">
        <label class="mgrid-form-label" for="description">Description (optional)</label>
        <textarea class="mgrid-form-control" id="description" name="description" rows="3" maxlength="1200"><?= e((string) ($doc['description'] ?? '')) ?></textarea>
      </div>
      <div class="col-12">
        <label class="mgrid-form-label" for="document_file">Select replacement file (PDF/JPG/JPEG/PNG, max 8MB)</label>
        <input class="mgrid-form-control" type="file" id="document_file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" required>
      </div>
      <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn-mgrid btn-mgrid-primary"><i class="ti ti-upload"></i> Upload New Version</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
