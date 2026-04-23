<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();
$admin = auth_admin();
if ($admin === null) {
    redirect('admin/admin_announcements.php');
}
$adminId = (int) $admin['admin_id'];

if (!announcements_module_ready($pdo)) {
    flash_set('error', __('error.schema_missing'));
    redirect('admin/admin_announcements.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify(is_string($_POST['_csrf'] ?? null) ? $_POST['_csrf'] : null)) {
    $title = clean_string($_POST['title'] ?? '');
    $message = clean_string($_POST['message'] ?? '');
    $scope = clean_string($_POST['target_scope'] ?? 'all');
    if (!in_array($scope, ['all', 'tier', 'users'], true)) {
        $scope = 'all';
    }
    $tier = clean_string($_POST['target_tier'] ?? '');
    $rawUsers = clean_string($_POST['target_user_ids'] ?? '');
    $ids = [];
    if ($scope === 'users' && $rawUsers !== '') {
        foreach (preg_split('/[\s,;]+/', $rawUsers) ?: [] as $p) {
            if (is_numeric($p)) {
                $ids[] = (int) $p;
            }
        }
    }

    if ($title === '' || $message === '') {
        flash_set('error', __('announce.create.title_body'));
    } elseif ($scope === 'tier' && $tier === '') {
        flash_set('error', __('announce.create.tier_label'));
    } elseif ($scope === 'users' && $ids === []) {
        flash_set('error', __('announce.create.user_ids'));
    } else {
        $aid = announcement_create_draft($pdo, $adminId, $title, $message, $scope, $tier !== '' ? $tier : null, $ids);
        if ($aid) {
            flash_set('success', __('announce.create.draft_ok'));
            redirect('admin/admin_announcements.php');
        }
        flash_set('error', __('announce.create.draft_fail'));
    }
}

$mgrid_page_title = mgrid_title('title.admin_create_announcement');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card">
  <div class="mgrid-card-body">
    <p class="text-muted small">Drafts do not notify members until you press <strong>Send</strong> on the announcements list. “All” targets active accounts only.</p>
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-12"><label class="form-label">Title *</label><input class="mgrid-form-control" name="title" required maxlength="255"></div>
      <div class="col-12"><label class="form-label">Message *</label><textarea class="mgrid-form-control" name="message" rows="6" required></textarea></div>
      <div class="col-md-6">
        <label class="form-label">Audience</label>
        <select name="target_scope" class="mgrid-form-control" id="targetScope">
          <option value="all">All active members</option>
          <option value="tier">Members by M-SCORE tier</option>
          <option value="users">Specific user IDs</option>
        </select>
      </div>
      <div class="col-md-6" id="tierWrap" style="display:none;">
        <label class="form-label">Tier label</label>
        <input class="mgrid-form-control" name="target_tier" placeholder="e.g. Growth, Emerging" maxlength="120">
        <div class="form-text">Matched against <code>mscore_current_scores.tier_label</code> and <code>m_scores.tier</code>.</div>
      </div>
      <div class="col-12" id="usersWrap" style="display:none;">
        <label class="form-label">User IDs</label>
        <textarea class="mgrid-form-control" name="target_user_ids" rows="3" placeholder="Comma or line separated numeric user IDs"></textarea>
      </div>
      <div class="col-12">
        <button type="submit" class="btn-mgrid btn-mgrid-primary">Save draft</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('admin/admin_announcements.php')) ?>">Back</a>
      </div>
    </form>
  </div>
</div>
<script>
(function(){
  var s = document.getElementById('targetScope');
  var tw = document.getElementById('tierWrap');
  var uw = document.getElementById('usersWrap');
  function sync(){
    tw.style.display = s.value === 'tier' ? 'block' : 'none';
    uw.style.display = s.value === 'users' ? 'block' : 'none';
  }
  s.addEventListener('change', sync);
  sync();
})();
</script>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
