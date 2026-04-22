<?php

declare(strict_types=1);

/**
 * Bell + unread dropdown for member topbar. Include only when layout is user and member is logged in.
 */

$ndUser = auth_user();
if ($ndUser === null) {
    return;
}
$ndUid = (int) $ndUser['user_id'];
$pdo = db();
$ndReady = notifications_module_ready($pdo);
$ndCount = $ndReady ? notifications_unread_count($ndUid) : 0;
$ndItems = $ndReady ? getUnreadNotifications($ndUid, 8) : [];
?>
<div class="dropdown mgrid-notif-dropdown">
  <button class="btn btn-mgrid btn-mgrid-ghost position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifications" id="mgridNotifBell">
    <i class="ti ti-bell"></i>
    <?php if ($ndCount > 0): ?>
      <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;"><?= $ndCount > 99 ? '99+' : (int) $ndCount ?></span>
    <?php endif; ?>
  </button>
  <ul class="dropdown-menu dropdown-menu-end shadow mgrid-notif-menu" aria-labelledby="mgridNotifBell" style="min-width:320px;max-width:380px;">
    <li class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
      <span class="fw-semibold">Notifications</span>
      <a class="small" href="<?= e(url('user/notifications.php')) ?>">View all</a>
    </li>
    <?php if (!$ndReady): ?>
      <li class="px-3 py-3 text-muted small">Notifications module not installed.</li>
    <?php elseif ($ndItems === []): ?>
      <li class="px-3 py-3 text-muted small">You’re all caught up.</li>
    <?php else: ?>
      <?php foreach ($ndItems as $n): ?>
        <li>
          <div class="dropdown-item-text small py-2 border-bottom">
            <div class="d-flex justify-content-between gap-2">
              <div class="flex-grow-1">
                <?php if (!empty($n['action_url'])): ?>
                  <a href="<?= e((string) $n['action_url']) ?>" class="stretched-link text-decoration-none fw-semibold"><?= e((string) $n['title']) ?></a>
                <?php else: ?>
                  <span class="fw-semibold"><?= e((string) $n['title']) ?></span>
                <?php endif; ?>
                <?php $snip = strip_tags((string) $n['message']); if (strlen($snip) > 120) { $snip = substr($snip, 0, 117) . '…'; } ?>
                <div class="text-muted" style="font-size:0.85rem;"><?= e($snip) ?></div>
                <div class="text-muted" style="font-size:0.75rem;"><?= e((string) $n['source_module']) ?> · <?= e(substr((string) $n['created_at'], 0, 16)) ?></div>
              </div>
              <form method="post" action="<?= e(url('user/mark_notification_read.php')) ?>" class="align-self-start">
                <?= csrf_field() ?>
                <input type="hidden" name="notification_id" value="<?= (int) $n['id'] ?>">
                <input type="hidden" name="redirect" value="<?= e((string) ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
                <button type="submit" class="btn btn-link btn-sm p-0" title="Mark read">✓</button>
              </form>
            </div>
          </div>
        </li>
      <?php endforeach; ?>
    <?php endif; ?>
  </ul>
</div>
