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
        $errors[] = __('auth.error.token');
    } else {
        $loginValue = clean_string($_POST['login'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($loginValue === '' || $password === '') {
            $errors[] = __('auth.error.empty_login');
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
                    $errors[] = __('auth.error.admin_inactive');
                } else {
                    auth_login_admin($admin);
                    flash_set('success', __('auth.success.welcome_named', ['name' => (string) $admin['full_name']]));
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
                $errors[] = __('auth.error.credentials');
            } elseif (($row['status'] ?? '') === 'suspended') {
                $errors[] = __('auth.error.suspended');
            } else {
                auth_login_user($row);
                if (($row['status'] ?? '') !== 'active') {
                    flash_set('success', __('auth.success.verify_first'));
                    redirect('user/verify-id.php');
                }
                flash_set('success', __('auth.success.welcome_named', ['name' => (string) $row['full_name']]));
                redirect('user/dashboard.php');
            }
        }
    }
}

$mgrid_page_title = mgrid_title('title.login');
$mgrid_layout = 'auth';
require __DIR__ . '/includes/header.php';
?>

<div class="mgrid-auth-card" style="max-width:560px; margin:0 auto;">
  <div class="mgrid-auth-brand-wrap">
    <div class="mgrid-auth-logo-mark">
      <img src="<?= e(asset('images/logos/logo.png')) ?>" alt="Malkia Grid logo" />
    </div>
    <div>
      <span class="mgrid-auth-brand-name">M GRID</span>
      <span class="mgrid-auth-brand-tagline">Women Rising in Power and Opportunity</span>
    </div>
  </div>
  <p class="mb-3"><a class="text-decoration-none" href="<?= e(url('index.php')) ?>" data-i18n="auth.back_home">&larr; Back to Home</a></p>
  <div class="mgrid-auth-tabs" role="tablist" aria-label="Authentication pages">
    <a class="mgrid-auth-tab is-active" href="<?= e(url('login.php')) ?>" role="tab" aria-selected="true" data-i18n="auth.sign_in_tab">Sign in</a>
    <a class="mgrid-auth-tab" href="<?= e(url('register.php')) ?>" role="tab" aria-selected="false" data-i18n="auth.register_tab">Register</a>
  </div>
  <h1 class="mgrid-display" style="font-size:2rem; margin-bottom:6px;" data-i18n="auth.welcome_login">Welcome back</h1>
  <p style="color: var(--mgrid-ink-500); font-size: 14.5px;" data-i18n="auth.lead_login">Sign in to continue to your verified member profile.</p>

  <?php if ($msg = flash_get('success')): ?>
    <div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php if ($msg = flash_get('error')): ?>
    <div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div>
  <?php endif; ?>
  <?php foreach ($errors as $err): ?>
    <div class="mgrid-alert mgrid-alert-danger"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="mb-3">
      <label for="login" class="mgrid-form-label" data-i18n="auth.label_login">Email or phone</label>
      <input type="text" class="mgrid-form-control" id="login" name="login" autocomplete="username" value="<?= e($loginValue) ?>" required>
    </div>
    <div class="mb-4">
      <label for="password" class="mgrid-form-label" data-i18n="auth.label_password">Password</label>
      <input type="password" class="mgrid-form-control" id="password" name="password" autocomplete="current-password" required>
    </div>
    <button type="submit" class="btn-mgrid btn-mgrid-primary w-100 justify-content-center py-3 mb-3" data-i18n="auth.submit_login">Sign in</button>
    <p class="text-center" style="font-size: 13.5px; color: var(--mgrid-ink-500); margin-top: 20px;">
      <span data-i18n="auth.meta_new">New to Malkia Grid?</span>
      <a href="<?= e(url('register.php')) ?>" style="color: var(--mgrid-gold-600); font-weight: 500;" data-i18n="auth.meta_create">Create your account</a>
    </p>
  </form>
</div>

<?php
require __DIR__ . '/includes/footer.php';
