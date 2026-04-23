<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';
$pdo = db();

$statuses = ['submitted', 'under_review', 'shortlisted', 'accepted', 'rejected', 'withdrawn'];
$compStatuses = ['n_a', 'in_progress', 'completed', 'cancelled'];
$certStatuses = ['n_a', 'none', 'issued', 'verified'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf'] ?? null;
    if (!csrf_verify(is_string($token) ? $token : null)) {
        flash_set('error', __('settings.error.token'));
        redirect('admin/admin_applications.php');
    }
    $aid = (int) ($_POST['application_id'] ?? 0);
    $newSt = clean_string($_POST['new_status'] ?? '');
    $comp = clean_string($_POST['completion_status'] ?? 'n_a');
    $cert = clean_string($_POST['certificate_status'] ?? 'n_a');
    $notes = clean_string($_POST['admin_notes'] ?? '');

    if ($aid > 0 && opportunities_module_ready($pdo) && in_array($newSt, $statuses, true)) {
        if (!in_array($comp, $compStatuses, true)) {
            $comp = 'n_a';
        }
        if (!in_array($cert, $certStatuses, true)) {
            $cert = 'n_a';
        }
        $pdo->prepare('
            UPDATE opportunity_applications SET
              status = :st, completion_status = :cs, certificate_status = :cert, admin_notes = :n
            WHERE id = :id LIMIT 1
        ')->execute([
            'st' => $newSt,
            'cs' => $comp,
            'cert' => $cert,
            'n' => $notes !== '' ? $notes : null,
            'id' => $aid,
        ]);
        flash_set('success', __('opp.admin.application_updated'));
    }
    redirect('admin/admin_applications.php');
}

$ready = opportunities_module_ready($pdo);
$sf = clean_string($_GET['status'] ?? '');
$q = clean_string($_GET['q'] ?? '');

$rows = [];
if ($ready) {
    $where = ['1=1'];
    $params = [];
    if ($sf !== '' && in_array($sf, $statuses, true)) {
        $where[] = 'a.status = :st';
        $params['st'] = $sf;
    }
    if ($q !== '') {
        $where[] = '(u.full_name LIKE :q OR u.m_id LIKE :q2 OR o.title LIKE :q3)';
        $like = '%' . $q . '%';
        $params['q'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
    }
    $sql = '
        SELECT a.*, u.full_name, u.m_id, o.title AS opp_title, o.opportunity_type
        FROM opportunity_applications a
        INNER JOIN users u ON u.id = a.user_id
        INNER JOIN opportunities o ON o.id = a.opportunity_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY a.applied_at DESC
        LIMIT 200
    ';
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll() ?: [];
}

$mgrid_page_title = mgrid_title('title.admin_applications');
require __DIR__ . '/includes/shell_open.php';
?>

<?php if ($msg = flash_get('success')): ?><div class="mgrid-alert mgrid-alert-success"><?= e($msg) ?></div><?php endif; ?>
<?php if ($msg = flash_get('error')): ?><div class="mgrid-alert mgrid-alert-danger"><?= e($msg) ?></div><?php endif; ?>

<div class="mgrid-card mb-3">
  <div class="mgrid-card-body d-flex flex-wrap justify-content-between gap-2">
    <h1 class="mgrid-card-title mb-0"><i class="ti ti-file-text"></i> Opportunity applications</h1>
    <a class="btn-mgrid btn-mgrid-outline btn-sm" href="<?= e(url('admin/admin_opportunities.php')) ?>">Listings</a>
  </div>
</div>

<?php if (!$ready): ?>
  <div class="mgrid-alert mgrid-alert-danger">Schema missing.</div>
<?php else: ?>
  <form method="get" class="mgrid-card mb-3 p-3 row g-2">
    <div class="col-md-3">
      <select name="status" class="mgrid-form-control">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $s): ?>
          <option value="<?= e($s) ?>" <?= $sf === $s ? 'selected' : '' ?>><?= e(opportunity_application_status_label($s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6"><input type="search" name="q" class="mgrid-form-control" placeholder="Name, M-ID, title" value="<?= e($q) ?>"></div>
    <div class="col-md-2 d-grid"><button class="btn-mgrid btn-mgrid-primary">Filter</button></div>
  </form>

  <div class="mgrid-card">
    <div class="mgrid-card-body p-0">
      <div class="table-responsive">
        <table class="mgrid-table mb-0">
          <thead><tr><th>Member</th><th>Opportunity</th><th>Applied</th><th>Status</th><th>Update</th></tr></thead>
          <tbody>
            <?php if ($rows === []): ?><tr><td colspan="5" class="text-center p-4 text-muted">No rows.</td></tr><?php endif; ?>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td><?= e((string) $r['full_name']) ?><div class="small text-muted"><?= e((string) $r['m_id']) ?></div></td>
                <td><?= e((string) $r['opp_title']) ?><div class="small text-muted"><?= e((string) $r['opportunity_type']) ?></div></td>
                <td class="small"><?= e(substr((string) $r['applied_at'], 0, 16)) ?></td>
                <td><span class="badge text-bg-<?= e(opportunity_application_status_badge((string) $r['status'])) ?>"><?= e(opportunity_application_status_label((string) $r['status'])) ?></span></td>
                <td style="min-width:280px;">
                  <form method="post" class="d-flex flex-column gap-1">
                    <?= csrf_field() ?>
                    <input type="hidden" name="application_id" value="<?= (int) $r['id'] ?>">
                    <select name="new_status" class="mgrid-form-control form-control-sm"><?php foreach ($statuses as $s): ?>
                      <option value="<?= e($s) ?>" <?= ((string) $r['status']) === $s ? 'selected' : '' ?>><?= e(opportunity_application_status_label($s)) ?></option>
                    <?php endforeach; ?></select>
                    <select name="completion_status" class="mgrid-form-control form-control-sm"><?php foreach ($compStatuses as $s): ?>
                      <option value="<?= e($s) ?>" <?= ((string) $r['completion_status']) === $s ? 'selected' : '' ?>><?= e(opportunity_completion_status_label($s)) ?></option>
                    <?php endforeach; ?></select>
                    <select name="certificate_status" class="mgrid-form-control form-control-sm"><?php foreach ($certStatuses as $s): ?>
                      <option value="<?= e($s) ?>" <?= ((string) $r['certificate_status']) === $s ? 'selected' : '' ?>><?= e(opportunity_certificate_status_label($s)) ?></option>
                    <?php endforeach; ?></select>
                    <input type="text" name="admin_notes" class="mgrid-form-control form-control-sm" placeholder="Admin notes" value="<?= e((string) ($r['admin_notes'] ?? '')) ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Save</button>
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
