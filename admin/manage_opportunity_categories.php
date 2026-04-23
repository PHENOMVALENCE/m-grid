<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

if (!opportunities_module_ready($pdo)) {
    flash_set('error', __('error.schema_missing'));
    redirect('admin/admin_opportunities.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_verify(is_string($token) ? $token : null)) {
        flash_set('error', __('settings.error.token'));
        redirect('admin/manage_opportunity_categories.php');
    }
    $action = clean_string($_POST['action'] ?? '');

    if ($action === 'add') {
        $name = clean_string($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9_]+/', '_', clean_string($_POST['slug'] ?? '')) ?? '');
        $slug = trim($slug, '_');
        $sort = (int) ($_POST['sort_order'] ?? 10);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '' || $slug === '') {
            flash_set('error', __('error.name_slug_required'));
        } else {
            try {
                $pdo->prepare('INSERT INTO opportunity_categories (name, slug, sort_order, is_active) VALUES (:n,:s,:o,:a)')
                    ->execute(['n' => $name, 's' => $slug, 'o' => $sort, 'a' => $active]);
                flash_set('success', __('admin.generic.added'));
            } catch (Throwable $e) {
                flash_set('error', __('opp.cat.add_fail'));
            }
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = clean_string($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9_]+/', '_', clean_string($_POST['slug'] ?? '')) ?? '');
        $slug = trim($slug, '_');
        $sort = (int) ($_POST['sort_order'] ?? 10);
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($id <= 0 || $name === '' || $slug === '') {
            flash_set('error', __('opp.cat.invalid'));
        } else {
            try {
                $pdo->prepare('UPDATE opportunity_categories SET name=:n, slug=:s, sort_order=:o, is_active=:a WHERE id=:id LIMIT 1')
                    ->execute(['n' => $name, 's' => $slug, 'o' => $sort, 'a' => $active, 'id' => $id]);
                flash_set('success', __('admin.generic.saved'));
            } catch (Throwable $e) {
                flash_set('error', __('opp.cat.save_fail'));
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM opportunity_categories WHERE id = :id LIMIT 1')->execute(['id' => $id]);
                flash_set('success', __('admin.generic.deleted'));
            } catch (Throwable $e) {
                flash_set('error', __('opp.cat.delete_blocked'));
            }
        }
    }
    redirect('admin/manage_opportunity_categories.php');
}

$rows = $pdo->query('SELECT * FROM opportunity_categories ORDER BY sort_order, name')->fetchAll() ?: [];
$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM opportunity_categories WHERE id = :id LIMIT 1');
    $st->execute(['id' => $editId]);
    $editRow = $st->fetch() ?: null;
}

$mgrid_page_title = mgrid_title('title.opp_categories');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mb-3"><a href="<?= e(url('admin/admin_opportunities.php')) ?>" class="btn btn-sm btn-outline-secondary">← Listings</a></div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="mgrid-card">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title"><?= $editRow ? 'Edit' : 'Add' ?> category</h2></div>
      <div class="mgrid-card-body">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'add' ?>">
          <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int) $editRow['id'] ?>"><?php endif; ?>
          <div class="mb-2"><label class="form-label">Name</label><input class="mgrid-form-control" name="name" required value="<?= e((string) ($editRow['name'] ?? '')) ?>"></div>
          <div class="mb-2"><label class="form-label">Slug</label><input class="mgrid-form-control" name="slug" required value="<?= e((string) ($editRow['slug'] ?? '')) ?>"></div>
          <div class="mb-2"><label class="form-label">Sort</label><input type="number" class="mgrid-form-control" name="sort_order" value="<?= (int) ($editRow['sort_order'] ?? 10) ?>"></div>
          <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" id="ia" <?= !$editRow || (int) ($editRow['is_active'] ?? 0) ? 'checked' : '' ?>><label class="form-check-label" for="ia">Active</label></div>
          <button class="btn-mgrid btn-mgrid-primary"><?= $editRow ? 'Save' : 'Add' ?></button>
          <?php if ($editRow): ?><a class="btn btn-outline-secondary ms-1" href="<?= e(url('admin/manage_opportunity_categories.php')) ?>">Cancel</a><?php endif; ?>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="mgrid-card">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">All</h2></div>
      <div class="mgrid-card-body p-0">
        <div class="table-responsive">
          <table class="mgrid-table mb-0">
            <thead><tr><th>Name</th><th>Slug</th><th>Order</th><th>Active</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= e((string) $r['name']) ?></td>
                  <td><code><?= e((string) $r['slug']) ?></code></td>
                  <td><?= (int) $r['sort_order'] ?></td>
                  <td><?= (int) $r['is_active'] ? 'Yes' : 'No' ?></td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/manage_opportunity_categories.php?edit=' . (int) $r['id'])) ?>">Edit</a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete?');"><?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
