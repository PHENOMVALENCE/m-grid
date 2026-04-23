<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$auth = auth_user();
$uid = (int) $auth['user_id'];
$score = mscore_current_for_user($uid);

if ($score === null || isset($_GET['recalculate'])) {
    try {
        $score = calculateUserMScore($uid);
        flash_set('success', __('mscore.user.ok'));
        if (isset($_GET['recalculate'])) {
            redirect('user/my_mscore.php');
        }
    } catch (Throwable $e) {
        if ($score === null) {
            flash_set('error', __('mscore.user.fail', ['msg' => $e->getMessage()]));
        }
    }
}

$mgrid_page_title = mgrid_title('title.mscore');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?>
  <div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
  <div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div>
<?php endif; ?>

<?php
$totalScore = (float) ($score['total_score'] ?? 0);
$tier = (string) ($score['tier_label'] ?? mscore_tier_for_score($totalScore));
$readiness = (string) ($score['readiness_label'] ?? mscore_readiness_label($totalScore));
$breakdown = $score['breakdown'] ?? [];
$recommendations = $score['recommendations'] ?? [];
?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <div class="mgrid-topbar-label">Credibility Index</div>
        <h1 class="mgrid-display mb-1" style="font-size:2rem;">My M-SCORE</h1>
        <p class="mb-0" style="color:var(--mgrid-ink-500);"><?= e($readiness) ?></p>
      </div>
      <a href="<?= e(url('user/my_mscore.php?recalculate=1')) ?>" class="btn-mgrid btn-mgrid-outline"><i class="ti ti-refresh"></i> Recalculate</a>
    </div>
    <div class="mt-3">
      <div class="d-flex align-items-center gap-2 mb-1">
        <div class="mgrid-mono-id" style="font-size:30px; color:var(--mgrid-gold-600);"><?= number_format($totalScore, 2) ?></div>
        <span class="badge text-bg-<?= e(mscore_tier_badge_class($tier)) ?>"><?= e($tier) ?></span>
      </div>
      <div class="mgrid-progress-track" style="height:8px;">
        <div class="mgrid-progress-fill" style="width: <?= max(0, min(100, $totalScore)) ?>%;"></div>
      </div>
      <div class="mgrid-progress-meta"><span><?= number_format($totalScore, 2) ?> / 100</span><span>Last updated: <?= e(substr((string) ($score['calculated_at'] ?? date('Y-m-d H:i:s')), 0, 16)) ?></span></div>
    </div>
  </div>
</div>

<div class="mgrid-grid-2 mb-3">
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-chart-bar"></i> Score Breakdown</h2></div>
    <div class="mgrid-card-body">
      <?php if ($breakdown === []): ?>
        <p class="text-muted mb-0">No breakdown available yet.</p>
      <?php else: ?>
        <?php foreach ($breakdown as $item): ?>
          <?php
          $awarded = (float) ($item['points_awarded'] ?? 0);
          $max = (float) ($item['max_points'] ?? 0);
          $pct = (float) ($item['percentage'] ?? 0);
          ?>
          <div class="mb-3">
            <div class="d-flex justify-content-between">
              <strong><?= e((string) ($item['category_name'] ?? $item['category_key'] ?? 'Category')) ?></strong>
              <span><?= number_format($awarded, 2) ?> / <?= number_format($max, 2) ?></span>
            </div>
            <div class="mgrid-progress-track mt-1">
              <div class="mgrid-progress-fill" style="width: <?= max(0, min(100, $pct)) ?>%;"></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-bulb"></i> Recommendations</h2></div>
    <div class="mgrid-card-body">
      <?php if ($recommendations === []): ?>
        <p class="text-muted mb-0">No recommendations available.</p>
      <?php else: ?>
        <ul class="mb-0">
          <?php foreach ($recommendations as $tip): ?>
            <li class="mb-2"><?= e((string) $tip) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
