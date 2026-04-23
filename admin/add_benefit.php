<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

if (!mbenefits_module_ready($pdo)) {
    flash_set('error', __('ben.schema_missing'));
    redirect('admin/admin_benefits.php');
}

$cats = $pdo->query('SELECT id, name FROM benefit_categories WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll() ?: [];
$provs = $pdo->query('SELECT id, name FROM benefit_providers WHERE is_active = 1 ORDER BY name')->fetchAll() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_verify(is_string($token) ? $token : null)) {
        flash_set('error', __('settings.error.token'));
        redirect('admin/add_benefit.php');
    }

    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $providerId = (int) ($_POST['provider_id'] ?? 0);
    $title = clean_string($_POST['title'] ?? '');
    $slug = strtolower(preg_replace('/[^a-z0-9\-]+/', '-', clean_string($_POST['slug'] ?? '')) ?? '');
    $slug = trim((string) $slug, '-');
    $short = clean_string($_POST['short_description'] ?? '');
    $full = clean_string($_POST['full_description'] ?? '');
    $terms = clean_string($_POST['terms_and_conditions'] ?? '');
    $btype = clean_string($_POST['benefit_type'] ?? 'discount');
    if (!in_array($btype, ['discount', 'credit', 'voucher', 'service', 'other'], true)) {
        $btype = 'discount';
    }
    $valueLabel = clean_string($_POST['value_label'] ?? '');
    $valueNum = $_POST['value_numeric'] !== '' && is_numeric($_POST['value_numeric'] ?? null) ? (float) $_POST['value_numeric'] : null;
    $minScore = is_numeric($_POST['min_mscore'] ?? null) ? (float) $_POST['min_mscore'] : 0.0;
    $tier = clean_string($_POST['eligible_tier'] ?? '');
    $tier = $tier === '' ? null : strtolower(preg_replace('/[^a-z0-9_]+/', '_', $tier) ?? '');
    $reqDocs = isset($_POST['requires_verified_documents']) ? 1 : 0;
    $profPct = (int) ($_POST['requires_profile_complete_percent'] ?? 0);
    $profPct = max(0, min(100, $profPct));
    $allowRepeat = isset($_POST['allow_repeat_claims']) ? 1 : 0;
    $redemption = clean_string($_POST['redemption_method'] ?? '');
    $validFrom = clean_string($_POST['valid_from'] ?? '');
    $validTo = clean_string($_POST['valid_to'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '' || $slug === '' || $short === '' || $valueLabel === '' || $validFrom === '' || $validTo === '' || $categoryId <= 0 || $providerId <= 0) {
        flash_set('error', __('error.fill_required'));
    } else {
        try {
            $ins = $pdo->prepare('
                INSERT INTO benefit_offers (
                  category_id, provider_id, title, slug, short_description, full_description, terms_and_conditions,
                  benefit_type, value_label, value_numeric, min_mscore, eligible_tier,
                  requires_verified_documents, requires_profile_complete_percent, allow_repeat_claims,
                  redemption_method, valid_from, valid_to, is_active
                ) VALUES (
                  :cid, :pid, :title, :slug, :shortd, :fulld, :terms,
                  :btype, :vlabel, :vnum, :minsc, :tier,
                  :rdoc, :ppct, :arep,
                  :redm, :vf, :vt, :act
                )
            ');
            $ins->execute([
                'cid' => $categoryId,
                'pid' => $providerId,
                'title' => $title,
                'slug' => $slug,
                'shortd' => $short,
                'fulld' => $full !== '' ? $full : null,
                'terms' => $terms !== '' ? $terms : null,
                'btype' => $btype,
                'vlabel' => $valueLabel,
                'vnum' => $valueNum,
                'minsc' => $minScore,
                'tier' => $tier,
                'rdoc' => $reqDocs,
                'ppct' => $profPct,
                'arep' => $allowRepeat,
                'redm' => $redemption !== '' ? $redemption : null,
                'vf' => $validFrom,
                'vt' => $validTo,
                'act' => $isActive,
            ]);
            flash_set('success', __('ben.offer.created'));
            redirect('admin/admin_benefits.php');
        } catch (Throwable $e) {
            flash_set('error', __('error.save_duplicate_slug'));
        }
    }
}

$mgrid_page_title = mgrid_title('title.add_benefit');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card">
  <div class="mgrid-card-header"><h1 class="mgrid-card-title">New offer</h1></div>
  <div class="mgrid-card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6">
        <label class="form-label">Category *</label>
        <select name="category_id" class="mgrid-form-control" required>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int) $c['id'] ?>"><?= e((string) $c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Provider *</label>
        <select name="provider_id" class="mgrid-form-control" required>
          <?php foreach ($provs as $p): ?>
            <option value="<?= (int) $p['id'] ?>"><?= e((string) $p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">Title *</label>
        <input type="text" name="title" class="mgrid-form-control" required maxlength="200">
      </div>
      <div class="col-md-4">
        <label class="form-label">Slug * (url-safe)</label>
        <input type="text" name="slug" class="mgrid-form-control" required maxlength="100" pattern="[a-z0-9\-]+">
      </div>
      <div class="col-12">
        <label class="form-label">Short description *</label>
        <input type="text" name="short_description" class="mgrid-form-control" required maxlength="500">
      </div>
      <div class="col-12">
        <label class="form-label">Full description</label>
        <textarea name="full_description" class="mgrid-form-control" rows="4"></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Terms &amp; conditions</label>
        <textarea name="terms_and_conditions" class="mgrid-form-control" rows="3"></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label">Benefit type</label>
        <select name="benefit_type" class="mgrid-form-control">
          <?php foreach (['discount', 'credit', 'voucher', 'service', 'other'] as $t): ?>
            <option value="<?= e($t) ?>"><?= e(mbenefits_benefit_type_label($t)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Value label *</label>
        <input type="text" name="value_label" class="mgrid-form-control" required maxlength="120" placeholder="e.g. 20% off">
      </div>
      <div class="col-md-4">
        <label class="form-label">Value numeric (optional)</label>
        <input type="number" step="0.01" name="value_numeric" class="mgrid-form-control">
      </div>
      <div class="col-md-4">
        <label class="form-label">Min M-SCORE</label>
        <input type="number" step="0.01" name="min_mscore" class="mgrid-form-control" value="0">
      </div>
      <div class="col-md-4">
        <label class="form-label">Min tier slug (blank = any)</label>
        <input type="text" name="eligible_tier" class="mgrid-form-control" placeholder="starter, emerging, growth, investment_ready">
      </div>
      <div class="col-md-4">
        <label class="form-label">Profile completion min %</label>
        <input type="number" name="requires_profile_complete_percent" class="mgrid-form-control" value="0" min="0" max="100">
      </div>
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="requires_verified_documents" id="rdoc">
          <label class="form-check-label" for="rdoc">Requires at least one verified document</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="allow_repeat_claims" id="arep">
          <label class="form-check-label" for="arep">Allow repeat claims (after redeem, new claim allowed if no pending/approved)</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" id="act" checked>
          <label class="form-check-label" for="act">Active</label>
        </div>
      </div>
      <div class="col-12">
        <label class="form-label">Redemption method</label>
        <textarea name="redemption_method" class="mgrid-form-control" rows="2"></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Valid from *</label>
        <input type="date" name="valid_from" class="mgrid-form-control" required value="<?= e(date('Y-m-d')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Valid to *</label>
        <input type="date" name="valid_to" class="mgrid-form-control" required value="<?= e(date('Y-m-d', strtotime('+1 year'))) ?>">
      </div>
      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn-mgrid btn-mgrid-primary">Save offer</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('admin/admin_benefits.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
