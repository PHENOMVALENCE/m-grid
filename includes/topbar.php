<?php

declare(strict_types=1);

$u = auth_actor();
if ($u === null) {
    return;
}
?>
<header class="app-header">
  <nav class="mgrid-topbar">
    <button type="button" class="btn btn-mgrid btn-mgrid-ghost d-lg-none" id="mgridSidebarToggle" aria-label="Toggle sidebar">
      <i class="ti ti-menu-2"></i>
    </button>
    <div class="mgrid-topbar-breadcrumb">
      <span data-i18n="topbar.signed_in_as" class="d-none">Signed in as</span>
      <span data-i18n="<?= ($mgrid_sidebar_context ?? 'user') === 'admin' ? 'topbar.role_admin' : 'topbar.role_member' ?>"><?= ($mgrid_sidebar_context ?? 'user') === 'admin' ? 'Admin' : 'Member' ?></span>
      <i class="ti ti-chevron-right"></i>
      <span class="mgrid-topbar-breadcrumb-current"><?= e((string) ($mgrid_page_title ?? (function_exists('__') ? __('topbar.fallback_page') : 'Home'))) ?></span>
    </div>
    <div class="mgrid-topbar-actions">
      <?php if (($mgrid_sidebar_context ?? '') === 'user' && auth_user() !== null): ?>
        <?php require __DIR__ . '/../user/notification_dropdown_include.php'; ?>
      <?php endif; ?>
      <?php require __DIR__ . '/lang_toggle.php'; ?>
      <div class="mgrid-sidebar-avatar"><?= e(strtoupper(substr($u['full_name'], 0, 1))) ?></div>
    </div>
  </nav>
</header>
