<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$pdo = db();
$docId = (int) ($_GET['id'] ?? 0);

$doc = $docId > 0 ? mgrid_document_find_for_admin($pdo, $docId) : null;
if ($doc === null) {
    flash_set('error', __('doc.not_found'));
    redirect('admin/admin_documents.php');
}

$versions = mgrid_document_versions($pdo, $docId);

$logStmt = $pdo->prepare('
    SELECT l.action, l.remark, l.action_at, a.full_name AS admin_name
    FROM document_verification_logs l
    LEFT JOIN admins a ON a.id = l.admin_id
    WHERE l.document_id = :id
    ORDER BY l.action_at DESC
');
$logStmt->execute(['id' => $docId]);
$logs = $logStmt->fetchAll() ?: [];

$mgrid_page_title = mgrid_title('title.admin_review_doc');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title"><i class="ti ti-file-search"></i> Review Document</h1>
    <a href="<?= e(url('admin/admin_documents.php')) ?>" class="btn-mgrid btn-mgrid-ghost">Back</a>
  </div>
  <div class="mgrid-card-body">
    <div class="mgrid-grid-2">
      <div>
        <h2 class="h6 mb-3">User Details</h2>
        <p class="mb-1"><strong>Name:</strong> <?= e((string) $doc['full_name']) ?></p>
        <p class="mb-1"><strong>M-ID:</strong> <span class="mgrid-mono-id"><?= e((string) $doc['m_id']) ?></span></p>
        <p class="mb-1"><strong>Email:</strong> <?= e((string) $doc['email']) ?></p>
        <p class="mb-0"><strong>Phone:</strong> <?= e((string) $doc['phone']) ?></p>
      </div>
      <div>
        <h2 class="h6 mb-3">Document Metadata</h2>
        <p class="mb-1"><strong>Type:</strong> <?= e((string) $doc['type_name']) ?></p>
        <p class="mb-1"><strong>Title:</strong> <?= e((string) $doc['title']) ?></p>
        <p class="mb-1"><strong>Uploaded:</strong> <?= e(substr((string) $doc['uploaded_at'], 0, 16)) ?></p>
        <p class="mb-0"><strong>Status:</strong> <span class="badge text-bg-<?= e(mgrid_document_status_badge((string) $doc['status'])) ?>"><?= e(mgrid_document_status_label((string) $doc['status'])) ?></span></p>
      </div>
    </div>

    <hr>
    <div class="d-flex gap-2 mb-3">
      <a href="<?= e(url('document_view.php?id=' . (int) $doc['id'])) ?>" target="_blank" class="btn-mgrid btn-mgrid-outline">Preview / Open</a>
      <a href="<?= e(url('document_view.php?id=' . (int) $doc['id'] . '&download=1')) ?>" class="btn-mgrid btn-mgrid-ghost">Download</a>
    </div>

    <form method="post" action="<?= e(url('update_document_status.php')) ?>" class="row g-3">
      <?= csrf_field() ?>
      <input type="hidden" name="document_id" value="<?= (int) $doc['id'] ?>">
      <div class="col-12">
        <label class="mgrid-form-label" for="remark">Admin remark</label>
        <textarea id="remark" name="remark" class="mgrid-form-control" rows="3" maxlength="2000" placeholder="Add clear reason or verification note..."><?= e((string) ($doc['admin_remark'] ?? '')) ?></textarea>
      </div>
      <div class="col-12 d-flex gap-2 flex-wrap">
        <button class="btn-mgrid btn-mgrid-primary" type="submit" name="action" value="verified">Verify</button>
        <button class="btn-mgrid btn-mgrid-outline" type="submit" name="action" value="resubmission_requested">Request Resubmission</button>
        <button class="btn btn-danger" type="submit" name="action" value="rejected">Reject</button>
      </div>
    </form>
  </div>
</div>

<div class="mgrid-grid-2">
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-history"></i> Version History</h2></div>
    <div class="mgrid-card-body">
      <?php if ($versions === []): ?>
        <p class="text-muted mb-0">No versions found.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($versions as $v): ?>
            <li class="mb-2 pb-2 border-bottom">
              <div class="d-flex justify-content-between">
                <strong>v<?= (int) $v['version_number'] ?> — <?= e((string) $v['title']) ?></strong>
                <span class="badge text-bg-<?= e(mgrid_document_status_badge((string) $v['status'])) ?>"><?= e(mgrid_document_status_label((string) $v['status'])) ?></span>
              </div>
              <div class="small text-muted"><?= e(substr((string) $v['uploaded_at'], 0, 16)) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-list-details"></i> Action Logs</h2></div>
    <div class="mgrid-card-body">
      <?php if ($logs === []): ?>
        <p class="text-muted mb-0">No action logs yet.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($logs as $log): ?>
            <li class="mb-2 pb-2 border-bottom">
              <div class="fw-semibold"><?= e((string) strtoupper((string) $log['action'])) ?></div>
              <div class="small text-muted"><?= e((string) ($log['admin_name'] ?? 'System')) ?> · <?= e(substr((string) $log['action_at'], 0, 16)) ?></div>
              <?php if (!empty($log['remark'])): ?>
                <div class="small mt-1"><?= e((string) $log['remark']) ?></div>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
