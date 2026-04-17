<?php

declare(strict_types=1);

/** Public marketing navigation (included from header when layout = public). */
?>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
  <div class="container py-2">
    <a class="navbar-brand fw-bold text-dark d-flex align-items-center gap-2" href="<?= e(url('index.php')) ?>">
      <span class="rounded-circle d-inline-flex align-items-center justify-content-center mgrid-gold bg-dark"
        style="width:40px;height:40px;font-size:0.85rem;">M</span>
      <span>M-GRID</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mgridNav"
      aria-controls="mgridNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="mgridNav">
      <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link" href="#about">About</a></li>
        <li class="nav-item"><a class="nav-link" href="#how">How it works</a></li>
        <li class="nav-item"><a class="nav-link" href="#benefits">Benefits</a></li>
        <li class="nav-item"><a class="nav-link" href="#partners">Partners</a></li>
        <li class="nav-item"><a class="nav-link" href="#faq">FAQ</a></li>
        <?php $u = auth_actor(); ?>
        <?php if ($u === null): ?>
          <li class="nav-item"><a class="nav-link" href="<?= e(url('login.php')) ?>">Sign in</a></li>
          <li class="nav-item ms-lg-2">
            <a class="btn mgrid-btn-gold px-4" href="<?= e(url('register.php')) ?>">Get Your M-ID</a>
          </li>
        <?php else: ?>
          <li class="nav-item"><span class="nav-link small text-muted"><?= e($u['full_name']) ?></span></li>
          <li class="nav-item ms-lg-2">
            <a class="btn btn-outline-dark" href="<?= e(($u['account_type'] ?? 'user') === 'admin' ? url('admin/dashboard.php') : url('user/dashboard.php')) ?>">My space</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
