<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$ready = notifications_module_ready($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
    $act = clean_string($_POST['action'] ?? '');
    if ($act === 'mark_all_read') {
        markAllNotificationsReadForUser($uid);
        flash_set('success', __('notif.mark_all_read'));
    } elseif ($act === 'mark_read') {
        $nid = (int) ($_POST['notification_id'] ?? 0);
        if ($nid > 0) {
            markNotificationAsRead($nid, $uid);
        }
    }
    redirect('user/notifications.php');
}

$filter = clean_string($_GET['filter'] ?? '');
$unreadOnly = $filter === 'unread' ? true : ($filter === 'read' ? false : null);
$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 25;
$offset = ($page - 1) * $per;

$items = $ready ? notifications_list_for_user($uid, $unreadOnly, $per, $offset) : [];
$unreadTotal = $ready ? notifications_unread_count($uid) : 0;

$mgrid_page_title = mgrid_title('title.notifications');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2 align-items-center">
    <div>
      <h1 class="mgrid-display mb-1" style="font-size:1.5rem;">Notifications</h1>
      <p class="text-muted mb-0 small"><?= $ready ? (int) $unreadTotal . ' unread' : 'Module not installed.' ?></p>
    </div>
    <?php if ($ready && $unreadTotal > 0): ?>
      <form method="post" class="m-0">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="mark_all_read">
        <button type="submit" class="btn-mgrid btn-mgrid-outline btn-sm">Mark all read</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<?php if ($ready): ?>
  <div class="mgrid-card mb-3 p-3">
    <div class="btn-group btn-group-sm" role="group">
      <a class="btn btn-outline-secondary <?= $filter === '' ? 'active' : '' ?>" href="<?= e(url('user/notifications.php')) ?>">All</a>
      <a class="btn btn-outline-secondary <?= $filter === 'unread' ? 'active' : '' ?>" href="<?= e(url('user/notifications.php?filter=unread')) ?>">Unread</a>
      <a class="btn btn-outline-secondary <?= $filter === 'read' ? 'active' : '' ?>" href="<?= e(url('user/notifications.php?filter=read')) ?>">Read</a>
    </div>
  </div>

  <div class="list-group shadow-sm">
    <?php if ($items === []): ?>
      <div class="list-group-item text-muted">No notifications in this view.</div>
    <?php endif; ?>
    <?php foreach ($items as $n): ?>
      <div class="list-group-item list-group-item-action flex-column align-items-start <?= (int) $n['is_read'] === 0 ? 'border-start border-3 border-primary' : '' ?>">
        <div class="d-flex w-100 justify-content-between">
          <div class="me-2">
            <span class="badge text-bg-<?= e(notification_type_badge_class((string) $n['type'])) ?>"><?= e((string) $n['type']) ?></span>
            <span class="badge text-bg-secondary"><?= e((string) $n['source_module']) ?></span>
            <?php if ((int) $n['is_read'] === 0): ?><span class="badge text-bg-primary">New</span><?php endif; ?>
          </div>
          <small class="text-muted"><?= e(substr((string) $n['created_at'], 0, 16)) ?></small>
        </div>
        <h2 class="h6 mt-2 mb-1"><?= e((string) $n['title']) ?></h2>
        <p class="mb-2 small" style="white-space:pre-wrap;"><?= e((string) $n['message']) ?></p>
        <div class="d-flex flex-wrap gap-2">
          <?php if (!empty($n['action_url'])): ?>
            <a class="btn btn-sm btn-primary" href="<?= e((string) $n['action_url']) ?>">Open</a>
          <?php endif; ?>
          <?php if ((int) $n['is_read'] === 0): ?>
            <form method="post" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="mark_read">
              <input type="hidden" name="notification_id" value="<?= (int) $n['id'] ?>">
              <button type="submit" class="btn btn-sm btn-outline-secondary">Mark read</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="mgrid-alert mgrid-alert-danger">Import <code>database/m_grid_notifications.sql</code> to enable notifications.</div>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
