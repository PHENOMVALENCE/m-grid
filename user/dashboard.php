<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];

$stmt = $pdo->prepare('
    SELECT u.m_id, u.full_name, u.email, u.phone, u.preferred_language, u.created_at,
           p.region, p.business_status, p.profile_completion, p.bio,
           s.score, s.tier, s.last_calculated_at
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    LEFT JOIN m_scores s ON s.user_id = u.id
    WHERE u.id = :id
    LIMIT 1
');
$stmt->execute(['id' => $uid]);
$row = $stmt->fetch() ?: [];

$mgrid_page_title = 'My dashboard — Malkia Grid';
require __DIR__ . '/includes/shell_open.php';

$tierRaw = (string) ($row['tier'] ?? 'pending');
$tierSlug = strtolower(preg_replace('/[^a-z0-9]+/', '_', $tierRaw));
$scorePct = isset($row['score']) && $row['score'] !== null && $row['score'] !== ''
    ? max(0, min(100, (float) $row['score']))
    : 0;
$profileCompletion = (int) ($row['profile_completion'] ?? 0);
$languageLabel = ($row['preferred_language'] ?? '') === 'sw' ? 'Kiswahili (preference)' : 'English';
$memberSince = substr((string) ($row['created_at'] ?? ''), 0, 10);

$missingItems = [];
if (trim((string) ($row['region'] ?? '')) === '') {
    $missingItems[] = 'Set your region';
}
if (trim((string) ($row['business_status'] ?? '')) === '') {
    $missingItems[] = 'Choose your business status';
}
if (trim((string) ($row['bio'] ?? '')) === '') {
    $missingItems[] = 'Add your biography';
}
?>

<div class="row g-4 mb-1">
  <div class="col-12">
    <div class="card mgrid-dash-hero border-0 shadow-sm mgrid-dash-hero--member">
      <div class="card-body p-4 p-lg-5">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
          <div>
            <p class="mgrid-topbar-label mb-2 d-block" data-i18n="dash.welcome_back">Welcome back</p>
            <h1 class="h3 mb-2 fw-semibold mgrid-display"><?= e((string) ($row['full_name'] ?? 'Member')) ?></h1>
            <p class="text-muted mb-0 small" data-i18n="dash.subtitle">Your identity profile is active. Keep your details current to unlock opportunities faster.</p>
          </div>
          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge rounded-pill mgrid-badge-soft px-3 py-2">M-ID: <?= e((string) ($row['m_id'] ?? '—')) ?></span>
            <span class="badge rounded-pill mgrid-badge-tier-<?= e($tierSlug === '' ? 'pending' : $tierSlug) ?> px-3 py-2 text-uppercase"><?= e($tierRaw) ?></span>
          </div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4">
          <a class="btn btn-primary btn-sm px-3" href="<?= e(url('user/profile.php')) ?>" data-i18n="dash.btn_profile">Manage M-Profile</a>
          <a class="btn btn-outline-primary btn-sm px-3" href="<?= e(url('user/verify-id.php')) ?>" data-i18n="dash.btn_verify">Verification status</a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100 mgrid-dash-stat">
      <div class="card-body">
        <h2 class="h6 text-uppercase text-muted mgrid-dash-stat-label" data-i18n="dash.stat_mid_label">Your M-ID</h2>
        <p class="fs-4 fw-semibold text-primary mb-0 mgrid-mono-id"><?= e((string) ($row['m_id'] ?? '')) ?></p>
        <p class="small text-muted mt-2 mb-0" data-i18n="dash.stat_mid_help">Permanent identifier for all partner and programme pathways.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex flex-column align-items-center text-center">
        <h2 class="h6 text-uppercase text-muted align-self-start w-100 mgrid-dash-stat-label" data-i18n="dash.stat_score_label">M-Score</h2>
        <div class="mgrid-score mt-2 mb-1">
          <div class="mgrid-score-ring" style="--pct: <?= (int) round($scorePct) ?>" data-tier="<?= e($tierSlug) ?>">
            <div class="mgrid-score-inner">
              <span class="mgrid-score-value"><?= isset($row['score']) && $row['score'] !== null && $row['score'] !== '' ? e((string) $row['score']) : '—' ?></span>
              <span class="mgrid-score-label"><?= e($tierRaw) ?></span>
            </div>
          </div>
        </div>
        <p class="small text-muted mt-2 mb-0" data-i18n="dash.stat_score_help">Methodology updates and tier criteria are published as modules roll out.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 text-uppercase text-muted mgrid-dash-stat-label">Profile completion</h2>
        <div class="progress mt-2 mb-2" style="height:10px;">
          <div class="progress-bar" role="progressbar" style="width: <?= $profileCompletion ?>%;" aria-valuenow="<?= $profileCompletion ?>" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <p class="small text-muted mb-0"><?= $profileCompletion ?>% — complete key fields to improve partner readiness.</p>
        <?php if ($missingItems !== []): ?>
          <ul class="list-unstyled small mt-3 mb-0">
            <?php foreach ($missingItems as $item): ?>
              <li class="mb-1 d-flex gap-2 align-items-start"><span class="mgrid-check">•</span><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mt-2">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h2 class="h5 mgrid-dash-section-title mb-3">Profile summary</h2>
        <ul class="list-unstyled small text-muted mb-0">
          <li class="mb-2"><strong class="text-dark">Region:</strong> <?= e((string) ($row['region'] ?? '—')) ?></li>
          <li class="mb-2"><strong class="text-dark">Business status:</strong> <?= e(str_replace('_', ' ', (string) ($row['business_status'] ?? '—'))) ?></li>
          <li class="mb-2"><strong class="text-dark">Language:</strong> <?= e($languageLabel) ?></li>
          <li><strong class="text-dark">Member since:</strong> <?= e($memberSince) ?></li>
        </ul>
        <a class="btn btn-outline-primary btn-sm mt-3" href="<?= e(url('user/profile.php')) ?>">Edit profile details</a>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h2 class="h5 mgrid-dash-section-title mb-3">Quick actions</h2>
        <div class="row g-2">
          <div class="col-sm-6">
            <a href="<?= e(url('user/profile.php')) ?>" class="mgrid-quick-link">
              <i class="ti ti-user-edit"></i>
              <span>Update profile</span>
            </a>
          </div>
          <div class="col-sm-6">
            <a href="<?= e(url('user/verify-id.php')) ?>" class="mgrid-quick-link">
              <i class="ti ti-id-badge-2"></i>
              <span>ID verification</span>
            </a>
          </div>
          <div class="col-sm-6">
            <a href="<?= e(url('user/settings.php')) ?>" class="mgrid-quick-link">
              <i class="ti ti-settings"></i>
              <span>Account settings</span>
            </a>
          </div>
          <div class="col-sm-6">
            <a href="<?= e(url('user/dashboard.php')) ?>" class="mgrid-quick-link">
              <i class="ti ti-activity-heartbeat"></i>
              <span>Refresh overview</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<h2 class="h5 mgrid-dash-section-title mt-5 mb-3">Coming modules</h2>
<div class="row g-3">
  <?php
    $tiles = [
        ['Documents', 'ti ti-file-certificate', 'Secure uploads & verification status.'],
        ['Opportunities', 'ti ti-briefcase', 'Curated programmes aligned to your profile.'],
        ['M-Benefits', 'ti ti-heart-handshake', 'Grants, learning, and wellness journeys.'],
        ['Loan access (M-Fund)', 'ti ti-building-bank', 'Finance-ready pathways when you choose to apply.'],
    ];
foreach ($tiles as $t) {
    ?>
  <div class="col-md-6 col-xl-3">
    <div class="card border-0 shadow-sm h-100 mgrid-dash-tile--planned">
      <div class="card-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="<?= e($t[1]) ?> fs-5"></i>
          <h3 class="h6 mb-0"><?= e($t[0]) ?></h3>
        </div>
        <p class="small text-muted mb-0"><?= e($t[2]) ?></p>
        <span class="badge bg-light text-dark border mt-3">Planned</span>
      </div>
    </div>
  </div>
    <?php
}
?>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
