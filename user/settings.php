<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];

$st = $pdo->prepare('SELECT preferred_language, password_hash, full_name, email, phone FROM users WHERE id = :id LIMIT 1');
$st->execute(['id' => $uid]);
$userRow = $st->fetch() ?: [];
$lang = (string) ($userRow['preferred_language'] ?? 'sw');
$langLabel = $lang === 'sw' ? __('lang.ui_sw') : __('lang.ui_en');
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = __('settings.error.token');
    } else {
        $action = clean_string($_POST['action'] ?? '');

        if ($action === 'language') {
            $newLang = clean_string($_POST['preferred_language'] ?? 'sw');
            if (!in_array($newLang, ['en', 'sw'], true)) {
                $errors[] = __('settings.error.lang_invalid');
            } else {
                $up = $pdo->prepare('UPDATE users SET preferred_language = :lang WHERE id = :id LIMIT 1');
                $up->execute(['lang' => $newLang, 'id' => $uid]);
                $_SESSION['preferred_language'] = $newLang;
                flash_set('success', __('settings.success.lang'));
                redirect('user/settings.php');
            }
        } elseif ($action === 'password') {
            $currentPassword = (string) ($_POST['current_password'] ?? '');
            $newPassword = (string) ($_POST['new_password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');
            $storedHash = (string) ($userRow['password_hash'] ?? '');

            if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
                $errors[] = __('settings.error.password_fields');
            } elseif (!password_verify($currentPassword, $storedHash)) {
                $errors[] = __('settings.error.password_wrong');
            } elseif (strlen($newPassword) < 8) {
                $errors[] = __('settings.error.password_short');
            } elseif (!hash_equals($newPassword, $confirmPassword)) {
                $errors[] = __('settings.error.password_mismatch');
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $upPw = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id LIMIT 1');
                $upPw->execute(['hash' => $newHash, 'id' => $uid]);
                flash_set('success', __('settings.success.password'));
                redirect('user/settings.php');
            }
        } else {
            $errors[] = __('settings.error.unsupported_action');
        }
    }
}

$mgrid_page_title = mgrid_title('settings.document_title');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="row g-4">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-body p-4 p-lg-5">
        <h1 class="h4 mgrid-dash-page-title mb-2"><?= e(__('settings.heading_main')) ?></h1>
        <p class="text-muted small mb-0"><?= e(__('settings.intro')) ?></p>
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
        <h2 class="h5 mgrid-dash-section-title mb-2"><?= e(__('settings.lang_card_title')) ?></h2>
        <p class="small text-muted mb-3"><?= e(__('settings.lang_on_file')) ?>: <strong><?= e($langLabel) ?></strong></p>
        <p class="small text-muted mb-3"><?= e(__('settings.lang_intro')) ?></p>
        <form method="post" class="row g-3" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="language">
          <div class="col-12">
            <label for="preferred_language" class="form-label"><?= e(__('settings.interface_label')) ?></label>
            <select class="form-select" id="preferred_language" name="preferred_language">
              <option value="en" <?= $lang === 'en' ? 'selected' : '' ?>><?= e(__('lang.ui_en')) ?></option>
              <option value="sw" <?= $lang === 'sw' ? 'selected' : '' ?>><?= e(__('lang.ui_sw')) ?></option>
            </select>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-primary btn-sm px-4"><?= e(__('settings.save_language')) ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100 mgrid-settings-card">
      <div class="card-body p-4">
        <h2 class="h5 mgrid-dash-section-title mb-2"><?= e(__('settings.security_title')) ?></h2>
        <p class="small text-muted mb-3"><?= e(__('settings.security_intro')) ?></p>
        <form method="post" class="row g-3" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="password">
          <div class="col-12">
            <label for="current_password" class="form-label"><?= e(__('settings.lbl_current_password')) ?></label>
            <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
          </div>
          <div class="col-12">
            <label for="new_password" class="form-label"><?= e(__('settings.lbl_new_password')) ?></label>
            <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" required minlength="8">
          </div>
          <div class="col-12">
            <label for="confirm_password" class="form-label"><?= e(__('settings.lbl_confirm_password')) ?></label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button type="submit" class="btn btn-outline-primary btn-sm px-4"><?= e(__('settings.btn_change_password')) ?></button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12">
    <div class="card border-0 shadow-sm mgrid-settings-card">
      <div class="card-body p-4">
        <h2 class="h6 text-uppercase text-muted mgrid-dash-stat-label mb-2"><?= e(__('settings.snapshot_title')) ?></h2>
        <div class="row g-3 small">
          <div class="col-md-4"><strong class="text-dark"><?= e(__('settings.lbl_name')) ?>:</strong> <?= e((string) ($userRow['full_name'] ?? '—')) ?></div>
          <div class="col-md-4"><strong class="text-dark"><?= e(__('settings.lbl_email')) ?>:</strong> <?= e((string) ($userRow['email'] ?? '—')) ?></div>
          <div class="col-md-4"><strong class="text-dark"><?= e(__('settings.lbl_phone')) ?>:</strong> <?= e((string) ($userRow['phone'] ?? '—')) ?></div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
