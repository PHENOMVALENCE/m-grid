<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$ready = opportunities_module_ready($pdo);

$rows = [];
if ($ready) {
    $st = $pdo->prepare('
        SELECT a.*, o.title, o.opportunity_type, o.provider_name
        FROM opportunity_applications a
        INNER JOIN opportunities o ON o.id = a.opportunity_id
        WHERE a.user_id = :u
        ORDER BY a.applied_at DESC
        LIMIT 100
    ');
    $st->execute(['u' => $uid]);
    $rows = $st->fetchAll() ?: [];
}

$mgrid_page_title = mgrid_title('title.my_opps');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2">
    <h1 class="mgrid-display mb-0" style="font-size:1.5rem;">My opportunity applications</h1>
    <a class="btn-mgrid btn-mgrid-primary btn-sm" href="<?= e(url('user/opportunities.php')) ?>">Browse</a>
  </div>
</div>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">Module not installed.</div>
<?php elseif ($rows === []): ?>
  <div class="mgrid-card"><div class="mgrid-card-body text-muted">No applications yet.</div></div>
<?php else: ?>
  <div class="mgrid-card">
    <div class="mgrid-card-body p-0">
      <div class="table-responsive">
        <table class="mgrid-table mb-0">
          <thead>
            <tr>
              <th>Title</th>
              <th>Type</th>
              <th>Applied</th>
              <th>Status</th>
              <th>Completion</th>
              <th>Certificate</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td>
                  <strong><?= e((string) $r['title']) ?></strong>
                  <div class="small text-muted"><?= e((string) $r['provider_name']) ?></div>
                </td>
                <td><?= e(ucwords(str_replace('_', ' ', (string) $r['opportunity_type']))) ?></td>
                <td class="small"><?= e(substr((string) $r['applied_at'], 0, 16)) ?></td>
                <td><span class="badge text-bg-<?= e(opportunity_application_status_badge((string) $r['status'])) ?>"><?= e(opportunity_application_status_label((string) $r['status'])) ?></span></td>
                <td class="small"><?= e(opportunity_completion_status_label((string) $r['completion_status'])) ?></td>
                <td class="small"><?= e(opportunity_certificate_status_label((string) $r['certificate_status'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
