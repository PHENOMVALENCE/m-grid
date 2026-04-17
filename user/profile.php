<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_member.php';

$pdo = db();
$uid = (int) auth_user()['user_id'];
$stmt = $pdo->prepare('
    SELECT u.*, p.region, p.date_of_birth, p.age_range, p.business_status, p.bio, p.profile_completion
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    WHERE u.id = :id
    LIMIT 1
');
$stmt->execute(['id' => $uid]);
$row = $stmt->fetch() ?: [];

$mgrid_page_title = 'M-Profile — M-GRID';
require __DIR__ . '/includes/shell_open.php';
?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <h1 class="h4 fw-bold mb-4">M-Profile</h1>
    <div class="row g-4">
      <div class="col-md-6">
        <p class="text-muted small mb-1">M-ID</p>
        <p class="fw-bold fs-5"><?= e((string) ($row['m_id'] ?? '')) ?></p>
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
    <p class="small text-muted mt-4 mb-0">Profile editing will connect here; for now, contact support for corrections to verified fields.</p>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
