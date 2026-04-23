<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$pdo = db();
$q = clean_string($_GET['q'] ?? '');
$sql = '
    SELECT u.id, u.m_id, u.full_name, u.email, u.phone, u.status, u.created_at,
           p.national_id_status
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.id
    WHERE 1 = 1
';
$params = [];
if ($q !== '') {
    $sql .= ' AND (full_name LIKE :q OR email LIKE :q2 OR phone LIKE :q3 OR m_id LIKE :q4)';
    $like = '%' . $q . '%';
    $params = ['q' => $like, 'q2' => $like, 'q3' => $like, 'q4' => $like];
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';
$st = $pdo->prepare($sql);
$st->execute($params);
$users = $st->fetchAll();

$mgrid_page_title = mgrid_title('title.admin_users');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="card border-0 shadow-sm">
  <div class="card-body p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
      <h1 class="h4 mgrid-dash-page-title mb-0">Member directory</h1>
      <form class="d-flex gap-2" method="get" action="">
        <input type="search" class="form-control" name="q" placeholder="Search name, email, phone, M-ID" value="<?= e($q) ?>" style="min-width:220px;">
        <button class="btn btn-primary" type="submit">Search</button>
      </form>
    </div>
    <div class="table-responsive">
      <table class="table table-striped align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>M-ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Status</th>
            <th>ID Check</th>
            <th>Joined</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $r): ?>
            <tr>
              <td class="fw-semibold"><?= e((string) $r['m_id']) ?></td>
              <td><?= e((string) $r['full_name']) ?></td>
              <td class="small"><?= e((string) $r['email']) ?></td>
              <td class="small"><?= e((string) $r['phone']) ?></td>
              <td><span class="badge bg-light text-dark border"><?= e((string) $r['status']) ?></span></td>
              <td><span class="badge bg-light text-dark border"><?= e((string) ($r['national_id_status'] ?? 'not_submitted')) ?></span></td>
              <td class="small text-muted"><?= e(substr((string) $r['created_at'], 0, 10)) ?></td>
              <td><a class="btn btn-sm btn-outline-primary" href="<?= e(url('admin/user-view.php?id=' . (int) $r['id'])) ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
