<?php

declare(strict_types=1);

/** Public marketing navigation (included from header when layout = public). */
$navPremium = !empty($mgrid_navbar_premium);
$vanilla = !empty($mgrid_public_vanilla);

if ($vanilla) {
    ?>
<nav
  class="mgrid-nav-vanilla<?= $navPremium ? ' mgrid-nav-vanilla--premium' : '' ?>"
  data-mgrid-navbar="1"
  <?= $navPremium ? 'data-mgrid-navbar-premium="1"' : '' ?>
>
  <div class="mgrid-container mgrid-nav-vanilla__inner">
    <a class="mgrid-nav-vanilla__brand" href="<?= e(url('index.php')) ?>">
      <span class="mgrid-brand-mark" aria-hidden="true">
        <img src="<?= e(asset('images/logos/logo.png')) ?>" alt="" />
      </span>
      <span class="mgrid-brand-lockup">
        <?php if ($navPremium): ?>
          <span class="mgrid-brand-name" data-i18n="nav.brand_mgrid">M GRID</span>
          <span class="mgrid-brand-tagline mgrid-brand-tagline--hide-xs" data-i18n="nav.brand_sub">Malkia Grid</span>
        <?php else: ?>
          <span class="mgrid-brand-name">M GRID</span>
          <span class="mgrid-brand-tagline mgrid-brand-tagline--hide-xs" data-i18n="nav.tagline">Women&apos;s economic network</span>
        <?php endif; ?>
      </span>
    </a>
    <button
      class="mgrid-nav-vanilla__toggle"
      type="button"
      data-mgrid-nav-toggle="1"
      aria-controls="mgridNavPanel"
      aria-expanded="false"
      aria-label="Toggle navigation"
    >
      <span></span><span></span><span></span>
    </button>
    <div class="mgrid-nav-vanilla__panel" id="mgridNavPanel">
      <ul class="mgrid-nav-vanilla__links">
        <li><a class="mgrid-nav-vanilla__link" href="#about" data-i18n="nav.about">About</a></li>
        <li><a class="mgrid-nav-vanilla__link" href="#how" data-i18n="nav.how">How it works</a></li>
        <li><a class="mgrid-nav-vanilla__link" href="#features">Features</a></li>
        <li><a class="mgrid-nav-vanilla__link" href="#benefits" data-i18n="nav.benefits">Benefits</a></li>
        <li><a class="mgrid-nav-vanilla__link" href="#partners" data-i18n="nav.partners">Partners</a></li>
        <li><a class="mgrid-nav-vanilla__link" href="#faq" data-i18n="nav.faq">FAQ</a></li>
      </ul>
      <div class="mgrid-nav-vanilla__row">
        <?php require __DIR__ . '/lang_toggle.php'; ?>
        <?php $u = auth_actor(); ?>
        <?php if ($u === null): ?>
          <a class="mgrid-nav-vanilla__link" href="<?= e(url('login.php')) ?>" data-i18n="nav.sign_in">Sign in</a>
          <a class="mgrid-nav-vanilla__btn mgrid-nav-vanilla__btn--primary" href="<?= e(url('register.php')) ?>" data-i18n="nav.register_mid">Register for M-ID</a>
        <?php else: ?>
          <span class="mgrid-text-small mgrid-nav-session-name"><?= e($u['full_name']) ?></span>
          <a class="mgrid-nav-vanilla__btn mgrid-nav-vanilla__btn--outline" href="<?= e(($u['account_type'] ?? 'user') === 'admin' ? url('admin/dashboard.php') : url('user/dashboard.php')) ?>" data-i18n="nav.dashboard">Dashboard</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
    <?php
    return;
}
?>
<nav class="navbar navbar-expand-lg mgrid-navbar<?= $navPremium ? ' mgrid-navbar--premium fixed-top' : ' sticky-top' ?>" data-bs-theme="light" data-mgrid-navbar<?= $navPremium ? ' data-mgrid-navbar-premium="1"' : '' ?>>
  <div class="container py-2">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>">
      <span class="d-inline-flex align-items-center justify-content-center mgrid-brand-mark" aria-hidden="true">
        <img src="<?= e(asset('images/logos/logo.png')) ?>" alt="" />
      </span>
      <span class="mgrid-brand-lockup">
        <?php if ($navPremium): ?>
          <span class="mgrid-brand-name" data-i18n="nav.brand_mgrid">M GRID</span>
          <span class="mgrid-brand-tagline d-none d-sm-block" data-i18n="nav.brand_sub">Malkia Grid</span>
        <?php else: ?>
          <span class="mgrid-brand-name">M GRID</span>
          <span class="mgrid-brand-tagline d-none d-sm-block" data-i18n="nav.tagline">Women&apos;s economic network</span>
        <?php endif; ?>
      </span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mgridNav"
      aria-controls="mgridNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mgridNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link" href="#about" data-i18n="nav.about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="#how" data-i18n="nav.how">How it works</a></li>
        <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="#benefits" data-i18n="nav.benefits">Benefits</a></li>
        <li class="nav-item"><a class="nav-link" href="#partners" data-i18n="nav.partners">Partners</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq" data-i18n="nav.faq">FAQ</a></li>
        <li class="nav-item d-flex align-items-center py-2 py-lg-0 me-lg-1">
          <?php require __DIR__ . '/lang_toggle.php'; ?>
        </li>
        <?php $u = auth_actor(); ?>
        <?php if ($u === null): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>" data-i18n="nav.sign_in">Sign in</a></li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-primary px-4" href="<?= e(url('register.php')) ?>" data-i18n="nav.register_mid">Register for M-ID</a>
          </li>
        <?php else: ?>
          <li class="nav-item d-none d-md-flex align-items-center"><span class="small mgrid-nav-session-name"><?= e($u['full_name']) ?></span></li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-dark" href="<?= e(($u['account_type'] ?? 'user') === 'admin' ? url('admin/dashboard.php') : url('user/dashboard.php')) ?>" data-i18n="nav.dashboard">Dashboard</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
