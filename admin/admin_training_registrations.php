<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

$ready = trainings_module_ready($pdo);
$sf = clean_string($_GET['status'] ?? '');
$q = clean_string($_GET['q'] ?? '');

$regStatuses = ['pending', 'approved', 'rejected', 'waitlisted', 'cancelled'];
$rows = [];
if ($ready) {
    $where = ['1=1'];
    $params = [];
    if ($sf !== '' && in_array($sf, $regStatuses, true)) {
        $where[] = 'r.status = :st';
        $params['st'] = $sf;
    }
    if ($q !== '') {
        $where[] = '(u.full_name LIKE :q OR u.m_id LIKE :q2 OR p.title LIKE :q3)';
        $like = '%' . $q . '%';
        $params['q'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
    }
    $sql = '
        SELECT r.*, u.full_name, u.m_id, p.title AS prog_title
        FROM training_registrations r
        INNER JOIN users u ON u.id = r.user_id
        INNER JOIN training_programs p ON p.id = r.training_program_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY r.applied_at DESC
        LIMIT 200
    ';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll() ?: [];
}

$mgrid_page_title = mgrid_title('title.admin_training_reg');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2">
    <h1 class="mgrid-card-title mb-0"><i class="ti ti-users-group"></i> Training registrations</h1>
    <a class="btn-mgrid btn-mgrid-outline btn-sm" href="<?= e(url('admin/admin_trainings.php')) ?>">Programmes</a>
  </div>
</div>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">Schema missing.</div>
<?php else: ?>
  <form method="get" class="mgrid-card mb-3 p-3 row g-2">
    <div class="col-md-3">
      <select name="status" class="mgrid-form-control">
        <option value="">All application statuses</option>
        <?php foreach ($regStatuses as $s): ?>
          <option value="<?= e($s) ?>" <?= $sf === $s ? 'selected' : '' ?>><?= e(training_registration_status_label($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6"><input type="search" name="q" class="mgrid-form-control" placeholder="Name, M-ID, programme" value="<?= e($q) ?>"></div>
    <div class="col-md-2 d-grid"><button class="btn-mgrid btn-mgrid-primary">Filter</button></div>
  </form>

  <div class="mgrid-card">
    <div class="mgrid-card-body p-0">
      <div class="table-responsive">
        <table class="mgrid-table mb-0">
          <thead>
            <tr>
              <th>Member</th>
              <th>Programme</th>
              <th>Applied</th>
              <th>App status</th>
              <th>Participation</th>
              <th>Certificate</th>
              <th>Update</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($rows === []): ?><tr><td colspan="7" class="text-center p-4 text-muted">No rows.</td></tr><?php endif; ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= e((string) $r['full_name']) ?><div class="small text-muted"><?= e((string) $r['m_id']) ?></div></td>
                <td><?= e((string) $r['prog_title']) ?></td>
                <td class="small"><?= e(substr((string) $r['applied_at'], 0, 16)) ?></td>
                <td><span class="badge text-bg-<?= e(training_registration_status_badge((string) $r['status'])) ?>"><?= e(training_registration_status_label((string) $r['status'])) ?></span></td>
                <td><?= e(training_participation_status_label((string) $r['participation_status'])) ?></td>
                <td><?= e(training_certificate_status_label((string) $r['certificate_status'])) ?></td>
                <td>
                  <form method="post" action="<?= e(url('admin/update_training_completion.php')) ?>" class="d-flex flex-column gap-1" style="min-width:260px;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="registration_id" value="<?= (int) $r['id'] ?>">
                    <label class="small text-muted mb-0">Registration status</label>
                    <select name="status" class="mgrid-form-control form-control-sm"><?php foreach ($regStatuses as $s): ?>
                      <option value="<?= e($s) ?>" <?= ((string) $r['status']) === $s ? 'selected' : '' ?>><?= e(training_registration_status_label($s)) ?></option>
                    <?php endforeach; ?></select>
                    <label class="small text-muted mb-0">Participation</label>
                    <?php $parts = ['registered', 'attended', 'completed', 'no_show', 'excused']; ?>
                    <select name="participation_status" class="mgrid-form-control form-control-sm"><?php foreach ($parts as $s): ?>
                      <option value="<?= e($s) ?>" <?= ((string) $r['participation_status']) === $s ? 'selected' : '' ?>><?= e(training_participation_status_label($s)) ?></option>
                    <?php endforeach; ?></select>
                    <label class="small text-muted mb-0">Certificate</label>
                    <?php $certs = ['none', 'issued', 'pending_verification', 'verified', 'rejected']; ?>
                    <select name="certificate_status" class="mgrid-form-control form-control-sm"><?php foreach ($certs as $s): ?>
                      <option value="<?= e($s) ?>" <?= ((string) $r['certificate_status']) === $s ? 'selected' : '' ?>><?= e(training_certificate_status_label($s)) ?></option>
                    <?php endforeach; ?></select>
                    <label class="small text-muted mb-0">Certificate document ID (optional)</label>
                    <input type="number" name="certificate_document_id" class="mgrid-form-control form-control-sm" placeholder="user_documents.id" value="<?= $r['certificate_document_id'] !== null && $r['certificate_document_id'] !== '' ? (int) $r['certificate_document_id'] : '' ?>">
                    <input type="text" name="admin_notes" class="mgrid-form-control form-control-sm" placeholder="Admin notes" value="<?= e((string) ($r['admin_notes'] ?? '')) ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Save &amp; sync M-SCORE</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/shell_close.php'; ?>
