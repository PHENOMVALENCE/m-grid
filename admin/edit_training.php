<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

if (!trainings_module_ready($pdo) || $id <= 0) {
    redirect('admin/admin_trainings.php');
}

$st = $pdo->prepare('SELECT * FROM training_programs WHERE id = :id LIMIT 1');
$st->execute(['id' => $id]);
$p = $st->fetch();
if (!$p) {
    redirect('admin/admin_trainings.php');
}

$types = ['course', 'workshop', 'webinar', 'cohort', 'mentorship', 'certification', 'other'];
$formats = ['physical', 'online', 'hybrid', 'unspecified'];

function ot_dt_local(?string $mysqlDt): string
{
    if ($mysqlDt === null || $mysqlDt === '') {
        return '';
    }
    $ts = strtotime($mysqlDt);
    if ($ts === false) {
        return '';
    }
    return date('Y-m-d\TH:i', $ts);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_verify(is_string($token) ? $token : null)) {
        flash_set('error', __('settings.error.token'));
        redirect('admin/edit_training.php?id=' . $id);
    }
    $title = clean_string($_POST['title'] ?? '');
    $slug = strtolower(preg_replace('/[^a-z0-9\-]+/', '-', clean_string($_POST['slug'] ?? '')) ?? '');
    $slug = trim($slug, '-');
    $tt = clean_string($_POST['training_type'] ?? 'course');
    if (!in_array($tt, $types, true)) {
        $tt = 'course';
    }
    $prov = clean_string($_POST['provider_name'] ?? '');
    $trainer = clean_string($_POST['trainer_name'] ?? '');
    $desc = clean_string($_POST['description'] ?? '');
    $elig = clean_string($_POST['eligibility'] ?? '');
    $ss = clean_string($_POST['schedule_start'] ?? '');
    $se = clean_string($_POST['schedule_end'] ?? '');
    $dur = clean_string($_POST['duration_label'] ?? '');
    $loc = clean_string($_POST['location'] ?? '');
    $fmt = clean_string($_POST['format'] ?? 'online');
    if (!in_array($fmt, $formats, true)) {
        $fmt = 'online';
    }
    $ext = clean_string($_POST['external_link'] ?? '');
    $regIn = isset($_POST['register_internal']) ? 1 : 0;
    $act = isset($_POST['is_active']) ? 1 : 0;
    $arch = isset($_POST['is_archived']) ? 1 : 0;

    if ($title === '' || $slug === '' || $prov === '' || $desc === '') {
        flash_set('error', __('error.required_fields'));
    } else {
        try {
            $pdo->prepare('
                UPDATE training_programs SET
                  title=:t, slug=:slug, training_type=:tt, provider_name=:prov, trainer_name=:tr,
                  description=:d, eligibility=:e, schedule_start=:ss, schedule_end=:se,
                  duration_label=:dur, location=:loc, format=:fmt, external_link=:ext,
                  register_internal=:rin, is_active=:act, is_archived=:arch
                WHERE id=:id LIMIT 1
            ')->execute([
                't' => $title,
                'slug' => $slug,
                'tt' => $tt,
                'prov' => $prov,
                'tr' => $trainer !== '' ? $trainer : null,
                'd' => $desc,
                'e' => $elig !== '' ? $elig : null,
                'ss' => $ss !== '' ? str_replace('T', ' ', $ss) : null,
                'se' => $se !== '' ? str_replace('T', ' ', $se) : null,
                'dur' => $dur !== '' ? $dur : null,
                'loc' => $loc !== '' ? $loc : null,
                'fmt' => $fmt,
                'ext' => $ext !== '' ? $ext : null,
                'rin' => $regIn,
                'act' => $act,
                'arch' => $arch,
                'id' => $id,
            ]);
            flash_set('success', __('admin.generic.saved'));
            redirect('admin/admin_trainings.php');
        } catch (Throwable $e) {
            flash_set('error', __('error.save_failed'));
        }
    }
    $st->execute(['id' => $id]);
    $p = $st->fetch() ?: $p;
}

$mgrid_page_title = mgrid_title('title.edit_training');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card">
  <div class="mgrid-card-body">
    <form method="post" class="row g-3">
      <?= csrf_field() ?>
      <div class="col-md-8"><label class="form-label">Title *</label><input class="mgrid-form-control" name="title" required maxlength="220" value="<?= e((string) $p['title']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Slug *</label><input class="mgrid-form-control" name="slug" required maxlength="120" value="<?= e((string) $p['slug']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Type</label><select name="training_type" class="mgrid-form-control"><?php foreach ($types as $t): ?>
        <option value="<?= e($t) ?>" <?= ((string) $p['training_type']) === $t ? 'selected' : '' ?>><?= e($t) ?></option>
      <?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Format</label><select name="format" class="mgrid-form-control"><?php foreach ($formats as $f): ?>
        <option value="<?= e($f) ?>" <?= ((string) $p['format']) === $f ? 'selected' : '' ?>><?= e($f) ?></option>
      <?php endforeach; ?></select></div>
      <div class="col-md-6"><label class="form-label">Provider *</label><input class="mgrid-form-control" name="provider_name" required maxlength="200" value="<?= e((string) $p['provider_name']) ?>"></div>
      <div class="col-md-6"><label class="form-label">Trainer</label><input class="mgrid-form-control" name="trainer_name" maxlength="200" value="<?= e((string) ($p['trainer_name'] ?? '')) ?>"></div>
      <div class="col-12"><label class="form-label">Description *</label><textarea name="description" class="mgrid-form-control" rows="4" required><?= e((string) $p['description']) ?></textarea></div>
      <div class="col-12"><label class="form-label">Eligibility</label><textarea name="eligibility" class="mgrid-form-control" rows="2"><?= e((string) ($p['eligibility'] ?? '')) ?></textarea></div>
      <div class="col-md-6"><label class="form-label">Schedule start</label><input type="datetime-local" name="schedule_start" class="mgrid-form-control" value="<?= e(ot_dt_local($p['schedule_start'] ?? null)) ?>"></div>
      <div class="col-md-6"><label class="form-label">Schedule end</label><input type="datetime-local" name="schedule_end" class="mgrid-form-control" value="<?= e(ot_dt_local($p['schedule_end'] ?? null)) ?>"></div>
      <div class="col-md-6"><label class="form-label">Duration label</label><input class="mgrid-form-control" name="duration_label" maxlength="120" value="<?= e((string) ($p['duration_label'] ?? '')) ?>"></div>
      <div class="col-md-6"><label class="form-label">Location</label><input class="mgrid-form-control" name="location" maxlength="240" value="<?= e((string) ($p['location'] ?? '')) ?>"></div>
      <div class="col-12"><label class="form-label">External link</label><input class="mgrid-form-control" type="url" name="external_link" value="<?= e((string) ($p['external_link'] ?? '')) ?>"></div>
      <div class="col-12">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="register_internal" id="ri" <?= (int) $p['register_internal'] ? 'checked' : '' ?>><label class="form-check-label" for="ri">Register on M-GRID</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" id="act" <?= (int) $p['is_active'] ? 'checked' : '' ?>><label class="form-check-label" for="act">Active</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_archived" id="ar" <?= (int) $p['is_archived'] ? 'checked' : '' ?>><label class="form-check-label" for="ar">Archived</label></div>
      </div>
      <div class="col-12"><button class="btn-mgrid btn-mgrid-primary">Save</button> <a href="<?= e(url('admin/admin_trainings.php')) ?>" class="btn btn-outline-secondary">Back</a></div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
