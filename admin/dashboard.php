<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$pdo = db();

$totals = $pdo->query("
    SELECT
      COUNT(*) AS total_users,
      SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_users,
      SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_users
    FROM users
")->fetch() ?: ['total_users' => 0, 'active_users' => 0, 'pending_users' => 0];

$recent = $pdo->query("
    SELECT id, m_id, full_name, email, phone, created_at, status
    FROM users
    ORDER BY created_at DESC
    LIMIT 8
")->fetchAll();

$mscoreTableExists = (bool) $pdo->query("SHOW TABLES LIKE 'mscore_current_scores'")->fetchColumn();
$mscoreStats = ['avg' => 0, 'starter' => 0, 'emerging' => 0, 'growth' => 0, 'investment' => 0];
if ($mscoreTableExists) {
    $mscoreStats['avg'] = (float) ($pdo->query('SELECT AVG(total_score) FROM mscore_current_scores')->fetchColumn() ?: 0);
    $tiers = $pdo->query('SELECT tier_label, COUNT(*) AS c FROM mscore_current_scores GROUP BY tier_label')->fetchAll() ?: [];
    foreach ($tiers as $t) {
        $label = (string) $t['tier_label'];
        if ($label === 'Starter') {
            $mscoreStats['starter'] = (int) $t['c'];
        } elseif ($label === 'Emerging') {
            $mscoreStats['emerging'] = (int) $t['c'];
        } elseif ($label === 'Growth') {
            $mscoreStats['growth'] = (int) $t['c'];
        } elseif ($label === 'Investment Ready') {
            $mscoreStats['investment'] = (int) $t['c'];
        }
    }
}

$docTableExists = (bool) $pdo->query("SHOW TABLES LIKE 'user_documents'")->fetchColumn();
$docStats = ['total' => 0, 'pending' => 0, 'verified' => 0, 'rejected' => 0];
$recentDocActivity = [];
if ($docTableExists) {
    $docStats = $pdo->query("
        SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
          SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) AS verified,
          SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected
        FROM user_documents
    ")->fetch() ?: $docStats;

    $recentDocActivity = $pdo->query("
        SELECT d.id, d.title, d.status, d.updated_at, u.full_name, u.m_id
        FROM user_documents d
        INNER JOIN users u ON u.id = d.user_id
        ORDER BY d.updated_at DESC
        LIMIT 5
    ")->fetchAll() ?: [];
}

$mgrid_page_title = mgrid_title('title.admin_dashboard');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-page-section">
<div class="mgrid-grid-4 mb-4">
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Total members</div>
    <div class="mgrid-stat-value"><?= (int) ($totals['total_users'] ?? 0) ?></div>
    <div class="mgrid-stat-sub"><i class="ti ti-users"></i> Registered profiles</div>
  </div>
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Active members</div>
    <div class="mgrid-stat-value"><?= (int) ($totals['active_users'] ?? 0) ?></div>
    <div class="mgrid-stat-sub"><i class="ti ti-check"></i> Approved and active</div>
  </div>
  <div class="mgrid-stat-card" style="background:var(--mgrid-warning-bg);">
    <div class="mgrid-stat-label" style="color:var(--mgrid-warning-text);">Pending verification</div>
    <div class="mgrid-stat-value" style="color:var(--mgrid-warning-text);"><?= (int) ($totals['pending_users'] ?? 0) ?></div>
    <div class="mgrid-stat-sub" style="color:var(--mgrid-warning-text);"><i class="ti ti-hourglass"></i> Awaiting review</div>
  </div>
  <div class="mgrid-stat-card">
    <?php $ac = $pdo->query("SELECT COUNT(*) AS c, SUM(CASE WHEN role = 'super_admin' THEN 1 ELSE 0 END) AS super_c FROM admins WHERE status = 'active'")->fetch(); ?>
    <div class="mgrid-stat-label">Administration team</div>
    <div class="mgrid-stat-value"><?= (int) ($ac['c'] ?? 0) ?></div>
    <div class="mgrid-stat-sub">Super admins: <?= (int) ($ac['super_c'] ?? 0) ?></div>
  </div>
</div>
</div>

<div class="mgrid-card mgrid-page-section">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title mb-0"><i class="ti ti-table"></i> Recent registrations</h1>
    <a class="btn-mgrid btn-mgrid-primary" href="<?= e(url('admin/users.php')) ?>">View all</a>
  </div>
  <div class="mgrid-card-body p-0">
    <div class="table-responsive">
      <table class="mgrid-table mb-0">
        <thead>
          <tr>
            <th>M-ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Joined</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if ($recent === []): ?>
            <tr><td colspan="6" class="text-muted small">No member accounts yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($recent as $r): ?>
            <?php
            $status = (string) ($r['status'] ?? 'pending');
            $badgeClass = $status === 'active' ? 'mgrid-tier-badge--diamond' : ($status === 'suspended' ? 'mgrid-tier-badge--bronze' : 'mgrid-tier-badge--pending');
            ?>
            <tr>
              <td class="mgrid-table-mid-cell"><?= e((string) $r['m_id']) ?></td>
              <td><?= e((string) $r['full_name']) ?></td>
              <td class="small"><?= e((string) $r['email']) ?></td>
              <td><span class="mgrid-tier-badge <?= e($badgeClass) ?>"><?= e($status) ?></span></td>
              <td class="small text-muted"><?= e(substr((string) $r['created_at'], 0, 10)) ?></td>
              <td><a class="btn-mgrid btn-mgrid-ghost" href="<?= e(url('admin/user-view.php?id=' . (int) $r['id'])) ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($docTableExists): ?>
  <div class="mgrid-grid-4 mt-4 mb-4">
    <div class="mgrid-stat-card"><div class="mgrid-stat-label">Documents Total</div><div class="mgrid-stat-value"><?= (int) ($docStats['total'] ?? 0) ?></div></div>
    <div class="mgrid-stat-card"><div class="mgrid-stat-label">Docs Pending</div><div class="mgrid-stat-value"><?= (int) ($docStats['pending'] ?? 0) ?></div></div>
    <div class="mgrid-stat-card"><div class="mgrid-stat-label">Docs Verified</div><div class="mgrid-stat-value"><?= (int) ($docStats['verified'] ?? 0) ?></div></div>
    <div class="mgrid-stat-card"><div class="mgrid-stat-label">Docs Rejected</div><div class="mgrid-stat-value"><?= (int) ($docStats['rejected'] ?? 0) ?></div></div>
  </div>

  <div class="mgrid-card">
    <div class="mgrid-card-header">
      <h2 class="mgrid-card-title"><i class="ti ti-activity"></i> Recent document activity</h2>
      <a class="btn-mgrid btn-mgrid-outline" href="<?= e(url('admin/admin_documents.php')) ?>">Open module</a>
    </div>
    <div class="mgrid-card-body">
      <?php if ($recentDocActivity === []): ?>
        <p class="text-muted mb-0">No recent document activity.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($recentDocActivity as $item): ?>
            <li class="mb-2 pb-2 border-bottom">
              <div class="d-flex justify-content-between">
                <strong><?= e((string) $item['title']) ?></strong>
                <span class="badge text-bg-<?= e(mgrid_document_status_badge((string) $item['status'])) ?>"><?= e(mgrid_document_status_label((string) $item['status'])) ?></span>
              </div>
              <div class="small text-muted"><?= e((string) $item['full_name']) ?> (<?= e((string) $item['m_id']) ?>) · <?= e(substr((string) $item['updated_at'], 0, 16)) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($mscoreTableExists): ?>
  <div class="mgrid-grid-4 mt-4">
    <div class="mgrid-stat-card"><div class="mgrid-stat-label">Avg M-SCORE</div><div class="mgrid-stat-value"><?= number_format((float) $mscoreStats['avg'], 2) ?></div></div>
    <div class="mgrid-stat-card"><div class="mgrid-stat-label">Starter</div><div class="mgrid-stat-value"><?= (int) $mscoreStats['starter'] ?></div></div>
    <div class="mgrid-stat-card"><div class="mgrid-stat-label">Growth</div><div class="mgrid-stat-value"><?= (int) $mscoreStats['growth'] ?></div></div>
    <div class="mgrid-stat-card"><div class="mgrid-stat-label">Investment Ready</div><div class="mgrid-stat-value"><?= (int) $mscoreStats['investment'] ?></div></div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php';
