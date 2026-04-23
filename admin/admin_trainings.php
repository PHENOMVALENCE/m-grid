<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_verify(is_string($token) ? $token : null)) {
        flash_set('error', __('settings.error.token'));
        redirect('admin/admin_trainings.php');
    }
    $action = clean_string($_POST['action'] ?? '');
    $pid = (int) ($_POST['program_id'] ?? 0);
    if ($pid > 0 && trainings_module_ready($pdo)) {
        if ($action === 'toggle_active') {
            $pdo->prepare('UPDATE training_programs SET is_active = 1 - is_active WHERE id = :id LIMIT 1')->execute(['id' => $pid]);
            flash_set('success', __('admin.generic.updated'));
        } elseif ($action === 'toggle_archive') {
            $pdo->prepare('UPDATE training_programs SET is_archived = 1 - is_archived WHERE id = :id LIMIT 1')->execute(['id' => $pid]);
            flash_set('success', __('admin.trainings.archive_updated'));
        }
    }
    redirect('admin/admin_trainings.php');
}

$ready = trainings_module_ready($pdo);
$showAll = ($_GET['all'] ?? '') === '1';
$rows = [];
if ($ready) {
    $sql = 'SELECT * FROM training_programs';
    if (!$showAll) {
        $sql .= ' WHERE is_archived = 0';
    }
    $sql .= ' ORDER BY updated_at DESC LIMIT 200';
    $rows = $pdo->query($sql)->fetchAll() ?: [];
}

$mgrid_page_title = mgrid_title('title.admin_trainings');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2">
    <h1 class="mgrid-card-title mb-0"><i class="ti ti-school"></i> Training programmes</h1>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin/admin_trainings.php?all=' . ($showAll ? '0' : '1'))) ?>"><?= $showAll ? 'Hide archived' : 'Show archived' ?></a>
      <a class="btn-mgrid btn-mgrid-primary btn-sm" href="<?= e(url('admin/add_training.php')) ?>">Add</a>
      <a class="btn-mgrid btn-mgrid-outline btn-sm" href="<?= e(url('admin/admin_training_registrations.php')) ?>">Registrations</a>
      <a class="btn-mgrid btn-mgrid-outline btn-sm" href="<?= e(url('admin/admin_opportunities.php')) ?>">Opportunities</a>
    </div>
  </div>
</div>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">Import SQL first.</div>
<?php else: ?>
  <div class="mgrid-card">
    <div class="mgrid-card-body p-0">
      <div class="table-responsive">
        <table class="mgrid-table mb-0">
          <thead><tr><th>Title</th><th>Type</th><th>Schedule</th><th>Format</th><th>Active</th><th>Archived</th><th></th></tr></thead>
          <tbody>
            <?php if ($rows === []): ?><tr><td colspan="7" class="text-center p-4 text-muted">None.</td></tr><?php endif; ?>
            <?php foreach ($rows as $p): ?>
              <tr>
                <td><strong><?= e((string) $p['title']) ?></strong><div class="small text-muted"><?= e((string) $p['slug']) ?></div></td>
                <td><?= e((string) $p['training_type']) ?></td>
                <td class="small"><?= e(substr((string) ($p['schedule_start'] ?? ''), 0, 16)) ?></td>
                <td><?= e((string) $p['format']) ?></td>
                <td><?= (int) $p['is_active'] ? 'Yes' : 'No' ?></td>
                <td><?= (int) $p['is_archived'] ? 'Yes' : 'No' ?></td>
                <td class="text-nowrap">
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/edit_training.php?id=' . (int) $p['id'])) ?>">Edit</a>
                  <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="program_id" value="<?= (int) $p['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-secondary">Toggle active</button></form>
                  <form method="post" class="d-inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle_archive"><input type="hidden" name="program_id" value="<?= (int) $p['id'] ?>"><button type="submit" class="btn btn-sm btn-outline-dark">Toggle archive</button></form>
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
