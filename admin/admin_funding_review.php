<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('
  SELECT f.*, u.full_name, u.email, u.phone, COALESCE(ms.total_score,0) AS mscore, COALESCE(ms.tier_label,"Starter") AS mscore_tier
  FROM funding_applications f
  INNER JOIN users u ON u.id = f.user_id
  LEFT JOIN mscore_current_scores ms ON ms.user_id = f.user_id
  WHERE f.id = :id
  LIMIT 1
');
$stmt->execute(['id' => $id]);
$app = $stmt->fetch();
if (!$app) {
    flash_set('error', __('fund.review.not_found'));
    redirect('admin/admin_funding_applications.php');
}

$elig = checkFundingEligibility((int) $app['user_id']);
$logsStmt = $pdo->prepare('SELECT * FROM funding_status_logs WHERE application_id = :id ORDER BY created_at DESC');
$logsStmt->execute(['id' => $id]);
$logs = $logsStmt->fetchAll() ?: [];
$reviewsStmt = $pdo->prepare('SELECT r.*, a.full_name AS admin_name FROM funding_reviews r LEFT JOIN admins a ON a.id = r.admin_id WHERE application_id = :id ORDER BY action_at DESC');
$reviewsStmt->execute(['id' => $id]);
$reviews = $reviewsStmt->fetchAll() ?: [];

$mgrid_page_title = mgrid_title('title.admin_funding_review');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title"><i class="ti ti-file-analytics"></i> Funding Case <?= e((string) $app['reference_number']) ?></h1>
    <a href="<?= e(url('admin/admin_funding_applications.php')) ?>" class="btn-mgrid btn-mgrid-ghost">Back</a>
  </div>
  <div class="mgrid-card-body">
    <div class="mgrid-grid-2">
      <div>
        <h2 class="h6">Applicant Summary</h2>
        <p class="mb-1"><strong>Name:</strong> <?= e((string) $app['full_name']) ?></p>
        <p class="mb-1"><strong>M-ID:</strong> <?= e((string) $app['m_id']) ?></p>
        <p class="mb-1"><strong>M-SCORE:</strong> <?= number_format((float) $app['mscore'], 2) ?> (<?= e((string) $app['mscore_tier']) ?>)</p>
        <p class="mb-0"><strong>Status:</strong> <span class="badge text-bg-<?= e(mfund_status_badge((string) $app['status'])) ?>"><?= e(mfund_status_label((string) $app['status'])) ?></span></p>
      </div>
      <div>
        <h2 class="h6">Application Summary</h2>
        <p class="mb-1"><strong>Type:</strong> <?= e(strtoupper((string) $app['application_type'])) ?></p>
        <p class="mb-1"><strong>Requested:</strong> TZS <?= number_format((float) $app['requested_amount'], 2) ?></p>
        <p class="mb-1"><strong>Business:</strong> <?= e((string) $app['business_name']) ?> (<?= e((string) $app['business_sector']) ?>)</p>
        <p class="mb-0"><strong>Purpose:</strong> <?= e((string) $app['purpose']) ?></p>
      </div>
    </div>
    <hr>
    <h2 class="h6">Eligibility Snapshot</h2>
    <ul class="small">
      <?php foreach ($elig['checks'] as $c): ?>
        <li><span class="badge text-bg-<?= $c['ok'] ? 'success' : 'danger' ?>"><?= $c['ok'] ? 'OK' : 'Missing' ?></span> <?= e((string) $c['message']) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php if (!empty($app['supporting_document_path'])): ?>
      <p><strong>Supporting Document:</strong> <a href="<?= e(url('funding_document_view.php?application_id=' . (int) $app['id'])) ?>" target="_blank">Preview</a> | <a href="<?= e(url('funding_document_view.php?application_id=' . (int) $app['id'] . '&download=1')) ?>">Download</a></p>
    <?php endif; ?>

    <form method="post" action="<?= e(url('update_funding_status.php')) ?>" class="row g-2 mt-1">
      <?= csrf_field() ?>
      <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
      <div class="col-md-3">
        <label class="mgrid-form-label">Action / New Status</label>
        <select name="new_status" class="mgrid-form-control" required>
          <?php foreach (['under_review','more_info_requested','approved','rejected','disbursed','active_repayment','completed','defaulted','cancelled'] as $s): ?>
            <option value="<?= e($s) ?>"><?= e(mfund_status_label($s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2"><label class="mgrid-form-label">Approved Amount</label><input name="approved_amount" type="number" step="0.01" class="mgrid-form-control"></div>
      <div class="col-md-2"><label class="mgrid-form-label">Interest Rate (%)</label><input name="interest_rate" type="number" step="0.0001" class="mgrid-form-control"></div>
      <div class="col-md-2"><label class="mgrid-form-label">Repayment Months</label><input name="repayment_duration_months" type="number" min="1" class="mgrid-form-control"></div>
      <div class="col-md-3"><label class="mgrid-form-label">Repayment Start Date</label><input name="repayment_start_date" type="date" class="mgrid-form-control"></div>
      <div class="col-md-4"><label class="mgrid-form-label">Funding Partner</label><input name="funding_partner_name" type="text" class="mgrid-form-control"></div>
      <div class="col-md-8"><label class="mgrid-form-label">Remarks</label><input name="remarks" type="text" class="mgrid-form-control" required></div>
      <div class="col-12 d-flex justify-content-end"><button class="btn-mgrid btn-mgrid-primary">Save Review Action</button></div>
    </form>

    <hr>
    <div class="mgrid-grid-2">
      <div class="mgrid-card">
        <div class="mgrid-card-body">
          <h3 class="h6">Record Disbursement</h3>
          <form method="post" action="<?= e(url('record_disbursement.php')) ?>" class="row g-2">
            <?= csrf_field() ?>
            <input type="hidden" name="application_id" value="<?= (int) $app['id'] ?>">
            <div class="col-md-4"><input name="disbursed_amount" type="number" step="0.01" min="0.01" class="mgrid-form-control" placeholder="Amount" required></div>
            <div class="col-md-4"><input name="disbursement_date" type="date" class="mgrid-form-control" required></div>
            <div class="col-md-4"><input name="disbursement_method" type="text" class="mgrid-form-control" placeholder="Method" required></div>
            <div class="col-12"><input name="reference_note" type="text" class="mgrid-form-control" placeholder="Reference note"></div>
            <div class="col-12 d-flex justify-content-end"><button class="btn-mgrid btn-mgrid-outline">Record Disbursement</button></div>
          </form>
        </div>
      </div>
      <div class="mgrid-card">
        <div class="mgrid-card-body d-flex flex-column justify-content-center">
          <h3 class="h6">Repayment Management</h3>
          <p class="small text-muted">Record repayments, track schedule and monitor overdue installments.</p>
          <a class="btn-mgrid btn-mgrid-primary" href="<?= e(url('admin/manage_repayments.php?application_id=' . (int) $app['id'])) ?>">Open Repayment Manager</a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="mgrid-grid-2">
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-timeline-event"></i> Status Logs</h2></div>
    <div class="mgrid-card-body">
      <?php if ($logs === []): ?><p class="text-muted">No logs.</p><?php else: ?><ul class="list-unstyled mb-0"><?php foreach ($logs as $l): ?><li class="mb-2 pb-2 border-bottom"><strong><?= e(mfund_status_label((string) $l['new_status'])) ?></strong><div class="small text-muted"><?= e(substr((string) $l['created_at'], 0, 16)) ?></div><div class="small"><?= e((string) ($l['note'] ?? '')) ?></div></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
  </div>
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-clipboard-text"></i> Review Notes</h2></div>
    <div class="mgrid-card-body">
      <?php if ($reviews === []): ?><p class="text-muted">No review notes.</p><?php else: ?><ul class="list-unstyled mb-0"><?php foreach ($reviews as $r): ?><li class="mb-2 pb-2 border-bottom"><strong><?= e((string) $r['action']) ?></strong><div class="small text-muted"><?= e((string) ($r['admin_name'] ?? 'Admin')) ?> · <?= e(substr((string) $r['action_at'], 0, 16)) ?></div><div class="small"><?= e((string) ($r['remarks'] ?? '')) ?></div></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
