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
        SELECT u.*,
               p.region, p.date_of_birth, p.age_range, p.business_status, p.bio, p.profile_completion,
               p.created_at AS profile_created_at, p.updated_at AS profile_updated_at,
               p.national_id_status, p.national_id_submitted_at, p.national_id_reviewed_at, p.national_id_notes,
               s.score AS m_score, s.tier AS m_tier, s.last_calculated_at AS m_score_updated
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

$mTierRaw = (string) ($row['m_tier'] ?? '');
$tierSlug = $mTierRaw !== ''
    ? strtolower(preg_replace('/[^a-z0-9]+/', '_', $mTierRaw))
    : 'pending';
$tierDisplay = $mTierRaw !== ''
    ? ucwords(str_replace('_', ' ', strtolower($mTierRaw)))
    : 'Pending';
$scoreDisp = isset($row['m_score']) && $row['m_score'] !== null && $row['m_score'] !== ''
    ? (string) $row['m_score']
    : '—';
$scorePct = isset($row['m_score']) && $row['m_score'] !== null && $row['m_score'] !== ''
    ? max(0, min(100, (float) $row['m_score']))
    : 0;

$fmtDate = static function (?string $d): string {
    if ($d === null || $d === '') {
        return '—';
    }
    $t = strtotime($d);

    return $t ? date('j M Y', $t) : '—';
};

$userStatus = (string) ($row['status'] ?? 'pending');
$userStatusLabel = match ($userStatus) {
    'active' => 'Active',
    'suspended' => 'Suspended',
    default => 'Pending verification',
};

$langLabel = ((string) ($row['preferred_language'] ?? 'en')) === 'sw' ? 'Kiswahili' : 'English';
$bizKey = (string) ($row['business_status'] ?? '');
$bizLabel = $businessStatuses[$bizKey] ?? ($bizKey !== '' ? ucwords(str_replace('_', ' ', $bizKey)) : '—');

$nidStatus = (string) ($row['national_id_status'] ?? 'not_submitted');
$nidLabels = [
    'not_submitted' => 'Not submitted',
    'pending' => 'Under review',
    'approved' => 'Verified',
    'rejected' => 'Update required',
];
$nidLabel = $nidLabels[$nidStatus] ?? $nidStatus;
$profileCompletion = (int) ($row['profile_completion'] ?? 0);

$dobRaw = $row['date_of_birth'] ?? null;
$dobDisp = $dobRaw ? $fmtDate((string) $dobRaw) : '—';
$ageRangeDisp = trim((string) ($row['age_range'] ?? '')) !== '' ? (string) $row['age_range'] : '—';
?>

<div class="mgrid-profile-page mgrid-page-section">
  <section class="mgrid-profile-hero" aria-labelledby="mprofile-hero-name">
    <div class="mgrid-profile-hero-main">
      <p class="mgrid-profile-hero-kicker">M-Profile</p>
      <h1 id="mprofile-hero-name" class="mgrid-profile-hero-name mgrid-display"><?= e((string) ($row['full_name'] ?? 'Member')) ?></h1>
      <p class="mgrid-profile-hero-mid mgrid-mono-id"><i class="ti ti-fingerprint" aria-hidden="true"></i> <?= e((string) ($row['m_id'] ?? '—')) ?></p>
      <p class="mgrid-profile-hero-lead"><?= e($userStatusLabel) ?> · <?= e($langLabel) ?> · Member since <?= e($fmtDate(isset($row['created_at']) ? (string) $row['created_at'] : null)) ?></p>
    </div>
    <div class="mgrid-profile-hero-aside">
      <div class="mgrid-profile-score-card" data-score-ring="<?= e((string) round($scorePct)) ?>">
        <div class="mgrid-profile-score-card-label">M-SCORE</div>
        <div class="mgrid-profile-score-card-ring">
          <svg width="108" height="108" viewBox="0 0 100 100" aria-hidden="true">
            <circle class="mgrid-score-ring-track" cx="50" cy="50" r="45"></circle>
            <circle class="mgrid-score-ring-fill mgrid-score-ring-fill--<?= e($tierSlug) ?>" cx="50" cy="50" r="45"></circle>
          </svg>
          <div class="mgrid-profile-score-card-inner">
            <span class="mgrid-profile-score-card-value"><?= e($scoreDisp) ?></span>
          </div>
        </div>
        <span class="mgrid-tier-badge mgrid-tier-badge--<?= e($tierSlug) ?>"><?= e($tierDisplay) ?></span>
        <p class="mgrid-profile-score-card-meta">Updated <?= e($fmtDate(isset($row['m_score_updated']) ? (string) $row['m_score_updated'] : null)) ?></p>
        <a class="btn-mgrid btn-mgrid-outline mgrid-profile-score-cta" href="<?= e(url('user/my_mscore.php')) ?>">View methodology</a>
      </div>
    </div>
  </section>

  <div class="mgrid-card mgrid-profile-overview">
    <div class="mgrid-card-header mgrid-profile-overview-header">
      <div>
        <h2 class="mgrid-card-title mb-1"><i class="ti ti-id-badge-2"></i> Your particulars</h2>
        <p class="mgrid-profile-overview-sub mb-0">Everything partners and programmes see from your M-Profile record.</p>
      </div>
      <span class="mgrid-badge mgrid-badge--<?= $profileCompletion >= 100 ? 'verified' : ($profileCompletion >= 50 ? 'review' : 'pending') ?>"><?= $profileCompletion ?>% complete</span>
    </div>
    <div class="mgrid-card-body">
    <?php if ($msg = flash_get('success')): ?>
      <div class="alert alert-success small mb-4"><?= e($msg) ?></div>
    <?php endif; ?>
    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger small py-2 mb-3"><?= e($err) ?></div>
    <?php endforeach; ?>

      <div class="mgrid-profile-sections">
        <section class="mgrid-profile-block" aria-labelledby="mprofile-identity">
          <h3 id="mprofile-identity" class="mgrid-profile-block-title">Identity &amp; account</h3>
          <div class="mgrid-profile-fields">
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">M-ID</span>
              <span class="mgrid-profile-field-value mgrid-mono-id"><?= e((string) ($row['m_id'] ?? '—')) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Full legal name</span>
              <span class="mgrid-profile-field-value"><?= e((string) ($row['full_name'] ?? '—')) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Account status</span>
              <span class="mgrid-profile-field-value"><?= e($userStatusLabel) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Member since</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['created_at']) ? (string) $row['created_at'] : null)) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Account last updated</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['updated_at']) ? (string) $row['updated_at'] : null)) ?></span>
            </div>
          </div>
        </section>

        <section class="mgrid-profile-block" aria-labelledby="mprofile-contact">
          <h3 id="mprofile-contact" class="mgrid-profile-block-title">Contact &amp; preferences</h3>
          <div class="mgrid-profile-fields">
            <div class="mgrid-profile-field mgrid-profile-field--wide">
              <span class="mgrid-profile-field-label">Email</span>
              <span class="mgrid-profile-field-value"><a href="mailto:<?= e((string) ($row['email'] ?? '')) ?>"><?= e((string) ($row['email'] ?? '—')) ?></a></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Phone</span>
              <span class="mgrid-profile-field-value"><a href="tel:<?= e(preg_replace('/\s+/', '', (string) ($row['phone'] ?? ''))) ?>"><?= e((string) ($row['phone'] ?? '—')) ?></a></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Preferred language</span>
              <span class="mgrid-profile-field-value"><?= e($langLabel) ?></span>
            </div>
          </div>
        </section>

        <section class="mgrid-profile-block" aria-labelledby="mprofile-business">
          <h3 id="mprofile-business" class="mgrid-profile-block-title">Location &amp; business context</h3>
          <div class="mgrid-profile-fields">
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Region</span>
              <span class="mgrid-profile-field-value"><?= (string) ($row['region'] ?? '') !== '' ? e((string) $row['region']) : '—' ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Business status</span>
              <span class="mgrid-profile-field-value"><?= e($bizLabel) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Date of birth</span>
              <span class="mgrid-profile-field-value"><?= e($dobDisp) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Age range</span>
              <span class="mgrid-profile-field-value"><?= e($ageRangeDisp) ?></span>
            </div>
            <div class="mgrid-profile-field mgrid-profile-field--full">
              <span class="mgrid-profile-field-label">Profile strength</span>
              <div class="mgrid-profile-progress">
                <div class="mgrid-progress-track" role="progressbar" aria-valuenow="<?= $profileCompletion ?>" aria-valuemin="0" aria-valuemax="100">
                  <div class="mgrid-progress-fill" style="width: <?= max(0, min(100, $profileCompletion)) ?>%;"></div>
                </div>
                <span class="mgrid-profile-progress-caption"><?= $profileCompletion ?>% aligned with M-Profile milestones</span>
              </div>
            </div>
          </div>
        </section>

        <section class="mgrid-profile-block" aria-labelledby="mprofile-verify">
          <h3 id="mprofile-verify" class="mgrid-profile-block-title">National ID verification</h3>
          <div class="mgrid-profile-fields">
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Status</span>
              <span class="mgrid-profile-field-value"><span class="mgrid-badge mgrid-badge--<?= $nidStatus === 'approved' ? 'verified' : ($nidStatus === 'rejected' ? 'rejected' : ($nidStatus === 'pending' ? 'review' : 'inactive')) ?>"><?= e($nidLabel) ?></span></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Submitted</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['national_id_submitted_at']) ? (string) $row['national_id_submitted_at'] : null)) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">Reviewed</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['national_id_reviewed_at']) ? (string) $row['national_id_reviewed_at'] : null)) ?></span>
            </div>
            <?php if ($nidStatus === 'rejected' && trim((string) ($row['national_id_notes'] ?? '')) !== ''): ?>
            <div class="mgrid-profile-field mgrid-profile-field--full">
              <span class="mgrid-profile-field-label">Reviewer note</span>
              <span class="mgrid-profile-field-value mgrid-profile-field-value--note"><?= e((string) $row['national_id_notes']) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </section>

        <section class="mgrid-profile-block mgrid-profile-block--bio" aria-labelledby="mprofile-bio">
          <h3 id="mprofile-bio" class="mgrid-profile-block-title">Biography &amp; narrative</h3>
          <div class="mgrid-profile-bio">
            <?php if (!empty($row['bio'])): ?>
              <p class="mgrid-profile-bio-text"><?= nl2br(e((string) $row['bio'])) ?></p>
            <?php else: ?>
              <p class="mgrid-profile-bio-empty">You have not added a biography yet. Use the form below to share your story, goals, and the impact you are building.</p>
            <?php endif; ?>
          </div>
        </section>

        <section class="mgrid-profile-block mgrid-profile-block--meta" aria-labelledby="mprofile-record">
          <h3 id="mprofile-record" class="mgrid-profile-block-title">Record metadata</h3>
          <div class="mgrid-profile-fields mgrid-profile-fields--compact">
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">M-Profile record opened</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['profile_created_at']) ? (string) $row['profile_created_at'] : null)) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label">M-Profile last saved</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['profile_updated_at']) ? (string) $row['profile_updated_at'] : null)) ?></span>
            </div>
          </div>
        </section>
      </div>
    </div>
  </div>

  <div class="mgrid-card mgrid-page-section mgrid-profile-edit-card">
    <div class="mgrid-card-header">
      <div>
        <h2 class="mgrid-card-title mb-1"><i class="ti ti-edit"></i> Manage profile details</h2>
        <p class="mgrid-profile-overview-sub mb-0">Update your contact and profile information below.</p>
      </div>
    </div>
    <div class="mgrid-card-body">
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
      <div class="col-12 d-flex justify-content-end gap-2 pt-2">
        <button type="submit" class="btn-mgrid btn-mgrid-primary px-4">Save changes</button>
      </div>
    </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
