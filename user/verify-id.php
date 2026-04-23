<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$user = auth_user();
$uid = (int) ($user['user_id'] ?? 0);
$errors = [];
$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
$maxBytes = 5 * 1024 * 1024; // 5MB

$profileStmt = $pdo->prepare('
    SELECT u.status, p.national_id_photo, p.national_id_status, p.national_id_notes, p.national_id_submitted_at, p.national_id_reviewed_at
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    WHERE u.id = :id
    LIMIT 1
');
$profileStmt->execute(['id' => $uid]);
$profile = $profileStmt->fetch() ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $errors[] = __('nid.verify.token');
    } else {
        if (!isset($_FILES['national_id_photo']) || !is_array($_FILES['national_id_photo'])) {
            $errors[] = __('nid.verify.pick_photo');
        } else {
            $file = $_FILES['national_id_photo'];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                $errors[] = __('nid.verify.upload_failed');
            } elseif ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > $maxBytes) {
                $errors[] = __('nid.verify.size');
            } else {
                $tmp = (string) ($file['tmp_name'] ?? '');
                $original = (string) ($file['name'] ?? '');
                $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
                $mime = mime_content_type($tmp) ?: '';
                if (!in_array($ext, $allowedExt, true) || !in_array($mime, $allowedMime, true)) {
                    $errors[] = __('nid.verify.format');
                } else {
                    $targetDir = MGRID_ROOT . '/uploads/documents/national_ids';
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0775, true);
                    }
                    $safeName = sprintf('nid_%d_%s.%s', $uid, bin2hex(random_bytes(8)), $ext);
                    $targetPath = $targetDir . '/' . $safeName;
                    $relativePath = 'uploads/documents/national_ids/' . $safeName;
                    if (!move_uploaded_file($tmp, $targetPath)) {
                        $errors[] = __('nid.verify.store_failed');
                    } else {
                        $up = $pdo->prepare('
                            UPDATE user_profiles
                            SET national_id_photo = :p,
                                national_id_status = "pending",
                                national_id_notes = NULL,
                                national_id_submitted_at = NOW(),
                                national_id_reviewed_at = NULL,
                                national_id_reviewed_by = NULL,
                                updated_at = NOW()
                            WHERE user_id = :uid
                        ');
                        $up->execute(['p' => $relativePath, 'uid' => $uid]);
                        flash_set('success', __('nid.flash.uploaded'));
                        redirect('user/verify-id.php');
                    }
                }
            }
        }
    }
}

$profileStmt->execute(['id' => $uid]);
$profile = $profileStmt->fetch() ?: [];
$status = (string) ($profile['status'] ?? 'pending');
$idStatus = (string) ($profile['national_id_status'] ?? 'not_submitted');

$mgrid_page_title = mgrid_title('title.verify_id');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?>
  <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php if ($msg = flash_get('error')): ?>
  <div class="alert alert-danger"><?= e($msg) ?></div>
<?php endif; ?>
<?php foreach ($errors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-body p-4">
    <h1 class="h4 mgrid-dash-page-title mb-2">National ID Verification</h1>
    <p class="text-muted small mb-0">
      You can use the full platform after your National ID photo is reviewed and approved by an admin.
    </p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body p-4">
        <h2 class="h5 mgrid-dash-section-title mb-3">Upload National ID photo</h2>
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="national_id_photo">Photo file (JPG, PNG, WEBP, max 5MB)</label>
            <input class="form-control" type="file" id="national_id_photo" name="national_id_photo" accept=".jpg,.jpeg,.png,.webp" required>
          </div>
          <button class="btn btn-primary" type="submit">Submit for approval</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body p-4">
        <h2 class="h5 mgrid-dash-section-title mb-3">Verification status</h2>
        <p class="mb-2"><strong>Account status:</strong> <span class="badge bg-light text-dark border"><?= e($status) ?></span></p>
        <p class="mb-2"><strong>ID status:</strong> <span class="badge bg-light text-dark border"><?= e($idStatus) ?></span></p>
        <?php if (!empty($profile['national_id_submitted_at'])): ?>
          <p class="small text-muted mb-2">Last submitted: <?= e((string) $profile['national_id_submitted_at']) ?></p>
        <?php endif; ?>
        <?php if (!empty($profile['national_id_notes'])): ?>
          <div class="alert alert-warning small mb-0">
            <strong>Admin note:</strong> <?= e((string) $profile['national_id_notes']) ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
