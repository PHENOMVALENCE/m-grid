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

$mgrid_page_title = 'My dashboard — M-GRID';
require __DIR__ . '/includes/shell_open.php';
?>

<div class="row mb-4">
  <div class="col-12">
    <div class="card mgrid-dash-hero border-0 shadow-sm">
      <div class="card-body p-4">
        <p class="text-muted small mb-1">Karibu / Welcome</p>
        <h1 class="h3 mb-0 fw-bold"><?= e((string) ($row['full_name'] ?? '')) ?></h1>
        <p class="text-muted mb-0 small">Your M-GRID space is ready to grow with you.</p>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 text-uppercase text-muted">Your M-ID</h2>
        <p class="fs-4 fw-bold text-primary mb-0"><?= e((string) ($row['m_id'] ?? '')) ?></p>
        <p class="small text-muted mt-2 mb-0">Permanent identifier — never changes.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 text-uppercase text-muted">M-Score</h2>
        <p class="fs-2 fw-bold mb-0">—</p>
        <p class="small text-muted mt-2 mb-0">Tier: <strong><?= e((string) ($row['tier'] ?? 'pending')) ?></strong>. Scoring arrives in a later release.</p>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h2 class="h6 text-uppercase text-muted">Profile completion</h2>
        <div class="progress mt-2 mb-2" style="height:10px;">
          <div class="progress-bar bg-warning" role="progressbar" style="width: <?= (int) ($row['profile_completion'] ?? 0) ?>%;"></div>
        </div>
        <p class="small text-muted mb-0"><?= (int) ($row['profile_completion'] ?? 0) ?>% — add biography and documents when modules open.</p>
      </div>
    </div>
  </div>
</div>

<div class="row g-4 mt-1">
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h2 class="h5 fw-bold mb-3">Profile summary</h2>
        <ul class="list-unstyled small text-muted mb-0">
          <li class="mb-2"><strong class="text-dark">Region:</strong> <?= e((string) ($row['region'] ?? '—')) ?></li>
          <li class="mb-2"><strong class="text-dark">Business status:</strong> <?= e(str_replace('_', ' ', (string) ($row['business_status'] ?? '—'))) ?></li>
          <li class="mb-2"><strong class="text-dark">Language:</strong> <?= ($row['preferred_language'] ?? '') === 'sw' ? 'Kiswahili' : 'English' ?></li>
          <li><strong class="text-dark">Member since:</strong> <?= e(substr((string) ($row['created_at'] ?? ''), 0, 10)) ?></li>
        </ul>
        <a class="btn btn-outline-primary btn-sm mt-3" href="<?= e(url('user/profile.php')) ?>">Open M-Profile</a>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <h2 class="h5 fw-bold mb-3">Recent updates</h2>
        <p class="small text-muted mb-0">No programme notices yet. Future releases will show opportunities, verification steps, and partner news here.</p>
      </div>
    </div>
  </div>
</div>

<h2 class="h5 fw-bold mt-5 mb-3">Coming modules</h2>
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
    <div class="card border-0 shadow-sm h-100 opacity-75">
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
