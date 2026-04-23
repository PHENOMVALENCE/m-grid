<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
    $act = clean_string($_POST['action'] ?? '');
    $id = (int) ($_POST['announcement_id'] ?? 0);
    if ($act === 'send' && $id > 0 && announcements_module_ready($pdo)) {
        $res = sendAnnouncementToUsers($id);
        if ($res['ok']) {
            flash_set('success', __('announce.sent', ['count' => (string) (int) $res['count']]));
        } else {
            flash_set('error', (string) ($res['error'] ?? __('announce.send_failed')));
        }
    } elseif ($act === 'cancel' && $id > 0 && announcements_module_ready($pdo)) {
        $pdo->prepare('UPDATE announcements SET status = "cancelled" WHERE id = :id AND status = "draft" LIMIT 1')->execute(['id' => $id]);
        flash_set('success', __('announce.cancelled'));
    }
    redirect('admin/admin_announcements.php');
}

$ready = announcements_module_ready($pdo);
$rows = $ready ? ($pdo->query('SELECT a.*, ad.full_name AS admin_name FROM announcements a INNER JOIN admins ad ON ad.id = a.created_by_admin_id ORDER BY a.created_at DESC LIMIT 100')->fetchAll() ?: []) : [];

$mgrid_page_title = mgrid_title('title.admin_announcements');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2">
    <h1 class="mgrid-card-title mb-0"><i class="ti ti-speakerphone"></i> Broadcast announcements</h1>
    <a class="btn-mgrid btn-mgrid-primary btn-sm" href="<?= e(url('admin/create_announcement.php')) ?>">Create</a>
  </div>
</div>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">Import <code>database/m_grid_notifications.sql</code>.</div>
<?php else: ?>
  <div class="mgrid-card">
    <div class="mgrid-card-body p-0">
      <div class="table-responsive">
        <table class="mgrid-table mb-0">
          <thead><tr><th>Title</th><th>Scope</th><th>Status</th><th>Recipients</th><th>Created</th><th></th></tr></thead>
          <tbody>
            <?php if ($rows === []): ?><tr><td colspan="6" class="text-center p-4 text-muted">None yet.</td></tr><?php endif; ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><strong><?= e((string) $r['title']) ?></strong></td>
                <td><?= e((string) $r['target_scope']) ?><?php if ((string) $r['target_scope'] === 'tier' && !empty($r['target_tier'])): ?> · <?= e((string) $r['target_tier']) ?><?php endif; ?></td>
                <td><span class="badge text-bg-<?= (string) $r['status'] === 'sent' ? 'success' : ((string) $r['status'] === 'draft' ? 'warning' : 'secondary') ?>"><?= e((string) $r['status']) ?></span></td>
                <td><?= (int) $r['recipient_count'] ?></td>
                <td class="small"><?= e(substr((string) $r['created_at'], 0, 16)) ?></td>
                <td class="text-nowrap">
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/view_announcement.php?id=' . (int) $r['id'])) ?>">View</a>
                  <?php if ((string) $r['status'] === 'draft'): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Send to all resolved recipients? This cannot be undone.');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="send">
                      <input type="hidden" name="announcement_id" value="<?= (int) $r['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-primary">Send</button>
                    </form>
                    <form method="post" class="d-inline" onsubmit="return confirm('Cancel this draft?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="cancel">
                      <input type="hidden" name="announcement_id" value="<?= (int) $r['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Cancel</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
