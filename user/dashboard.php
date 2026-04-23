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

$mgrid_page_title = mgrid_title('title.dashboard');
require __DIR__ . '/includes/shell_open.php';

$tierRaw = (string) ($row['tier'] ?? 'pending');
$tierSlug = strtolower(preg_replace('/[^a-z0-9]+/', '_', $tierRaw));
$scorePct = isset($row['score']) && $row['score'] !== null && $row['score'] !== ''
    ? max(0, min(100, (float) $row['score']))
    : 0;
$profileCompletion = (int) ($row['profile_completion'] ?? 0);
$languageLabel = ($row['preferred_language'] ?? '') === 'sw' ? __('lang.ui_sw') : __('lang.ui_en');
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

<div class="mgrid-page-head mgrid-page-section">
<div class="mgrid-user-hero">
  <div class="mgrid-user-hero-left">
    <span class="mgrid-topbar-label" data-i18n="dash.welcome_back">Welcome back</span>
    <h1 class="mgrid-display"><?= e((string) ($row['full_name'] ?? 'Member')) ?></h1>
    <p data-i18n="dash.subtitle">Your identity profile is active. Keep your details current to unlock opportunities faster.</p>
    <div class="mgrid-user-hero-badges">
      <span class="mgrid-mid-badge"><i class="ti ti-fingerprint"></i><span><?= e((string) ($row['m_id'] ?? '—')) ?></span></span>
      <span class="mgrid-tier-badge mgrid-tier-badge--<?= e($tierSlug) ?>"><?= e($tierRaw) ?></span>
    </div>
  </div>
  <div class="mgrid-user-hero-actions">
    <a href="<?= e(url('user/profile.php')) ?>" class="btn-mgrid btn-mgrid-primary"><i class="ti ti-user-edit"></i> <span data-i18n="dash.btn_profile">Manage M-Profile</span></a>
    <a href="<?= e(url('user/verify-id.php')) ?>" class="btn-mgrid btn-mgrid-outline"><i class="ti ti-shield-check"></i> <span data-i18n="dash.btn_verify">Verification status</span></a>
  </div>
</div>
</div>

<div class="mgrid-grid-3 mb-4 mgrid-page-section">
  <div class="mgrid-stat-card mgrid-stat-card--mid">
    <div class="mgrid-stat-label" data-i18n="dash.stat_mid_label">Your M-ID</div>
    <div class="mgrid-stat-main">
      <div class="mgrid-stat-mid-value"><?= e((string) ($row['m_id'] ?? '')) ?></div>
    </div>
    <p class="mgrid-stat-sub mgrid-stat-sub--meta" data-i18n="dash.stat_mid_help">Permanent identifier for all partner and programme pathways.</p>
  </div>
  <div class="mgrid-stat-card mgrid-stat-card--score">
    <div class="mgrid-stat-label" data-i18n="dash.stat_score_label">M-Score</div>
    <div class="mgrid-stat-main mgrid-stat-main--center">
      <div class="mgrid-score-ring-wrap" data-score-ring="<?= e((string) round($scorePct)) ?>">
        <div class="mgrid-score-ring">
          <svg width="112" height="112" viewBox="0 0 100 100" aria-hidden="true">
            <circle class="mgrid-score-ring-track" cx="50" cy="50" r="45"></circle>
            <circle class="mgrid-score-ring-fill mgrid-score-ring-fill--<?= e($tierSlug) ?>" cx="50" cy="50" r="45"></circle>
          </svg>
        </div>
        <div class="mgrid-score-ring-inner">
          <span class="mgrid-score-ring-number"><?= isset($row['score']) && $row['score'] !== null && $row['score'] !== '' ? e((string) $row['score']) : '—' ?></span>
          <span class="mgrid-score-ring-label"><?= e($tierRaw) ?></span>
        </div>
      </div>
    </div>
    <p class="mgrid-stat-sub mgrid-stat-sub--meta" data-i18n="dash.stat_score_help">Methodology updates and tier criteria are published as modules roll out.</p>
  </div>
  <div class="mgrid-stat-card mgrid-stat-card--completion">
    <div class="mgrid-stat-label" data-i18n="dash.stat_completion_label">Profile completion</div>
    <div class="mgrid-stat-main">
      <div class="mgrid-progress-wrap">
        <div class="mgrid-progress-track">
          <div class="mgrid-progress-fill" style="width: <?= $profileCompletion ?>%;"></div>
        </div>
        <div class="mgrid-progress-meta"><span><?= $profileCompletion ?>%</span><span data-i18n="dash.stat_target_full">Target 100%</span></div>
      </div>
    </div>
    <p class="mgrid-stat-sub mgrid-stat-sub--meta"><span><?= $profileCompletion ?>%</span> <span data-i18n="dash.profile_completion_tail">— complete key fields to improve partner readiness.</span></p>
  </div>
</div>

<div class="mgrid-grid-2 mgrid-page-section">
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-user"></i><span data-i18n="dash.card_profile_title">Profile summary</span></h2></div>
    <div class="mgrid-card-body">
        <ul class="list-unstyled small text-muted mb-0">
          <li class="mb-2"><strong class="text-dark" data-i18n="dash.lbl_region">Region:</strong> <?= e((string) ($row['region'] ?? '—')) ?></li>
          <li class="mb-2"><strong class="text-dark" data-i18n="dash.lbl_business">Business status:</strong> <?= e(str_replace('_', ' ', (string) ($row['business_status'] ?? '—'))) ?></li>
          <li class="mb-2"><strong class="text-dark" data-i18n="dash.lbl_language">Language:</strong> <?= e($languageLabel) ?></li>
          <li><strong class="text-dark" data-i18n="dash.lbl_member_since">Member since:</strong> <?= e($memberSince) ?></li>
        </ul>
        <a class="btn-mgrid btn-mgrid-outline mt-3" href="<?= e(url('user/profile.php')) ?>"><span data-i18n="dash.edit_profile">Edit profile details</span></a>
    </div>
  </div>
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-bolt"></i><span data-i18n="dash.card_quick_title">Quick actions</span></h2></div>
    <div class="mgrid-card-body">
      <div class="mgrid-grid-2">
        <a href="<?= e(url('user/profile.php')) ?>" class="mgrid-quick-link"><i class="ti ti-user-edit"></i><span data-i18n="dash.qa_profile">Update profile</span></a>
        <a href="<?= e(url('user/my_mscore.php')) ?>" class="mgrid-quick-link"><i class="ti ti-chart-arcs"></i><span data-i18n="dash.qa_mscore">View M-SCORE</span></a>
        <a href="<?= e(url('user/verify-id.php')) ?>" class="mgrid-quick-link"><i class="ti ti-id-badge-2"></i><span data-i18n="dash.qa_verify">ID verification</span></a>
        <a href="<?= e(url('user/settings.php')) ?>" class="mgrid-quick-link"><i class="ti ti-settings"></i><span data-i18n="dash.qa_settings">Account settings</span></a>
        <a href="<?= e(url('user/my_documents.php')) ?>" class="mgrid-quick-link"><i class="ti ti-file-certificate"></i><span data-i18n="dash.qa_documents">My documents</span></a>
      </div>
    </div>
  </div>
</div>

<h2 class="h5 mgrid-dash-section-title mt-5 mb-3 mgrid-page-section" data-i18n="dash.coming_modules">Coming modules</h2>
<div class="mgrid-grid-4 mgrid-page-section">
  <?php
    $tiles = [
        ['dash.tile_docs_title', 'ti ti-file-certificate', 'dash.tile_docs_desc', 'Documents', 'Secure uploads & verification status.'],
        ['dash.tile_opp_title', 'ti ti-briefcase', 'dash.tile_opp_desc', 'Opportunities', 'Curated programmes aligned to your profile.'],
        ['dash.tile_ben_title', 'ti ti-heart-handshake', 'dash.tile_ben_desc', 'M-Benefits', 'Grants, learning, and wellness journeys.'],
        ['dash.tile_fund_title', 'ti ti-building-bank', 'dash.tile_fund_desc', 'Loan access (M-Fund)', 'Finance-ready pathways when you choose to apply.'],
    ];
foreach ($tiles as $t) {
    ?>
  <div class="mgrid-module-tile">
      <div class="mgrid-module-tile-icon"><i class="<?= e($t[1]) ?>"></i></div>
      <h3 data-i18n="<?= e($t[0]) ?>"><?= e($t[3]) ?></h3>
      <p data-i18n="<?= e($t[2]) ?>"><?= e($t[4]) ?></p>
      <span class="mgrid-module-tile-badge" data-i18n="dash.tile_planned">Planned</span>
      </div>
    <?php
}
?>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
