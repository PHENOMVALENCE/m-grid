<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();
$appId = (int) ($_GET['application_id'] ?? 0);

$stmt = $pdo->prepare('
  SELECT f.id, f.reference_number, f.status, f.user_id, u.full_name, u.m_id
  FROM funding_applications f
  INNER JOIN users u ON u.id = f.user_id
  WHERE f.id = :id
  LIMIT 1
');
$stmt->execute(['id' => $appId]);
$app = $stmt->fetch();
if (!$app) {
    flash_set('error', __('fund.review.not_found'));
    redirect('admin/admin_funding_applications.php');
}

$schedStmt = $pdo->prepare('SELECT * FROM funding_repayment_schedules WHERE application_id = :id ORDER BY installment_number ASC');
$schedStmt->execute(['id' => $appId]);
$schedules = $schedStmt->fetchAll() ?: [];

$logsStmt = $pdo->prepare('SELECT * FROM funding_repayment_logs WHERE application_id = :id ORDER BY payment_date DESC, id DESC');
$logsStmt->execute(['id' => $appId]);
$repayLogs = $logsStmt->fetchAll() ?: [];
$totals = fundingRepaymentTotals($pdo, $appId);

$mgrid_page_title = mgrid_title('title.manage_repayments');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title"><i class="ti ti-calendar-dollar"></i> Repayments — <?= e((string) $app['reference_number']) ?></h1>
    <a class="btn-mgrid btn-mgrid-ghost" href="<?= e(url('admin/admin_funding_review.php?id=' . (int) $app['id'])) ?>">Back to Review</a>
  </div>
  <div class="mgrid-card-body">
    <div class="mgrid-grid-4">
      <div class="mgrid-stat-card"><div class="mgrid-stat-label">Expected</div><div class="mgrid-stat-value"><?= number_format((float) $totals['expected_total'], 2) ?></div></div>
      <div class="mgrid-stat-card"><div class="mgrid-stat-label">Paid</div><div class="mgrid-stat-value"><?= number_format((float) $totals['paid_total'], 2) ?></div></div>
      <div class="mgrid-stat-card"><div class="mgrid-stat-label">Balance</div><div class="mgrid-stat-value"><?= number_format((float) $totals['balance'], 2) ?></div></div>
      <div class="mgrid-stat-card"><div class="mgrid-stat-label">Overdue</div><div class="mgrid-stat-value"><?= (int) $totals['overdue_count'] ?></div></div>
    </div>
  </div>
</div>

<div class="mgrid-grid-2">
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-list-check"></i> Schedule</h2></div>
    <div class="mgrid-card-body p-0">
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
    </div>
  </div>

  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-plus"></i> Record Repayment</h2></div>
    <div class="mgrid-card-body">
      <form method="post" action="<?= e(url('record_repayment.php')) ?>" class="row g-2">
        <?= csrf_field() ?>
        <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
        <div class="col-md-6">
          <label class="mgrid-form-label">Schedule (optional)</label>
          <select name="schedule_id" class="mgrid-form-control">
            <option value="">Select installment</option>
            <?php foreach ($schedules as $s): ?>
              <option value="<?= (int) $s['id'] ?>">#<?= (int) $s['installment_number'] ?> - due <?= e((string) $s['due_date']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="mgrid-form-label">Amount Paid</label><input name="amount_paid" type="number" step="0.01" min="0.01" class="mgrid-form-control" required></div>
        <div class="col-md-6"><label class="mgrid-form-label">Payment Date</label><input name="payment_date" type="date" class="mgrid-form-control" required></div>
        <div class="col-md-6"><label class="mgrid-form-label">Payment Method</label><input name="payment_method" type="text" class="mgrid-form-control" required></div>
        <div class="col-12"><label class="mgrid-form-label">Reference Note</label><input name="reference_note" type="text" class="mgrid-form-control"></div>
        <div class="col-12"><label class="mgrid-form-label">Remarks</label><input name="remarks" type="text" class="mgrid-form-control"></div>
        <div class="col-12 d-flex justify-content-end"><button class="btn-mgrid btn-mgrid-primary">Record Payment</button></div>
      </form>

      <hr>
      <h3 class="h6">Repayment Logs</h3>
      <?php if ($repayLogs === []): ?><p class="text-muted mb-0">No repayment logs yet.</p><?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($repayLogs as $l): ?>
            <li class="mb-2 pb-2 border-bottom">
              <strong>TZS <?= number_format((float) $l['amount_paid'], 2) ?></strong> on <?= e((string) $l['payment_date']) ?>
              <div class="small text-muted"><?= e((string) $l['payment_method']) ?><?= !empty($l['reference_note']) ? ' · ' . e((string) $l['reference_note']) : '' ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
