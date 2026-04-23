<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$types = mgrid_document_types($pdo);

$mgrid_page_title = mgrid_title('title.upload_doc');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-card">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title"><i class="ti ti-upload"></i> Upload Document</h1>
    <a href="<?= e(url('user/my_documents.php')) ?>" class="btn-mgrid btn-mgrid-ghost">Back to My Documents</a>
  </div>
  <div class="mgrid-card-body">
    <?php if ($types === []): ?>
      <div class="mgrid-alert mgrid-alert-warning">No active document types configured. Contact admin.</div>
    <?php else: ?>
      <form method="post" action="<?= e(url('save_document.php')) ?>" enctype="multipart/form-data" class="row g-3" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="mode" value="upload">
        <div class="col-md-6">
          <label class="mgrid-form-label" for="document_type_id">Document type</label>
          <select class="mgrid-form-control" id="document_type_id" name="document_type_id" required>
            <option value="">Choose...</option>
            <?php foreach ($types as $type): ?>
              <option value="<?= (int) $type['id'] ?>"><?= e((string) $type['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="mgrid-form-label" for="title">Document title</label>
          <input class="mgrid-form-control" type="text" id="title" name="title" maxlength="180" required placeholder="e.g. NIDA Front Copy">
        </div>
        <div class="col-12">
          <label class="mgrid-form-label" for="description">Description (optional)</label>
          <textarea class="mgrid-form-control" id="description" name="description" rows="3" maxlength="1200" placeholder="Any context for admin reviewer"></textarea>
        </div>
        <div class="col-12">
          <label class="mgrid-form-label" for="document_file">Select file (PDF/JPG/JPEG/PNG, max 8MB)</label>
          <input class="mgrid-form-control" type="file" id="document_file" name="document_file" accept=".pdf,.jpg,.jpeg,.png" required>
        </div>
        <div class="col-12 d-flex justify-content-end">
          <button type="submit" class="btn-mgrid btn-mgrid-primary"><i class="ti ti-device-floppy"></i> Save Document</button>
        </div>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
