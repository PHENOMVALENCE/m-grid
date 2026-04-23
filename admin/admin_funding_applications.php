<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

$status = clean_string($_GET['status'] ?? '');
$type = clean_string($_GET['application_type'] ?? '');
$q = clean_string($_GET['q'] ?? '');
$scoreMin = is_numeric($_GET['score_min'] ?? null) ? (float) $_GET['score_min'] : null;
$scoreMax = is_numeric($_GET['score_max'] ?? null) ? (float) $_GET['score_max'] : null;
$dateFrom = clean_string($_GET['date_from'] ?? '');
$dateTo = clean_string($_GET['date_to'] ?? '');
$amtMin = is_numeric($_GET['amount_min'] ?? null) ? (float) $_GET['amount_min'] : null;
$amtMax = is_numeric($_GET['amount_max'] ?? null) ? (float) $_GET['amount_max'] : null;

$where = ['1=1'];
$params = [];
if ($status !== '') { $where[] = 'f.status = :status'; $params['status'] = $status; }
if ($type !== '') { $where[] = 'f.application_type = :type'; $params['type'] = $type; }
if ($q !== '') { $where[] = '(u.full_name LIKE :q OR u.m_id LIKE :q2 OR f.reference_number LIKE :q3)'; $like = '%' . $q . '%'; $params['q'] = $like; $params['q2'] = $like; $params['q3'] = $like; }
if ($dateFrom !== '') { $where[] = 'DATE(f.submitted_at) >= :df'; $params['df'] = $dateFrom; }
if ($dateTo !== '') { $where[] = 'DATE(f.submitted_at) <= :dt'; $params['dt'] = $dateTo; }
if ($amtMin !== null) { $where[] = 'f.requested_amount >= :amin'; $params['amin'] = $amtMin; }
if ($amtMax !== null) { $where[] = 'f.requested_amount <= :amax'; $params['amax'] = $amtMax; }
if ($scoreMin !== null) { $where[] = 'COALESCE(ms.total_score,0) >= :smin'; $params['smin'] = $scoreMin; }
if ($scoreMax !== null) { $where[] = 'COALESCE(ms.total_score,0) <= :smax'; $params['smax'] = $scoreMax; }
$whereSql = implode(' AND ', $where);

$sql = '
  SELECT f.*, u.full_name, COALESCE(ms.total_score,0) AS mscore
  FROM funding_applications f
  INNER JOIN users u ON u.id = f.user_id
  LEFT JOIN mscore_current_scores ms ON ms.user_id = f.user_id
  WHERE ' . $whereSql . '
  ORDER BY f.submitted_at DESC
  LIMIT 200
';
$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v, (is_int($v) || is_float($v)) ? PDO::PARAM_STR : PDO::PARAM_STR);
}
$stmt->execute();
$rows = $stmt->fetchAll() ?: [];

$stats = $pdo->query('
  SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = "submitted" THEN 1 ELSE 0 END) AS submitted_c,
    SUM(CASE WHEN status = "under_review" THEN 1 ELSE 0 END) AS review_c,
    SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) AS approved_c,
    SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) AS rejected_c,
    SUM(CASE WHEN status = "disbursed" THEN 1 ELSE 0 END) AS disbursed_c,
    SUM(CASE WHEN status = "active_repayment" THEN 1 ELSE 0 END) AS active_repay_c,
    SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) AS completed_c
  FROM funding_applications
')->fetch() ?: [];

$mgrid_page_title = mgrid_title('title.admin_funding_apps');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-grid-4 mb-3">
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Total</div><div class="mgrid-stat-value"><?= (int) ($stats['total'] ?? 0) ?></div></div>
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Pending Review</div><div class="mgrid-stat-value"><?= (int) (($stats['submitted_c'] ?? 0) + ($stats['review_c'] ?? 0)) ?></div></div>
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Approved/Disbursed</div><div class="mgrid-stat-value"><?= (int) (($stats['approved_c'] ?? 0) + ($stats['disbursed_c'] ?? 0)) ?></div></div>
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Active Repayment</div><div class="mgrid-stat-value"><?= (int) ($stats['active_repay_c'] ?? 0) ?></div></div>
</div>

<div class="mgrid-card">
  <div class="mgrid-card-header"><h1 class="mgrid-card-title"><i class="ti ti-coin"></i> Funding Applications</h1></div>
  <div class="mgrid-card-body">
    <form method="get" class="row g-2 mb-3">
      <div class="col-md-2"><select name="status" class="mgrid-form-control"><option value="">All statuses</option><?php foreach (['submitted','under_review','more_info_requested','approved','rejected','disbursed','active_repayment','completed','defaulted','cancelled'] as $s): ?><option value="<?= e($s) ?>" <?= $status===$s?'selected':'' ?>><?= e(mfund_status_label($s)) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-2"><select name="application_type" class="mgrid-form-control"><option value="">All types</option><option value="loan" <?= $type==='loan'?'selected':'' ?>>Loan</option><option value="grant" <?= $type==='grant'?'selected':'' ?>>Grant</option><option value="support" <?= $type==='support'?'selected':'' ?>>Support</option></select></div>
      <div class="col-md-2"><input class="mgrid-form-control" type="number" step="0.01" name="score_min" placeholder="Score min" value="<?= e((string) ($_GET['score_min'] ?? '')) ?>"></div>
      <div class="col-md-2"><input class="mgrid-form-control" type="number" step="0.01" name="score_max" placeholder="Score max" value="<?= e((string) ($_GET['score_max'] ?? '')) ?>"></div>
      <div class="col-md-4"><input class="mgrid-form-control" type="search" name="q" placeholder="Search name, M-ID, ref" value="<?= e($q) ?>"></div>
      <div class="col-md-2"><input class="mgrid-form-control" type="date" name="date_from" value="<?= e($dateFrom) ?>"></div>
      <div class="col-md-2"><input class="mgrid-form-control" type="date" name="date_to" value="<?= e($dateTo) ?>"></div>
      <div class="col-md-2"><input class="mgrid-form-control" type="number" step="0.01" name="amount_min" placeholder="Amt min" value="<?= e((string) ($_GET['amount_min'] ?? '')) ?>"></div>
      <div class="col-md-2"><input class="mgrid-form-control" type="number" step="0.01" name="amount_max" placeholder="Amt max" value="<?= e((string) ($_GET['amount_max'] ?? '')) ?>"></div>
      <div class="col-md-2 d-grid"><button class="btn-mgrid btn-mgrid-primary">Filter</button></div>
    </form>
    <div class="table-responsive">
      <table class="mgrid-table">
        <thead><tr><th>Reference</th><th>User</th><th>M-ID</th><th>M-SCORE</th><th>Type</th><th>Requested</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if ($rows === []): ?><tr><td colspan="9" class="text-center" style="padding:20px;">No applications found.</td></tr><?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="mgrid-table-mid-cell"><?= e((string) $r['reference_number']) ?></td>
              <td><?= e((string) $r['full_name']) ?></td>
              <td><?= e((string) $r['m_id']) ?></td>
              <td><?= number_format((float) $r['mscore'], 2) ?></td>
              <td><?= e(strtoupper((string) $r['application_type'])) ?></td>
              <td><?= number_format((float) $r['requested_amount'], 2) ?></td>
              <td><?= e(substr((string) $r['submitted_at'], 0, 16)) ?></td>
              <td><span class="badge text-bg-<?= e(mfund_status_badge((string) $r['status'])) ?>"><?= e(mfund_status_label((string) $r['status'])) ?></span></td>
              <td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/admin_funding_review.php?id=' . (int) $r['id'])) ?>">Review</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
