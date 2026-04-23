<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';
$auth = auth_user();
$uid = (int) $auth['user_id'];
$appId = (int) ($_GET['id'] ?? 0);
$pdo = db();

$appStmt = $pdo->prepare('SELECT * FROM funding_applications WHERE id = :id AND user_id = :uid LIMIT 1');
$appStmt->execute(['id' => $appId, 'uid' => $uid]);
$app = $appStmt->fetch();
if (!$app) {
    flash_set('error', __('fund.review.not_found'));
    redirect('user/my_funding_applications.php');
}

$logsStmt = $pdo->prepare('SELECT old_status, new_status, note, created_at FROM funding_status_logs WHERE application_id = :id ORDER BY created_at DESC');
$logsStmt->execute(['id' => $appId]);
$logs = $logsStmt->fetchAll() ?: [];

$repSchedStmt = $pdo->prepare('SELECT * FROM funding_repayment_schedules WHERE application_id = :id ORDER BY installment_number ASC');
$repSchedStmt->execute(['id' => $appId]);
$schedules = $repSchedStmt->fetchAll() ?: [];
$totals = fundingRepaymentTotals($pdo, $appId);

$mgrid_page_title = mgrid_title('title.funding_detail');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title"><i class="ti ti-file-description"></i> Application <?= e((string) $app['reference_number']) ?></h1>
    <a class="btn-mgrid btn-mgrid-ghost" href="<?= e(url('user/my_funding_applications.php')) ?>">Back</a>
  </div>
  <div class="mgrid-card-body">
    <div class="mgrid-grid-2">
      <div>
        <p class="mb-1"><strong>Type:</strong> <?= e(strtoupper((string) $app['application_type'])) ?></p>
        <p class="mb-1"><strong>Requested:</strong> TZS <?= number_format((float) $app['requested_amount'], 2) ?></p>
        <p class="mb-1"><strong>Business:</strong> <?= e((string) $app['business_name']) ?></p>
        <p class="mb-1"><strong>Sector:</strong> <?= e((string) $app['business_sector']) ?></p>
      </div>
      <div>
        <p class="mb-1"><strong>Status:</strong> <span class="badge text-bg-<?= e(mfund_status_badge((string) $app['status'])) ?>"><?= e(mfund_status_label((string) $app['status'])) ?></span></p>
        <p class="mb-1"><strong>Submitted:</strong> <?= e(substr((string) $app['submitted_at'], 0, 16)) ?></p>
        <p class="mb-1"><strong>Purpose:</strong> <?= e((string) $app['purpose']) ?></p>
        <p class="mb-0"><strong>Admin Remark:</strong> <?= e((string) ($app['current_admin_remark'] ?? '—')) ?></p>
        <?php if (!empty($app['supporting_document_path'])): ?>
          <p class="mb-0"><strong>Supporting File:</strong> <a href="<?= e(url('funding_document_view.php?application_id=' . (int) $app['id'])) ?>" target="_blank">View</a></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="mgrid-grid-2">
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-timeline"></i> Status Timeline</h2></div>
    <div class="mgrid-card-body">
      <?php if ($logs === []): ?>
        <p class="text-muted">No status logs yet.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($logs as $l): ?>
            <li class="mb-2 pb-2 border-bottom">
              <strong><?= e(mfund_status_label((string) $l['new_status'])) ?></strong>
              <?php if (!empty($l['old_status'])): ?><span class="small text-muted"> (from <?= e(mfund_status_label((string) $l['old_status'])) ?>)</span><?php endif; ?>
              <div class="small text-muted"><?= e(substr((string) $l['created_at'], 0, 16)) ?></div>
              <?php if (!empty($l['note'])): ?><div class="small"><?= e((string) $l['note']) ?></div><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-calendar-dollar"></i> Repayment</h2></div>
    <div class="mgrid-card-body">
      <p class="mb-1"><strong>Total Expected:</strong> TZS <?= number_format((float) $totals['expected_total'], 2) ?></p>
      <p class="mb-1"><strong>Total Paid:</strong> TZS <?= number_format((float) $totals['paid_total'], 2) ?></p>
      <p class="mb-1"><strong>Balance:</strong> TZS <?= number_format((float) $totals['balance'], 2) ?></p>
      <p class="mb-2"><strong>Overdue Installments:</strong> <?= (int) $totals['overdue_count'] ?></p>

      <?php if ($schedules !== []): ?>
        <div class="table-responsive">
          <table class="mgrid-table">
            <thead><tr><th>#</th><th>Due Date</th><th>Expected</th><th>Paid</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($schedules as $s): ?>
                <tr>
                  <td><?= (int) $s['installment_number'] ?></td>
                  <td><?= e((string) $s['due_date']) ?></td>
                  <td><?= number_format((float) $s['expected_amount'], 2) ?></td>
                  <td><?= number_format((float) $s['paid_amount'], 2) ?></td>
                  <td><span class="badge text-bg-<?= e(mfund_status_badge((string) $s['status'])) ?>"><?= e(ucfirst((string) $s['status'])) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
