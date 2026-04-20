<?php

declare(strict_types=1);

/**
 * Dashboard sidebar — context: "user" or "admin" via $mgrid_sidebar_context.
 */
$ctx = $mgrid_sidebar_context ?? 'user';
$isAdmin = $ctx === 'admin';
?>
<aside class="left-sidebar">
  <div>
    <div class="brand-logo d-flex align-items-center justify-content-between">
      <a href="<?= e($isAdmin ? url('admin/dashboard.php') : url('user/dashboard.php')) ?>" class="text-nowrap logo-img">
        <span class="mgrid-sidebar-brand">Malkia Grid</span>
      </a>
      <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
        <i class="ti ti-x fs-6"></i>
      </div>
    </div>
    <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
      <ul id="sidebarnav" class="mt-3">
        <li class="nav-small-cap">
          <?php if ($isAdmin): ?>
            <span class="hide-menu text-muted small" data-i18n="sidebar.administration">Administration</span>
          <?php else: ?>
            <span class="hide-menu text-muted small" data-i18n="sidebar.your_journey">Your journey</span>
          <?php endif; ?>
        </li>
        <?php if ($isAdmin): ?>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('admin/dashboard.php')) ?>">
              <i class="ti ti-layout-dashboard"></i>
              <span class="hide-menu" data-i18n="sidebar.dashboard">Dashboard</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('admin/users.php')) ?>">
              <i class="ti ti-users"></i>
              <span class="hide-menu" data-i18n="sidebar.members">Members</span>
            </a>
          </li>
        <?php else: ?>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('user/dashboard.php')) ?>">
              <i class="ti ti-smart-home"></i>
              <span class="hide-menu" data-i18n="sidebar.dashboard">Dashboard</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('user/profile.php')) ?>">
              <i class="ti ti-id"></i>
              <span class="hide-menu" data-i18n="sidebar.m_profile">M-Profile</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('user/settings.php')) ?>">
              <i class="ti ti-settings"></i>
              <span class="hide-menu" data-i18n="sidebar.settings">Settings</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('user/verify-id.php')) ?>">
              <i class="ti ti-shield-check"></i>
              <span class="hide-menu" data-i18n="sidebar.id_verification">ID Verification</span>
            </a>
          </li>
          <li class="nav-small-cap mt-4">
            <span class="hide-menu text-muted small" data-i18n="sidebar.coming_soon">Coming soon</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link opacity-50" href="javascript:void(0)" onclick="return false;">
              <i class="ti ti-file-certificate"></i>
              <span class="hide-menu" data-i18n="sidebar.documents">Documents</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link opacity-50" href="javascript:void(0)" onclick="return false;">
              <i class="ti ti-heart-handshake"></i>
              <span class="hide-menu" data-i18n="sidebar.m_benefits">M-Benefits</span>
            </a>
          </li>
        <?php endif; ?>
        <li class="sidebar-item mt-4">
          <a class="sidebar-link text-danger" href="<?= e(url('logout.php')) ?>">
            <i class="ti ti-logout"></i>
            <span class="hide-menu" data-i18n="sidebar.logout">Logout</span>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
