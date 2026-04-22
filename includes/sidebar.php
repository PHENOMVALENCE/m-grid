<?php

declare(strict_types=1);

/**
 * Dashboard sidebar — context: "user" or "admin" via $mgrid_sidebar_context.
 */
$ctx = $mgrid_sidebar_context ?? 'user';
$isAdmin = $ctx === 'admin';
$current = basename((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
$actor = auth_actor();
$initial = strtoupper(substr((string) ($actor['full_name'] ?? 'M'), 0, 1));
$actorId = (string) ($actor['m_id'] ?? $actor['admin_code'] ?? '');

$isActive = static function (array $files) use ($current): bool {
    return in_array($current, $files, true);
};
?>
<aside class="mgrid-sidebar" id="mgridSidebar">
  <div class="mgrid-sidebar-logo">
    <div class="mgrid-sidebar-logo-mark">
      <img src="<?= e(asset('images/logos/logo.png')) ?>" alt="Malkia Grid logo" />
    </div>
    <a href="<?= e($isAdmin ? url('admin/dashboard.php') : url('user/dashboard.php')) ?>" class="text-decoration-none">
      <div class="mgrid-sidebar-logo-name">M GRID</div>
      <span class="mgrid-sidebar-logo-sub">Women Rising in Power</span>
    </a>
    <button class="btn btn-sm text-white d-lg-none ms-auto" id="mgridSidebarClose" type="button" aria-label="Close sidebar">
      <i class="ti ti-x"></i>
    </button>
  </div>
  <nav class="mgrid-sidebar-nav">
    <?php if ($isAdmin): ?>
      <div class="mgrid-nav-section-label">Dashboard</div>
      <a class="mgrid-nav-link <?= $isActive(['dashboard.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/dashboard.php')) ?>">
        <i class="ti ti-layout-dashboard"></i><span data-i18n="sidebar.dashboard">Overview</span>
      </a>
      <div class="mgrid-nav-section-label">Members</div>
      <a class="mgrid-nav-link <?= $isActive(['users.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/users.php')) ?>">
        <i class="ti ti-users"></i><span data-i18n="sidebar.members">All Members</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['admin_mscores.php','admin_mscore_detail.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/admin_mscores.php')) ?>">
        <i class="ti ti-chart-dots-3"></i><span>M-SCORE Monitoring</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['admin_documents.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/admin_documents.php')) ?>">
        <i class="ti ti-file-certificate"></i><span>Document Verification</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['pending-verification.php']) ? 'is-active' : '' ?>" href="javascript:void(0)" onclick="return false;">
        <i class="ti ti-shield-check"></i><span>Pending Verification</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['score-management.php']) ? 'is-active' : '' ?>" href="javascript:void(0)" onclick="return false;">
        <i class="ti ti-chart-arcs"></i><span>M-Score Management</span>
      </a>
      <div class="mgrid-nav-section-label">Platform</div>
      <a class="mgrid-nav-link" href="javascript:void(0)" onclick="return false;">
        <i class="ti ti-handshake"></i><span>Partners</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['admin_funding_applications.php','admin_funding_review.php','manage_repayments.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/admin_funding_applications.php')) ?>">
        <i class="ti ti-cash-banknote"></i><span>Loan Applications</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['admin_benefits.php','add_benefit.php','edit_benefit.php','admin_benefit_claims.php','manage_benefit_categories.php','manage_benefit_providers.php','update_benefit_claim_status.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/admin_benefits.php')) ?>">
        <i class="ti ti-gift"></i><span>M-Benefits</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['admin_opportunities.php','add_opportunity.php','edit_opportunity.php','admin_applications.php','manage_opportunity_categories.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/admin_opportunities.php')) ?>">
        <i class="ti ti-briefcase"></i><span>Opportunities</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['admin_trainings.php','add_training.php','edit_training.php','admin_training_registrations.php','update_training_completion.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/admin_trainings.php')) ?>">
        <i class="ti ti-school"></i><span>Trainings</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['admin_announcements.php','create_announcement.php','view_announcement.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/admin_announcements.php')) ?>">
        <i class="ti ti-bell-ringing"></i><span>Announcements</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['admin_analytics.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/admin_analytics.php')) ?>">
        <i class="ti ti-chart-line"></i><span>Analytics</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['admin_reports.php','export_report.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/admin_reports.php')) ?>">
        <i class="ti ti-file-analytics"></i><span>Reports</span>
      </a>
      <div class="mgrid-nav-section-label">System</div>
      <a class="mgrid-nav-link <?= $isActive(['admin_accounts.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/admin_accounts.php')) ?>">
        <i class="ti ti-user-star"></i><span>Admin Accounts</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['platform_settings.php']) ? 'is-active' : '' ?>" href="<?= e(url('admin/platform_settings.php')) ?>">
        <i class="ti ti-settings"></i><span>Settings</span>
      </a>
    <?php else: ?>
      <div class="mgrid-nav-section-label" data-i18n="sidebar.section_overview">Overview</div>
      <a class="mgrid-nav-link <?= $isActive(['dashboard.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/dashboard.php')) ?>">
        <i class="ti ti-smart-home"></i><span data-i18n="sidebar.dashboard">Dashboard</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['profile.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/profile.php')) ?>">
        <i class="ti ti-id"></i><span data-i18n="sidebar.m_profile">M-Profile</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['my_mscore.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/my_mscore.php')) ?>">
        <i class="ti ti-chart-arcs"></i><span data-i18n="sidebar.m_score">M-SCORE</span>
      </a>
      <div class="mgrid-nav-section-label" data-i18n="sidebar.section_identity">Identity</div>
      <a class="mgrid-nav-link <?= $isActive(['verify-id.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/verify-id.php')) ?>">
        <i class="ti ti-id-badge-2"></i><span data-i18n="sidebar.id_verification">ID Verification</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['my_documents.php','upload_document.php','reupload_document.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/my_documents.php')) ?>">
        <i class="ti ti-file-certificate"></i><span data-i18n="sidebar.documents">Documents</span>
      </a>
      <div class="mgrid-nav-section-label" data-i18n="sidebar.section_opportunities">Opportunities</div>
      <a class="mgrid-nav-link <?= $isActive(['opportunities.php','opportunity_detail.php','apply_opportunity.php','my_opportunities.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/opportunities.php')) ?>">
        <i class="ti ti-briefcase"></i><span data-i18n="sidebar.opportunities_link">Opportunities</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['trainings.php','training_detail.php','register_training.php','my_trainings.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/trainings.php')) ?>">
        <i class="ti ti-school"></i><span data-i18n="sidebar.trainings">Trainings</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['funding_overview.php','apply_funding.php','my_funding_applications.php','funding_application_detail.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/funding_overview.php')) ?>">
        <i class="ti ti-cash-banknote"></i><span data-i18n="sidebar.m_fund">M-Fund (Loans)</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['benefits.php','benefit_detail.php','claim_benefit.php','my_benefits.php','benefit_claim_detail.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/benefits.php')) ?>">
        <i class="ti ti-gift"></i><span data-i18n="sidebar.m_benefits">M-Benefits</span>
      </a>
      <a class="mgrid-nav-link" href="javascript:void(0)" onclick="return false;">
        <i class="ti ti-handshake"></i><span data-i18n="sidebar.m_partners">M-Partners</span>
      </a>
      <div class="mgrid-nav-section-label" data-i18n="sidebar.section_account">Account</div>
      <a class="mgrid-nav-link <?= $isActive(['notifications.php','mark_notification_read.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/notifications.php')) ?>">
        <i class="ti ti-bell"></i><span data-i18n="sidebar.notifications">Notifications</span>
      </a>
      <a class="mgrid-nav-link <?= $isActive(['settings.php']) ? 'is-active' : '' ?>" href="<?= e(url('user/settings.php')) ?>">
        <i class="ti ti-settings"></i><span data-i18n="sidebar.settings">Settings</span>
      </a>
    <?php endif; ?>
    <a class="mgrid-nav-link" href="<?= e(url('logout.php')) ?>">
      <i class="ti ti-logout"></i><span data-i18n="sidebar.logout">Sign out</span>
    </a>
  </nav>
  <div class="mgrid-sidebar-user">
    <div class="mgrid-sidebar-avatar"><?= e($initial) ?></div>
    <div>
      <div class="mgrid-sidebar-user-name"><?= e((string) ($actor['full_name'] ?? 'Member')) ?></div>
      <div class="mgrid-sidebar-user-mid"><?= e($actorId) ?></div>
    </div>
  </div>
</aside>
