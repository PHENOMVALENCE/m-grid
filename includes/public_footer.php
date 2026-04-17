<?php

declare(strict_types=1);
?>
<footer class="mgrid-footer pt-5 pb-4 mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="fw-bold fs-4 mb-2">M-GRID</div>
        <p class="small mb-0 opacity-75">
          A digital home for women’s economic dignity — M-ID, M-Profile, and future pathways to opportunity.
        </p>
      </div>
      <div class="col-md-4">
        <div class="fw-semibold mb-2">Malkia wa Nguvu / Clouds Media Group</div>
        <p class="small opacity-75 mb-0">Building trust, visibility, and access with care and credibility.</p>
      </div>
      <div class="col-md-4">
        <div class="fw-semibold mb-2">Quick links</div>
        <ul class="list-unstyled small">
          <li><a href="<?= e(url('register.php')) ?>">Create your M-ID</a></li>
          <li><a href="<?= e(url('login.php')) ?>">Member sign in</a></li>
          <li><a href="#faq">FAQ</a></li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary opacity-25 my-4">
    <p class="small text-center mb-0 opacity-75">&copy; <?= (int) date('Y') ?> M-GRID. All rights reserved.</p>
  </div>
</footer>
