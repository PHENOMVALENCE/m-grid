<?php

declare(strict_types=1);

require __DIR__ . '/includes/init_admin.php';

$mgrid_page_title = mgrid_title('title.admin_platform_settings');
require __DIR__ . '/includes/shell_open.php';
?>

<div class="mgrid-grid-2 mgrid-page-section">
  <div class="mgrid-card">
    <div class="mgrid-card-header">
      <h1 class="mgrid-card-title mb-0"><i class="ti ti-settings"></i> Platform settings</h1>
    </div>
    <div class="mgrid-card-body">
      <p class="mb-3" style="color: var(--mgrid-ink-500);">Core platform controls and governance settings will be managed here.</p>
      <ul class="mb-0" style="color: var(--mgrid-ink-700);">
        <li>Branding and communication defaults</li>
        <li>Language and localization defaults</li>
        <li>Security and access control policies</li>
      </ul>
    </div>
  </div>

  <div class="mgrid-card">
    <div class="mgrid-card-header">
      <h2 class="mgrid-card-title mb-0"><i class="ti ti-link"></i> Quick admin tools</h2>
    </div>
    <div class="mgrid-card-body">
      <div class="mgrid-grid-2">
        <a class="mgrid-quick-link" href="<?= e(url('admin/admin_accounts.php')) ?>"><i class="ti ti-user-star"></i><span>Administration team</span></a>
        <a class="mgrid-quick-link" href="<?= e(url('admin/admin_analytics.php')) ?>"><i class="ti ti-chart-line"></i><span>Analytics</span></a>
        <a class="mgrid-quick-link" href="<?= e(url('admin/admin_reports.php')) ?>"><i class="ti ti-file-analytics"></i><span>Reports</span></a>
        <a class="mgrid-quick-link" href="<?= e(url('admin/admin_announcements.php')) ?>"><i class="ti ti-bell-ringing"></i><span>Announcements</span></a>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/shell_close.php';
