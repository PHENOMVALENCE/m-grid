<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$id = (int) ($_GET['id'] ?? 0);

if (!mbenefits_module_ready($pdo) || $id <= 0) {
    flash_set('error', __('benefit.claim_detail.not_found'));
    redirect('user/my_benefits.php');
}

$st = $pdo->prepare('
  SELECT c.*, o.title AS offer_title, o.id AS offer_id, p.name AS provider_name
  FROM benefit_claims c
  INNER JOIN benefit_offers o ON o.id = c.benefit_offer_id
  INNER JOIN benefit_providers p ON p.id = o.provider_id
  WHERE c.id = :id AND c.user_id = :u
  LIMIT 1
');
$st->execute(['id' => $id, 'u' => $uid]);
$claim = $st->fetch();
if (!$claim) {
    flash_set('error', __('benefit.claim_detail.not_found'));
    redirect('user/my_benefits.php');
}

$logs = $pdo->prepare('
  SELECT l.*, a.full_name AS admin_name
  FROM benefit_claim_logs l
  LEFT JOIN admins a ON a.id = l.admin_id
  WHERE l.claim_id = :cid
  ORDER BY l.created_at ASC
');
$logs->execute(['cid' => $id]);
$logRows = $logs->fetchAll() ?: [];

$mgrid_page_title = mgrid_title('title.claim_ref', ['ref' => (string) $claim['claim_reference']]);
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body">
    <div class="d-flex flex-wrap justify-content-between gap-2">
      <div>
        <div class="mgrid-topbar-label">Claim reference</div>
        <div class="mgrid-mono-id" style="font-size:1.25rem;"><?= e((string) $claim['claim_reference']) ?></div>
      </div>
      <span class="badge text-bg-<?= e(mbenefits_claim_status_badge((string) $claim['status'])) ?> align-self-center"><?= e(mbenefits_claim_status_label((string) $claim['status'])) ?></span>
    </div>
    <h1 class="h4 mt-3 mb-1"><?= e((string) $claim['offer_title']) ?></h1>
    <p class="text-muted mb-0"><?= e((string) $claim['provider_name']) ?> · <?= e(substr((string) $claim['claimed_at'], 0, 16)) ?></p>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="mgrid-card">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">Notes</h2></div>
      <div class="mgrid-card-body">
        <?php if (trim((string) ($claim['user_notes'] ?? '')) !== ''): ?>
          <p class="small text-muted mb-1">Your note</p>
          <p><?= e((string) $claim['user_notes']) ?></p>
        <?php endif; ?>
        <?php if (trim((string) ($claim['admin_remarks'] ?? '')) !== ''): ?>
          <p class="small text-muted mb-1">Admin</p>
          <p class="mb-0"><?= e((string) $claim['admin_remarks']) ?></p>
        <?php else: ?>
          <p class="text-muted mb-0">No admin remarks yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="mgrid-card">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">Activity</h2></div>
      <div class="mgrid-card-body">
        <?php if ($logRows === []): ?>
          <p class="text-muted mb-0">No log entries.</p>
        <?php else: ?>
          <ul class="list-unstyled mb-0">
            <?php foreach ($logRows as $l): ?>
              <li class="mb-2 pb-2 border-bottom">
                <div class="small text-muted"><?= e(substr((string) $l['created_at'], 0, 19)) ?></div>
                <div><?= e((string) ($l['old_status'] ?? '—')) ?> → <strong><?= e((string) $l['new_status']) ?></strong></div>
                <?php if (trim((string) ($l['note'] ?? '')) !== ''): ?><div class="small"><?= e((string) $l['note']) ?></div><?php endif; ?>
                <?php if (trim((string) ($l['admin_name'] ?? '')) !== ''): ?><div class="small text-muted">By <?= e((string) $l['admin_name']) ?></div><?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="d-flex gap-2">
  <a class="btn-mgrid btn-mgrid-outline" href="<?= e(url('user/my_benefits.php')) ?>">Back to history</a>
  <a class="btn-mgrid btn-mgrid-primary" href="<?= e(url('user/benefit_detail.php?id=' . (int) $claim['offer_id'])) ?>">Offer details</a>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
