<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$pdo = db();
$admin = auth_admin();
$id = (int) ($_GET['id'] ?? 0);
if ($id < 1) {
    http_response_code(404);
    echo 'User not found.';
    exit;
}

$actionErrors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['_csrf'] ?? null)) {
        $actionErrors[] = 'Invalid security token.';
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $note = clean_string($_POST['review_note'] ?? '');
        if ($action === 'approve_id') {
            $up = $pdo->prepare('
                UPDATE users u
                JOIN user_profiles p ON p.user_id = u.id
                SET u.status = "active",
                    p.national_id_status = "approved",
                    p.national_id_notes = :note,
                    p.national_id_reviewed_at = NOW(),
                    p.national_id_reviewed_by = :aid,
                    p.updated_at = NOW()
                WHERE u.id = :id
            ');
            $up->execute(['note' => ($note !== '' ? $note : 'Approved by admin'), 'aid' => (int) $admin['admin_id'], 'id' => $id]);
            admin_log($pdo, (int) $admin['admin_id'], $id, 'approve_national_id', 'Approved National ID for user #' . $id);
            flash_set('success', __('admin.nid.approved'));
            redirect('admin/user-view.php?id=' . $id);
        } elseif ($action === 'reject_id') {
            $up = $pdo->prepare('
                UPDATE users u
                JOIN user_profiles p ON p.user_id = u.id
                SET u.status = "pending",
                    p.national_id_status = "rejected",
                    p.national_id_notes = :note,
                    p.national_id_reviewed_at = NOW(),
                    p.national_id_reviewed_by = :aid,
                    p.updated_at = NOW()
                WHERE u.id = :id
            ');
            $up->execute(['note' => ($note !== '' ? $note : 'Please upload a clearer photo.'), 'aid' => (int) $admin['admin_id'], 'id' => $id]);
            admin_log($pdo, (int) $admin['admin_id'], $id, 'reject_national_id', 'Rejected National ID for user #' . $id);
            flash_set('success', __('admin.nid.rejected'));
            redirect('admin/user-view.php?id=' . $id);
        }
    }
}

$stmt = $pdo->prepare('
    SELECT u.*, p.region, p.date_of_birth, p.age_range, p.business_status, p.bio, p.profile_completion,
           p.national_id_photo, p.national_id_status, p.national_id_notes, p.national_id_submitted_at, p.national_id_reviewed_at,
           s.score, s.tier
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    LEFT JOIN m_scores s ON s.user_id = u.id
    WHERE u.id = :id
    LIMIT 1
');
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();
if (!$row) {
    http_response_code(404);
    echo 'Member not found.';
    exit;
}

admin_log(
    $pdo,
    (int) $admin['admin_id'],
    $id,
    'view_user',
    'Opened member record ' . $row['m_id']
);

$mgrid_page_title = mgrid_title('title.admin_member_detail', ['mid' => (string) $row['m_id']]);
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mb-3">
  <a class="btn btn-sm btn-light border" href="<?= e(url('admin/users.php')) ?>">&larr; Back to directory</a>
</div>
<?php if ($msg = flash_get('success')): ?>
  <div class="alert alert-success"><?= e($msg) ?></div>
<?php endif; ?>
<?php foreach ($actionErrors as $err): ?>
  <div class="alert alert-danger"><?= e($err) ?></div>
<?php endforeach; ?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <h1 class="h4 mgrid-dash-page-title mb-4"><?= e((string) $row['full_name']) ?></h1>
    <div class="row g-4">
      <div class="col-md-4">
        <p class="text-muted small mb-1">M-ID</p>
        <p class="fw-bold fs-5"><?= e((string) $row['m_id']) ?></p>
      </div>
      <div class="col-md-4">
        <p class="text-muted small mb-1">Status</p>
        <p class="mb-0"><span class="badge bg-light text-dark border"><?= e((string) $row['status']) ?></span></p>
      </div>
      <div class="col-md-4">
        <p class="text-muted small mb-1">Joined</p>
        <p class="mb-0"><?= e((string) $row['created_at']) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Email</p>
        <p class="mb-0"><?= e((string) $row['email']) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Phone</p>
        <p class="mb-0"><?= e((string) $row['phone']) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Region</p>
        <p class="mb-0"><?= e((string) ($row['region'] ?? '')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Business status</p>
        <p class="mb-0"><?= e(str_replace('_', ' ', (string) ($row['business_status'] ?? ''))) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">M-Score tier</p>
        <p class="mb-0"><?= e((string) ($row['tier'] ?? 'pending')) ?></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">Profile completion</p>
        <p class="mb-0"><?= (int) ($row['profile_completion'] ?? 0) ?>%</p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">National ID status</p>
        <p class="mb-0"><span class="badge bg-light text-dark border"><?= e((string) ($row['national_id_status'] ?? 'not_submitted')) ?></span></p>
      </div>
      <div class="col-md-6">
        <p class="text-muted small mb-1">National ID submitted</p>
        <p class="mb-0"><?= e((string) ($row['national_id_submitted_at'] ?? '—')) ?></p>
      </div>
      <div class="col-12">
        <p class="text-muted small mb-1">Bio</p>
        <?php if (!empty($row['bio'])): ?>
          <p class="mb-0"><?= e((string) $row['bio']) ?></p>
        <?php else: ?>
          <p class="mb-0 text-muted">—</p>
        <?php endif; ?>
      </div>
      <div class="col-12">
        <p class="text-muted small mb-1">National ID photo</p>
        <?php if (!empty($row['national_id_photo'])): ?>
          <a class="btn btn-sm btn-outline-primary mb-2" target="_blank" href="<?= e(url((string) $row['national_id_photo'])) ?>">Open uploaded photo</a>
          <div>
            <img src="<?= e(url((string) $row['national_id_photo'])) ?>" alt="National ID upload" style="max-width: 100%; max-height: 340px; border-radius: 8px; border: 1px solid #e5e5e5;">
          </div>
        <?php else: ?>
          <p class="mb-0 text-muted">No file uploaded yet.</p>
        <?php endif; ?>
      </div>
      <div class="col-12">
        <form method="post" class="row g-2 align-items-end">
          <?= csrf_field() ?>
          <div class="col-md-8">
            <label class="form-label">Review note (optional)</label>
            <input type="text" class="form-control" name="review_note" maxlength="255" placeholder="Reason, instruction, or approval note">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-success w-100" type="submit" name="action" value="approve_id">Approve ID</button>
            <button class="btn btn-danger w-100" type="submit" name="action" value="reject_id">Reject ID</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
