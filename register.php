<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

if (auth_actor() !== null) {
    $u = auth_actor();
    redirect(($u['account_type'] ?? 'user') === 'admin' ? 'admin/dashboard.php' : 'user/dashboard.php');
}

$errors = [];
$form = [
    'full_name' => '',
    'phone' => '',
    'email' => '',
    'region' => '',
    'date_of_birth' => '',
    'age_range' => '',
    'business_status' => '',
    'preferred_language' => 'sw',
];

$regions = [
    'Arusha', 'Dar es Salaam', 'Dodoma', 'Geita', 'Iringa', 'Kagera', 'Katavi', 'Kigoma', 'Kilimanjaro',
    'Lindi', 'Manyara', 'Mara', 'Mbeya', 'Mjini Magharibi', 'Morogoro', 'Mtwara', 'Mwanza', 'Njombe',
    'Pemba North', 'Pemba South', 'Pwani', 'Rukwa', 'Ruvuma', 'Shinyanga', 'Simiyu', 'Singida', 'Songwe',
    'Tabora', 'Tanga', 'Other / Diaspora',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = __('register.error.token');
    } else {
        foreach (array_keys($form) as $k) {
            if (isset($_POST[$k])) {
                $form[$k] = clean_string((string) $_POST[$k]);
            }
        }
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        if ($form['full_name'] === '' || mb_strlen($form['full_name']) < 2) {
            $errors[] = __('register.error.full_name');
        }
        if ($form['phone'] === '') {
            $errors[] = __('register.error.phone');
        }
        $phoneNorm = normalise_phone($form['phone']);
        if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('register.error.email');
        } else {
            $form['email'] = strtolower($form['email']);
        }
        if ($form['region'] === '') {
            $errors[] = __('register.error.region');
        }
        if ($form['business_status'] === '') {
            $errors[] = __('register.error.business');
        }
        if (!in_array($form['preferred_language'], ['en', 'sw'], true)) {
            $errors[] = __('register.error.language');
        }
        if ($form['date_of_birth'] === '' && $form['age_range'] === '') {
            $errors[] = __('register.error.dob_or_age');
        }
        if ($form['date_of_birth'] !== '') {
            $d = DateTime::createFromFormat('Y-m-d', $form['date_of_birth']);
            if (!$d || $d->format('Y-m-d') !== $form['date_of_birth']) {
                $errors[] = __('register.error.dob_invalid');
            }
        }
        if (strlen($password) < 8) {
            $errors[] = __('register.error.password_short');
        }
        if (!hash_equals($password, $confirm)) {
            $errors[] = __('register.error.password_mismatch');
        }

        if ($errors === []) {
            $pdo = db();
            $chk = $pdo->prepare('SELECT id FROM users WHERE email = :e OR phone = :p LIMIT 1');
            $chk->execute(['e' => $form['email'], 'p' => $phoneNorm]);
            if ($chk->fetch()) {
                $errors[] = __('register.error.duplicate');
            }
        }

        if ($errors === []) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $dobSql = $form['date_of_birth'] !== '' ? $form['date_of_birth'] : null;
            $ageSql = $form['age_range'] !== '' ? $form['age_range'] : null;

            try {
                $pdo = db();
                $pdo->beginTransaction();

                $mId = m_id_allocate_next($pdo);

                $ins = $pdo->prepare('
                    INSERT INTO users (m_id, full_name, phone, email, password_hash, status, preferred_language)
                    VALUES (:m_id, :full_name, :phone, :email, :ph, "pending", :lang)
                ');
                $ins->execute([
                    'm_id' => $mId,
                    'full_name' => $form['full_name'],
                    'phone' => $phoneNorm,
                    'email' => $form['email'],
                    'ph' => $hash,
                    'lang' => $form['preferred_language'],
                ]);
                $userId = (int) $pdo->lastInsertId();

                $prof = $pdo->prepare('
                    INSERT INTO user_profiles (user_id, region, date_of_birth, age_range, business_status, bio, profile_photo, profile_completion)
                    VALUES (:uid, :region, :dob, :age, :bs, NULL, NULL, 15)
                ');
                $prof->execute([
                    'uid' => $userId,
                    'region' => $form['region'],
                    'dob' => $dobSql,
                    'age' => $ageSql,
                    'bs' => $form['business_status'],
                ]);

                $sc = $pdo->prepare('
                    INSERT INTO m_scores (user_id, score, tier, last_calculated_at)
                    VALUES (:uid, NULL, "pending", NULL)
                ');
                $sc->execute(['uid' => $userId]);

                $pdo->commit();
                flash_set('success', __('register.flash_ok', ['mid' => $mId]));
                redirect('login.php');
            } catch (Throwable $e) {
                $tx = db();
                if ($tx->inTransaction()) {
                    $tx->rollBack();
                }
                $errors[] = __('register.error.generic');
            }
        }
    }
}

$mgrid_page_title = mgrid_title('title.register');
$mgrid_layout = 'auth';
require __DIR__ . '/includes/header.php';
?>

<div class="mgrid-auth-card" style="max-width:680px; margin:0 auto;">
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
    <a class="mgrid-auth-tab" href="<?= e(url('login.php')) ?>" role="tab" aria-selected="false" data-i18n="auth.sign_in_tab">Sign in</a>
    <a class="mgrid-auth-tab is-active" href="<?= e(url('register.php')) ?>" role="tab" aria-selected="true" data-i18n="auth.register_tab">Register</a>
  </div>
  <h1 class="mgrid-display" style="font-size:2rem; margin-bottom:6px;" data-i18n="auth.register_title">Create your M-ID account</h1>
  <p style="color: var(--mgrid-ink-500); font-size: 14.5px;" data-i18n="auth.register_lead">Provide accurate details. Your M-ID is issued automatically after submission.</p>
  <?php foreach ($errors as $err): ?>
    <div class="mgrid-alert mgrid-alert-danger"><?= e($err) ?></div>
  <?php endforeach; ?>
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="mgrid-auth-grid-2">
      <div>
        <label class="mgrid-form-label" for="full_name" data-i18n="auth.label_full_name">Full name</label>
        <input class="mgrid-form-control" type="text" id="full_name" name="full_name" required value="<?= e($form['full_name']) ?>">
        <p class="small text-muted mb-0 mt-1" data-i18n="register.help_full_name">Use your everyday name as on your ID if you have one.</p>
      </div>
      <div>
        <label class="mgrid-form-label" for="phone" data-i18n="auth.label_phone">Phone</label>
        <input class="mgrid-form-control" type="text" id="phone" name="phone" required value="<?= e($form['phone']) ?>" placeholder="+255...">
        <p class="small text-muted mb-0 mt-1" data-i18n="register.help_phone">Use the phone number you use every day.</p>
      </div>
      <div>
        <label class="mgrid-form-label" for="email" data-i18n="auth.label_email">Email</label>
        <input class="mgrid-form-control" type="email" id="email" name="email" required value="<?= e($form['email']) ?>">
        <p class="small text-muted mb-0 mt-1" data-i18n="register.help_email">We will send important updates here.</p>
      </div>
      <div>
        <label class="mgrid-form-label" for="region" data-i18n="auth.label_region">Region</label>
        <select class="mgrid-form-control" id="region" name="region" required>
          <option value=""><?= e(__('register.choose')) ?></option>
          <?php foreach ($regions as $r): ?>
            <option value="<?= e($r) ?>" <?= $form['region'] === $r ? 'selected' : '' ?>><?= e($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="mgrid-form-label" for="date_of_birth"><span data-i18n="auth.label_dob">Date of birth (optional if age range given)</span></label>
        <input class="mgrid-form-control" type="date" id="date_of_birth" name="date_of_birth" value="<?= e($form['date_of_birth']) ?>">
      </div>
      <div>
        <label class="mgrid-form-label" for="age_range" data-i18n="auth.label_age">Age range</label>
        <select class="mgrid-form-control" id="age_range" name="age_range">
          <option value=""><?= e(__('register.choose')) ?></option>
              <?php
                $ranges = ['18-25', '26-35', '36-45', '46-55', '56-65', '65+'];
foreach ($ranges as $ar) {
    ?>
                <option value="<?= e($ar) ?>" <?= $form['age_range'] === $ar ? 'selected' : '' ?>><?= e($ar) ?></option>
              <?php
}
?>
        </select>
      </div>
      <div>
        <label class="mgrid-form-label" for="business_status" data-i18n="auth.label_business">Business status</label>
        <select class="mgrid-form-control" id="business_status" name="business_status" required>
          <option value=""><?= e(__('register.choose')) ?></option>
              <?php
    $bss = [
        'employed' => 'register.bs.employed',
        'self_employed' => 'register.bs.self_employed',
        'student' => 'register.bs.student',
        'homemaker' => 'register.bs.homemaker',
        'seeking' => 'register.bs.seeking',
        'other' => 'register.bs.other',
    ];
foreach ($bss as $val => $labelKey) {
    ?>
                <option value="<?= e($val) ?>" <?= $form['business_status'] === $val ? 'selected' : '' ?>><?= e(__($labelKey)) ?></option>
              <?php
}
?>
        </select>
      </div>
      <div>
        <label class="mgrid-form-label" for="preferred_language" data-i18n="auth.label_pref_lang">Preferred language</label>
        <select class="mgrid-form-control" id="preferred_language" name="preferred_language">
          <option value="en" <?= $form['preferred_language'] === 'en' ? 'selected' : '' ?> data-i18n="auth.opt_lang_en">English</option>
          <option value="sw" <?= $form['preferred_language'] === 'sw' ? 'selected' : '' ?> data-i18n="auth.opt_lang_sw">Kiswahili</option>
        </select>
      </div>
      <div>
        <label class="mgrid-form-label" for="password" data-i18n="auth.label_pw">Password</label>
        <input class="mgrid-form-control" type="password" id="password" name="password" autocomplete="new-password" required minlength="8">
      </div>
      <div>
        <label class="mgrid-form-label" for="confirm_password" data-i18n="auth.label_pw_confirm">Confirm password</label>
        <input class="mgrid-form-control" type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
      </div>
    </div>
    <div class="mt-4">
      <button type="submit" class="btn-mgrid btn-mgrid-primary w-100 justify-content-center py-3" data-i18n="auth.submit_register">Create account &amp; receive M-ID</button>
      <p class="text-center" style="font-size: 13.5px; color: var(--mgrid-ink-500); margin-top: 20px;">
        <span data-i18n="auth.meta_have_account">Already registered?</span>
        <a href="<?= e(url('login.php')) ?>" style="color: var(--mgrid-gold-600); font-weight: 500;" data-i18n="auth.meta_sign_in">Sign in</a>
      </p>
    </div>
  </form>
</div>

<?php
require __DIR__ . '/includes/footer.php';
