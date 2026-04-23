<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$pdo = db();
$userId = (int) ($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    flash_set('error', __('admin.user.invalid'));
    redirect('admin/admin_mscores.php');
}

$userStmt = $pdo->prepare('SELECT id, full_name, m_id, email FROM users WHERE id = :id LIMIT 1');
$userStmt->execute(['id' => $userId]);
$user = $userStmt->fetch();
if (!$user) {
    flash_set('error', __('admin.user.not_found'));
    redirect('admin/admin_mscores.php');
}

$current = mscore_current_for_user($userId);
if ($current === null) {
    $current = calculateUserMScore($userId);
}

$histStmt = $pdo->prepare('
    SELECT total_score, tier_label, calculated_at
    FROM mscore_score_history
    WHERE user_id = :uid
    ORDER BY calculated_at DESC
    LIMIT 10
');
$histStmt->execute(['uid' => $userId]);
$history = $histStmt->fetchAll() ?: [];

$mgrid_page_title = mgrid_title('title.admin_mscore_detail');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-header">
    <h1 class="mgrid-card-title"><i class="ti ti-chart-infographic"></i> User M-SCORE Detail</h1>
    <a href="<?= e(url('admin/admin_mscores.php')) ?>" class="btn-mgrid btn-mgrid-ghost">Back</a>
  </div>
  <div class="mgrid-card-body">
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
      <div>
        <div><strong><?= e((string) $user['full_name']) ?></strong></div>
        <div class="small text-muted"><?= e((string) $user['m_id']) ?> · <?= e((string) $user['email']) ?></div>
      </div>
      <a class="btn-mgrid btn-mgrid-outline" href="<?= e(url('recalculate_mscore.php?user_id=' . $userId . '&from=admin_detail')) ?>">Recalculate Score</a>
    </div>
    <div class="d-flex align-items-center gap-2">
      <div class="mgrid-mono-id" style="font-size:28px; color:var(--mgrid-gold-600);"><?= number_format((float) $current['total_score'], 2) ?></div>
      <span class="badge text-bg-<?= e(mscore_tier_badge_class((string) $current['tier_label'])) ?>"><?= e((string) $current['tier_label']) ?></span>
      <span class="small text-muted"><?= e((string) ($current['readiness_label'] ?? mscore_readiness_label((float) $current['total_score']))) ?></span>
    </div>
  </div>
</div>

<div class="mgrid-grid-2">
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-chart-bar"></i> Breakdown</h2></div>
    <div class="mgrid-card-body">
      <?php foreach (($current['breakdown'] ?? []) as $item): ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between">
            <strong><?= e((string) ($item['category_name'] ?? $item['category_key'])) ?></strong>
            <span><?= number_format((float) ($item['points_awarded'] ?? 0), 2) ?> / <?= number_format((float) ($item['max_points'] ?? 0), 2) ?></span>
          </div>
          <div class="mgrid-progress-track mt-1"><div class="mgrid-progress-fill" style="width: <?= max(0, min(100, (float) ($item['percentage'] ?? 0))) ?>%;"></div></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div class="mgrid-card">
    <div class="mgrid-card-header"><h2 class="mgrid-card-title"><i class="ti ti-history"></i> Score History</h2></div>
    <div class="mgrid-card-body">
      <?php if ($history === []): ?>
        <p class="text-muted mb-0">No history yet.</p>
      <?php else: ?>
        <ul class="list-unstyled mb-0">
          <?php foreach ($history as $h): ?>
            <li class="mb-2 pb-2 border-bottom">
              <strong><?= number_format((float) $h['total_score'], 2) ?></strong>
              <span class="badge text-bg-<?= e(mscore_tier_badge_class((string) $h['tier_label'])) ?> ms-1"><?= e((string) $h['tier_label']) ?></span>
              <div class="small text-muted"><?= e(substr((string) $h['calculated_at'], 0, 16)) ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
