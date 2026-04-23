<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$ready = mbenefits_module_ready($pdo);

$claims = [];
if ($ready) {
    $st = $pdo->prepare('
        SELECT c.*, o.title AS offer_title, p.name AS provider_name
        FROM benefit_claims c
        INNER JOIN benefit_offers o ON o.id = c.benefit_offer_id
        INNER JOIN benefit_providers p ON p.id = o.provider_id
        WHERE c.user_id = :u
        ORDER BY c.claimed_at DESC
        LIMIT 100
    ');
    $st->execute(['u' => $uid]);
    $claims = $st->fetchAll() ?: [];
}

$mgrid_page_title = mgrid_title('title.my_benefits');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2">
    <div>
      <div class="mgrid-topbar-label">M-BENEFITS</div>
      <h1 class="mgrid-display mb-0" style="font-size:1.75rem;">My claims &amp; history</h1>
    </div>
    <a class="btn-mgrid btn-mgrid-primary align-self-center" href="<?= e(url('user/benefits.php')) ?>">Browse offers</a>
  </div>
</div>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">M-Benefits is not installed.</div>
<?php elseif ($claims === []): ?>
  <div class="mgrid-card"><div class="mgrid-card-body text-muted">You have not claimed any benefits yet.</div></div>
<?php else: ?>
  <div class="mgrid-card">
    <div class="mgrid-card-body p-0">
      <div class="table-responsive">
        <table class="mgrid-table mb-0">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Benefit</th>
              <th>Provider</th>
              <th>Claimed</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($claims as $c): ?>
              <tr>
                <td class="mgrid-table-mid-cell"><?= e((string) $c['claim_reference']) ?></td>
                <td><?= e((string) $c['offer_title']) ?></td>
                <td><?= e((string) $c['provider_name']) ?></td>
                <td><?= e(substr((string) $c['claimed_at'], 0, 16)) ?></td>
                <td><span class="badge text-bg-<?= e(mbenefits_claim_status_badge((string) $c['status'])) ?>"><?= e(mbenefits_claim_status_label((string) $c['status'])) ?></span></td>
                <td>
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(url('user/benefit_claim_detail.php?id=' . (int) $c['id'])) ?>">Details</a>
                  <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('user/benefit_detail.php?id=' . (int) $c['benefit_offer_id'])) ?>">Offer</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
