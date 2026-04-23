<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

if (!opportunities_module_ready($pdo) || $id <= 0) {
    redirect('admin/admin_opportunities.php');
}

$st = $pdo->prepare('SELECT * FROM opportunities WHERE id = :id LIMIT 1');
$st->execute(['id' => $id]);
$o = $st->fetch();
if (!$o) {
    redirect('admin/admin_opportunities.php');
}

$cats = $pdo->query('SELECT id, name FROM opportunity_categories ORDER BY sort_order, name')->fetchAll() ?: [];
$types = ['grant', 'job', 'internship', 'fellowship', 'accelerator', 'tender', 'webinar', 'workshop', 'training_program', 'other'];
$formats = ['physical', 'online', 'hybrid', 'unspecified'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_verify(is_string($token) ? $token : null)) {
        flash_set('error', __('settings.error.token'));
        redirect('admin/edit_opportunity.php?id=' . $id);
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
                UPDATE opportunities SET
                  category_id=:cid, title=:title, slug=:slug, opportunity_type=:ot, provider_name=:prov,
                  description=:d, requirements=:r, start_date=:sd, end_date=:ed, deadline=:dl,
                  location=:loc, format=:fmt, external_link=:ext, application_method=:appm,
                  apply_internal=:ain, is_active=:act, is_archived=:arch
                WHERE id=:id LIMIT 1
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
                'id' => $id,
            ]);
            flash_set('success', __('admin.generic.saved'));
            redirect('admin/admin_opportunities.php');
        } catch (Throwable $e) {
            flash_set('error', __('error.save_failed'));
        }
    }
    $st->execute(['id' => $id]);
    $o = $st->fetch() ?: $o;
}

$mgrid_page_title = mgrid_title('title.edit_opp');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card">
  <div class="mgrid-card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-6"><label class="form-label">Category *</label>
        <select name="category_id" class="mgrid-form-control" required><?php foreach ($cats as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= (int) $c['id'] === (int) $o['category_id'] ? 'selected' : '' ?>><?= e((string) $c['name']) ?></option>
        <?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Type</label>
        <select name="opportunity_type" class="mgrid-form-control"><?php foreach ($types as $t): ?>
          <option value="<?= e($t) ?>" <?= ((string) $o['opportunity_type']) === $t ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?></select></div>
      <div class="col-md-8"><label class="form-label">Title *</label><input class="mgrid-form-control" name="title" required maxlength="220" value="<?= e((string) $o['title']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Slug *</label><input class="mgrid-form-control" name="slug" required maxlength="120" value="<?= e((string) $o['slug']) ?>"></div>
      <div class="col-12"><label class="form-label">Provider *</label><input class="mgrid-form-control" name="provider_name" required maxlength="200" value="<?= e((string) $o['provider_name']) ?>"></div>
      <div class="col-12"><label class="form-label">Description *</label><textarea name="description" class="mgrid-form-control" rows="4" required><?= e((string) $o['description']) ?></textarea></div>
      <div class="col-12"><label class="form-label">Requirements</label><textarea name="requirements" class="mgrid-form-control" rows="2"><?= e((string) ($o['requirements'] ?? '')) ?></textarea></div>
      <div class="col-md-4"><label class="form-label">Deadline</label><input type="date" name="deadline" class="mgrid-form-control" value="<?= e((string) ($o['deadline'] ?? '')) ?>"></div>
      <div class="col-md-4"><label class="form-label">Start</label><input type="date" name="start_date" class="mgrid-form-control" value="<?= e((string) ($o['start_date'] ?? '')) ?>"></div>
      <div class="col-md-4"><label class="form-label">End</label><input type="date" name="end_date" class="mgrid-form-control" value="<?= e((string) ($o['end_date'] ?? '')) ?>"></div>
      <div class="col-md-6"><label class="form-label">Location</label><input class="mgrid-form-control" name="location" maxlength="240" value="<?= e((string) ($o['location'] ?? '')) ?>"></div>
      <div class="col-md-6"><label class="form-label">Format</label>
        <select name="format" class="mgrid-form-control"><?php foreach ($formats as $f): ?>
          <option value="<?= e($f) ?>" <?= ((string) $o['format']) === $f ? 'selected' : '' ?>><?= e($f) ?></option>
        <?php endforeach; ?></select></div>
      <div class="col-12"><label class="form-label">External link</label><input class="mgrid-form-control" name="external_link" type="url" value="<?= e((string) ($o['external_link'] ?? '')) ?>"></div>
      <div class="col-12"><label class="form-label">Application method</label><textarea name="application_method" class="mgrid-form-control" rows="2"><?= e((string) ($o['application_method'] ?? '')) ?></textarea></div>
      <div class="col-12">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="apply_internal" id="ain" <?= (int) $o['apply_internal'] ? 'checked' : '' ?>><label class="form-check-label" for="ain">Apply on M-GRID</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="act" <?= (int) $o['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="act">Active</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_archived" id="arc" <?= (int) $o['is_archived'] ? 'checked' : '' ?>><label class="form-check-label" for="arc">Archived</label></div>
      </div>
      <div class="col-12"><button class="btn-mgrid btn-mgrid-primary">Save</button> <a href="<?= e(url('admin/admin_opportunities.php')) ?>" class="btn btn-outline-secondary">Back</a></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
