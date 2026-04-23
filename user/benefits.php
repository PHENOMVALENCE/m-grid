<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$ready = mbenefits_module_ready($pdo);

$catFilter = (int) ($_GET['category'] ?? 0);
$offers = $ready ? mbenefits_list_active_offers($pdo, $catFilter > 0 ? $catFilter : null) : [];
$cats = $ready ? ($pdo->query('SELECT id, name FROM benefit_categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC')->fetchAll() ?: []) : [];

$mgrid_page_title = mgrid_title('title.benefits');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-3 align-items-start">
    <div>
      <div class="mgrid-topbar-label">M-BENEFITS</div>
      <h1 class="mgrid-display mb-1" style="font-size:2rem;">Rewards &amp; partner value</h1>
      <p class="mb-0" style="color:var(--mgrid-ink-500);">Browse verified offers. Eligibility is linked to your M-SCORE, tier, and profile strength.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn-mgrid btn-mgrid-outline" href="<?= e(url('user/my_benefits.php')) ?>"><i class="ti ti-history"></i> My claims</a>
    </div>
  </div>
</div>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">The M-Benefits database tables are not installed. Import <code>database/m_grid_mbenefits.sql</code> to enable this module.</div>
<?php else: ?>
  <form method="get" class="mgrid-card mb-3 p-3 d-flex flex-wrap gap-2 align-items-end">
    <div>
      <label class="form-label small text-muted mb-0">Category</label>
      <select name="category" class="mgrid-form-control" onchange="this.form.submit()">
        <option value="0">All categories</option>
        <?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $catFilter === (int) $c['id'] ? 'selected' : '' ?>><?= e((string) $c['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <div class="row g-3">
    <?php if ($offers === []): ?>
      <div class="col-12"><p class="text-muted">No active offers in this view.</p></div>
    <?php endif; ?>
    <?php foreach ($offers as $o): ?>
      <?php
        $eligible = mbenefits_evaluate_eligibility($pdo, $uid, $o)['ok'];
        $rule = mbenefits_eligibility_rule_summary($o);
        ?>
      <div class="col-md-6 col-xl-4">
        <div class="mgrid-card h-100 mgrid-benefit-card">
          <div class="mgrid-card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="badge text-bg-<?= e(mbenefits_benefit_type_badge((string) $o['benefit_type'])) ?>"><?= e(mbenefits_benefit_type_label((string) $o['benefit_type'])) ?></span>
              <span class="badge text-bg-<?= $eligible ? 'success' : 'secondary' ?>"><?= $eligible ? 'Eligible' : 'Not eligible' ?></span>
            </div>
            <div class="small text-muted mb-1"><?= e((string) $o['category_name']) ?> · <?= e((string) $o['provider_name']) ?></div>
            <h2 class="h5 mb-2"><?= e((string) $o['title']) ?></h2>
            <p class="small flex-grow-1" style="color:var(--mgrid-ink-500);"><?= e((string) $o['short_description']) ?></p>
            <div class="mb-2">
              <span class="mgrid-mono-id" style="font-size:1.1rem;"><?= e((string) $o['value_label']) ?></span>
            </div>
            <div class="small text-muted mb-3">
              <div><strong>Rules:</strong> <?= e($rule) ?></div>
              <div><strong>Valid:</strong> <?= e((string) $o['valid_from']) ?> → <?= e((string) $o['valid_to']) ?></div>
            </div>
            <div class="d-flex gap-2 mt-auto">
              <a class="btn-mgrid btn-mgrid-primary flex-grow-1" href="<?= e(url('user/benefit_detail.php?id=' . (int) $o['id'])) ?>">View details</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
