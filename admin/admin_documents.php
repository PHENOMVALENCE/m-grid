<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$pdo = db();
$status = clean_string($_GET['status'] ?? '');
$typeId = (int) ($_GET['type_id'] ?? 0);
$q = clean_string($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];
if (in_array($status, ['pending', 'verified', 'rejected', 'resubmission_requested'], true)) {
    $where[] = 'd.status = :status';
    $params['status'] = $status;
}
if ($typeId > 0) {
    $where[] = 'd.document_type_id = :type_id';
    $params['type_id'] = $typeId;
}
if ($q !== '') {
    $where[] = '(u.full_name LIKE :q OR u.m_id LIKE :q2 OR d.title LIKE :q3)';
    $like = '%' . $q . '%';
    $params['q'] = $like;
    $params['q2'] = $like;
    $params['q3'] = $like;
}
$whereSql = implode(' AND ', $where);

$countSql = '
    SELECT COUNT(*)
    FROM user_documents d
    INNER JOIN users u ON u.id = d.user_id
    WHERE ' . $whereSql;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));
if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

$sql = '
    SELECT d.id, d.title, d.uploaded_at, d.status, d.version_number, d.admin_remark,
           u.full_name, u.m_id, dt.name AS type_name
    FROM user_documents d
    INNER JOIN users u ON u.id = d.user_id
    INNER JOIN document_types dt ON dt.id = d.document_type_id
    WHERE ' . $whereSql . '
    ORDER BY d.uploaded_at DESC
    LIMIT :limit OFFSET :offset
';
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll() ?: [];

$types = mgrid_document_types($pdo);

$statAll = (int) $pdo->query('SELECT COUNT(*) FROM user_documents')->fetchColumn();
$statPending = (int) $pdo->query("SELECT COUNT(*) FROM user_documents WHERE status = 'pending'")->fetchColumn();
$statVerified = (int) $pdo->query("SELECT COUNT(*) FROM user_documents WHERE status = 'verified'")->fetchColumn();
$statRejected = (int) $pdo->query("SELECT COUNT(*) FROM user_documents WHERE status = 'rejected'")->fetchColumn();

$mgrid_page_title = mgrid_title('title.admin_documents');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?>
  <div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
  <div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<div class="mgrid-grid-4 mb-3">
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Total Documents</div><div class="mgrid-stat-value"><?= $statAll ?></div></div>
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Pending Verification</div><div class="mgrid-stat-value"><?= $statPending ?></div></div>
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Verified</div><div class="mgrid-stat-value"><?= $statVerified ?></div></div>
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Rejected</div><div class="mgrid-stat-value"><?= $statRejected ?></div></div>
</div>

<div class="mgrid-card">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title"><i class="ti ti-shield-check"></i> Document Verification</h1>
  </div>
  <div class="mgrid-card-body">
    <form method="get" class="row g-2 mb-3">
      <div class="col-md-3">
        <select name="status" class="mgrid-form-control">
          <option value="">All statuses</option>
          <?php foreach (['pending' => 'Pending', 'verified' => 'Verified', 'rejected' => 'Rejected', 'resubmission_requested' => 'Resubmission Requested'] as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $status === $k ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <select name="type_id" class="mgrid-form-control">
          <option value="0">All types</option>
          <?php foreach ($types as $type): ?>
            <option value="<?= (int) $type['id'] ?>" <?= $typeId === (int) $type['id'] ? 'selected' : '' ?>><?= e((string) $type['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <input class="mgrid-form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Search user, M-ID, title">
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn-mgrid btn-mgrid-primary" type="submit">Filter</button>
      </div>
    </form>

    <div class="table-responsive">
      <table class="mgrid-table">
        <thead>
          <tr>
            <th>User</th>
            <th>M-ID</th>
            <th>Type</th>
            <th>Title</th>
            <th>Uploaded</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($rows === []): ?>
            <tr><td colspan="7" class="text-center" style="padding:22px; color:var(--mgrid-ink-500);">No documents found for this filter.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e((string) $r['full_name']) ?></td>
              <td class="mgrid-table-mid-cell"><?= e((string) $r['m_id']) ?></td>
              <td><?= e((string) $r['type_name']) ?></td>
              <td>
                <?= e((string) $r['title']) ?>
                <div class="small text-muted">v<?= (int) $r['version_number'] ?></div>
              </td>
              <td><?= e(substr((string) $r['uploaded_at'], 0, 16)) ?></td>
              <td><span class="badge text-bg-<?= e(mgrid_document_status_badge((string) $r['status'])) ?>"><?= e(mgrid_document_status_label((string) $r['status'])) ?></span></td>
              <td>
                <div class="d-flex gap-2">
                  <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('document_view.php?id=' . (int) $r['id'])) ?>" target="_blank">Preview</a>
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/review_document.php?id=' . (int) $r['id'])) ?>">Review</a>
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
          <a class="page-link" href="<?= e(url('admin/admin_documents.php?' . http_build_query(['status' => $status, 'type_id' => $typeId, 'q' => $q, 'page' => $p]))) ?>"><?= $p ?></a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
