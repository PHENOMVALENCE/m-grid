<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

if (auth_actor() !== null) {
    $u = auth_actor();
    redirect(($u['account_type'] ?? 'user') === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$errors = [];
$loginValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        $loginValue = clean_string($_POST['login'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($loginValue === '' || $password === '') {
            $errors[] = 'Enter your email or phone and password.';
        } else {
            $pdo = db();

            $adminStmt = $pdo->prepare('
                SELECT id, admin_id, full_name, email, password_hash, status, role
                FROM admins
                WHERE email = :login OR admin_id = :admin_id
                LIMIT 1
            ');
            $adminStmt->execute([
                'login' => strtolower($loginValue),
                'admin_id' => strtoupper($loginValue),
            ]);
            $admin = $adminStmt->fetch();
            if ($admin && password_verify($password, (string) $admin['password_hash'])) {
                if (($admin['status'] ?? '') !== 'active') {
                    $errors[] = 'Admin account is not active.';
                } else {
                    auth_login_admin($admin);
                    flash_set('success', 'Welcome back, ' . $admin['full_name'] . '.');
                    redirect('admin/dashboard.php');
                }
            }

            $stmt = $pdo->prepare('
                SELECT id, m_id, full_name, phone, email, password_hash, status, preferred_language
                FROM users
                WHERE email = :email OR phone = :phone
                LIMIT 1
            ');
            $stmt->execute([
                'email' => strtolower($loginValue),
                'phone' => normalise_phone($loginValue),
            ]);
            $row = $stmt->fetch();
            if (!$row || !password_verify($password, (string) $row['password_hash'])) {
                $errors[] = 'We could not match those details. Check and try again.';
            } elseif (($row['status'] ?? '') === 'suspended') {
                $errors[] = 'This account is suspended. Please contact support.';
            } else {
                auth_login_user($row);
                if (($row['status'] ?? '') !== 'active') {
                    flash_set('success', 'Welcome. Please upload your National ID for admin approval.');
                    redirect('user/verify-id.php');
                }
                flash_set('success', 'Welcome back, ' . $row['full_name'] . '.');
                redirect('user/dashboard.php');
            }
        }
    }
}

$mgrid_page_title = 'Sign in — Malkia Grid';
$mgrid_layout = 'auth';
require __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center w-100 px-2">
  <div class="col-md-8 col-lg-6 col-xxl-4">
    <div class="card mb-0 shadow-sm border-0">
      <div class="card-body p-4 p-md-5">
        <a href="<?= e(url('index.php')) ?>" class="text-center d-block py-2 text-decoration-none">
          <span class="mgrid-auth-brand">Malkia Grid</span>
        </a>
        <div class="text-center mb-3">
          <a class="small mgrid-auth-home-link text-decoration-none" href="<?= e(url('index.php')) ?>" data-i18n="auth.back_home">&larr; Back to Home</a>
        </div>

        <div class="mgrid-auth-switch mb-3" role="tablist" aria-label="Authentication pages">
          <a class="mgrid-auth-switch-link is-active" href="<?= e(url('login.php')) ?>" role="tab" aria-selected="true" data-i18n="auth.sign_in_tab">Sign in</a>
          <a class="mgrid-auth-switch-link" href="<?= e(url('register.php')) ?>" role="tab" aria-selected="false" data-i18n="auth.register_tab">Register</a>
        </div>

        <div class="text-center mb-4">
          <h1 class="h4 mb-2 mgrid-auth-title" data-i18n="auth.welcome_login">Welcome back</h1>
          <p class="lead-tight mb-0" data-i18n="auth.lead_login">Sign in to continue to your verified member profile.</p>
        </div>

        <?php if ($msg = flash_get('success')): ?>
          <div class="alert alert-success small"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php if ($msg = flash_get('error')): ?>
          <div class="alert alert-danger small"><?= e($msg) ?></div>
        <?php endif; ?>
        <?php foreach ($errors as $err): ?>
          <div class="alert alert-danger small py-2"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post" novalidate>
          <?= csrf_field() ?>
          <div class="mb-3">
            <label for="login" class="form-label" data-i18n="auth.label_login">Email or phone</label>
            <input type="text" class="form-control" id="login" name="login" autocomplete="username"
              value="<?= e($loginValue) ?>" required>
          </div>
          <div class="mb-4">
            <label for="password" class="form-label" data-i18n="auth.label_password">Password</label>
            <input type="password" class="form-control" id="password" name="password" autocomplete="current-password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100 py-8 fs-4 mb-3" data-i18n="auth.submit_login">Sign in</button>
          <div class="text-center mgrid-auth-meta">
            <span class="text-muted" data-i18n="auth.meta_new">New to Malkia Grid?</span>
            <a class="fw-semibold" href="<?= e(url('register.php')) ?>" data-i18n="auth.meta_create">Create your account</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php
require __DIR__ . '/includes/footer.php';
