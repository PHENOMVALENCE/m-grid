<?php

declare(strict_types=1);

$u = auth_actor();
if ($u === null) {
    return;
}
?>
<header class="app-header">
  <nav class="navbar navbar-expand-lg navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item d-block d-xl-none">
        <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
          <i class="ti ti-menu-2"></i>
        </a>
      </li>
      <li class="nav-item d-none d-md-flex align-items-center ms-2">
        <span class="text-muted small">Signed in as</span>
        <span class="fw-semibold ms-2"><?= e($u['full_name']) ?></span>
      </li>
    </ul>
    <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
      <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end gap-2">
        <li class="nav-item d-none d-lg-block">
          <span class="badge rounded-pill mgrid-badge-soft px-3 py-2"><?= e((string) ($u['m_id'] ?? $u['admin_code'] ?? '')) ?></span>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link" href="javascript:void(0)" id="mgridUserMenu" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="d-flex align-items-center justify-content-center rounded-circle bg-dark text-white fw-bold"
              style="width:38px;height:38px;font-size:0.9rem;">
              <?= e(strtoupper(substr($u['full_name'], 0, 1))) ?>
            </span>
          </a>
          <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="mgridUserMenu">
            <div class="message-body">
              <?php if (($mgrid_sidebar_context ?? '') === 'user'): ?>
                <a class="d-flex align-items-center gap-2 dropdown-item" href="<?= e(url('user/profile.php')) ?>">
                  <i class="ti ti-user fs-6"></i>
                  <span class="fs-3">M-Profile</span>
                </a>
                <a class="d-flex align-items-center gap-2 dropdown-item" href="<?= e(url('user/settings.php')) ?>">
                  <i class="ti ti-settings fs-6"></i>
                  <span class="fs-3">Settings</span>
                </a>
              <?php else: ?>
                <a class="d-flex align-items-center gap-2 dropdown-item" href="<?= e(url('admin/users.php')) ?>">
                  <i class="ti ti-users fs-6"></i>
                  <span class="fs-3">Members</span>
                </a>
              <?php endif; ?>
              <a class="btn btn-outline-primary mx-3 mt-2 d-block" href="<?= e(url('logout.php')) ?>">Logout</a>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </nav>
</header>
