<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$ready = trainings_module_ready($pdo);

$rows = [];
if ($ready) {
    $st = $pdo->prepare('
        SELECT r.*, p.title, p.training_type, p.provider_name, p.schedule_start
        FROM training_registrations r
        INNER JOIN training_programs p ON p.id = r.training_program_id
        WHERE r.user_id = :u
        ORDER BY r.applied_at DESC
        LIMIT 100
    ');
    $st->execute(['u' => $uid]);
    $rows = $st->fetchAll() ?: [];
}

$mgrid_page_title = mgrid_title('title.my_trainings');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2">
    <h1 class="mgrid-display mb-0" style="font-size:1.5rem;">My training registrations</h1>
    <a class="btn-mgrid btn-mgrid-primary btn-sm" href="<?= e(url('user/trainings.php')) ?>">Browse</a>
  </div>
</div>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">Module not installed.</div>
<?php elseif ($rows === []): ?>
  <div class="mgrid-card"><div class="mgrid-card-body text-muted">No registrations yet.</div></div>
<?php else: ?>
  <div class="mgrid-card">
    <div class="mgrid-card-body p-0">
      <div class="table-responsive">
        <table class="mgrid-table mb-0">
          <thead>
            <tr>
              <th>Programme</th>
              <th>Type</th>
              <th>Schedule</th>
              <th>Registered</th>
              <th>Application</th>
              <th>Participation</th>
              <th>Certificate</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><strong><?= e((string) $r['title']) ?></strong><div class="small text-muted"><?= e((string) $r['provider_name']) ?></div></td>
                <td><?= e(ucfirst((string) $r['training_type'])) ?></td>
                <td class="small"><?= $r['schedule_start'] ? e(substr((string) $r['schedule_start'], 0, 16)) : '—' ?></td>
                <td class="small"><?= e(substr((string) $r['applied_at'], 0, 16)) ?></td>
                <td><span class="badge text-bg-<?= e(training_registration_status_badge((string) $r['status'])) ?>"><?= e(training_registration_status_label((string) $r['status'])) ?></span></td>
                <td><span class="badge text-bg-<?= e(training_participation_status_badge((string) $r['participation_status'])) ?>"><?= e(training_participation_status_label((string) $r['participation_status'])) ?></span></td>
                <td><span class="badge text-bg-<?= e(training_certificate_status_badge((string) $r['certificate_status'])) ?>"><?= e(training_certificate_status_label((string) $r['certificate_status'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
