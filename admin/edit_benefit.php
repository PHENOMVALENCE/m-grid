<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

if (!mbenefits_module_ready($pdo) || $id <= 0) {
    flash_set('error', __('ben.edit.invalid'));
    redirect('admin/admin_benefits.php');
}

$st = $pdo->prepare('SELECT * FROM benefit_offers WHERE id = :id LIMIT 1');
$st->execute(['id' => $id]);
$offer = $st->fetch();
if (!$offer) {
    flash_set('error', __('ben.edit.not_found'));
    redirect('admin/admin_benefits.php');
}

$cats = $pdo->query('SELECT id, name FROM benefit_categories ORDER BY sort_order, name')->fetchAll() ?: [];
$provs = $pdo->query('SELECT id, name FROM benefit_providers ORDER BY name')->fetchAll() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_verify(is_string($token) ? $token : null)) {
        flash_set('error', __('settings.error.token'));
        redirect('admin/edit_benefit.php?id=' . $id);
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
    $tierRaw = clean_string($_POST['eligible_tier'] ?? '');
    $tier = $tierRaw === '' ? null : strtolower(preg_replace('/[^a-z0-9_]+/', '_', $tierRaw) ?? '');
    $reqDocs = isset($_POST['requires_verified_documents']) ? 1 : 0;
    $profPct = max(0, min(100, (int) ($_POST['requires_profile_complete_percent'] ?? 0)));
    $allowRepeat = isset($_POST['allow_repeat_claims']) ? 1 : 0;
    $redemption = clean_string($_POST['redemption_method'] ?? '');
    $validFrom = clean_string($_POST['valid_from'] ?? '');
    $validTo = clean_string($_POST['valid_to'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($title === '' || $slug === '' || $short === '' || $valueLabel === '' || $validFrom === '' || $validTo === '' || $categoryId <= 0 || $providerId <= 0) {
        flash_set('error', __('error.fill_required'));
    } else {
        try {
            $up = $pdo->prepare('
                UPDATE benefit_offers SET
                  category_id = :cid, provider_id = :pid, title = :title, slug = :slug,
                  short_description = :shortd, full_description = :fulld, terms_and_conditions = :terms,
                  benefit_type = :btype, value_label = :vlabel, value_numeric = :vnum, min_mscore = :minsc, eligible_tier = :tier,
                  requires_verified_documents = :rdoc, requires_profile_complete_percent = :ppct, allow_repeat_claims = :arep,
                  redemption_method = :redm, valid_from = :vf, valid_to = :vt, is_active = :act
                WHERE id = :id LIMIT 1
            ');
            $up->execute([
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
                'id' => $id,
            ]);
            flash_set('success', __('ben.offer.updated'));
            redirect('admin/admin_benefits.php');
        } catch (Throwable $e) {
            flash_set('error', __('error.save_duplicate_slug'));
        }
    }
    $st->execute(['id' => $id]);
    $offer = $st->fetch() ?: $offer;
}

$mgrid_page_title = mgrid_title('title.edit_benefit');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card">
  <div class="mgrid-card-header"><h1 class="mgrid-card-title">Edit offer</h1></div>
  <div class="mgrid-card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6">
        <label class="form-label">Category *</label>
        <select name="category_id" class="mgrid-form-control" required>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $offer['category_id'] ? 'selected' : '' ?>><?= e((string) $c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Provider *</label>
        <select name="provider_id" class="mgrid-form-control" required>
          <?php foreach ($provs as $p): ?>
            <option value="<?= (int) $p['id'] ?>" <?= (int) $p['id'] === (int) $offer['provider_id'] ? 'selected' : '' ?>><?= e((string) $p['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-8">
        <label class="form-label">Title *</label>
        <input type="text" name="title" class="mgrid-form-control" required maxlength="200" value="<?= e((string) $offer['title']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Slug *</label>
        <input type="text" name="slug" class="mgrid-form-control" required maxlength="100" value="<?= e((string) $offer['slug']) ?>" pattern="[a-z0-9\-]+">
      </div>
      <div class="col-12">
        <label class="form-label">Short description *</label>
        <input type="text" name="short_description" class="mgrid-form-control" required maxlength="500" value="<?= e((string) $offer['short_description']) ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Full description</label>
        <textarea name="full_description" class="mgrid-form-control" rows="4"><?= e((string) ($offer['full_description'] ?? '')) ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label">Terms &amp; conditions</label>
        <textarea name="terms_and_conditions" class="mgrid-form-control" rows="3"><?= e((string) ($offer['terms_and_conditions'] ?? '')) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label">Benefit type</label>
        <select name="benefit_type" class="mgrid-form-control">
          <?php foreach (['discount', 'credit', 'voucher', 'service', 'other'] as $t): ?>
            <option value="<?= e($t) ?>" <?= ((string) $offer['benefit_type']) === $t ? 'selected' : '' ?>><?= e(mbenefits_benefit_type_label($t)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Value label *</label>
        <input type="text" name="value_label" class="mgrid-form-control" required maxlength="120" value="<?= e((string) $offer['value_label']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Value numeric</label>
        <input type="number" step="0.01" name="value_numeric" class="mgrid-form-control" value="<?= $offer['value_numeric'] !== null ? e((string) $offer['value_numeric']) : '' ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Min M-SCORE</label>
        <input type="number" step="0.01" name="min_mscore" class="mgrid-form-control" value="<?= e((string) $offer['min_mscore']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Min tier slug</label>
        <input type="text" name="eligible_tier" class="mgrid-form-control" value="<?= e((string) ($offer['eligible_tier'] ?? '')) ?>" placeholder="growth, emerging, …">
      </div>
      <div class="col-md-4">
        <label class="form-label">Profile completion min %</label>
        <input type="number" name="requires_profile_complete_percent" class="mgrid-form-control" value="<?= (int) ($offer['requires_profile_complete_percent'] ?? 0) ?>" min="0" max="100">
      </div>
      <div class="col-12">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="requires_verified_documents" id="rdoc" <?= (int) ($offer['requires_verified_documents'] ?? 0) === 1 ? 'checked' : '' ?>>
          <label class="form-check-label" for="rdoc">Requires verified document(s)</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="allow_repeat_claims" id="arep" <?= (int) ($offer['allow_repeat_claims'] ?? 0) === 1 ? 'checked' : '' ?>>
          <label class="form-check-label" for="arep">Allow repeat claims</label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_active" id="act" <?= (int) ($offer['is_active'] ?? 0) === 1 ? 'checked' : '' ?>>
          <label class="form-check-label" for="act">Active</label>
        </div>
      </div>
      <div class="col-12">
        <label class="form-label">Redemption method</label>
        <textarea name="redemption_method" class="mgrid-form-control" rows="2"><?= e((string) ($offer['redemption_method'] ?? '')) ?></textarea>
      </div>
      <div class="col-md-6">
        <label class="form-label">Valid from *</label>
        <input type="date" name="valid_from" class="mgrid-form-control" required value="<?= e((string) $offer['valid_from']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Valid to *</label>
        <input type="date" name="valid_to" class="mgrid-form-control" required value="<?= e((string) $offer['valid_to']) ?>">
      </div>
      <div class="col-12 d-flex gap-2">
        <button type="submit" class="btn-mgrid btn-mgrid-primary">Save</button>
        <a class="btn btn-outline-secondary" href="<?= e(url('admin/admin_benefits.php')) ?>">Back</a>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
