<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];

$st = $pdo->prepare('SELECT preferred_language, password_hash, full_name, email, phone FROM users WHERE id = :id LIMIT 1');
$st->execute(['id' => $uid]);
$userRow = $st->fetch() ?: [];
$lang = (string) ($userRow['preferred_language'] ?? 'en');
$langLabel = $lang === 'sw' ? 'Kiswahili (preference)' : 'English';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $action = clean_string($_POST['action'] ?? '');

        if ($action === 'language') {
            $newLang = clean_string($_POST['preferred_language'] ?? 'en');
            if (!in_array($newLang, ['en', 'sw'], true)) {
                $errors[] = 'Please choose a valid language.';
            } else {
                $up = $pdo->prepare('UPDATE users SET preferred_language = :lang WHERE id = :id LIMIT 1');
                $up->execute(['lang' => $newLang, 'id' => $uid]);
                $_SESSION['preferred_language'] = $newLang;
                flash_set('success', 'Language preference updated.');
                redirect('user/settings.php');
            }
        } elseif ($action === 'password') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
            $storedHash = (string) ($userRow['password_hash'] ?? '');

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $errors[] = 'Fill in all password fields.';
            } elseif (!password_verify($currentPassword, $storedHash)) {
                $errors[] = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif (!hash_equals($newPassword, $confirmPassword)) {
                $errors[] = 'New password confirmation does not match.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $upPw = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id LIMIT 1');
                $upPw->execute(['hash' => $newHash, 'id' => $uid]);
                flash_set('success', 'Password changed successfully.');
                redirect('user/settings.php');
            }
        } else {
            $errors[] = 'Unsupported settings action.';
        }
    }
}

$mgrid_page_title = 'Settings — Malkia Grid';
require __DIR__ . '/includes/shell_open.php';
?>

<div class="row g-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4 p-lg-5">
        <h1 class="h4 mgrid-dash-page-title mb-2">Settings</h1>
        <p class="text-muted small mb-0">Manage language and account security for your M-Profile.</p>
      </div>
    </div>
  </div>

  <div class="col-12">
    <?php if ($msg = flash_get('success')): ?>
      <div class="alert alert-success small mb-0"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger small py-2 mb-2"><?= e($err) ?></div>
    <?php endforeach; ?>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100 mgrid-settings-card">
      <div class="card-body p-4">
        <h2 class="h5 mgrid-dash-section-title mb-2">Language preferences</h2>
        <p class="small text-muted mb-3">Preferred language on file: <strong><?= e($langLabel) ?></strong></p>
        <p class="small text-muted mb-3">The live interface is <strong>English</strong>. Choose <strong>Kiswahili</strong> to record your preference for future Kiswahili copy and flows.</p>
        <form method="post" class="row g-3" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="language">
          <div class="col-12">
            <label for="preferred_language" class="form-label">Interface language</label>
            <select class="form-select" id="preferred_language" name="preferred_language">
              <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>>English (default)</option>
              <option value="sw" <?= $lang === 'sw' ? 'selected' : '' ?>>Kiswahili (coming)</option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm px-4">Save language</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100 mgrid-settings-card">
      <div class="card-body p-4">
        <h2 class="h5 mgrid-dash-section-title mb-2">Security</h2>
        <p class="small text-muted mb-3">Change your account password regularly to keep your profile secure.</p>
        <form method="post" class="row g-3" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="password">
          <div class="col-12">
            <label for="current_password" class="form-label">Current password</label>
            <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
          </div>
          <div class="col-12">
            <label for="new_password" class="form-label">New password</label>
            <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" required minlength="8">
          </div>
          <div class="col-12">
            <label for="confirm_password" class="form-label">Confirm new password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-outline-primary btn-sm px-4">Change password</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card border-0 shadow-sm mgrid-settings-card">
      <div class="card-body p-4">
        <h2 class="h6 text-uppercase text-muted mgrid-dash-stat-label mb-2">Account profile snapshot</h2>
        <div class="row g-3 small">
          <div class="col-md-4"><strong class="text-dark">Name:</strong> <?= e((string) ($userRow['full_name'] ?? '—')) ?></div>
          <div class="col-md-4"><strong class="text-dark">Email:</strong> <?= e((string) ($userRow['email'] ?? '—')) ?></div>
          <div class="col-md-4"><strong class="text-dark">Phone:</strong> <?= e((string) ($userRow['phone'] ?? '—')) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
