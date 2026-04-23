<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_verify(is_string($token) ? $token : null)) {
        flash_set('error', __('settings.error.token'));
        redirect('admin/admin_benefits.php');
    }
    $action = clean_string($_POST['action'] ?? '');
    if ($action === 'toggle_active') {
        $oid = (int) ($_POST['offer_id'] ?? 0);
        if ($oid > 0 && mbenefits_module_ready($pdo)) {
            $st = $pdo->prepare('UPDATE benefit_offers SET is_active = 1 - is_active WHERE id = :id LIMIT 1');
            $st->execute(['id' => $oid]);
            flash_set('success', __('admin.benefits.visibility_updated'));
        }
    }
    redirect('admin/admin_benefits.php');
}

$ready = mbenefits_module_ready($pdo);
$showAll = ($_GET['all'] ?? '') === '1';
$offers = [];
if ($ready) {
    $sql = '
        SELECT o.*, c.name AS category_name, p.name AS provider_name
        FROM benefit_offers o
        INNER JOIN benefit_categories c ON c.id = o.category_id
        INNER JOIN benefit_providers p ON p.id = o.provider_id
    ';
    if (!$showAll) {
        $sql .= ' WHERE o.is_active = 1';
    }
    $sql .= ' ORDER BY o.updated_at DESC LIMIT 200';
    $offers = $pdo->query($sql)->fetchAll() ?: [];
}

$mgrid_page_title = mgrid_title('title.admin_benefits');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2">
    <h1 class="mgrid-card-title mb-0"><i class="ti ti-gift"></i> Benefit offers</h1>
    <div class="d-flex flex-wrap gap-2">
      <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin/admin_benefits.php?all=' . ($showAll ? '0' : '1'))) ?>"><?= $showAll ? 'Active only' : 'Show all' ?></a>
      <a class="btn-mgrid btn-mgrid-primary btn-sm" href="<?= e(url('admin/add_benefit.php')) ?>">Add offer</a>
      <a class="btn-mgrid btn-mgrid-outline btn-sm" href="<?= e(url('admin/admin_benefit_claims.php')) ?>">Claims</a>
      <a class="btn-mgrid btn-mgrid-outline btn-sm" href="<?= e(url('admin/manage_benefit_categories.php')) ?>">Categories</a>
      <a class="btn-mgrid btn-mgrid-outline btn-sm" href="<?= e(url('admin/manage_benefit_providers.php')) ?>">Providers</a>
    </div>
  </div>
</div>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">Import <code>database/m_grid_mbenefits.sql</code> first.</div>
<?php else: ?>
  <div class="mgrid-card">
    <div class="mgrid-card-body p-0">
      <div class="table-responsive">
        <table class="mgrid-table mb-0">
          <thead>
            <tr>
              <th>Title</th>
              <th>Category</th>
              <th>Provider</th>
              <th>Value</th>
              <th>Valid</th>
              <th>Active</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php if ($offers === []): ?><tr><td colspan="7" class="text-center p-4 text-muted">No offers.</td></tr><?php endif; ?>
            <?php foreach ($offers as $o): ?>
              <tr>
                <td><strong><?= e((string) $o['title']) ?></strong><div class="small text-muted"><?= e((string) $o['slug']) ?></div></td>
                <td><?= e((string) $o['category_name']) ?></td>
                <td><?= e((string) $o['provider_name']) ?></td>
                <td><?= e((string) $o['value_label']) ?></td>
                <td class="small"><?= e((string) $o['valid_from']) ?> → <?= e((string) $o['valid_to']) ?></td>
                <td><span class="badge text-bg-<?= (int) $o['is_active'] === 1 ? 'success' : 'secondary' ?>"><?= (int) $o['is_active'] === 1 ? 'Yes' : 'No' ?></span></td>
                <td class="text-nowrap">
                  <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/edit_benefit.php?id=' . (int) $o['id'])) ?>">Edit</a>
                  <form method="post" class="d-inline" onsubmit="return confirm('Toggle active state?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="offer_id" value="<?= (int) $o['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Toggle</button>
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
