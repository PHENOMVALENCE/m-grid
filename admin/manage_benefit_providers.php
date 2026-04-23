<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

if (!mbenefits_module_ready($pdo)) {
    flash_set('error', __('ben.schema_missing'));
    redirect('admin/admin_benefits.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_verify(is_string($token) ? $token : null)) {
        flash_set('error', __('settings.error.token'));
        redirect('admin/manage_benefit_providers.php');
    }
    $action = clean_string($_POST['action'] ?? '');

    if ($action === 'add') {
        $name = clean_string($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9_]+/', '_', clean_string($_POST['slug'] ?? '')) ?? '');
        $slug = trim((string) $slug, '_');
        $email = clean_string($_POST['contact_email'] ?? '');
        $web = clean_string($_POST['website_url'] ?? '');
        $desc = clean_string($_POST['description'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($name === '' || $slug === '') {
            flash_set('error', __('error.name_slug_required'));
        } else {
            try {
                $pdo->prepare('
                  INSERT INTO benefit_providers (name, slug, contact_email, website_url, description, is_active)
                  VALUES (:n,:s,:e,:w,:d,:a)
                ')->execute([
                    'n' => $name,
                    's' => $slug,
                    'e' => $email !== '' ? $email : null,
                    'w' => $web !== '' ? $web : null,
                    'd' => $desc !== '' ? $desc : null,
                    'a' => $active,
                ]);
                flash_set('success', __('ben.prov.added'));
            } catch (Throwable $e) {
                flash_set('error', __('error.save_duplicate_slug'));
            }
        }
    } elseif ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = clean_string($_POST['name'] ?? '');
        $slug = strtolower(preg_replace('/[^a-z0-9_]+/', '_', clean_string($_POST['slug'] ?? '')) ?? '');
        $slug = trim((string) $slug, '_');
        $email = clean_string($_POST['contact_email'] ?? '');
        $web = clean_string($_POST['website_url'] ?? '');
        $desc = clean_string($_POST['description'] ?? '');
        $active = isset($_POST['is_active']) ? 1 : 0;
        if ($id <= 0 || $name === '' || $slug === '') {
            flash_set('error', __('error.invalid_selection'));
        } else {
            try {
                $pdo->prepare('
                  UPDATE benefit_providers SET name=:n, slug=:s, contact_email=:e, website_url=:w, description=:d, is_active=:a
                  WHERE id=:id LIMIT 1
                ')->execute([
                    'n' => $name,
                    's' => $slug,
                    'e' => $email !== '' ? $email : null,
                    'w' => $web !== '' ? $web : null,
                    'd' => $desc !== '' ? $desc : null,
                    'a' => $active,
                    'id' => $id,
                ]);
                flash_set('success', __('ben.prov.saved'));
            } catch (Throwable $e) {
                flash_set('error', __('error.save_failed'));
            }
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare('DELETE FROM benefit_providers WHERE id = :id LIMIT 1')->execute(['id' => $id]);
                flash_set('success', __('ben.prov.deleted'));
            } catch (Throwable $e) {
                flash_set('error', __('ben.prov.delete_blocked'));
            }
        }
    }
    redirect('admin/manage_benefit_providers.php');
}

$rows = $pdo->query('SELECT * FROM benefit_providers ORDER BY name ASC')->fetchAll() ?: [];
$editId = (int) ($_GET['edit'] ?? 0);
$editRow = null;
if ($editId > 0) {
    $st = $pdo->prepare('SELECT * FROM benefit_providers WHERE id = :id LIMIT 1');
    $st->execute(['id' => $editId]);
    $editRow = $st->fetch() ?: null;
}

$mgrid_page_title = mgrid_title('title.ben_providers');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mb-3"><a href="<?= e(url('admin/admin_benefits.php')) ?>" class="btn btn-sm btn-outline-secondary">← Offers</a></div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="mgrid-card">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title"><?= $editRow ? 'Edit provider' : 'Add provider' ?></h2></div>
      <div class="mgrid-card-body">
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="<?= $editRow ? 'update' : 'add' ?>">
          <?php if ($editRow): ?><input type="hidden" name="id" value="<?= (int) $editRow['id'] ?>"><?php endif; ?>
          <div class="mb-2">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="mgrid-form-control" required value="<?= e((string) ($editRow['name'] ?? '')) ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Slug</label>
            <input type="text" name="slug" class="mgrid-form-control" required value="<?= e((string) ($editRow['slug'] ?? '')) ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Contact email</label>
            <input type="email" name="contact_email" class="mgrid-form-control" value="<?= e((string) ($editRow['contact_email'] ?? '')) ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Website URL</label>
            <input type="url" name="website_url" class="mgrid-form-control" value="<?= e((string) ($editRow['website_url'] ?? '')) ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Description</label>
            <textarea name="description" class="mgrid-form-control" rows="3"><?= e((string) ($editRow['description'] ?? '')) ?></textarea>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="is_active" id="pa" <?= !$editRow || (int) ($editRow['is_active'] ?? 0) === 1 ? 'checked' : '' ?>>
            <label class="form-check-label" for="pa">Active</label>
          </div>
          <button type="submit" class="btn-mgrid btn-mgrid-primary"><?= $editRow ? 'Save' : 'Add' ?></button>
          <?php if ($editRow): ?><a class="btn btn-outline-secondary ms-1" href="<?= e(url('admin/manage_benefit_providers.php')) ?>">Cancel</a><?php endif; ?>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-7">
    <div class="mgrid-card">
      <div class="mgrid-card-header"><h2 class="mgrid-card-title">All providers</h2></div>
      <div class="mgrid-card-body p-0">
        <div class="table-responsive">
          <table class="mgrid-table mb-0">
            <thead><tr><th>Name</th><th>Slug</th><th>Active</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($rows as $r): ?>
                <tr>
                  <td><?= e((string) $r['name']) ?></td>
                  <td><code><?= e((string) $r['slug']) ?></code></td>
                  <td><?= (int) $r['is_active'] === 1 ? 'Yes' : 'No' ?></td>
                  <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/manage_benefit_providers.php?edit=' . (int) $r['id'])) ?>">Edit</a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Delete?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
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
