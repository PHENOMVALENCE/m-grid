<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$errors = [];

$regions = [
    'Arusha', 'Dar es Salaam', 'Dodoma', 'Geita', 'Iringa', 'Kagera', 'Katavi', 'Kigoma', 'Kilimanjaro',
    'Lindi', 'Manyara', 'Mara', 'Mbeya', 'Mjini Magharibi', 'Morogoro', 'Mtwara', 'Mwanza', 'Njombe',
    'Pemba North', 'Pemba South', 'Pwani', 'Rukwa', 'Ruvuma', 'Shinyanga', 'Simiyu', 'Singida', 'Songwe',
    'Tabora', 'Tanga', 'Other / Diaspora',
];

$businessStatuses = [
    'employed' => 'Employed',
    'self_employed' => 'Self-employed',
    'student' => 'Student',
    'homemaker' => 'Homemaker / caregiver',
    'seeking' => 'Seeking opportunity',
    'other' => 'Other',
];

$loadProfile = static function (PDO $pdoConn, int $userId): array {
    $stmtProfile = $pdoConn->prepare('
        SELECT u.*, p.region, p.date_of_birth, p.age_range, p.business_status, p.bio, p.profile_completion,
               s.score AS m_score, s.tier AS m_tier
        FROM users u
        LEFT JOIN user_profiles p ON p.user_id = u.id
        LEFT JOIN m_scores s ON s.user_id = u.id
        WHERE u.id = :id
        LIMIT 1
    ');
    $stmtProfile->execute(['id' => $userId]);
    return $stmtProfile->fetch() ?: [];
};

$row = $loadProfile($pdo, $uid);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = 'Invalid security token. Please refresh and try again.';
    } else {
        $fullName = clean_string($_POST['full_name'] ?? '');
        $email = strtolower(clean_string($_POST['email'] ?? ''));
        $phoneInput = clean_string($_POST['phone'] ?? '');
        $phoneNorm = normalise_phone($phoneInput);
        $region = clean_string($_POST['region'] ?? '');
        $businessStatus = clean_string($_POST['business_status'] ?? '');
        $preferredLanguage = clean_string($_POST['preferred_language'] ?? 'en');
        $bio = clean_string($_POST['bio'] ?? '');

        if ($fullName === '' || mb_strlen($fullName) < 2) {
            $errors[] = 'Please enter your full name.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }
        if ($phoneNorm === '') {
            $errors[] = 'Please provide your phone number.';
        }
        if ($region === '') {
            $errors[] = 'Please select your region.';
        }
        if (!array_key_exists($businessStatus, $businessStatuses)) {
            $errors[] = 'Please choose your business status.';
        }
        if (!in_array($preferredLanguage, ['en', 'sw'], true)) {
            $errors[] = 'Please choose a valid preferred language.';
        }
        if (mb_strlen($bio) > 500) {
            $errors[] = 'Biography should be 500 characters or fewer.';
        }

        if ($errors === []) {
            $chk = $pdo->prepare('SELECT id FROM users WHERE (email = :email OR phone = :phone) AND id <> :id LIMIT 1');
            $chk->execute([
                'email' => $email,
                'phone' => $phoneNorm,
                'id' => $uid,
            ]);
            if ($chk->fetch()) {
                $errors[] = 'That email or phone number is already used by another account.';
            }
        }

        if ($errors === []) {
            $filled = 0;
            foreach ([$fullName, $email, $phoneNorm, $region, $businessStatus, $preferredLanguage, $bio] as $part) {
                if ($part !== '') {
                    $filled++;
                }
            }
            $profileCompletion = max(15, (int) round(($filled / 7) * 100));

            try {
                $pdo->beginTransaction();

                $upUser = $pdo->prepare('
                    UPDATE users
                    SET full_name = :full_name,
                        email = :email,
                        phone = :phone,
                        preferred_language = :preferred_language
                    WHERE id = :id
                    LIMIT 1
                ');
                $upUser->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phoneNorm,
                    'preferred_language' => $preferredLanguage,
                    'id' => $uid,
                ]);

                $upProfile = $pdo->prepare('
                    INSERT INTO user_profiles (user_id, region, date_of_birth, age_range, business_status, bio, profile_photo, profile_completion)
                    VALUES (:uid, :region, NULL, NULL, :business_status, :bio, NULL, :profile_completion)
                    ON DUPLICATE KEY UPDATE
                        region = VALUES(region),
                        business_status = VALUES(business_status),
                        bio = VALUES(bio),
                        profile_completion = VALUES(profile_completion)
                ');
                $upProfile->execute([
                    'uid' => $uid,
                    'region' => $region,
                    'business_status' => $businessStatus,
                    'bio' => $bio !== '' ? $bio : null,
                    'profile_completion' => $profileCompletion,
                ]);

                $pdo->commit();
                $_SESSION['preferred_language'] = $preferredLanguage;
                flash_set('success', 'Your profile details were updated successfully.');
                redirect('user/profile.php');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = 'Unable to save profile changes right now. Please try again.';
            }
        }

        if ($errors !== []) {
            $row['full_name'] = $fullName;
            $row['email'] = $email;
            $row['phone'] = $phoneInput;
            $row['region'] = $region;
            $row['business_status'] = $businessStatus;
            $row['preferred_language'] = $preferredLanguage;
            $row['bio'] = $bio;
        }
    }
}

$mgrid_page_title = 'M-Profile — Malkia Grid';
require __DIR__ . '/includes/shell_open.php';
?>

<?php
$tierRaw = strtolower((string) ($row['m_tier'] ?? 'pending'));
$badgeTier = 'pending';
if (str_contains($tierRaw, 'gold')) {
    $badgeTier = 'gold';
} elseif (str_contains($tierRaw, 'silver')) {
    $badgeTier = 'silver';
} elseif (str_contains($tierRaw, 'bronze')) {
    $badgeTier = 'bronze';
} elseif (str_contains($tierRaw, 'diamond')) {
    $badgeTier = 'diamond';
}
$scoreDisp = isset($row['m_score']) && $row['m_score'] !== null && $row['m_score'] !== ''
    ? (string) $row['m_score']
    : '—';
$isGoldTier = $badgeTier === 'gold';
?>
<div class="mgrid-mid-card--premium mb-4<?= $isGoldTier ? ' mgrid-tier-gold-hero' : '' ?>">
  <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
    <div>
      <div class="mgrid-mid-card-name"><?= e((string) ($row['full_name'] ?? '')) ?></div>
      <div class="mgrid-mid-card-id"><?= e((string) ($row['m_id'] ?? '')) ?></div>
    </div>
    <?php if (!empty($row['m_tier'])): ?>
      <span class="badge rounded-pill mgrid-badge-tier-<?= e($badgeTier) ?> mgrid-mid-card-tier text-uppercase"><?= e((string) $row['m_tier']) ?></span>
    <?php endif; ?>
  </div>
  <div class="mgrid-mid-card-score"><?= e($scoreDisp) ?></div>
  <div class="mgrid-mid-card-score-label">M-Score</div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <h1 class="h4 mgrid-dash-page-title mb-4">M-Profile</h1>
    <?php if ($msg = flash_get('success')): ?>
      <div class="alert alert-success small"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger small py-2"><?= e($err) ?></div>
    <?php endforeach; ?>
    <div class="row g-4">
      <div class="col-md-6">
        <p class="text-muted small mb-1">M-ID</p>
        <p class="fw-bold fs-5 mgrid-mono-id"><?= e((string) ($row['m_id'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Full name</p>
        <p class="fw-semibold"><?= e((string) ($row['full_name'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Email</p>
        <p class="mb-0"><?= e((string) ($row['email'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Phone</p>
        <p class="mb-0"><?= e((string) ($row['phone'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Region</p>
        <p class="mb-0"><?= e((string) ($row['region'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Business status</p>
        <p class="mb-0"><?= e(str_replace('_', ' ', (string) ($row['business_status'] ?? ''))) ?></p>
      </div>
      <div class="col-12">
        <p class="text-muted small mb-1">Biography</p>
        <?php if (!empty($row['bio'])): ?>
          <p class="mb-0"><?= e((string) $row['bio']) ?></p>
        <?php else: ?>
          <p class="mb-0 text-muted">Not added yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm mt-4">
  <div class="card-body p-4">
    <h2 class="h5 mgrid-dash-section-title mb-1">Manage profile details</h2>
    <p class="small text-muted mb-4">Update your contact and profile information below.</p>

    <form method="post" class="row g-3" novalidate>
      <?= csrf_field() ?>
      <div class="col-md-6">
        <label class="form-label" for="full_name">Full name</label>
        <input class="form-control" type="text" id="full_name" name="full_name" required value="<?= e((string) ($row['full_name'] ?? '')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="phone">Phone</label>
        <input class="form-control" type="text" id="phone" name="phone" required value="<?= e((string) ($row['phone'] ?? '')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="email">Email</label>
        <input class="form-control" type="email" id="email" name="email" required value="<?= e((string) ($row['email'] ?? '')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="region">Region</label>
        <select class="form-select" id="region" name="region" required>
          <option value="">Choose...</option>
          <?php foreach ($regions as $regionName): ?>
            <option value="<?= e($regionName) ?>" <?= (string) ($row['region'] ?? '') === $regionName ? 'selected' : '' ?>><?= e($regionName) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="business_status">Business status</label>
        <select class="form-select" id="business_status" name="business_status" required>
          <option value="">Choose...</option>
          <?php foreach ($businessStatuses as $statusKey => $statusLabel): ?>
            <option value="<?= e($statusKey) ?>" <?= (string) ($row['business_status'] ?? '') === $statusKey ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="preferred_language">Preferred language</label>
        <select class="form-select" id="preferred_language" name="preferred_language">
          <option value="en" <?= (string) ($row['preferred_language'] ?? 'en') === 'en' ? 'selected' : '' ?>>English (default)</option>
          <option value="sw" <?= (string) ($row['preferred_language'] ?? 'en') === 'sw' ? 'selected' : '' ?>>Kiswahili (coming)</option>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label" for="bio">Biography</label>
        <textarea class="form-control" id="bio" name="bio" rows="4" maxlength="500" placeholder="Tell us about your work and goals."><?= e((string) ($row['bio'] ?? '')) ?></textarea>
      </div>
      <div class="col-12 d-flex justify-content-end">
        <button type="submit" class="btn btn-primary px-4">Save changes</button>
      </div>
    </form>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
