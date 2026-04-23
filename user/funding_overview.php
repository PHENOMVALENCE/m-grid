<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$auth = auth_user();
$uid = (int) $auth['user_id'];
$elig = checkFundingEligibility($uid);
$pdo = db();

$recentStmt = $pdo->prepare('
  SELECT id, reference_number, application_type, requested_amount, status, submitted_at
  FROM funding_applications
  WHERE user_id = :uid
  ORDER BY submitted_at DESC
  LIMIT 5
');
$recentStmt->execute(['uid' => $uid]);
$recent = $recentStmt->fetchAll() ?: [];

$mgrid_page_title = mgrid_title('title.funding_overview');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-3">
    <div>
      <div class="mgrid-topbar-label">M-FUND</div>
      <h1 class="mgrid-display mb-1" style="font-size:2rem;">Funding Readiness</h1>
      <p class="mb-0" style="color:var(--mgrid-ink-500);">
        <?= $elig['eligible'] ? 'You are eligible to submit a funding application.' : 'You are not yet eligible. Complete the missing requirements below.' ?>
      </p>
    </div>
    <div>
      <div class="mgrid-mono-id" style="font-size:26px; color:var(--mgrid-gold-600);"><?= number_format((float) $elig['score'], 2) ?></div>
      <div class="small text-muted">M-SCORE (minimum <?= number_format((float) $elig['min_score'], 2) ?>)</div>
    </div>
  </div>
</div>

<div class="mgrid-grid-2">
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-checklist"></i> Eligibility Checklist</h2></div>
    <div class="mgrid-card-body">
      <ul class="list-unstyled mb-0">
        <?php foreach ($elig['checks'] as $c): ?>
          <li class="mb-2">
            <span class="badge text-bg-<?= $c['ok'] ? 'success' : 'danger' ?> me-1"><?= $c['ok'] ? 'OK' : 'Missing' ?></span>
            <?= e((string) $c['message']) ?>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="mt-3">
        <?php if ($elig['eligible']): ?>
          <a class="btn-mgrid btn-mgrid-primary" href="<?= e(url('user/apply_funding.php')) ?>">Apply for Funding</a>
        <?php else: ?>
          <button class="btn btn-secondary" disabled>Not Eligible Yet</button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-history"></i> Recent Applications</h2></div>
    <div class="mgrid-card-body">
      <?php if ($recent === []): ?>
        <p class="text-muted">No funding applications yet.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($recent as $r): ?>
            <li class="mb-2 pb-2 border-bottom">
              <div class="d-flex justify-content-between">
                <strong><?= e((string) $r['reference_number']) ?></strong>
                <span class="badge text-bg-<?= e(mfund_status_badge((string) $r['status'])) ?>"><?= e(mfund_status_label((string) $r['status'])) ?></span>
              </div>
              <div class="small text-muted"><?= e((string) strtoupper((string) $r['application_type'])) ?> · TZS <?= number_format((float) $r['requested_amount'], 2) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <a class="btn-mgrid btn-mgrid-outline mt-2" href="<?= e(url('user/my_funding_applications.php')) ?>">View All Applications</a>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
