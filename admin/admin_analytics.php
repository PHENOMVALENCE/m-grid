<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$pdo = db();
$kpis = getAnalyticsOverviewKpis($pdo);
$u = $kpis['users'];
$m = $kpis['mscore'];
$f = $kpis['funding'];
$b = $kpis['benefits'];
$t = $kpis['training'];
$pr = $kpis['partners'];
$opp = getOpportunityEngagementStats($pdo, null, null);

$growth = getUserGrowthByMonth($pdo, 18, null);
$fundingBreak = getFundingStatusBreakdown($pdo);
$docBreak = getDocumentStatusBreakdown($pdo);
$trainMonth = getTrainingParticipationByMonth($pdo, null, null);
$benCat = $b['by_category'] ?? [];

$mgrid_page_title = mgrid_title('title.admin_analytics');
require __DIR__ . '/includes/shell_open.php';

$labelsGrowth = array_column($growth, 'ym');
$dataGrowth = array_map(static fn ($v) => (int) ($v['c'] ?? 0), $growth);
$labelsTier = array_column($m['tiers'] ?? [], 'label');
$dataTier = array_map(static fn ($v) => (int) ($v['count'] ?? 0), $m['tiers'] ?? []);
$labelsFund = array_column($fundingBreak, 'status');
$dataFund = array_map(static fn ($v) => (int) ($v['c'] ?? 0), $fundingBreak);
$labelsDoc = array_column($docBreak, 'status');
$dataDoc = array_map(static fn ($v) => (int) ($v['c'] ?? 0), $docBreak);
$labelsTrain = array_column($trainMonth, 'ym');
$dataTrain = array_map(static fn ($v) => (int) ($v['c'] ?? 0), $trainMonth);
$labelsBen = array_column($benCat, 'category_name');
$dataBen = array_map(static fn ($v) => (int) ($v['n'] ?? 0), $benCat);
if ($labelsTier === []) {
    $labelsTier = ['No data'];
    $dataTier = [0];
}
if ($labelsFund === []) {
    $labelsFund = ['—'];
    $dataFund = [0];
}
if ($labelsDoc === []) {
    $labelsDoc = ['—'];
    $dataDoc = [0];
}
if ($labelsTrain === []) {
    $labelsTrain = ['—'];
    $dataTrain = [0];
}
if ($labelsBen === []) {
    $labelsBen = ['—'];
    $dataBen = [0];
}
if ($labelsGrowth === []) {
    $labelsGrowth = ['—'];
    $dataGrowth = [0];
}
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
  <div>
    <h1 class="mgrid-display mb-1" style="font-size:1.75rem;"><i class="ti ti-chart-infographic"></i> Analytics</h1>
    <p class="text-muted mb-0 small">Live aggregates from platform modules. For filtered exports use Reports.</p>
  </div>
  <a class="btn-mgrid btn-mgrid-outline btn-sm" href="<?= e(url('admin/admin_reports.php')) ?>">Reports &amp; export</a>
</div>

<div class="mgrid-grid-4 mb-4">
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Total users</div>
    <div class="mgrid-stat-value"><?= (int) ($u['total_users'] ?? 0) ?></div>
    <div class="mgrid-stat-sub">Active <?= (int) ($u['active_users'] ?? 0) ?> · Pending <?= (int) ($u['pending_users'] ?? 0) ?></div>
  </div>
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Verified ID (M-Profile)</div>
    <div class="mgrid-stat-value"><?= (int) ($u['verified_profiles'] ?? 0) ?></div>
    <div class="mgrid-stat-sub">Avg profile <?= number_format((float) ($u['avg_profile_completion'] ?? 0), 1) ?>%</div>
  </div>
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Avg M-SCORE</div>
    <div class="mgrid-stat-value"><?= number_format((float) ($m['avg_score'] ?? 0), 2) ?></div>
    <div class="mgrid-stat-sub">Investment-ready <?= (int) ($m['investment_ready'] ?? 0) ?> · <?= e((string) ($m['source'] ?? 'none')) ?></div>
  </div>
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Funding applications</div>
    <div class="mgrid-stat-value"><?= $f['available'] ? (int) ($f['total_applications'] ?? 0) : '—' ?></div>
    <div class="mgrid-stat-sub">Approved pipeline TZS <?= $f['available'] ? number_format((float) ($f['approved_volume'] ?? 0), 0) : '—' ?></div>
  </div>
</div>

<div class="mgrid-grid-4 mb-4">
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Disbursed (M-FUND)</div>
    <div class="mgrid-stat-value"><?= $f['available'] ? number_format((float) ($f['total_disbursed'] ?? 0), 0) : '—' ?></div>
    <div class="mgrid-stat-sub">Recorded disbursements</div>
  </div>
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Partner requests</div>
    <div class="mgrid-stat-value"><?= $pr['available'] ? (int) $pr['total'] : '—' ?></div>
    <div class="mgrid-stat-sub"><?= $pr['available'] ? 'From partner_requests' : 'Module/table not present' ?></div>
  </div>
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Benefits claims</div>
    <div class="mgrid-stat-value"><?= $b['available'] ? (int) ($b['claims_total'] ?? 0) : '—' ?></div>
    <div class="mgrid-stat-sub">All statuses</div>
  </div>
  <div class="mgrid-stat-card">
    <div class="mgrid-stat-label">Training completed</div>
    <div class="mgrid-stat-value"><?= $t['available'] ? (int) ($t['completed'] ?? 0) : '—' ?></div>
    <div class="mgrid-stat-sub">Registrations <?= $t['available'] ? (int) ($t['registrations_total'] ?? 0) : '—' ?></div>
  </div>
</div>

<div class="mgrid-stat-card mb-3">
  <div class="mgrid-stat-label">Opportunity applications</div>
  <div class="mgrid-stat-value"><?= $opp['available'] ? (int) ($opp['applications'] ?? 0) : '—' ?></div>
  <div class="mgrid-stat-sub">Engagement from opportunity_applications</div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="mgrid-card h-100">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">User growth (by month)</h2></div>
      <div class="mgrid-card-body"><canvas id="chartGrowth" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="mgrid-card h-100">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">M-SCORE tier distribution</h2></div>
      <div class="mgrid-card-body"><canvas id="chartTier" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="mgrid-card h-100">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">Funding status</h2></div>
      <div class="mgrid-card-body"><canvas id="chartFunding" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="mgrid-card h-100">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">Document verification</h2></div>
      <div class="mgrid-card-body"><canvas id="chartDocs" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="mgrid-card h-100">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">Training registrations (by month)</h2></div>
      <div class="mgrid-card-body"><canvas id="chartTrain" height="220"></canvas></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="mgrid-card h-100">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">Benefits claims by category</h2></div>
      <div class="mgrid-card-body"><canvas id="chartBen" height="220"></canvas></div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
(function(){
  var common = { responsive: true, maintainAspectRatio: false };
  var pal = ['#6f42c1','#0d6efd','#198754','#fd7e14','#dc3545','#20c997','#6610f2','#0dcaf0'];

  new Chart(document.getElementById('chartGrowth'), {
    type: 'line',
    data: { labels: <?= json_encode($labelsGrowth, JSON_THROW_ON_ERROR) ?>, datasets: [{ label: 'New users', data: <?= json_encode($dataGrowth, JSON_THROW_ON_ERROR) ?>, borderColor: pal[0], tension: 0.2, fill: false }] },
    options: { ...common, scales: { y: { beginAtZero: true, ticks: { precision:0 } } } }
  });
  new Chart(document.getElementById('chartTier'), {
    type: 'doughnut',
    data: { labels: <?= json_encode($labelsTier, JSON_THROW_ON_ERROR) ?>, datasets: [{ data: <?= json_encode($dataTier, JSON_THROW_ON_ERROR) ?>, backgroundColor: pal }] },
    options: { ...common, plugins: { legend: { position: 'bottom' } } }
  });
  new Chart(document.getElementById('chartFunding'), {
    type: 'bar',
    data: { labels: <?= json_encode($labelsFund, JSON_THROW_ON_ERROR) ?>, datasets: [{ label: 'Applications', data: <?= json_encode($dataFund, JSON_THROW_ON_ERROR) ?>, backgroundColor: pal[1] }] },
    options: { ...common, scales: { y: { beginAtZero: true, ticks: { precision:0 } } } }
  });
  new Chart(document.getElementById('chartDocs'), {
    type: 'pie',
    data: { labels: <?= json_encode($labelsDoc, JSON_THROW_ON_ERROR) ?>, datasets: [{ data: <?= json_encode($dataDoc, JSON_THROW_ON_ERROR) ?>, backgroundColor: pal }] },
    options: { ...common, plugins: { legend: { position: 'bottom' } } }
  });
  new Chart(document.getElementById('chartTrain'), {
    type: 'bar',
    data: { labels: <?= json_encode($labelsTrain, JSON_THROW_ON_ERROR) ?>, datasets: [{ label: 'Registrations', data: <?= json_encode($dataTrain, JSON_THROW_ON_ERROR) ?>, backgroundColor: pal[2] }] },
    options: { ...common, scales: { y: { beginAtZero: true, ticks: { precision:0 } } } }
  });
  new Chart(document.getElementById('chartBen'), {
    type: 'bar',
    data: { labels: <?= json_encode($labelsBen, JSON_THROW_ON_ERROR) ?>, datasets: [{ label: 'Claims', data: <?= json_encode($dataBen, JSON_THROW_ON_ERROR) ?>, backgroundColor: pal[3] }] },
    options: { ...common, indexAxis: 'y', scales: { x: { beginAtZero: true, ticks: { precision:0 } } } }
  });
})();
</script>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
