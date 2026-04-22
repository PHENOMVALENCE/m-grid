<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$pdo = db();
$actor = auth_admin();
$isSuperAdmin = auth_is_super_admin();
$actorId = (int) ($actor['admin_id'] ?? 0);
$errors = [];
$formData = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'title' => '',
    'department' => '',
    'role' => 'admin',
    'status' => 'active',
];

$adminIdAllocateNext = static function (PDO $pdoConn): string {
    $year = (int) date('Y');
    $upd = $pdoConn->prepare('
        INSERT INTO admin_id_counters (year, last_number)
        VALUES (:y, 1)
        ON DUPLICATE KEY UPDATE last_number = last_number + 1
    ');
    $upd->execute(['y' => $year]);

    $sel = $pdoConn->prepare('SELECT last_number FROM admin_id_counters WHERE year = :y LIMIT 1');
    $sel->execute(['y' => $year]);
    $row = $sel->fetch();
    $n = (int) ($row['last_number'] ?? 1);

    return sprintf('ADM-%d-%04d', $year, $n);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isSuperAdmin) {
        $errors[] = 'Only super admins can manage admin accounts.';
    } elseif (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $action = clean_string($_POST['action'] ?? '');

        if ($action === 'create_admin') {
            $fullName = clean_string($_POST['full_name'] ?? '');
            $email = strtolower(clean_string($_POST['email'] ?? ''));
            $phone = clean_string($_POST['phone'] ?? '');
            $title = clean_string($_POST['title'] ?? '');
            $department = clean_string($_POST['department'] ?? '');
            $role = clean_string($_POST['role'] ?? 'admin');
            $status = clean_string($_POST['status'] ?? 'active');
            $password = (string) ($_POST['password'] ?? '');
            $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
            $formData = [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'title' => $title,
                'department' => $department,
                'role' => $role,
                'status' => $status,
            ];

            if (mb_strlen($fullName) < 3) {
                $errors[] = 'Full name should be at least 3 characters.';
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Provide a valid email address.';
            }
            if (!in_array($role, ['admin', 'super_admin'], true)) {
                $errors[] = 'Invalid role selected.';
            }
            if (!in_array($status, ['active', 'disabled'], true)) {
                $errors[] = 'Invalid status selected.';
            }
            if ($phone !== '' && mb_strlen($phone) > 32) {
                $errors[] = 'Phone is too long.';
            }
            if ($title !== '' && mb_strlen($title) > 120) {
                $errors[] = 'Title is too long.';
            }
            if ($department !== '' && mb_strlen($department) > 120) {
                $errors[] = 'Department is too long.';
            }
            if (mb_strlen($password) < 8) {
                $errors[] = 'Password should be at least 8 characters.';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = 'Passwords do not match.';
            }

            if ($errors === []) {
                $chk = $pdo->prepare('SELECT id FROM admins WHERE email = :email LIMIT 1');
                $chk->execute(['email' => $email]);
                if ($chk->fetch()) {
                    $errors[] = 'An admin with this email already exists.';
                }
            }

            if ($errors === []) {
                try {
                    $pdo->beginTransaction();
                    $newAdminCode = $adminIdAllocateNext($pdo);
                    $ins = $pdo->prepare('
                        INSERT INTO admins (admin_id, full_name, email, phone, title, department, password_hash, role, status)
                        VALUES (:admin_id, :full_name, :email, :phone, :title, :department, :password_hash, :role, :status)
                    ');
                    $ins->execute([
                        'admin_id' => $newAdminCode,
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => ($phone !== '') ? $phone : null,
                        'title' => ($title !== '') ? $title : null,
                        'department' => ($department !== '') ? $department : null,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                        'status' => $status,
                    ]);
                    $pdo->commit();
                    flash_set('success', 'Admin account created successfully.');
                    redirect('admin/admin_accounts.php');
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $errors[] = 'Unable to create admin account right now.';
                }
            }
        } elseif ($action === 'update_admin') {
            $targetId = (int) ($_POST['target_id'] ?? 0);
            $fullName = clean_string($_POST['full_name'] ?? '');
            $email = strtolower(clean_string($_POST['email'] ?? ''));
            $phone = clean_string($_POST['phone'] ?? '');
            $title = clean_string($_POST['title'] ?? '');
            $department = clean_string($_POST['department'] ?? '');
            $role = clean_string($_POST['role'] ?? '');
            $status = clean_string($_POST['status'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');

            if ($targetId <= 0) {
                $errors[] = 'Invalid admin selected.';
            }
            if (mb_strlen($fullName) < 3) {
                $errors[] = 'Full name should be at least 3 characters.';
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Provide a valid email address.';
            }
            if ($phone !== '' && mb_strlen($phone) > 32) {
                $errors[] = 'Phone is too long.';
            }
            if ($title !== '' && mb_strlen($title) > 120) {
                $errors[] = 'Title is too long.';
            }
            if ($department !== '' && mb_strlen($department) > 120) {
                $errors[] = 'Department is too long.';
            }
            if (!in_array($role, ['admin', 'super_admin'], true)) {
                $errors[] = 'Invalid role selected.';
            }
            if (!in_array($status, ['active', 'disabled'], true)) {
                $errors[] = 'Invalid status selected.';
            }
            if ($newPassword !== '' && mb_strlen($newPassword) < 8) {
                $errors[] = 'New password should be at least 8 characters.';
            }

            $target = null;
            if ($errors === []) {
                $stTarget = $pdo->prepare('SELECT id, role, status, email FROM admins WHERE id = :id LIMIT 1');
                $stTarget->execute(['id' => $targetId]);
                $target = $stTarget->fetch();
                if (!$target) {
                    $errors[] = 'Selected admin account was not found.';
                }
            }

            if ($errors === []) {
                $chk = $pdo->prepare('SELECT id FROM admins WHERE email = :email AND id <> :id LIMIT 1');
                $chk->execute(['email' => $email, 'id' => $targetId]);
                if ($chk->fetch()) {
                    $errors[] = 'Another admin already uses this email.';
                }
            }

            if ($errors === [] && $target) {
                if ($targetId === $actorId && $status !== 'active') {
                    $errors[] = 'You cannot disable your own admin account.';
                }

                if ((string) $target['role'] === 'super_admin' && $role === 'admin') {
                    $countSuper = (int) ($pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin'")->fetchColumn() ?: 0);
                    if ($countSuper <= 1) {
                        $errors[] = 'At least one super admin must remain.';
                    }
                }
            }

            if ($errors === []) {
                if ($newPassword !== '') {
                    $up = $pdo->prepare('
                        UPDATE admins
                        SET full_name = :full_name,
                            email = :email,
                            phone = :phone,
                            title = :title,
                            department = :department,
                            role = :role,
                            status = :status,
                            password_hash = :password_hash
                        WHERE id = :id
                        LIMIT 1
                    ');
                    $up->execute([
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => ($phone !== '') ? $phone : null,
                        'title' => ($title !== '') ? $title : null,
                        'department' => ($department !== '') ? $department : null,
                        'role' => $role,
                        'status' => $status,
                        'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                        'id' => $targetId,
                    ]);
                } else {
                    $up = $pdo->prepare('
                        UPDATE admins
                        SET full_name = :full_name,
                            email = :email,
                            phone = :phone,
                            title = :title,
                            department = :department,
                            role = :role,
                            status = :status
                        WHERE id = :id
                        LIMIT 1
                    ');
                    $up->execute([
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => ($phone !== '') ? $phone : null,
                        'title' => ($title !== '') ? $title : null,
                        'department' => ($department !== '') ? $department : null,
                        'role' => $role,
                        'status' => $status,
                        'id' => $targetId,
                    ]);
                }
                flash_set('success', 'Admin account updated.');
                redirect('admin/admin_accounts.php');
            }
        }
    }
}

$admins = $pdo->query("
    SELECT id, admin_id, full_name, email, phone, title, department, role, status, created_at
    FROM admins
    ORDER BY created_at DESC
")->fetchAll() ?: [];
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$selectedAdmin = null;
if ($editId > 0) {
    foreach ($admins as $adminRow) {
        if ((int) ($adminRow['id'] ?? 0) === $editId) {
            $selectedAdmin = $adminRow;
            break;
        }
    }
}

$adminCount = count($admins);
$activeCount = 0;
$superAdminCount = 0;
$disabledCount = 0;
foreach ($admins as $row) {
    if ((string) ($row['status'] ?? '') === 'active') {
        $activeCount++;
    } else {
        $disabledCount++;
    }
    if ((string) ($row['role'] ?? '') === 'super_admin') {
        $superAdminCount++;
    }
}

$mgrid_page_title = 'Admin Accounts — M GRID';
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-page-head mgrid-admin-accounts-head">
  <p class="mgrid-admin-accounts-kicker mb-1">SYSTEM GOVERNANCE</p>
  <h1 class="mb-2">Admin accounts</h1>
  <p class="mb-0">Manage privileged access, account roles, and administrative profile records in one secure workspace.</p>
</div>

<div class="mgrid-grid-4 mgrid-page-section">
  <article class="mgrid-stat-card mgrid-admin-stat">
    <p class="mgrid-stat-label">Total admins</p>
    <p class="mgrid-stat-mid-value mb-0"><?= e((string) $adminCount) ?></p>
  </article>
  <article class="mgrid-stat-card mgrid-admin-stat">
    <p class="mgrid-stat-label">Active</p>
    <p class="mgrid-stat-mid-value mb-0"><?= e((string) $activeCount) ?></p>
  </article>
  <article class="mgrid-stat-card mgrid-admin-stat">
    <p class="mgrid-stat-label">Super admins</p>
    <p class="mgrid-stat-mid-value mb-0"><?= e((string) $superAdminCount) ?></p>
  </article>
  <article class="mgrid-stat-card mgrid-admin-stat">
    <p class="mgrid-stat-label">Disabled</p>
    <p class="mgrid-stat-mid-value mb-0"><?= e((string) $disabledCount) ?></p>
  </article>
</div>

<div class="mgrid-card mgrid-page-section">
  <div class="mgrid-card-header">
    <h2 class="mgrid-card-title mb-0"><i class="ti ti-user-star"></i> Admin directory</h2>
    <span class="badge text-bg-secondary"><?= e((string) $adminCount) ?> records</span>
  </div>
  <div class="mgrid-card-body p-0 mgrid-admin-table-wrap">
    <?php if ($msg = flash_get('success')): ?>
      <div class="alert alert-success m-3 mb-0"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger m-3 mb-0"><?= e($err) ?></div>
    <?php endforeach; ?>
    <?php if (!$isSuperAdmin): ?>
      <div class="alert alert-warning m-3 mb-0">Read-only mode: only super admins can create or update admin accounts.</div>
    <?php endif; ?>
    <div class="table-responsive">
      <table class="mgrid-table mb-0" id="adminAccountsTable">
        <thead>
          <tr>
            <th>Admin ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Profile details</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created</th>
            <?php if ($isSuperAdmin): ?>
              <th class="text-end">Manage</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php if ($admins === []): ?>
            <tr>
              <td colspan="<?= $isSuperAdmin ? 8 : 7 ?>" class="text-center py-4 text-muted">No admin accounts found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($admins as $a): ?>
              <tr>
                <td class="mgrid-table-mid-cell"><?= e((string) ($a['admin_id'] ?? '')) ?></td>
                <td>
                  <div class="fw-semibold"><?= e((string) ($a['full_name'] ?? '')) ?></div>
                </td>
                <td>
                  <div><?= e((string) ($a['email'] ?? '')) ?></div>
                </td>
                <td>
                  <div class="small mgrid-admin-profile-meta">
                    <?= e((string) (($a['title'] ?? '') !== '' ? $a['title'] : 'No title set')) ?>
                    <?= (($a['department'] ?? '') !== '') ? ' · ' . e((string) $a['department']) : '' ?>
                    <br>
                    <?= e((string) (($a['phone'] ?? '') !== '' ? $a['phone'] : 'No phone on file')) ?>
                  </div>
                </td>
                <td><span class="badge text-bg-<?= (($a['role'] ?? '') === 'super_admin') ? 'dark' : 'secondary' ?>"><?= e((string) ($a['role'] ?? 'admin')) ?></span></td>
                <td><span class="badge text-bg-<?= (($a['status'] ?? '') === 'active') ? 'success' : 'secondary' ?>"><?= e((string) ($a['status'] ?? '')) ?></span></td>
                <td><?= e(substr((string) ($a['created_at'] ?? ''), 0, 10)) ?></td>
                <?php if ($isSuperAdmin): ?>
                  <td class="text-end">
                    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('admin/admin_accounts.php')) . '?edit=' . (int) ($a['id'] ?? 0) ?>#admin-manage-panel">Manage</a>
                  </td>
                <?php endif; ?>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php if ($isSuperAdmin): ?>
  <div class="mgrid-card mgrid-page-section" id="admin-manage-panel">
    <div class="mgrid-card-header">
      <h2 class="mgrid-card-title mb-0"><i class="ti ti-adjustments"></i> Manage selected admin</h2>
      <?php if ($selectedAdmin): ?>
        <span class="badge text-bg-dark"><?= e((string) ($selectedAdmin['admin_id'] ?? '')) ?></span>
      <?php endif; ?>
    </div>
    <div class="mgrid-card-body">
      <?php if (!$selectedAdmin): ?>
        <div class="mgrid-empty py-4">
          <p class="mb-0">Choose an admin from the directory table and click <strong>Manage</strong> to edit profile, role, status, or reset password.</p>
        </div>
      <?php else: ?>
        <form method="post" class="row g-3 mgrid-admin-manage-form" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="update_admin">
          <input type="hidden" name="target_id" value="<?= (int) ($selectedAdmin['id'] ?? 0) ?>">
          <div class="col-md-6">
            <label class="form-label" for="edit_full_name">Full name</label>
            <input class="form-control" id="edit_full_name" name="full_name" value="<?= e((string) ($selectedAdmin['full_name'] ?? '')) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="edit_email">Email</label>
            <input class="form-control" type="email" id="edit_email" name="email" value="<?= e((string) ($selectedAdmin['email'] ?? '')) ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="edit_phone">Phone</label>
            <input class="form-control" id="edit_phone" name="phone" value="<?= e((string) ($selectedAdmin['phone'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="edit_title">Title</label>
            <input class="form-control" id="edit_title" name="title" value="<?= e((string) ($selectedAdmin['title'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="edit_department">Department</label>
            <input class="form-control" id="edit_department" name="department" value="<?= e((string) ($selectedAdmin['department'] ?? '')) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label" for="edit_role">Role</label>
            <select class="form-select" id="edit_role" name="role">
              <option value="admin" <?= (($selectedAdmin['role'] ?? '') === 'admin') ? 'selected' : '' ?>>admin</option>
              <option value="super_admin" <?= (($selectedAdmin['role'] ?? '') === 'super_admin') ? 'selected' : '' ?>>super_admin</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="edit_status">Status</label>
            <select class="form-select" id="edit_status" name="status">
              <option value="active" <?= (($selectedAdmin['status'] ?? '') === 'active') ? 'selected' : '' ?>>active</option>
              <option value="disabled" <?= (($selectedAdmin['status'] ?? '') === 'disabled') ? 'selected' : '' ?>>disabled</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="edit_new_password">Reset password</label>
            <input class="form-control" type="password" id="edit_new_password" name="new_password" placeholder="Optional new password">
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4">Save changes</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="mgrid-card mgrid-page-section">
    <div class="mgrid-card-header">
      <h2 class="mgrid-card-title mb-0"><i class="ti ti-user-plus"></i> Create new admin</h2>
      <span class="badge text-bg-success">Super admin only</span>
    </div>
    <div class="mgrid-card-body">
      <form method="post" class="row g-3 mgrid-admin-create-form" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create_admin">
        <div class="col-md-6">
          <label class="form-label" for="full_name">Full name</label>
          <input class="form-control" id="full_name" name="full_name" value="<?= e($formData['full_name']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="email">Email</label>
          <input class="form-control" type="email" id="email" name="email" value="<?= e($formData['email']) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="phone">Phone</label>
          <input class="form-control" id="phone" name="phone" value="<?= e($formData['phone']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="title">Title</label>
          <input class="form-control" id="title" name="title" value="<?= e($formData['title']) ?>" placeholder="e.g. Compliance Lead">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="department">Department</label>
          <input class="form-control" id="department" name="department" value="<?= e($formData['department']) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="role">Role</label>
          <select class="form-select" id="role" name="role">
            <option value="admin" <?= ($formData['role'] === 'admin') ? 'selected' : '' ?>>admin</option>
            <option value="super_admin" <?= ($formData['role'] === 'super_admin') ? 'selected' : '' ?>>super_admin</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="status">Status</label>
          <select class="form-select" id="status" name="status">
            <option value="active" <?= ($formData['status'] === 'active') ? 'selected' : '' ?>>active</option>
            <option value="disabled" <?= ($formData['status'] === 'disabled') ? 'selected' : '' ?>>disabled</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="password">Password</label>
          <input class="form-control" type="password" id="password" name="password" minlength="8" required>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="password_confirm">Confirm password</label>
          <input class="form-control" type="password" id="password_confirm" name="password_confirm" minlength="8" required>
        </div>
        <div class="col-12 d-flex justify-content-end">
          <button type="submit" class="btn btn-primary px-4">Create admin</button>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
  (function () {
    var $table = $("#adminAccountsTable");
    if (!$table.length || typeof $.fn.DataTable !== "function") {
      return;
    }

    if ($.fn.DataTable.isDataTable($table)) {
      $table.DataTable().destroy();
    }

    $table.DataTable({
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      order: [[6, "desc"]],
      responsive: false,
      autoWidth: false,
      language: {
        search: "Search admins:",
        lengthMenu: "Show _MENU_",
        info: "Showing _START_ to _END_ of _TOTAL_ admins",
        infoEmpty: "No admins available",
        zeroRecords: "No matching admin accounts found"
      },
      columnDefs: [
        { orderable: false, targets: <?= $isSuperAdmin ? '[7]' : '[]' ?> }
      ]
    });
  })();
</script>

<?php require __DIR__ . '/includes/shell_close.php';
