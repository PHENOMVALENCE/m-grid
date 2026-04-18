<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$stmt = $pdo->prepare('
    SELECT u.*, p.region, p.date_of_birth, p.age_range, p.business_status, p.bio, p.profile_completion,
           s.score AS m_score, s.tier AS m_tier
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    LEFT JOIN m_scores s ON s.user_id = u.id
    WHERE u.id = :id
    LIMIT 1
');
$stmt->execute(['id' => $uid]);
$row = $stmt->fetch() ?: [];

$mgrid_page_title = 'M-Profile — Malkia Grid';
require __DIR__ . '/includes/shell_open.php';
?>

<?php
$tierRaw = strtolower((string) ($row['m_tier'] ?? 'pending'));
$badgeTier = 'pending';
if (str_contains($tierRaw, 'gold')) {
    $badgeTier = 'gold';
} elseif (str_contains($tierRaw, 'silver')) {
    $badgeTier = 'silver';
} elseif (str_contains($tierRaw, 'bronze')) {
    $badgeTier = 'bronze';
} elseif (str_contains($tierRaw, 'diamond')) {
    $badgeTier = 'diamond';
}
$scoreDisp = isset($row['m_score']) && $row['m_score'] !== null && $row['m_score'] !== ''
    ? (string) $row['m_score']
    : '—';
$isGoldTier = $badgeTier === 'gold';
?>
<div class="mgrid-mid-card--premium mb-4<?= $isGoldTier ? ' mgrid-tier-gold-hero' : '' ?>">
  <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
    <div>
      <div class="mgrid-mid-card-name"><?= e((string) ($row['full_name'] ?? '')) ?></div>
      <div class="mgrid-mid-card-id"><?= e((string) ($row['m_id'] ?? '')) ?></div>
    </div>
    <?php if (!empty($row['m_tier'])): ?>
      <span class="badge rounded-pill mgrid-badge-tier-<?= e($badgeTier) ?> mgrid-mid-card-tier text-uppercase"><?= e((string) $row['m_tier']) ?></span>
    <?php endif; ?>
  </div>
  <div class="mgrid-mid-card-score"><?= e($scoreDisp) ?></div>
  <div class="mgrid-mid-card-score-label">M-Score</div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <h1 class="h4 mgrid-dash-page-title mb-4">M-Profile</h1>
    <div class="row g-4">
      <div class="col-md-6">
        <p class="text-muted small mb-1">M-ID</p>
        <p class="fw-bold fs-5 mgrid-mono-id"><?= e((string) ($row['m_id'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Full name</p>
        <p class="fw-semibold"><?= e((string) ($row['full_name'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Email</p>
        <p class="mb-0"><?= e((string) ($row['email'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Phone</p>
        <p class="mb-0"><?= e((string) ($row['phone'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Region</p>
        <p class="mb-0"><?= e((string) ($row['region'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Business status</p>
        <p class="mb-0"><?= e(str_replace('_', ' ', (string) ($row['business_status'] ?? ''))) ?></p>
      </div>
      <div class="col-12">
        <p class="text-muted small mb-1">Biography</p>
        <?php if (!empty($row['bio'])): ?>
          <p class="mb-0"><?= e((string) $row['bio']) ?></p>
        <?php else: ?>
          <p class="mb-0 text-muted">Not added yet.</p>
        <?php endif; ?>
      </div>
    </div>
    <p class="small text-muted mt-4 mb-0">Profile editing will connect here; for now, contact support for corrections to verified fields.</p>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
