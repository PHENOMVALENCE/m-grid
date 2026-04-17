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

$mgrid_page_title = 'Admin dashboard — M-GRID';
require __DIR__ . '/includes/shell_open.php';
?>

<div class="row g-4 mb-4">
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <p class="text-muted small mb-1">Total members</p>
        <p class="fs-3 fw-bold mb-0"><?= (int) ($totals['total_users'] ?? 0) ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <p class="text-muted small mb-1">Active members</p>
        <p class="fs-3 fw-bold mb-0"><?= (int) ($totals['active_users'] ?? 0) ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <p class="text-muted small mb-1">Pending verification</p>
        <p class="fs-3 fw-bold mb-0"><?= (int) ($totals['pending_users'] ?? 0) ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card border-0 shadow-sm">
      <div class="card-body">
        <p class="text-muted small mb-1">Administrators</p>
        <?php $ac = $pdo->query("SELECT COUNT(*) AS c FROM admins WHERE status = 'active'")->fetch(); ?>
        <p class="fs-3 fw-bold mb-0"><?= (int) ($ac['c'] ?? 0) ?></p>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h4 fw-bold mb-0">Recent registrations</h1>
      <a class="btn btn-sm btn-primary" href="<?= e(url('admin/users.php')) ?>">View all</a>
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
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
            <tr>
              <td class="fw-semibold"><?= e((string) $r['m_id']) ?></td>
              <td><?= e((string) $r['full_name']) ?></td>
              <td class="small"><?= e((string) $r['email']) ?></td>
              <td><span class="badge bg-light text-dark border"><?= e((string) $r['status']) ?></span></td>
              <td class="small text-muted"><?= e(substr((string) $r['created_at'], 0, 10)) ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/user-view.php?id=' . (int) $r['id'])) ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
