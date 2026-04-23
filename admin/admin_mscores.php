<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$pdo = db();
$tier = clean_string($_GET['tier'] ?? '');
$scoreMin = is_numeric($_GET['score_min'] ?? null) ? (float) $_GET['score_min'] : null;
$scoreMax = is_numeric($_GET['score_max'] ?? null) ? (float) $_GET['score_max'] : null;
$q = clean_string($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];
if ($tier !== '') {
    $where[] = 'm.tier_label = :tier';
    $params['tier'] = $tier;
}
if ($scoreMin !== null) {
    $where[] = 'm.total_score >= :score_min';
    $params['score_min'] = $scoreMin;
}
if ($scoreMax !== null) {
    $where[] = 'm.total_score <= :score_max';
    $params['score_max'] = $scoreMax;
}
if ($q !== '') {
    $where[] = '(u.full_name LIKE :q OR u.m_id LIKE :q2)';
    $like = '%' . $q . '%';
    $params['q'] = $like;
    $params['q2'] = $like;
}
$whereSql = implode(' AND ', $where);

$count = $pdo->prepare('
    SELECT COUNT(*)
    FROM mscore_current_scores m
    INNER JOIN users u ON u.id = m.user_id
    WHERE ' . $whereSql
);
$count->execute($params);
$total = (int) $count->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare('
    SELECT m.user_id, m.m_id, m.total_score, m.tier_label, m.readiness_label, m.calculated_at, u.full_name
    FROM mscore_current_scores m
    INNER JOIN users u ON u.id = m.user_id
    WHERE ' . $whereSql . '
    ORDER BY m.total_score DESC, m.calculated_at DESC
    LIMIT :limit OFFSET :offset
');
foreach ($params as $k => $v) {
    $stmt->bindValue(':' . $k, $v, is_float($v) ? PDO::PARAM_STR : PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll() ?: [];

$avgScore = (float) ($pdo->query('SELECT AVG(total_score) FROM mscore_current_scores')->fetchColumn() ?: 0);
$tierCountsStmt = $pdo->query('SELECT tier_label, COUNT(*) AS c FROM mscore_current_scores GROUP BY tier_label');
$tierCountsRaw = $tierCountsStmt->fetchAll() ?: [];
$tierCounts = ['Starter' => 0, 'Emerging' => 0, 'Growth' => 0, 'Investment Ready' => 0];
foreach ($tierCountsRaw as $t) {
    $k = (string) $t['tier_label'];
    if (isset($tierCounts[$k])) {
        $tierCounts[$k] = (int) $t['c'];
    }
}

$mgrid_page_title = mgrid_title('title.admin_mscores');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?>
  <div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
  <div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<div class="mgrid-grid-4 mb-3">
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Average M-SCORE</div><div class="mgrid-stat-value"><?= number_format($avgScore, 2) ?></div></div>
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Starter</div><div class="mgrid-stat-value"><?= $tierCounts['Starter'] ?></div></div>
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Growth</div><div class="mgrid-stat-value"><?= $tierCounts['Growth'] ?></div></div>
  <div class="mgrid-stat-card"><div class="mgrid-stat-label">Investment Ready</div><div class="mgrid-stat-value"><?= $tierCounts['Investment Ready'] ?></div></div>
</div>

<div class="mgrid-card">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title"><i class="ti ti-chart-histogram"></i> M-SCORE Monitoring</h1>
    <div class="d-flex gap-2">
      <a class="btn-mgrid btn-mgrid-outline" href="<?= e(url('bulk_recalculate_mscores.php')) ?>">Bulk Recalculate</a>
    </div>
  </div>
  <div class="mgrid-card-body">
    <form class="row g-2 mb-3" method="get">
      <div class="col-md-3">
        <select name="tier" class="mgrid-form-control">
          <option value="">All tiers</option>
          <?php foreach (['Starter', 'Emerging', 'Growth', 'Investment Ready'] as $t): ?>
            <option value="<?= e($t) ?>" <?= $tier === $t ? 'selected' : '' ?>><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><input class="mgrid-form-control" type="number" step="0.01" name="score_min" placeholder="Min score" value="<?= e((string) ($_GET['score_min'] ?? '')) ?>"></div>
      <div class="col-md-2"><input class="mgrid-form-control" type="number" step="0.01" name="score_max" placeholder="Max score" value="<?= e((string) ($_GET['score_max'] ?? '')) ?>"></div>
      <div class="col-md-3"><input class="mgrid-form-control" type="search" name="q" placeholder="Search name or M-ID" value="<?= e($q) ?>"></div>
      <div class="col-md-2 d-grid"><button class="btn-mgrid btn-mgrid-primary">Filter</button></div>
    </form>

    <div class="table-responsive">
      <table class="mgrid-table">
        <thead><tr><th>User</th><th>M-ID</th><th>Score</th><th>Tier</th><th>Readiness</th><th>Updated</th><th></th></tr></thead>
        <tbody>
          <?php if ($rows === []): ?>
            <tr><td colspan="7" class="text-center" style="padding:20px;">No records found.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td><?= e((string) $r['full_name']) ?></td>
              <td class="mgrid-table-mid-cell"><?= e((string) $r['m_id']) ?></td>
              <td><?= number_format((float) $r['total_score'], 2) ?></td>
              <td><span class="badge text-bg-<?= e(mscore_tier_badge_class((string) $r['tier_label'])) ?>"><?= e((string) $r['tier_label']) ?></span></td>
              <td class="small"><?= e((string) $r['readiness_label']) ?></td>
              <td><?= e(substr((string) $r['calculated_at'], 0, 16)) ?></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/admin_mscore_detail.php?user_id=' . (int) $r['user_id'])) ?>">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
