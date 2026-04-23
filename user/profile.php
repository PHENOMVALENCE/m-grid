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
        $errors[] = __('settings.error.token');
    } else {
        $fullName = clean_string($_POST['full_name'] ?? '');
        $email = strtolower(clean_string($_POST['email'] ?? ''));
        $phoneInput = clean_string($_POST['phone'] ?? '');
        $phoneNorm = normalise_phone($phoneInput);
        $region = clean_string($_POST['region'] ?? '');
        $businessStatus = clean_string($_POST['business_status'] ?? '');
        $preferredLanguage = clean_string($_POST['preferred_language'] ?? 'sw');
        $bio = clean_string($_POST['bio'] ?? '');

        if ($fullName === '' || mb_strlen($fullName) < 2) {
            $errors[] = __('register.error.full_name');
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('register.error.email');
        }
        if ($phoneNorm === '') {
            $errors[] = __('register.error.phone');
        }
        if ($region === '') {
            $errors[] = __('register.error.region');
        }
        if (!array_key_exists($businessStatus, $businessStatuses)) {
            $errors[] = __('register.error.business');
        }
        if (!in_array($preferredLanguage, ['en', 'sw'], true)) {
            $errors[] = __('register.error.language');
        }
        if (mb_strlen($bio) > 500) {
            $errors[] = __('profile.error.bio_length');
        }

        if ($errors === []) {
            $chk = $pdo->prepare('SELECT id FROM users WHERE (email = :email OR phone = :phone) AND id <> :id LIMIT 1');
            $chk->execute([
                'email' => $email,
                'phone' => $phoneNorm,
                'id' => $uid,
            ]);
            if ($chk->fetch()) {
                $errors[] = __('profile.error.duplicate_contact');
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
                flash_set('success', __('profile.success_updated'));
                redirect('user/profile.php');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = __('profile.error.save_failed');
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

$mgrid_page_title = mgrid_title('title.profile');
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
$userStatusKey = match ($userStatus) {
    'active' => 'profile.status_active',
    'suspended' => 'profile.status_suspended',
    default => 'profile.status_pending_verification',
};

$langLabel = ((string) ($row['preferred_language'] ?? 'sw')) === 'sw' ? __('lang.ui_sw') : __('lang.ui_en');
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
$nidStatusKey = match ($nidStatus) {
    'approved' => 'profile.nid_verified',
    'rejected' => 'profile.nid_update_required',
    'pending' => 'profile.nid_under_review',
    default => 'profile.nid_not_submitted',
};
$profileCompletion = (int) ($row['profile_completion'] ?? 0);

$dobRaw = $row['date_of_birth'] ?? null;
$dobDisp = $dobRaw ? $fmtDate((string) $dobRaw) : '—';
$ageRangeDisp = trim((string) ($row['age_range'] ?? '')) !== '' ? (string) $row['age_range'] : '—';
?>

<div class="mgrid-profile-page mgrid-page-section">
  <section class="mgrid-profile-hero" aria-labelledby="mprofile-hero-name">
    <div class="mgrid-profile-hero-main">
      <p class="mgrid-profile-hero-kicker" data-i18n="profile.page_kicker">M-Profile</p>
      <h1 id="mprofile-hero-name" class="mgrid-profile-hero-name mgrid-display"><?= e((string) ($row['full_name'] ?? 'Member')) ?></h1>
      <p class="mgrid-profile-hero-mid mgrid-mono-id"><i class="ti ti-fingerprint" aria-hidden="true"></i> <?= e((string) ($row['m_id'] ?? '—')) ?></p>
      <p class="mgrid-profile-hero-lead"><span data-i18n="<?= e($userStatusKey) ?>"><?= e($userStatusLabel) ?></span> · <span data-i18n="<?= ((string) ($row['preferred_language'] ?? 'sw')) === 'sw' ? 'lang.sw' : 'lang.en' ?>"><?= e($langLabel) ?></span> · <span data-i18n="profile.hero_member_since">Member since</span> <?= e($fmtDate(isset($row['created_at']) ? (string) $row['created_at'] : null)) ?></p>
    </div>
    <div class="mgrid-profile-hero-aside">
      <div class="mgrid-profile-score-card" data-score-ring="<?= e((string) round($scorePct)) ?>">
        <div class="mgrid-profile-score-card-label" data-i18n="profile.score_card_label">M-SCORE</div>
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
        <p class="mgrid-profile-score-card-meta"><span data-i18n="profile.score_updated">Updated</span> <?= e($fmtDate(isset($row['m_score_updated']) ? (string) $row['m_score_updated'] : null)) ?></p>
        <a class="btn-mgrid btn-mgrid-outline mgrid-profile-score-cta" href="<?= e(url('user/my_mscore.php')) ?>"><span data-i18n="profile.cta_methodology">View methodology</span></a>
      </div>
    </div>
  </section>

  <div class="mgrid-card mgrid-profile-overview">
    <div class="mgrid-card-header mgrid-profile-overview-header">
      <div>
        <h2 class="mgrid-card-title mb-1"><i class="ti ti-id-badge-2"></i> <span data-i18n="profile.overview_title">Your particulars</span></h2>
        <p class="mgrid-profile-overview-sub mb-0" data-i18n="profile.overview_sub">Everything partners and programmes see from your M-Profile record.</p>
      </div>
      <span class="mgrid-badge mgrid-badge--<?= $profileCompletion >= 100 ? 'verified' : ($profileCompletion >= 50 ? 'review' : 'pending') ?>"><?= $profileCompletion ?>% <span data-i18n="profile.badge_complete">complete</span></span>
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
          <h3 id="mprofile-identity" class="mgrid-profile-block-title" data-i18n="profile.sec_identity">Identity &amp; account</h3>
          <div class="mgrid-profile-fields">
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_mid">M-ID</span>
              <span class="mgrid-profile-field-value mgrid-mono-id"><?= e((string) ($row['m_id'] ?? '—')) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_full_legal_name">Full legal name</span>
              <span class="mgrid-profile-field-value"><?= e((string) ($row['full_name'] ?? '—')) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_account_status">Account status</span>
              <span class="mgrid-profile-field-value" data-i18n="<?= e($userStatusKey) ?>"><?= e($userStatusLabel) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_member_since">Member since</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['created_at']) ? (string) $row['created_at'] : null)) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_account_updated">Account last updated</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['updated_at']) ? (string) $row['updated_at'] : null)) ?></span>
            </div>
          </div>
        </section>

        <section class="mgrid-profile-block" aria-labelledby="mprofile-contact">
          <h3 id="mprofile-contact" class="mgrid-profile-block-title" data-i18n="profile.sec_contact">Contact &amp; preferences</h3>
          <div class="mgrid-profile-fields">
            <div class="mgrid-profile-field mgrid-profile-field--wide">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_email">Email</span>
              <span class="mgrid-profile-field-value"><a href="mailto:<?= e((string) ($row['email'] ?? '')) ?>"><?= e((string) ($row['email'] ?? '—')) ?></a></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_phone">Phone</span>
              <span class="mgrid-profile-field-value"><a href="tel:<?= e(preg_replace('/\s+/', '', (string) ($row['phone'] ?? ''))) ?>"><?= e((string) ($row['phone'] ?? '—')) ?></a></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_pref_lang">Preferred language</span>
              <span class="mgrid-profile-field-value" data-i18n="<?= ((string) ($row['preferred_language'] ?? 'sw')) === 'sw' ? 'lang.sw' : 'lang.en' ?>"><?= e($langLabel) ?></span>
            </div>
          </div>
        </section>

        <section class="mgrid-profile-block" aria-labelledby="mprofile-business">
          <h3 id="mprofile-business" class="mgrid-profile-block-title" data-i18n="profile.sec_business">Location &amp; business context</h3>
          <div class="mgrid-profile-fields">
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_region">Region</span>
              <span class="mgrid-profile-field-value"><?= (string) ($row['region'] ?? '') !== '' ? e((string) $row['region']) : '—' ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_business_status">Business status</span>
              <span class="mgrid-profile-field-value"><?= e($bizLabel) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_dob">Date of birth</span>
              <span class="mgrid-profile-field-value"><?= e($dobDisp) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_age_range">Age range</span>
              <span class="mgrid-profile-field-value"><?= e($ageRangeDisp) ?></span>
            </div>
            <div class="mgrid-profile-field mgrid-profile-field--full">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_profile_strength">Profile strength</span>
              <div class="mgrid-profile-progress">
                <div class="mgrid-progress-track" role="progressbar" aria-valuenow="<?= $profileCompletion ?>" aria-valuemin="0" aria-valuemax="100">
                  <div class="mgrid-progress-fill" style="width: <?= max(0, min(100, $profileCompletion)) ?>%;"></div>
                </div>
                <span class="mgrid-profile-progress-caption"><?= $profileCompletion ?>% <span data-i18n="profile.progress_tail">aligned with M-Profile milestones</span></span>
              </div>
            </div>
          </div>
        </section>

        <section class="mgrid-profile-block" aria-labelledby="mprofile-verify">
          <h3 id="mprofile-verify" class="mgrid-profile-block-title" data-i18n="profile.sec_nid">National ID verification</h3>
          <div class="mgrid-profile-fields">
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_nid_status">Status</span>
              <span class="mgrid-profile-field-value"><span class="mgrid-badge mgrid-badge--<?= $nidStatus === 'approved' ? 'verified' : ($nidStatus === 'rejected' ? 'rejected' : ($nidStatus === 'pending' ? 'review' : 'inactive')) ?>" data-i18n="<?= e($nidStatusKey) ?>"><?= e($nidLabel) ?></span></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_nid_submitted">Submitted</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['national_id_submitted_at']) ? (string) $row['national_id_submitted_at'] : null)) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_nid_reviewed">Reviewed</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['national_id_reviewed_at']) ? (string) $row['national_id_reviewed_at'] : null)) ?></span>
            </div>
            <?php if ($nidStatus === 'rejected' && trim((string) ($row['national_id_notes'] ?? '')) !== ''): ?>
            <div class="mgrid-profile-field mgrid-profile-field--full">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_nid_note">Reviewer note</span>
              <span class="mgrid-profile-field-value mgrid-profile-field-value--note"><?= e((string) $row['national_id_notes']) ?></span>
            </div>
            <?php endif; ?>
          </div>
        </section>

        <section class="mgrid-profile-block mgrid-profile-block--bio" aria-labelledby="mprofile-bio">
          <h3 id="mprofile-bio" class="mgrid-profile-block-title" data-i18n="profile.sec_bio">Biography &amp; narrative</h3>
          <div class="mgrid-profile-bio">
            <?php if (!empty($row['bio'])): ?>
              <p class="mgrid-profile-bio-text"><?= nl2br(e((string) $row['bio'])) ?></p>
            <?php else: ?>
              <p class="mgrid-profile-bio-empty" data-i18n="profile.bio_empty">You have not added a biography yet. Use the form below to share your story, goals, and the impact you are building.</p>
            <?php endif; ?>
          </div>
        </section>

        <section class="mgrid-profile-block mgrid-profile-block--meta" aria-labelledby="mprofile-record">
          <h3 id="mprofile-record" class="mgrid-profile-block-title" data-i18n="profile.sec_meta">Record metadata</h3>
          <div class="mgrid-profile-fields mgrid-profile-fields--compact">
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_profile_record_opened">M-Profile record opened</span>
              <span class="mgrid-profile-field-value"><?= e($fmtDate(isset($row['profile_created_at']) ? (string) $row['profile_created_at'] : null)) ?></span>
            </div>
            <div class="mgrid-profile-field">
              <span class="mgrid-profile-field-label" data-i18n="profile.lbl_profile_last_saved">M-Profile last saved</span>
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
        <h2 class="mgrid-card-title mb-1"><i class="ti ti-edit"></i> <span data-i18n="profile.form_title">Manage profile details</span></h2>
        <p class="mgrid-profile-overview-sub mb-0" data-i18n="profile.form_sub">Update your contact and profile information below.</p>
      </div>
    </div>
    <div class="mgrid-card-body">
    <form method="post" class="row g-3" novalidate>
      <?= csrf_field() ?>
      <div class="col-md-6">
        <label class="form-label" for="full_name" data-i18n="auth.label_full_name">Full name</label>
        <input class="form-control" type="text" id="full_name" name="full_name" required value="<?= e((string) ($row['full_name'] ?? '')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="phone" data-i18n="auth.label_phone">Phone</label>
        <input class="form-control" type="text" id="phone" name="phone" required value="<?= e((string) ($row['phone'] ?? '')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="email" data-i18n="auth.label_email">Email</label>
        <input class="form-control" type="email" id="email" name="email" required value="<?= e((string) ($row['email'] ?? '')) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="region" data-i18n="auth.label_region">Region</label>
        <select class="form-select" id="region" name="region" required>
          <option value="" data-i18n="profile.opt_choose">Choose...</option>
          <?php foreach ($regions as $regionName): ?>
            <option value="<?= e($regionName) ?>" <?= (string) ($row['region'] ?? '') === $regionName ? 'selected' : '' ?>><?= e($regionName) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="business_status" data-i18n="auth.label_business">Business status</label>
        <select class="form-select" id="business_status" name="business_status" required>
          <option value="" data-i18n="profile.opt_choose">Choose...</option>
          <?php foreach ($businessStatuses as $statusKey => $statusLabel): ?>
            <option value="<?= e($statusKey) ?>" <?= (string) ($row['business_status'] ?? '') === $statusKey ? 'selected' : '' ?>><?= e($statusLabel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="preferred_language" data-i18n="auth.label_pref_lang">Preferred language</label>
        <select class="form-select" id="preferred_language" name="preferred_language">
          <option value="en" <?= (string) ($row['preferred_language'] ?? 'sw') === 'en' ? 'selected' : '' ?> data-i18n="auth.opt_lang_en">English</option>
          <option value="sw" <?= (string) ($row['preferred_language'] ?? 'sw') === 'sw' ? 'selected' : '' ?> data-i18n="auth.opt_lang_sw">Kiswahili</option>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label" for="bio" data-i18n="profile.lbl_bio">Biography</label>
        <textarea class="form-control" id="bio" name="bio" rows="4" maxlength="500" placeholder="Tell us about your work and goals." data-i18n-placeholder="profile.bio_placeholder"><?= e((string) ($row['bio'] ?? '')) ?></textarea>
      </div>
      <div class="col-12 d-flex justify-content-end gap-2 pt-2">
        <button type="submit" class="btn-mgrid btn-mgrid-primary px-4" data-i18n="profile.form_save">Save changes</button>
      </div>
    </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
