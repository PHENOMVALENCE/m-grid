<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$id = (int) ($_GET['id'] ?? 0);

if (!opportunities_module_ready($pdo) || $id <= 0) {
    flash_set('error', __('opp.detail.not_found'));
    redirect('user/opportunities.php');
}

$o = opportunities_get_by_id($pdo, $id);
if ($o === null || (int) $o['is_archived'] === 1 || (int) ($o['is_active'] ?? 0) !== 1) {
    flash_set('error', __('opp.detail.not_found'));
    redirect('user/opportunities.php');
}

$state = ot_opportunity_listing_state($o);
$canApply = (int) ($o['is_active'] ?? 0) === 1
    && $state === 'active'
    && (int) ($o['apply_internal'] ?? 0) === 1
    && !opportunities_user_has_active_application($pdo, $uid, $id);

$hasExternal = trim((string) ($o['external_link'] ?? '')) !== '';

$mgrid_page_title = (string) $o['title'] . ' — ' . __('site.brand');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($m = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($m) ?></div><?php endif; ?>
<?php if ($m = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($m) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body">
    <div class="d-flex flex-wrap gap-2 mb-2">
      <span class="badge text-bg-primary"><?= e(ucwords(str_replace('_', ' ', (string) $o['opportunity_type']))) ?></span>
      <span class="badge text-bg-secondary"><?= e((string) $o['category_name']) ?></span>
      <span class="badge text-bg-<?= $state === 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($state)) ?></span>
    </div>
    <h1 class="mgrid-display" style="font-size:1.75rem;"><?= e((string) $o['title']) ?></h1>
    <p class="text-muted mb-0"><?= e((string) $o['provider_name']) ?></p>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="mgrid-card mb-3">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">Description</h2></div>
      <div class="mgrid-card-body"><div style="white-space:pre-wrap;"><?= e((string) $o['description']) ?></div></div>
    </div>
    <?php if (trim((string) ($o['requirements'] ?? '')) !== ''): ?>
      <div class="mgrid-card mb-3">
        <div class="mgrid-card-header"><h2 class="mgrid-card-title">Requirements</h2></div>
        <div class="mgrid-card-body"><div style="white-space:pre-wrap;"><?= e((string) $o['requirements']) ?></div></div>
      </div>
    <?php endif; ?>
  </div>
  <div class="col-lg-4">
    <div class="mgrid-card mb-3">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">Key dates</h2></div>
      <div class="mgrid-card-body small">
        <?php if (!empty($o['deadline'])): ?><div><strong>Deadline:</strong> <?= e((string) $o['deadline']) ?></div><?php endif; ?>
        <?php if (!empty($o['start_date'])): ?><div><strong>Starts:</strong> <?= e((string) $o['start_date']) ?></div><?php endif; ?>
        <?php if (!empty($o['end_date'])): ?><div><strong>Ends:</strong> <?= e((string) $o['end_date']) ?></div><?php endif; ?>
        <div><strong>Format:</strong> <?= e(ucfirst((string) $o['format'])) ?></div>
        <?php if (trim((string) ($o['location'] ?? '')) !== ''): ?><div><strong>Location:</strong> <?= e((string) $o['location']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="mgrid-card mb-3">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">How to apply</h2></div>
      <div class="mgrid-card-body small" style="white-space:pre-wrap;"><?= e(trim((string) ($o['application_method'] ?? 'Follow the apply link or use the button below.'))) ?></div>
    </div>
    <div class="mgrid-card">
      <div class="mgrid-card-body">
        <?php if ($canApply): ?>
          <form method="post" action="<?= e(url('user/apply_opportunity.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="opportunity_id" value="<?= (int) $o['id'] ?>">
            <div class="mb-2">
              <label class="form-label small">Message to reviewer (optional)</label>
              <textarea name="user_message" class="mgrid-form-control" rows="3" maxlength="2000"></textarea>
            </div>
            <button type="submit" class="btn-mgrid btn-mgrid-primary w-100">Apply on M-GRID</button>
          </form>
        <?php elseif ((int) ($o['apply_internal'] ?? 0) === 1 && opportunities_user_has_active_application($pdo, $uid, $id)): ?>
          <p class="small text-muted">You already have an active application for this listing.</p>
          <a class="btn-mgrid btn-mgrid-outline w-100" href="<?= e(url('user/my_opportunities.php')) ?>">View my applications</a>
        <?php elseif ($state !== 'active'): ?>
          <button class="btn btn-secondary w-100" disabled>Not open for applications</button>
        <?php endif; ?>

        <?php if ($hasExternal): ?>
          <a class="btn-mgrid btn-mgrid-outline w-100 mt-2" href="<?= e((string) $o['external_link']) ?>" target="_blank" rel="noopener">External application</a>
        <?php endif; ?>

        <a class="btn btn-link w-100 mt-2" href="<?= e(url('user/opportunities.php')) ?>">Back to listings</a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
