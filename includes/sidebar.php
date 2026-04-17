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
        <span class="fw-bold fs-5 text-dark">M-GRID</span>
      </a>
      <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
        <i class="ti ti-x fs-6"></i>
      </div>
    </div>
    <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
      <ul id="sidebarnav" class="mt-3">
        <li class="nav-small-cap">
          <span class="hide-menu text-muted small"><?= $isAdmin ? 'Administration' : 'Your journey' ?></span>
        </li>
        <?php if ($isAdmin): ?>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('admin/dashboard.php')) ?>">
              <i class="ti ti-layout-dashboard"></i>
              <span class="hide-menu">Dashboard</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('admin/users.php')) ?>">
              <i class="ti ti-users"></i>
              <span class="hide-menu">Members</span>
            </a>
          </li>
        <?php else: ?>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('user/dashboard.php')) ?>">
              <i class="ti ti-smart-home"></i>
              <span class="hide-menu">Dashboard</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('user/profile.php')) ?>">
              <i class="ti ti-id"></i>
              <span class="hide-menu">M-Profile</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('user/settings.php')) ?>">
              <i class="ti ti-settings"></i>
              <span class="hide-menu">Settings</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link" href="<?= e(url('user/verify-id.php')) ?>">
              <i class="ti ti-shield-check"></i>
              <span class="hide-menu">ID Verification</span>
            </a>
          </li>
          <li class="nav-small-cap mt-4">
            <span class="hide-menu text-muted small">Coming soon</span>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link opacity-50" href="javascript:void(0)" onclick="return false;">
              <i class="ti ti-file-certificate"></i>
              <span class="hide-menu">Documents</span>
            </a>
          </li>
          <li class="sidebar-item">
            <a class="sidebar-link opacity-50" href="javascript:void(0)" onclick="return false;">
              <i class="ti ti-heart-handshake"></i>
              <span class="hide-menu">M-Benefits</span>
            </a>
          </li>
        <?php endif; ?>
        <li class="sidebar-item mt-4">
          <a class="sidebar-link text-danger" href="<?= e(url('logout.php')) ?>">
            <i class="ti ti-logout"></i>
            <span class="hide-menu">Logout</span>
          </a>
        </li>
      </ul>
    </nav>
  </div>
</aside>
