<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$id = (int) ($_GET['id'] ?? 0);

if (!trainings_module_ready($pdo) || $id <= 0) {
    flash_set('error', __('train.detail.not_found'));
    redirect('user/trainings.php');
}

$p = trainings_get_by_id($pdo, $id);
if ($p === null || (int) $p['is_archived'] === 1 || (int) ($p['is_active'] ?? 0) !== 1) {
    flash_set('error', __('train.detail.not_found'));
    redirect('user/trainings.php');
}

$state = ot_training_listing_state($p);
$canRegister = (int) $p['is_active'] === 1
    && $state === 'active'
    && (int) ($p['register_internal'] ?? 0) === 1
    && !trainings_user_has_active_registration($pdo, $uid, $id);

$ext = trim((string) ($p['external_link'] ?? ''));

$mgrid_page_title = (string) $p['title'] . ' — ' . __('site.brand');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($m = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($m) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body">
    <div class="d-flex flex-wrap gap-2 mb-2">
      <span class="badge text-bg-info"><?= e(ucfirst((string) $p['training_type'])) ?></span>
      <span class="badge text-bg-<?= $state === 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($state)) ?></span>
      <span class="badge text-bg-dark"><?= e(ucfirst((string) $p['format'])) ?></span>
    </div>
    <h1 class="mgrid-display" style="font-size:1.75rem;"><?= e((string) $p['title']) ?></h1>
    <p class="mb-0 text-muted"><?= e((string) $p['provider_name']) ?><?php if (trim((string) ($p['trainer_name'] ?? '')) !== ''): ?> · <?= e((string) $p['trainer_name']) ?><?php endif; ?></p>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="mgrid-card mb-3">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">About this session</h2></div>
      <div class="mgrid-card-body"><div style="white-space:pre-wrap;"><?= e((string) $p['description']) ?></div></div>
    </div>
    <?php if (trim((string) ($p['eligibility'] ?? '')) !== ''): ?>
      <div class="mgrid-card">
        <div class="mgrid-card-header"><h2 class="mgrid-card-title">Eligibility</h2></div>
        <div class="mgrid-card-body"><div style="white-space:pre-wrap;"><?= e((string) $p['eligibility']) ?></div></div>
      </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="mgrid-card mb-3">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">Schedule</h2></div>
      <div class="mgrid-card-body small">
        <div><strong>Starts:</strong> <?= $p['schedule_start'] ? e((string) $p['schedule_start']) : 'TBD' ?></div>
        <div><strong>Ends:</strong> <?= $p['schedule_end'] ? e((string) $p['schedule_end']) : '—' ?></div>
        <?php if (trim((string) ($p['duration_label'] ?? '')) !== ''): ?><div><strong>Duration:</strong> <?= e((string) $p['duration_label']) ?></div><?php endif; ?>
        <?php if (trim((string) ($p['location'] ?? '')) !== ''): ?><div><strong>Location:</strong> <?= e((string) $p['location']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="mgrid-card">
      <div class="mgrid-card-body">
        <?php if ($canRegister): ?>
          <form method="post" action="<?= e(url('user/register_training.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="training_program_id" value="<?= (int) $p['id'] ?>">
            <div class="mb-2">
              <label class="form-label small">Note to organiser (optional)</label>
              <textarea name="user_message" class="mgrid-form-control" rows="2" maxlength="2000"></textarea>
            </div>
            <button type="submit" class="btn-mgrid btn-mgrid-primary w-100">Register</button>
          </form>
        <?php elseif (trainings_user_has_active_registration($pdo, $uid, $id)): ?>
          <p class="small text-muted">You are already registered or pending for this programme.</p>
          <a class="btn-mgrid btn-mgrid-outline w-100" href="<?= e(url('user/my_trainings.php')) ?>">My trainings</a>
        <?php else: ?>
          <button class="btn btn-secondary w-100" disabled>Registration closed</button>
        <?php endif; ?>
        <?php if ($ext !== ''): ?>
          <a class="btn-mgrid btn-mgrid-outline w-100 mt-2" href="<?= e($ext) ?>" target="_blank" rel="noopener">External registration</a>
        <?php endif; ?>
        <a class="btn btn-link w-100 mt-2" href="<?= e(url('user/trainings.php')) ?>">All trainings</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
