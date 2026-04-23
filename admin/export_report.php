<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$type = clean_string($_GET['report'] ?? '');
$df = clean_string($_GET['date_from'] ?? '');
$dt = clean_string($_GET['date_to'] ?? '');

$allowed = ['users', 'funding', 'documents', 'mscore', 'training', 'benefits', 'opportunities'];
if (!in_array($type, $allowed, true)) {
    flash_set('error', __('export.invalid_type'));
    redirect('admin/admin_reports.php');
}

exportReportToCsv($type, $df !== '' ? $df : null, $dt !== '' ? $dt : null);
exit;
