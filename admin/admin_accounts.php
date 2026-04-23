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

$roleLabel = static function (string $role): string {
    return $role === 'super_admin' ? __('admin.role.super') : __('admin.role.admin');
};

/** Short label for dense table cells (full label in `title`). */
$roleLabelShort = static function (string $role): string {
    return $role === 'super_admin' ? __('admin.role.super_short') : __('admin.role.admin_short');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isSuperAdmin) {
        $errors[] = __('admin_accounts.err_super_only');
    } elseif (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = __('settings.error.token');
    } else {
        $action = clean_string($_POST['action'] ?? '');

        if ($action === 'create_admin') {
            $fullName = clean_string($_POST['full_name'] ?? '');
            $email = strtolower(clean_string($_POST['email'] ?? ''));
            $phone = clean_string($_POST['phone'] ?? '');
            $title = clean_string($_POST['title'] ?? '');
            $department = clean_string($_POST['department'] ?? '');
            $status = clean_string($_POST['status'] ?? 'active');
            $password = (string) ($_POST['password'] ?? '');
            $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
            $role = 'admin';
            $formData = [
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'title' => $title,
                'department' => $department,
                'status' => $status,
            ];

            if (mb_strlen($fullName) < 3) {
                $errors[] = __('admin_accounts.err_name_short');
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = __('admin_accounts.err_email_invalid');
            }
            if (!in_array($status, ['active', 'disabled'], true)) {
                $errors[] = __('admin_accounts.err_status_invalid');
            }
            if ($phone === '' || mb_strlen($phone) < 8) {
                $errors[] = __('admin_accounts.err_phone_required');
            } elseif (mb_strlen($phone) > 32) {
                $errors[] = __('admin_accounts.err_phone_long');
            } else {
                $digitsOnly = preg_replace('/\D+/', '', $phone);
                if (strlen($digitsOnly) < 8) {
                    $errors[] = __('admin_accounts.err_phone_digits');
                }
            }
            if (mb_strlen($title) < 2) {
                $errors[] = __('admin_accounts.err_title_required');
            } elseif (mb_strlen($title) > 120) {
                $errors[] = __('admin_accounts.err_title_long');
            }
            if (mb_strlen($department) < 2) {
                $errors[] = __('admin_accounts.err_dept_required');
            } elseif (mb_strlen($department) > 120) {
                $errors[] = __('admin_accounts.err_dept_long');
            }
            if (mb_strlen($password) < 8) {
                $errors[] = __('admin_accounts.err_password_short');
            }
            if ($password !== $passwordConfirm) {
                $errors[] = __('admin_accounts.err_password_mismatch');
            }
            if ((string) ($_POST['create_acknowledged'] ?? '') !== '1') {
                $errors[] = __('admin_accounts.err_ack_required');
            }

            if ($errors === []) {
                $chk = $pdo->prepare('SELECT id FROM admins WHERE email = :email LIMIT 1');
                $chk->execute(['email' => $email]);
                if ($chk->fetch()) {
                    $errors[] = __('admin_accounts.err_email_exists');
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
                        'phone' => $phone,
                        'title' => $title,
                        'department' => $department,
                        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                        'role' => $role,
                        'status' => $status,
                    ]);
                    $pdo->commit();
                    flash_set('success', __('admin_accounts.flash_created'));
                    redirect('admin/admin_accounts.php');
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $errors[] = __('admin_accounts.err_create_failed');
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
                $errors[] = __('admin_accounts.err_invalid_admin');
            }
            if (mb_strlen($fullName) < 3) {
                $errors[] = __('admin_accounts.err_name_short');
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = __('admin_accounts.err_email_invalid');
            }
            if ($phone !== '' && mb_strlen($phone) > 32) {
                $errors[] = __('admin_accounts.err_phone_long');
            }
            if ($title !== '' && mb_strlen($title) > 120) {
                $errors[] = __('admin_accounts.err_title_long');
            }
            if ($department !== '' && mb_strlen($department) > 120) {
                $errors[] = __('admin_accounts.err_dept_long');
            }
            if (!in_array($role, ['admin', 'super_admin'], true)) {
                $errors[] = __('admin_accounts.err_role_invalid');
            }
            if (!in_array($status, ['active', 'disabled'], true)) {
                $errors[] = __('admin_accounts.err_status_invalid');
            }
            if ($newPassword !== '' && mb_strlen($newPassword) < 8) {
                $errors[] = __('admin_accounts.err_password_new_short');
            }

            $target = null;
            if ($errors === []) {
                $stTarget = $pdo->prepare('SELECT id, role, status, email FROM admins WHERE id = :id LIMIT 1');
                $stTarget->execute(['id' => $targetId]);
                $target = $stTarget->fetch();
                if (!$target) {
                    $errors[] = __('admin_accounts.err_admin_not_found');
                }
            }

            if ($errors === [] && $target) {
                $origRole = (string) ($target['role'] ?? '');
                if ($targetId !== $actorId && $origRole === 'super_admin') {
                    $errors[] = __('admin_accounts.err_super_peer');
                }
            }

            if ($errors === [] && $target) {
                $origRole = (string) ($target['role'] ?? '');
                if ($origRole === 'admin' && $role === 'super_admin' && (string) ($_POST['confirm_super_promotion'] ?? '') !== '1') {
                    $errors[] = __('admin_accounts.err_promo_checkbox');
                }
            }

            if ($errors === []) {
                $chk = $pdo->prepare('SELECT id FROM admins WHERE email = :email AND id <> :id LIMIT 1');
                $chk->execute(['email' => $email, 'id' => $targetId]);
                if ($chk->fetch()) {
                    $errors[] = __('admin_accounts.err_email_taken');
                }
            }

            if ($errors === [] && $target) {
                if ($targetId === $actorId && $status !== 'active') {
                    $errors[] = __('admin_accounts.err_disable_self');
                }

                if ((string) $target['role'] === 'super_admin' && $role === 'admin') {
                    $countSuper = (int) ($pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin'")->fetchColumn() ?: 0);
                    if ($countSuper <= 1) {
                        $errors[] = __('admin_accounts.err_last_super');
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
                flash_set('success', __('admin_accounts.flash_updated'));
                redirect('admin/admin_accounts.php?edit=' . $targetId . '#admin-manage-panel');
            }
        } elseif ($action === 'delete_admin') {
            $targetId = (int) ($_POST['target_id'] ?? 0);
            $typed = strtolower(trim((string) ($_POST['delete_confirm_email'] ?? '')));

            if ($targetId <= 0) {
                $errors[] = __('admin_accounts.err_invalid_admin');
            }

            $target = null;
            if ($errors === []) {
                $stTarget = $pdo->prepare('SELECT id, role, email FROM admins WHERE id = :id LIMIT 1');
                $stTarget->execute(['id' => $targetId]);
                $target = $stTarget->fetch();
                if (!$target) {
                    $errors[] = __('admin_accounts.err_admin_not_found');
                }
            }

            if ($errors === [] && $target) {
                if ($targetId === $actorId) {
                    $errors[] = __('admin_accounts.err_delete_self');
                }
                if ((string) ($target['role'] ?? '') === 'super_admin') {
                    $errors[] = __('admin_accounts.err_delete_super');
                }
                $want = strtolower((string) ($target['email'] ?? ''));
                if ($typed === '' || $typed !== $want) {
                    $errors[] = __('admin_accounts.err_delete_confirm_email');
                }
            }

            if ($errors === [] && $target) {
                try {
                    $del = $pdo->prepare('DELETE FROM admins WHERE id = :id AND role = :role LIMIT 1');
                    $del->execute(['id' => $targetId, 'role' => 'admin']);
                    if ($del->rowCount() !== 1) {
                        $errors[] = __('admin_accounts.err_delete_failed');
                    } else {
                        flash_set('success', __('admin_accounts.flash_removed'));
                        redirect('admin/admin_accounts.php');
                    }
                } catch (Throwable $e) {
                    $errors[] = __('admin_accounts.err_delete_history');
                }
            }
        }
    }
}

$admins = $pdo->query("
    SELECT id, admin_id, full_name, email, phone, title, department, role, status, created_at, updated_at
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
$standardAdminCount = 0;
foreach ($admins as $row) {
    if ((string) ($row['status'] ?? '') === 'active') {
        $activeCount++;
    } else {
        $disabledCount++;
    }
    if ((string) ($row['role'] ?? '') === 'super_admin') {
        $superAdminCount++;
    } else {
        $standardAdminCount++;
    }
}

$peerSuperLocked = $selectedAdmin !== null
    && (string) ($selectedAdmin['role'] ?? '') === 'super_admin'
    && (int) ($selectedAdmin['id'] ?? 0) !== $actorId;

$mgrid_page_title = mgrid_title('title.admin_accounts');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-page-head mgrid-admin-accounts-head">
  <p class="mgrid-admin-accounts-kicker mb-1">SYSTEM GOVERNANCE</p>
  <h1 class="mb-2">Administration team</h1>
  <p class="mb-0">Super administrators onboard and manage standard administrators. Elevated roles and removals require explicit confirmation.</p>
</div>

<div class="mgrid-grid-4 mgrid-page-section">
  <article class="mgrid-stat-card mgrid-admin-stat">
    <p class="mgrid-stat-label">Total accounts</p>
    <p class="mgrid-stat-mid-value mb-0"><?= e((string) $adminCount) ?></p>
  </article>
  <article class="mgrid-stat-card mgrid-admin-stat">
    <p class="mgrid-stat-label">Administrators</p>
    <p class="mgrid-stat-mid-value mb-0"><?= e((string) $standardAdminCount) ?></p>
  </article>
  <article class="mgrid-stat-card mgrid-admin-stat">
    <p class="mgrid-stat-label">Super administrators</p>
    <p class="mgrid-stat-mid-value mb-0"><?= e((string) $superAdminCount) ?></p>
  </article>
  <article class="mgrid-stat-card mgrid-admin-stat">
    <p class="mgrid-stat-label">Disabled</p>
    <p class="mgrid-stat-mid-value mb-0"><?= e((string) $disabledCount) ?></p>
    <p class="mb-0 small text-muted mt-1">Active now: <?= e((string) $activeCount) ?></p>
  </article>
</div>

<div class="row g-3 mgrid-page-section mgrid-admin-accounts-layout">
  <div class="<?= $isSuperAdmin ? 'col-12 col-xxl-8' : 'col-12' ?>">
    <div class="mgrid-card mgrid-admin-directory-card">
      <div class="mgrid-card-header mgrid-admin-directory-head">
        <div>
          <h2 class="mgrid-card-title mb-1"><i class="ti ti-users-group"></i> Directory</h2>
          <p class="mb-0 small text-muted"><?= $isSuperAdmin ? 'Use <strong>Edit profile</strong> on a row or the actions column, then complete changes in the panel on the right (wide screens).' : 'Search and sort the team list.' ?></p>
        </div>
        <span class="badge rounded-pill text-bg-light border"><?= e((string) $adminCount) ?> accounts</span>
      </div>
      <div class="mgrid-card-body p-0 mgrid-admin-table-wrap">
        <?php if ($msg = flash_get('success')): ?>
          <div class="alert alert-success m-3 mb-0"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger m-3 mb-0"><?= e($err) ?></div>
        <?php endforeach; ?>
        <?php if (!$isSuperAdmin): ?>
          <div class="alert alert-warning m-3 mb-0">You can view the team directory. Only a super administrator can add, edit, or remove accounts.</div>
        <?php endif; ?>
        <div class="table-responsive mgrid-admin-directory-scroll">
          <table class="mgrid-table mb-0 mgrid-admin-directory-table" id="adminAccountsTable">
            <colgroup>
              <col class="mgrid-admin-col-id">
              <col class="mgrid-admin-col-name">
              <col class="mgrid-admin-col-org">
              <col class="mgrid-admin-col-contact">
              <col class="mgrid-admin-col-role">
              <col class="<?= $isSuperAdmin ? 'mgrid-admin-col-status' : 'mgrid-admin-col-status mgrid-admin-col-status--wide' ?>">
              <?php if ($isSuperAdmin): ?>
              <col class="mgrid-admin-col-actions">
              <?php endif; ?>
            </colgroup>
            <thead>
              <tr>
                <th class="mgrid-admin-th-id">Admin ID</th>
                <th class="mgrid-admin-th-name">Administrator</th>
                <th class="mgrid-admin-th-org">Organization</th>
                <th class="mgrid-admin-th-contact">Contact</th>
                <th class="mgrid-admin-th-role">Role</th>
                <th class="mgrid-admin-th-status">Status <span class="mgrid-admin-th-sub">· updated</span></th>
                <?php if ($isSuperAdmin): ?>
                  <th class="text-end mgrid-admin-th-actions">Actions</th>
                <?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if ($admins === []): ?>
                <tr>
                  <td colspan="<?= $isSuperAdmin ? 7 : 6 ?>" class="text-center py-4 text-muted">No admin accounts yet.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($admins as $a): ?>
                  <?php
                    $rowId = (int) ($a['id'] ?? 0);
                    $isRowSuper = (string) ($a['role'] ?? '') === 'super_admin';
                    $isSelf = $rowId === $actorId;
                    $titleDisp = (string) ($a['title'] ?? '');
                    $deptDisp = (string) ($a['department'] ?? '');
                    $phoneDisp = (string) ($a['phone'] ?? '');
                  ?>
                  <tr class="<?= ($editId === $rowId) ? 'mgrid-admin-row-active table-active' : '' ?>">
                    <td class="mgrid-admin-td-id">
                      <code class="mgrid-admin-code"><?= e((string) ($a['admin_id'] ?? '')) ?></code>
                    </td>
                    <td class="mgrid-admin-td-name">
                      <div class="mgrid-admin-name-line">
                        <span class="mgrid-admin-name-text"><?= e((string) ($a['full_name'] ?? '')) ?></span>
                        <?php if ($isSelf): ?>
                          <span class="badge rounded-pill text-bg-info mgrid-admin-you-badge">You</span>
                        <?php endif; ?>
                      </div>
                      <?php if ($isSuperAdmin): ?>
                        <div class="mgrid-admin-inline-edit-wrap">
                          <a class="mgrid-admin-inline-edit" href="<?= e(url('admin/admin_accounts.php')) . '?edit=' . $rowId ?>#admin-manage-panel"><i class="ti ti-pencil"></i><span>Edit profile</span></a>
                        </div>
                      <?php endif; ?>
                    </td>
                    <td class="mgrid-admin-td-org">
                      <div class="mgrid-admin-org-primary"><?php if ($titleDisp !== ''): ?><?= e($titleDisp) ?><?php else: ?><span class="text-muted">—</span><?php endif; ?></div>
                      <div class="mgrid-admin-org-secondary"><?php if ($deptDisp !== ''): ?><?= e($deptDisp) ?><?php else: ?><span class="text-muted">—</span><?php endif; ?></div>
                    </td>
                    <td class="mgrid-admin-td-contact">
                      <div class="mgrid-admin-email"><i class="ti ti-mail ti-xs me-1 opacity-75"></i><?= e((string) ($a['email'] ?? '')) ?></div>
                      <?php if ($phoneDisp !== ''): ?>
                        <div class="mgrid-admin-phone"><i class="ti ti-phone ti-xs me-1 opacity-75"></i><?= e($phoneDisp) ?></div>
                      <?php else: ?>
                        <div class="mgrid-admin-phone text-muted small">No phone</div>
                      <?php endif; ?>
                    </td>
                    <td class="mgrid-admin-td-role" data-order="<?= e((string) ($a['role'] ?? '')) ?>">
                      <?php $rShort = $roleLabelShort((string) ($a['role'] ?? 'admin')); ?>
                      <span class="badge rounded-pill mgrid-admin-role-badge <?= $isRowSuper ? 'text-bg-dark' : 'text-bg-secondary' ?>" title="<?= e($roleLabel((string) ($a['role'] ?? 'admin'))) ?>"><?= e($rShort) ?></span>
                    </td>
                    <td class="mgrid-admin-td-status" data-order="<?= e((string) ($a['updated_at'] ?? $a['created_at'] ?? '')) ?>">
                      <span class="badge rounded-pill <?= (($a['status'] ?? '') === 'active') ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e((string) ($a['status'] ?? '')) ?></span>
                      <div class="mgrid-admin-updated-line text-muted"><?= e(substr((string) ($a['updated_at'] ?? $a['created_at'] ?? ''), 0, 10)) ?></div>
                    </td>
                    <?php if ($isSuperAdmin): ?>
                      <td class="text-end text-nowrap mgrid-admin-actions mgrid-admin-td-actions">
                        <a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/admin_accounts.php')) . '?edit=' . $rowId ?>#admin-manage-panel" title="Edit profile and access">Edit</a>
                        <?php if (!$isRowSuper): ?>
                          <button type="button" class="btn btn-sm btn-outline-danger js-admin-delete-open"
                            data-id="<?= $rowId ?>"
                            data-name="<?= e((string) ($a['full_name'] ?? '')) ?>"
                            data-email="<?= e((string) ($a['email'] ?? '')) ?>"
                            <?= $isSelf ? 'disabled' : '' ?>>Remove</button>
                        <?php endif; ?>
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
  </div>

  <?php if ($isSuperAdmin): ?>
    <div class="col-12 col-xxl-4">
      <div class="row g-3 mgrid-admin-side-stack" id="admin-manage-panel">
        <div class="col-12 col-lg-6 col-xxl-12">
        <div class="mgrid-card mgrid-admin-side-card h-100">
          <div class="mgrid-card-header">
            <h2 class="mgrid-card-title mb-0"><i class="ti ti-user-cog"></i> Edit account</h2>
          </div>
          <div class="mgrid-card-body">
            <?php if (!$selectedAdmin): ?>
              <div class="mgrid-empty py-3">
                <p class="mb-2">Choose <strong>Edit profile</strong> under a name in the directory, or use the <strong>Edit</strong> button at the end of the row.</p>
                <p class="mb-0 small text-muted">Add new staff with the form in the next card.</p>
              </div>
            <?php elseif ($peerSuperLocked): ?>
              <div class="alert alert-secondary mb-0">
                <strong><?= e((string) ($selectedAdmin['full_name'] ?? '')) ?></strong> is a super administrator.
                For security, that account can only be changed by signing in as that user.
              </div>
            <?php else: ?>
              <form method="post" class="row g-3 mgrid-admin-manage-form" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_admin">
                <input type="hidden" name="target_id" value="<?= (int) ($selectedAdmin['id'] ?? 0) ?>">
                <div class="col-12">
                  <p class="small text-muted mb-0">Editing <strong><?= e((string) ($selectedAdmin['admin_id'] ?? '')) ?></strong></p>
                </div>
                <div class="col-12">
                  <label class="form-label" for="edit_full_name">Full name</label>
                  <input class="form-control" id="edit_full_name" name="full_name" value="<?= e((string) ($selectedAdmin['full_name'] ?? '')) ?>" required>
                </div>
                <div class="col-12">
                  <label class="form-label" for="edit_email">Email</label>
                  <input class="form-control" type="email" id="edit_email" name="email" value="<?= e((string) ($selectedAdmin['email'] ?? '')) ?>" required>
                </div>
                <div class="col-12">
                  <label class="form-label" for="edit_phone">Phone</label>
                  <input class="form-control" id="edit_phone" name="phone" value="<?= e((string) ($selectedAdmin['phone'] ?? '')) ?>">
                </div>
                <div class="col-12">
                  <label class="form-label" for="edit_title">Title</label>
                  <input class="form-control" id="edit_title" name="title" value="<?= e((string) ($selectedAdmin['title'] ?? '')) ?>">
                </div>
                <div class="col-12">
                  <label class="form-label" for="edit_department">Department</label>
                  <input class="form-control" id="edit_department" name="department" value="<?= e((string) ($selectedAdmin['department'] ?? '')) ?>">
                </div>
                <div class="col-12">
                  <label class="form-label" for="edit_role">Access level</label>
                  <select class="form-select" id="edit_role" name="role">
                    <option value="admin" <?= (($selectedAdmin['role'] ?? '') === 'admin') ? 'selected' : '' ?>><?= e($roleLabel('admin')) ?></option>
                    <option value="super_admin" <?= (($selectedAdmin['role'] ?? '') === 'super_admin') ? 'selected' : '' ?>><?= e($roleLabel('super_admin')) ?></option>
                  </select>
                </div>
                <?php if (($selectedAdmin['role'] ?? '') === 'admin'): ?>
                  <div class="col-12">
                    <div class="form-check border rounded-3 px-3 py-2 bg-light">
                      <input class="form-check-input" type="checkbox" value="1" id="confirm_super_promotion" name="confirm_super_promotion">
                      <label class="form-check-label small" for="confirm_super_promotion">
                        I confirm granting <strong>super administrator</strong> privileges (full control over this console and team accounts).
                      </label>
                    </div>
                  </div>
                <?php endif; ?>
                <div class="col-12">
                  <label class="form-label" for="edit_status">Status</label>
                  <select class="form-select" id="edit_status" name="status">
                    <option value="active" <?= (($selectedAdmin['status'] ?? '') === 'active') ? 'selected' : '' ?>>Active</option>
                    <option value="disabled" <?= (($selectedAdmin['status'] ?? '') === 'disabled') ? 'selected' : '' ?>>Disabled (cannot sign in)</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label" for="edit_new_password">New password</label>
                  <input class="form-control" type="password" id="edit_new_password" name="new_password" placeholder="Leave blank to keep current password" autocomplete="new-password">
                </div>
                <div class="col-12 d-grid gap-2">
                  <button type="submit" class="btn btn-primary">Save changes</button>
                  <a class="btn btn-outline-secondary btn-sm" href="<?= e(url('admin/admin_accounts.php')) ?>">Clear selection</a>
                </div>
              </form>
            <?php endif; ?>
          </div>
        </div>
        </div>

        <div class="col-12 col-lg-6 col-xxl-12">
        <div class="mgrid-card mgrid-admin-side-card h-100">
          <div class="mgrid-card-header">
            <h2 class="mgrid-card-title mb-0"><i class="ti ti-user-plus"></i> Add administrator</h2>
            <span class="badge text-bg-success rounded-pill">Standard role</span>
          </div>
          <div class="mgrid-card-body">
            <p class="small text-muted mb-3">Creates a <strong>standard administrator</strong>. All fields marked <span class="text-danger">*</span> are required for identity, accountability, and support contact.</p>
            <form method="post" class="mgrid-admin-create-form" novalidate>
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="create_admin">

              <fieldset class="mgrid-admin-form-section border rounded-3 px-3 py-3 mb-3">
                <legend class="float-none w-auto px-1 fs-6 mb-2">Identity &amp; sign-in</legend>
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label" for="full_name">Full legal name <span class="text-danger">*</span></label>
                    <input class="form-control" id="full_name" name="full_name" value="<?= e($formData['full_name']) ?>" required minlength="3" maxlength="255" autocomplete="name" placeholder="As it should appear in the directory">
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="email">Work email <span class="text-danger">*</span></label>
                    <input class="form-control" type="email" id="email" name="email" value="<?= e($formData['email']) ?>" required maxlength="255" autocomplete="email" placeholder="name@organization.org">
                    <div class="form-text">Used for sign-in and system notifications.</div>
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="phone">Work phone <span class="text-danger">*</span></label>
                    <input class="form-control" id="phone" name="phone" value="<?= e($formData['phone']) ?>" required minlength="8" maxlength="32" inputmode="tel" autocomplete="tel" placeholder="+255 … or local number">
                    <div class="form-text">Include country code where applicable (at least 8 digits).</div>
                  </div>
                </div>
              </fieldset>

              <fieldset class="mgrid-admin-form-section border rounded-3 px-3 py-3 mb-3">
                <legend class="float-none w-auto px-1 fs-6 mb-2">Role in the organization</legend>
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label" for="title">Job title <span class="text-danger">*</span></label>
                    <input class="form-control" id="title" name="title" value="<?= e($formData['title']) ?>" required minlength="2" maxlength="120" placeholder="e.g. Member services officer">
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="department">Department / unit <span class="text-danger">*</span></label>
                    <input class="form-control" id="department" name="department" value="<?= e($formData['department']) ?>" required minlength="2" maxlength="120" placeholder="e.g. Operations, Programs">
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="status">Initial account status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                      <option value="active" <?= ($formData['status'] === 'active') ? 'selected' : '' ?>>Active — can sign in immediately</option>
                      <option value="disabled" <?= ($formData['status'] === 'disabled') ? 'selected' : '' ?>>Disabled — onboard first, enable later</option>
                    </select>
                  </div>
                </div>
              </fieldset>

              <fieldset class="mgrid-admin-form-section border rounded-3 px-3 py-3 mb-3">
                <legend class="float-none w-auto px-1 fs-6 mb-2">Console access</legend>
                <div class="row g-3">
                  <div class="col-12">
                    <label class="form-label" for="password">Initial password <span class="text-danger">*</span></label>
                    <input class="form-control" type="password" id="password" name="password" minlength="8" required autocomplete="new-password" placeholder="Minimum 8 characters">
                  </div>
                  <div class="col-12">
                    <label class="form-label" for="password_confirm">Confirm password <span class="text-danger">*</span></label>
                    <input class="form-control" type="password" id="password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
                  </div>
                </div>
              </fieldset>

              <div class="form-check border border-2 border-warning rounded-3 px-3 py-3 mb-3 bg-warning bg-opacity-10">
                <input class="form-check-input" type="checkbox" value="1" id="create_acknowledged" name="create_acknowledged" required>
                <label class="form-check-label small" for="create_acknowledged">
                  <span class="text-danger fw-semibold">*</span> I confirm this person is <strong>authorized</strong> to access the M GRID admin console and that the information above is accurate.
                </label>
              </div>

              <div class="d-grid">
                <button type="submit" class="btn btn-primary">Create administrator</button>
              </div>
            </form>
          </div>
        </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php if ($isSuperAdmin): ?>
<div class="modal fade" id="adminDeleteModal" tabindex="-1" aria-labelledby="adminDeleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="post" id="adminDeleteForm">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="delete_admin">
        <input type="hidden" name="target_id" id="delete_target_id" value="">
        <div class="modal-header border-0 pb-0">
          <h2 class="modal-title h5" id="adminDeleteModalLabel">Remove administrator</h2>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2">This permanently deletes <strong id="delete_admin_name"></strong> if the account has no blocking records (funding reviews, disbursements, etc.). Otherwise you will see an error and should disable the account instead.</p>
          <label class="form-label" for="delete_confirm_email">Type the account email to confirm</label>
          <input class="form-control font-monospace" type="text" name="delete_confirm_email" id="delete_confirm_email" autocomplete="off" placeholder="">
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-danger">Remove account</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<?php
ob_start();
?>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
<script>
(function () {
  var $table = $("#adminAccountsTable");
  if (!$table.length || typeof $.fn.DataTable !== "function") {
    return;
  }
  var superMode = <?= $isSuperAdmin ? 'true' : 'false' ?>;
  var actionColIdx = superMode ? 6 : -1;

  if ($.fn.DataTable.isDataTable($table)) {
    $table.DataTable().destroy();
  }

  var defs = [];
  if (actionColIdx >= 0) {
    defs.push({ orderable: false, targets: [actionColIdx] });
  }

  $table.DataTable({
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[5, "desc"]],
    responsive: false,
    autoWidth: false,
    dom: "<'mgrid-admin-dt-toolbar d-flex flex-wrap align-items-end justify-content-between gap-2 px-2 pt-2 pb-1'lf>rtip",
    language: {
      search: "Filter directory:",
      lengthMenu: "Show _MENU_",
      info: "Showing _START_–_END_ of _TOTAL_",
      infoEmpty: "No accounts",
      zeroRecords: "No matching accounts"
    },
    columnDefs: defs
  });
})();
</script>
<?php
$mgrid_footer_extra = ob_get_clean();
if ($isSuperAdmin) {
    ob_start();
    ?>
<script>
(function () {
  var modalEl = document.getElementById("adminDeleteModal");
  if (!modalEl || typeof bootstrap === "undefined") {
    return;
  }
  var modal = new bootstrap.Modal(modalEl);
  var idInput = document.getElementById("delete_target_id");
  var nameEl = document.getElementById("delete_admin_name");
  var emailInput = document.getElementById("delete_confirm_email");
  document.querySelectorAll(".js-admin-delete-open").forEach(function (btn) {
    btn.addEventListener("click", function () {
      if (btn.disabled) {
        return;
      }
      idInput.value = btn.getAttribute("data-id") || "";
      nameEl.textContent = btn.getAttribute("data-name") || "";
      emailInput.value = "";
      emailInput.placeholder = btn.getAttribute("data-email") || "";
      modal.show();
    });
  });
})();
</script>
<?php
    $mgrid_footer_extra .= ob_get_clean();
}
require __DIR__ . '/includes/shell_close.php';
