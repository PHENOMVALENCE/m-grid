<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

if (!opportunities_module_ready($pdo)) {
    flash_set('error', __('error.schema_missing'));
    redirect('admin/admin_opportunities.php');
}

$cats = $pdo->query('SELECT id, name FROM opportunity_categories WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll() ?: [];
$types = ['grant', 'job', 'internship', 'fellowship', 'accelerator', 'tender', 'webinar', 'workshop', 'training_program', 'other'];
$formats = ['physical', 'online', 'hybrid', 'unspecified'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_verify(is_string($token) ? $token : null)) {
        flash_set('error', __('settings.error.token'));
        redirect('admin/add_opportunity.php');
    }
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $title = clean_string($_POST['title'] ?? '');
    $slug = strtolower(preg_replace('/[^a-z0-9\-]+/', '-', clean_string($_POST['slug'] ?? '')) ?? '');
    $slug = trim($slug, '-');
    $otype = clean_string($_POST['opportunity_type'] ?? 'other');
    if (!in_array($otype, $types, true)) {
        $otype = 'other';
    }
    $provider = clean_string($_POST['provider_name'] ?? '');
    $desc = clean_string($_POST['description'] ?? '');
    $req = clean_string($_POST['requirements'] ?? '');
    $df = clean_string($_POST['deadline'] ?? '');
    $sf = clean_string($_POST['start_date'] ?? '');
    $ef = clean_string($_POST['end_date'] ?? '');
    $loc = clean_string($_POST['location'] ?? '');
    $fmt = clean_string($_POST['format'] ?? 'unspecified');
    if (!in_array($fmt, $formats, true)) {
        $fmt = 'unspecified';
    }
    $ext = clean_string($_POST['external_link'] ?? '');
    $appm = clean_string($_POST['application_method'] ?? '');
    $applyIn = isset($_POST['apply_internal']) ? 1 : 0;
    $active = isset($_POST['is_active']) ? 1 : 0;
    $arch = isset($_POST['is_archived']) ? 1 : 0;

    if ($categoryId <= 0 || $title === '' || $slug === '' || $provider === '' || $desc === '') {
        flash_set('error', __('error.fill_required'));
    } else {
        try {
            $pdo->prepare('
                INSERT INTO opportunities (
                  category_id, title, slug, opportunity_type, provider_name, description, requirements,
                  start_date, end_date, deadline, location, format, external_link, application_method,
                  apply_internal, is_active, is_archived
                ) VALUES (
                  :cid,:title,:slug,:ot,:prov,:d,:r,:sd,:ed,:dl,:loc,:fmt,:ext,:appm,:ain,:act,:arch
                )
            ')->execute([
                'cid' => $categoryId,
                'title' => $title,
                'slug' => $slug,
                'ot' => $otype,
                'prov' => $provider,
                'd' => $desc,
                'r' => $req !== '' ? $req : null,
                'sd' => $sf !== '' ? $sf : null,
                'ed' => $ef !== '' ? $ef : null,
                'dl' => $df !== '' ? $df : null,
                'loc' => $loc !== '' ? $loc : null,
                'fmt' => $fmt,
                'ext' => $ext !== '' ? $ext : null,
                'appm' => $appm !== '' ? $appm : null,
                'ain' => $applyIn,
                'act' => $active,
                'arch' => $arch,
            ]);
            flash_set('success', __('admin.generic.created'));
            redirect('admin/admin_opportunities.php');
        } catch (Throwable $e) {
            flash_set('error', __('error.save_duplicate_slug'));
        }
    }
}

$mgrid_page_title = mgrid_title('title.add_opp');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card">
  <div class="mgrid-card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6"><label class="form-label">Category *</label>
        <select name="category_id" class="mgrid-form-control" required><?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= e((string) $c['name']) ?></option>
        <?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Type *</label>
        <select name="opportunity_type" class="mgrid-form-control"><?php foreach ($types as $t): ?>
          <option value="<?= e($t) ?>"><?= e($t) ?></option>
        <?php endforeach; ?></select></div>
      <div class="col-md-8"><label class="form-label">Title *</label><input class="mgrid-form-control" name="title" required maxlength="220"></div>
      <div class="col-md-4"><label class="form-label">Slug *</label><input class="mgrid-form-control" name="slug" required maxlength="120" pattern="[a-z0-9\-]+"></div>
      <div class="col-12"><label class="form-label">Provider *</label><input class="mgrid-form-control" name="provider_name" required maxlength="200"></div>
      <div class="col-12"><label class="form-label">Description *</label><textarea name="description" class="mgrid-form-control" rows="4" required></textarea></div>
      <div class="col-12"><label class="form-label">Requirements</label><textarea name="requirements" class="mgrid-form-control" rows="2"></textarea></div>
      <div class="col-md-4"><label class="form-label">Deadline</label><input type="date" name="deadline" class="mgrid-form-control"></div>
      <div class="col-md-4"><label class="form-label">Start date</label><input type="date" name="start_date" class="mgrid-form-control"></div>
      <div class="col-md-4"><label class="form-label">End date</label><input type="date" name="end_date" class="mgrid-form-control"></div>
      <div class="col-md-6"><label class="form-label">Location</label><input class="mgrid-form-control" name="location" maxlength="240"></div>
      <div class="col-md-6"><label class="form-label">Format</label>
        <select name="format" class="mgrid-form-control"><?php foreach ($formats as $f): ?><option value="<?= e($f) ?>"><?= e($f) ?></option><?php endforeach; ?></select></div>
      <div class="col-12"><label class="form-label">External link</label><input class="mgrid-form-control" name="external_link" type="url" maxlength="500"></div>
      <div class="col-12"><label class="form-label">Application method</label><textarea name="application_method" class="mgrid-form-control" rows="2"></textarea></div>
      <div class="col-12">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="apply_internal" id="ain" checked><label class="form-check-label" for="ain">Allow apply on M-GRID</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="act" checked><label class="form-check-label" for="act">Active</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_archived" id="arc"><label class="form-check-label" for="arc">Archived</label></div>
      </div>
      <div class="col-12"><button class="btn-mgrid btn-mgrid-primary">Save</button> <a href="<?= e(url('admin/admin_opportunities.php')) ?>" class="btn btn-outline-secondary">Cancel</a></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
