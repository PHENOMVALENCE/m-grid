<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$auth = auth_user();
$uid = (int) $auth['user_id'];

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare('
    SELECT COUNT(*)
    FROM user_documents d
    LEFT JOIN user_documents newer ON newer.parent_document_id = d.id
    WHERE d.user_id = :uid
      AND newer.id IS NULL
');
$countStmt->execute(['uid' => $uid]);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$listStmt = $pdo->prepare('
    SELECT d.*, dt.name AS type_name, dt.slug AS type_slug
    FROM user_documents d
    INNER JOIN document_types dt ON dt.id = d.document_type_id
    LEFT JOIN user_documents newer ON newer.parent_document_id = d.id
    WHERE d.user_id = :uid
      AND newer.id IS NULL
    ORDER BY d.uploaded_at DESC
    LIMIT :limit OFFSET :offset
');
$listStmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$documents = $listStmt->fetchAll() ?: [];

$tmpStmt = $pdo->prepare('SELECT COUNT(*) FROM user_documents WHERE user_id = :uid AND status = "pending"');
$tmpStmt->execute(['uid' => $uid]);
$pendingCount = (int) $tmpStmt->fetchColumn();

$verifiedStmt = $pdo->prepare('SELECT COUNT(*) FROM user_documents WHERE user_id = :uid AND status = "verified"');
$verifiedStmt->execute(['uid' => $uid]);
$verifiedCount = (int) $verifiedStmt->fetchColumn();

$mgrid_page_title = mgrid_title('title.documents');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?>
  <div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
  <div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
    <div>
      <div class="mgrid-topbar-label">Document Center</div>
      <h1 class="mgrid-display mb-1" style="font-size:2rem;">My Documents</h1>
      <p class="mb-0" style="color:var(--mgrid-ink-500);">Upload and manage verification documents for your M-Profile.</p>
    </div>
    <a href="<?= e(url('user/upload_document.php')) ?>" class="btn-mgrid btn-mgrid-primary"><i class="ti ti-upload"></i> Upload Document</a>
  </div>
</div>

<div class="mgrid-grid-3 mb-3">
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Total Documents</div>
    <div class="mgrid-stat-value"><?= (int) $total ?></div>
  </div>
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Pending Review</div>
    <div class="mgrid-stat-value"><?= (int) $pendingCount ?></div>
  </div>
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Verified</div>
    <div class="mgrid-stat-value"><?= (int) $verifiedCount ?></div>
  </div>
</div>

<div class="mgrid-card">
  <div class="mgrid-card-header">
    <h2 class="mgrid-card-title"><i class="ti ti-files"></i> Latest Document Versions</h2>
  </div>
  <div class="mgrid-card-body p-0">
    <div class="table-responsive">
      <table class="mgrid-table">
        <thead>
          <tr>
            <th>Document</th>
            <th>Type</th>
            <th>Uploaded</th>
            <th>Status</th>
            <th>Remarks</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($documents === []): ?>
            <tr><td colspan="6" class="text-center" style="padding:22px; color:var(--mgrid-ink-500);">No documents uploaded yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($documents as $doc): ?>
            <tr>
              <td>
                <strong><?= e((string) $doc['title']) ?></strong>
                <div class="small" style="color:var(--mgrid-ink-500);">Version <?= (int) $doc['version_number'] ?></div>
              </td>
              <td><?= e((string) $doc['type_name']) ?></td>
              <td><?= e(substr((string) $doc['uploaded_at'], 0, 16)) ?></td>
              <td>
                <span class="badge text-bg-<?= e(mgrid_document_status_badge((string) $doc['status'])) ?>">
                  <?= e(mgrid_document_status_label((string) $doc['status'])) ?>
                </span>
              </td>
              <td class="small"><?= e((string) ($doc['admin_remark'] ?? '—')) ?></td>
              <td>
                <div class="d-flex gap-2 flex-wrap">
                  <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('document_view.php?id=' . (int) $doc['id'])) ?>" target="_blank">Preview</a>
                  <?php if (mgrid_document_can_reupload((string) $doc['status'])): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('user/reupload_document.php?id=' . (int) $doc['id'])) ?>">Re-upload</a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($totalPages > 1): ?>
  <nav class="mt-3">
    <ul class="pagination pagination-sm mb-0">
      <?php for ($p = 1; $p <= $totalPages; $p++): ?>
        <li class="page-item<?= $p === $page ? ' active' : '' ?>">
          <a class="page-link" href="<?= e(url('user/my_documents.php?page=' . $p)) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
