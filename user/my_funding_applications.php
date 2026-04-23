<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';
$auth = auth_user();
$uid = (int) $auth['user_id'];
$pdo = db();

$stmt = $pdo->prepare('
  SELECT id, reference_number, application_type, requested_amount, status, submitted_at
  FROM funding_applications
  WHERE user_id = :uid
  ORDER BY submitted_at DESC
');
$stmt->execute(['uid' => $uid]);
$rows = $stmt->fetchAll() ?: [];

$mgrid_page_title = mgrid_title('title.my_funding');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-card">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title"><i class="ti ti-list-details"></i> My Funding Applications</h1>
    <div class="d-flex gap-2">
      <a class="btn-mgrid btn-mgrid-outline" href="<?= e(url('user/funding_overview.php')) ?>">Overview</a>
      <a class="btn-mgrid btn-mgrid-primary" href="<?= e(url('user/apply_funding.php')) ?>">Apply</a>
    </div>
  </div>
  <div class="mgrid-card-body p-0">
    <div class="table-responsive">
      <table class="mgrid-table">
        <thead><tr><th>Reference</th><th>Type</th><th>Requested</th><th>Submitted</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php if ($rows === []): ?>
            <tr><td colspan="6" class="text-center" style="padding:24px;">No applications submitted yet.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="mgrid-table-mid-cell"><?= e((string) $r['reference_number']) ?></td>
              <td><?= e(strtoupper((string) $r['application_type'])) ?></td>
              <td>TZS <?= number_format((float) $r['requested_amount'], 2) ?></td>
              <td><?= e(substr((string) $r['submitted_at'], 0, 16)) ?></td>
              <td><span class="badge text-bg-<?= e(mfund_status_badge((string) $r['status'])) ?>"><?= e(mfund_status_label((string) $r['status'])) ?></span></td>
              <td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('user/funding_application_detail.php?id=' . (int) $r['id'])) ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
