<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$pdo = db();
$df = clean_string($_GET['date_from'] ?? '');
$dt = clean_string($_GET['date_to'] ?? '');

$dff = $df !== '' ? $df : null;
$dtt = $dt !== '' ? $dt : null;

$u = getUserGrowthStats($pdo);
$m = getMScoreDistribution($pdo);
$f = getFundingStats($pdo);
$v = getVerificationStats($pdo);
$t = getTrainingStats($pdo, $dff, $dtt);
$b = getBenefitsStats($pdo, $dff, $dtt);
$o = getOpportunityEngagementStats($pdo, $dff, $dtt);
$pr = getPartnerRequestStats($pdo);

$mgrid_page_title = mgrid_title('title.admin_reports');
require __DIR__ . '/includes/shell_open.php';

$exportBase = function (string $report) use ($df, $dt): string {
    $q = ['report' => $report];
    if ($df !== '') {
        $q['date_from'] = $df;
    }
    if ($dt !== '') {
        $q['date_to'] = $dt;
    }
    return url('admin/export_report.php?' . http_build_query($q));
};
?>

<div class="no-print d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
  <div>
    <h1 class="mgrid-display mb-1" style="font-size:1.75rem;"><i class="ti ti-file-analytics"></i> Reports</h1>
    <p class="text-muted mb-0 small">Filter summaries by date where applicable, export CSV, or print this page.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn-mgrid btn-mgrid-outline btn-sm" href="<?= e(url('admin/admin_analytics.php')) ?>">Charts dashboard</a>
    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">Print summary</button>
  </div>
</div>

<form method="get" class="no-print mgrid-card mb-3 p-3">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small text-muted">Date from</label>
      <input type="date" name="date_from" class="mgrid-form-control" value="<?= e($df) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small text-muted">Date to</label>
      <input type="date" name="date_to" class="mgrid-form-control" value="<?= e($dt) ?>">
    </div>
    <div class="col-md-3 d-grid">
      <button type="submit" class="btn-mgrid btn-mgrid-primary">Apply</button>
    </div>
  </div>
  <p class="small text-muted mb-0 mt-2">User growth and M-SCORE exports are not date-filtered here; funding/documents/training/benefits/opportunities CSV respect dates when set.</p>
</form>

<div id="printArea" class="mgrid-card mb-3">
  <div class="mgrid-card-header"><h2 class="mgrid-card-title">Executive summary<?php if ($df !== '' || $dt !== ''): ?> (filtered slice)<?php endif; ?></h2></div>
  <div class="mgrid-card-body">
    <div class="table-responsive">
      <table class="mgrid-table mb-0">
        <tbody>
          <tr><th>Total users</th><td><?= (int) ($u['total_users'] ?? 0) ?></td></tr>
          <tr><th>Active users</th><td><?= (int) ($u['active_users'] ?? 0) ?></td></tr>
          <tr><th>Verified national ID (profiles)</th><td><?= (int) ($u['verified_profiles'] ?? 0) ?></td></tr>
          <tr><th>Average profile completion %</th><td><?= number_format((float) ($u['avg_profile_completion'] ?? 0), 2) ?></td></tr>
          <tr><th>Average M-SCORE</th><td><?= number_format((float) ($m['avg_score'] ?? 0), 2) ?> (<?= e((string) ($m['source'] ?? '')) ?>)</td></tr>
          <tr><th>Investment-ready count</th><td><?= (int) ($m['investment_ready'] ?? 0) ?></td></tr>
          <tr><th>Funding applications (total)</th><td><?= $f['available'] ? (int) ($f['total_applications'] ?? 0) : 'N/A' ?></td></tr>
          <tr><th>Approved pipeline volume (requested)</th><td><?= $f['available'] ? number_format((float) ($f['approved_volume'] ?? 0), 2) : 'N/A' ?></td></tr>
          <tr><th>Total disbursed</th><td><?= $f['available'] ? number_format((float) ($f['total_disbursed'] ?? 0), 2) : 'N/A' ?></td></tr>
          <tr><th>Documents (total)</th><td><?= $v['available'] ? (int) ($v['total'] ?? 0) : 'N/A' ?></td></tr>
          <tr><th>Training registrations (in range)</th><td><?= $t['available'] ? (int) ($t['registrations_total'] ?? 0) : 'N/A' ?></td></tr>
          <tr><th>Training completions (in range)</th><td><?= $t['available'] ? (int) ($t['completed'] ?? 0) : 'N/A' ?></td></tr>
          <tr><th>Benefits claims (in range)</th><td><?= $b['available'] ? (int) ($b['claims_total'] ?? 0) : 'N/A' ?></td></tr>
          <tr><th>Opportunity applications (in range)</th><td><?= $o['available'] ? (int) ($o['applications'] ?? 0) : 'N/A' ?></td></tr>
          <tr><th>Partner requests (all time)</th><td><?= $pr['available'] ? (int) $pr['total'] : 'No partner_requests table' ?></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div class="no-print mgrid-card mb-3">
  <div class="mgrid-card-header"><h2 class="mgrid-card-title">CSV export</h2></div>
  <div class="mgrid-card-body">
    <p class="small text-muted">Downloads use current date filters where supported.</p>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-primary btn-sm" href="<?= e($exportBase('users')) ?>">Users</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= e($exportBase('funding')) ?>">Funding</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= e($exportBase('documents')) ?>">Documents</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= e($exportBase('mscore')) ?>">M-SCORE</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= e($exportBase('training')) ?>">Training</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= e($exportBase('benefits')) ?>">Benefits</a>
      <a class="btn btn-outline-primary btn-sm" href="<?= e($exportBase('opportunities')) ?>">Opportunities</a>
    </div>
  </div>
</div>

<div class="no-print row g-3">
  <div class="col-md-6">
    <div class="mgrid-card h-100">
      <div class="mgrid-card-header"><h3 class="mgrid-card-title">Funding by status</h3></div>
      <div class="mgrid-card-body p-0">
        <table class="mgrid-table mb-0">
          <thead><tr><th>Status</th><th>Count</th></tr></thead>
          <tbody>
            <?php foreach (($f['by_status'] ?? []) as $stn => $cnt): ?>
              <tr><td><?= e((string) $stn) ?></td><td><?= (int) $cnt ?></td></tr>
            <?php endforeach; ?>
            <?php if (($f['by_status'] ?? []) === []): ?><tr><td colspan="2" class="text-muted">No data</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="mgrid-card h-100">
      <div class="mgrid-card-header"><h3 class="mgrid-card-title">Documents by status</h3></div>
      <div class="mgrid-card-body p-0">
        <table class="mgrid-table mb-0">
          <thead><tr><th>Status</th><th>Count</th></tr></thead>
          <tbody>
            <?php foreach (($v['by_status'] ?? []) as $stn => $cnt): ?>
              <tr><td><?= e((string) $stn) ?></td><td><?= (int) $cnt ?></td></tr>
            <?php endforeach; ?>
            <?php if (($v['by_status'] ?? []) === []): ?><tr><td colspan="2" class="text-muted">No data</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
@media print {
  .no-print, .mgrid-sidebar, .mgrid-topbar, .app-header { display: none !important; }
  .mgrid-main { margin: 0 !important; }
  .mgrid-content { padding: 0 !important; }
}
</style>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
