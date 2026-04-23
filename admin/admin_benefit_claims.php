<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

$ready = mbenefits_module_ready($pdo);
$status = clean_string($_GET['status'] ?? '');
$q = clean_string($_GET['q'] ?? '');

$claims = [];
if ($ready) {
    $where = ['1=1'];
    $params = [];
    if ($status !== '' && in_array($status, ['pending', 'approved', 'rejected', 'redeemed', 'cancelled'], true)) {
        $where[] = 'c.status = :st';
        $params['st'] = $status;
    }
    if ($q !== '') {
        $where[] = '(c.claim_reference LIKE :q OR u.full_name LIKE :q2 OR u.m_id LIKE :q3 OR o.title LIKE :q4)';
        $like = '%' . $q . '%';
        $params['q'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
        $params['q4'] = $like;
    }
    $sql = '
      SELECT c.*, u.full_name, u.m_id, o.title AS offer_title, p.name AS provider_name
      FROM benefit_claims c
      INNER JOIN users u ON u.id = c.user_id
      INNER JOIN benefit_offers o ON o.id = c.benefit_offer_id
      INNER JOIN benefit_providers p ON p.id = o.provider_id
      WHERE ' . implode(' AND ', $where) . '
      ORDER BY c.claimed_at DESC
      LIMIT 200
    ';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $claims = $st->fetchAll() ?: [];
}

$mgrid_page_title = mgrid_title('title.admin_benefit_claims');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2">
    <h1 class="mgrid-card-title mb-0"><i class="ti ti-ticket"></i> Benefit claims</h1>
    <a class="btn-mgrid btn-mgrid-outline btn-sm" href="<?= e(url('admin/admin_benefits.php')) ?>">Offers</a>
  </div>
</div>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">Schema missing.</div>
<?php else: ?>
  <form method="get" class="mgrid-card mb-3 p-3 row g-2">
    <div class="col-md-3">
      <select name="status" class="mgrid-form-control">
        <option value="">All statuses</option>
        <?php foreach (['pending', 'approved', 'rejected', 'redeemed', 'cancelled'] as $s): ?>
          <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e(mbenefits_claim_status_label($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6"><input type="search" name="q" class="mgrid-form-control" placeholder="Reference, M-ID, name, offer" value="<?= e($q) ?>"></div>
    <div class="col-md-2 d-grid"><button class="btn-mgrid btn-mgrid-primary">Filter</button></div>
  </form>

  <div class="mgrid-card">
    <div class="mgrid-card-body p-0">
      <div class="table-responsive">
        <table class="mgrid-table mb-0">
          <thead>
            <tr>
              <th>Reference</th>
              <th>Member</th>
              <th>Offer</th>
              <th>Provider</th>
              <th>Date</th>
              <th>Status</th>
              <th>Update</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($claims === []): ?><tr><td colspan="7" class="text-center p-4 text-muted">No claims.</td></tr><?php endif; ?>
            <?php foreach ($claims as $c): ?>
              <tr>
                <td class="mgrid-table-mid-cell"><?= e((string) $c['claim_reference']) ?></td>
                <td><?= e((string) $c['full_name']) ?><div class="small text-muted"><?= e((string) $c['m_id']) ?></div></td>
                <td><?= e((string) $c['offer_title']) ?></td>
                <td><?= e((string) $c['provider_name']) ?></td>
                <td class="small"><?= e(substr((string) $c['claimed_at'], 0, 16)) ?></td>
                <td><span class="badge text-bg-<?= e(mbenefits_claim_status_badge((string) $c['status'])) ?>"><?= e(mbenefits_claim_status_label((string) $c['status'])) ?></span></td>
                <td>
                  <form method="post" action="<?= e(url('admin/update_benefit_claim_status.php')) ?>" class="d-flex flex-column gap-1" style="min-width:200px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="claim_id" value="<?= (int) $c['id'] ?>">
                    <select name="new_status" class="mgrid-form-control form-control-sm">
                      <?php foreach (['pending', 'approved', 'rejected', 'redeemed', 'cancelled'] as $s): ?>
                        <option value="<?= e($s) ?>" <?= ((string) $c['status']) === $s ? 'selected' : '' ?>><?= e(mbenefits_claim_status_label($s)) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <input type="text" name="admin_remarks" class="mgrid-form-control form-control-sm" placeholder="Admin note" value="<?= e((string) ($c['admin_remarks'] ?? '')) ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
                  </form>
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
