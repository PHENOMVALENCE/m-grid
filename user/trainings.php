<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$ready = trainings_module_ready($pdo);

$type = clean_string($_GET['type'] ?? '');
$format = clean_string($_GET['format'] ?? '');
$list = clean_string($_GET['listing'] ?? '');
$q = clean_string($_GET['q'] ?? '');

$types = ['course', 'workshop', 'webinar', 'cohort', 'mentorship', 'certification', 'other'];
$formats = ['physical', 'online', 'hybrid', 'unspecified'];
$list = in_array($list, ['active', 'past', ''], true) ? $list : '';

$rows = $ready ? trainings_list_for_public(
    $pdo,
    $type !== '' ? $type : null,
    $format !== '' ? $format : null,
    $list !== '' ? $list : null,
    $q !== '' ? $q : null
) : [];

$mgrid_page_title = mgrid_title('title.trainings');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">Import <code>database/m_grid_opportunities_training.sql</code> to enable this module.</div>
<?php else: ?>
  <div class="mgrid-card mb-3">
    <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2">
      <div>
        <h1 class="mgrid-display mb-1" style="font-size:1.75rem;">Trainings &amp; workshops</h1>
        <p class="text-muted mb-0">Capacity-building sessions — online or in person.</p>
      </div>
      <a class="btn-mgrid btn-mgrid-outline align-self-center" href="<?= e(url('user/my_trainings.php')) ?>">My registrations</a>
    </div>
  </div>

  <form method="get" class="mgrid-card mb-3 p-3">
    <div class="row g-2">
      <div class="col-md-3">
        <label class="form-label small text-muted">Type</label>
        <select name="type" class="mgrid-form-control">
          <option value="">All</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e(ucfirst($t)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Format</label>
        <select name="format" class="mgrid-form-control">
          <option value="">Any</option>
          <?php foreach ($formats as $f): ?>
            <option value="<?= e($f) ?>" <?= $format === $f ? 'selected' : '' ?>><?= e(ucfirst($f)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Schedule</label>
        <select name="listing" class="mgrid-form-control">
          <option value="" <?= $list === '' ? 'selected' : '' ?>>All upcoming/past</option>
          <option value="active" <?= $list === 'active' ? 'selected' : '' ?>>Upcoming / ongoing</option>
          <option value="past" <?= $list === 'past' ? 'selected' : '' ?>>Past</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small text-muted">Search</label>
        <input type="search" name="q" class="mgrid-form-control" value="<?= e($q) ?>">
      </div>
      <div class="col-12"><button type="submit" class="btn-mgrid btn-mgrid-primary">Filter</button></div>
    </div>
  </form>

  <div class="mb-2"><a href="<?= e(url('user/opportunities.php')) ?>" class="small">← Browse opportunities</a></div>

  <div class="row g-3">
    <?php if ($rows === []): ?>
      <div class="col-12 text-muted">No programmes match your filters.</div>
    <?php endif; ?>
    <?php foreach ($rows as $p): ?>
      <?php $st = ot_training_listing_state($p); ?>
      <div class="col-md-6 col-lg-4">
        <div class="mgrid-card h-100">
          <div class="mgrid-card-body d-flex flex-column">
            <span class="badge text-bg-info w-fit mb-2"><?= e(ucfirst((string) $p['training_type'])) ?></span>
            <h2 class="h5"><?= e((string) $p['title']) ?></h2>
            <div class="small text-muted mb-2"><?= e((string) $p['provider_name']) ?></div>
            <?php $prev = strip_tags((string) $p['description']); if (strlen($prev) > 140) { $prev = substr($prev, 0, 137) . '…'; } ?>
            <p class="small flex-grow-1"><?= e($prev) ?></p>
            <div class="small mb-2">
              <div><strong>When:</strong> <?= $p['schedule_start'] ? e(substr((string) $p['schedule_start'], 0, 16)) : 'TBD' ?></div>
              <div><strong>Format:</strong> <?= e(ucfirst((string) $p['format'])) ?></div>
              <span class="badge text-bg-<?= $st === 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($st)) ?></span>
            </div>
            <a class="btn-mgrid btn-mgrid-primary mt-auto" href="<?= e(url('user/training_detail.php?id=' . (int) $p['id'])) ?>">Details</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
