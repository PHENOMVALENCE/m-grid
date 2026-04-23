<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

if (!announcements_module_ready($pdo) || $id <= 0) {
    redirect('admin/admin_announcements.php');
}

$st = $pdo->prepare('
  SELECT a.*, ad.full_name AS admin_name
  FROM announcements a
  INNER JOIN admins ad ON ad.id = a.created_by_admin_id
  WHERE a.id = :id LIMIT 1
');
$st->execute(['id' => $id]);
$row = $st->fetch();
if (!$row) {
    redirect('admin/admin_announcements.php');
}

$targets = $pdo->prepare('SELECT user_id FROM announcement_targets WHERE announcement_id = :id ORDER BY user_id ASC');
$targets->execute(['id' => $id]);
$targetRows = $targets->fetchAll() ?: [];

$logs = [];
if (mscore_table_exists($pdo, 'notification_delivery_log')) {
    $lg = $pdo->prepare('
      SELECT l.*, u.m_id, u.full_name
      FROM notification_delivery_log l
      INNER JOIN users u ON u.id = l.user_id
      WHERE l.announcement_id = :id
      ORDER BY l.created_at DESC
      LIMIT 500
    ');
    $lg->execute(['id' => $id]);
    $logs = $lg->fetchAll() ?: [];
}

$mgrid_page_title = mgrid_title('title.admin_view_announcement');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mb-3"><a href="<?= e(url('admin/admin_announcements.php')) ?>" class="btn btn-sm btn-outline-secondary">← All announcements</a></div>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-header d-flex justify-content-between flex-wrap gap-2">
    <h1 class="mgrid-card-title mb-0"><?= e((string) $row['title']) ?></h1>
    <span class="badge text-bg-<?= (string) $row['status'] === 'sent' ? 'success' : 'warning' ?>"><?= e((string) $row['status']) ?></span>
  </div>
  <div class="mgrid-card-body">
    <p class="small text-muted mb-2">By <?= e((string) $row['admin_name']) ?> · <?= e((string) $row['created_at']) ?><?php if (!empty($row['sent_at'])): ?> · Sent <?= e((string) $row['sent_at']) ?><?php endif; ?></p>
    <p class="small"><strong>Scope:</strong> <?= e((string) $row['target_scope']) ?><?php if (!empty($row['target_tier'])): ?> (tier: <?= e((string) $row['target_tier']) ?>)<?php endif; ?></p>
    <div style="white-space:pre-wrap;"><?= e((string) $row['message']) ?></div>
  </div>
</div>

<?php if ((string) $row['target_scope'] === 'users' && $targetRows !== []): ?>
  <div class="mgrid-card mb-3">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title">Explicit targets (user IDs)</h2></div>
    <div class="mgrid-card-body small"><?= e(implode(', ', array_map(static fn ($t) => (string) (int) $t['user_id'], $targetRows))) ?></div>
  </div>
<?php endif; ?>

<div class="mgrid-card">
  <div class="mgrid-card-header"><h2 class="mgrid-card-title">Delivery log (in-app)</h2></div>
  <div class="mgrid-card-body p-0">
    <?php if ($logs === []): ?>
      <p class="p-3 text-muted mb-0 small"><?= (string) $row['status'] === 'sent' ? 'No log rows (older send or logging disabled).' : 'Not sent yet — no delivery records.' ?></p>
    <?php else: ?>
      <div class="table-responsive">
        <table class="mgrid-table mb-0">
          <thead><tr><th>When</th><th>Member</th><th>M-ID</th><th>Notification #</th></tr></thead>
          <tbody>
            <?php foreach ($logs as $l): ?>
              <tr>
                <td class="small"><?= e((string) $l['created_at']) ?></td>
                <td><?= e((string) $l['full_name']) ?></td>
                <td><?= e((string) $l['m_id']) ?></td>
                <td><?= (int) ($l['notification_id'] ?? 0) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
