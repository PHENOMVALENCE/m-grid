<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$mgrid_page_title = 'Malkia Grid — Your digital identity for opportunity';
$mgrid_layout = 'public';

require __DIR__ . '/includes/header.php';
?>

<main>
  <!-- Hero -->
  <section class="mgrid-hero py-5 py-lg-6">
    <div class="container py-lg-5">
      <div class="row align-items-center g-5">
        <div class="col-lg-7">
          <p class="mgrid-eyebrow mb-3">Malkia wa Nguvu · Clouds Media Group</p>
          <h1 class="display-5 fw-semibold mb-3">Every woman deserves a trusted digital identity.</h1>
          <p class="lead opacity-90 mb-4">
            Malkia Grid is your gateway to <strong>M-ID</strong> and <strong>M-Profile</strong> — a credible foundation for
            future <strong>M-Score</strong> visibility, partnerships, and access to benefits and finance-ready services.
          </p>
          <div class="d-flex flex-wrap gap-3">
            <a class="btn btn-lg btn-primary px-4" href="<?= e(url('register.php')) ?>">Get Your M-ID</a>
            <a class="btn btn-lg btn-outline-light px-4" href="<?= e(url('login.php')) ?>">Member sign in</a>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="mgrid-mid-card--premium mb-3">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="mgrid-mid-card-name">Amina Hassan</div>
                <div class="mgrid-mid-card-id">M-2026-004821</div>
              </div>
              <span class="badge rounded-pill mgrid-badge-tier-gold mgrid-mid-card-tier">Gold</span>
            </div>
            <div class="mgrid-mid-card-score">68</div>
            <div class="mgrid-mid-card-score-label">M-Score</div>
          </div>
          <div class="mgrid-benefits-panel p-4 p-lg-5">
            <h3 class="h5 mgrid-section-title mb-3">What you receive today</h3>
            <ul class="list-unstyled mb-0 small">
              <li class="d-flex gap-2 mb-3"><span class="mgrid-check">✓</span> A unique, permanent <strong>M-ID</strong></li>
              <li class="d-flex gap-2 mb-3"><span class="mgrid-check">✓</span> A private <strong>M-Profile</strong> dashboard</li>
              <li class="d-flex gap-2 mb-3"><span class="mgrid-check">✓</span> A transparent path toward <strong>M-Score</strong> and programs</li>
              <li class="d-flex gap-2"><span class="mgrid-check">✓</span> Designed for <strong>English &amp; Kiswahili</strong> experiences</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- About -->
  <section id="about" class="py-5 py-lg-6">
    <div class="container">
      <div class="row justify-content-center text-center mb-5">
        <div class="col-lg-8">
          <h2 class="mgrid-section-title display-6 mb-3">About Malkia Grid</h2>
          <p class="text-muted lead">
            Malkia Grid is a national-scale platform for women — built with institutional-grade care so that
            identity, credibility, and opportunity can travel together safely.
          </p>
        </div>
      </div>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="card h-100 mgrid-card-soft border-0 p-4">
            <h3 class="h5 fw-bold">Dignity first</h3>
            <p class="text-muted small mb-0">Your story and data are treated with respect. Clear roles, secure sessions, and room to grow.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 mgrid-card-soft border-0 p-4">
            <h3 class="h5 fw-bold">Built for partnerships</h3>
            <p class="text-muted small mb-0">Structured profiles and future verification modules help institutions say “yes” with confidence.</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card h-100 mgrid-card-soft border-0 p-4">
            <h3 class="h5 fw-bold">Designed to scale</h3>
            <p class="text-muted small mb-0">M-Fund, M-Partner, and M-Benefits can plug in when you are ready — without rebuilding your identity.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- How it works -->
  <section id="how" class="py-5 bg-light">
    <div class="container">
      <h2 class="mgrid-section-title text-center display-6 mb-5">How it works</h2>
      <div class="row g-4 text-center">
        <div class="col-md-3">
          <div class="mgrid-step-ring mx-auto mb-3">1</div>
          <h3 class="h6 fw-bold">Register</h3>
          <p class="small text-muted mb-0">Create your account with a few accurate details.</p>
        </div>
        <div class="col-md-3">
          <div class="mgrid-step-ring mx-auto mb-3">2</div>
          <h3 class="h6 fw-bold">Receive M-ID</h3>
          <p class="small text-muted mb-0">Your permanent identifier is issued automatically.</p>
        </div>
        <div class="col-md-3">
          <div class="mgrid-step-ring mx-auto mb-3">3</div>
          <h3 class="h6 fw-bold">Grow M-Profile</h3>
          <p class="small text-muted mb-0">Complete your profile as modules go live.</p>
        </div>
        <div class="col-md-3">
          <div class="mgrid-step-ring mx-auto mb-3">4</div>
          <h3 class="h6 fw-bold">Unlock opportunity</h3>
          <p class="small text-muted mb-0">M-Score, benefits, and partners connect here over time.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Benefits -->
  <section id="benefits" class="py-5 py-lg-6">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <h2 class="mgrid-section-title display-6 mb-3">Benefits &amp; opportunities</h2>
          <p class="text-muted">
            Malkia Grid is phased on purpose: first identity and trust, then credibility scoring, then curated pathways to
            finance, services, and benefits — always with clarity and consent.
          </p>
          <ul class="text-muted">
            <li class="mb-2">A single <strong>M-ID</strong> you can reference across programs.</li>
            <li class="mb-2">A dashboard that grows with <strong>documents, offers, and verification</strong>.</li>
            <li class="mb-2">Future <strong>M-Score</strong> tiering with transparent milestones.</li>
            <li>Language-ready experiences for <strong>English and Kiswahili</strong>.</li>
          </ul>
        </div>
        <div class="col-lg-6">
          <div class="row g-3">
            <div class="col-6">
              <div class="card mgrid-card-soft border-0 h-100 p-3">
                <div class="fw-bold mgrid-gold mb-1">M-Fund</div>
                <p class="small text-muted mb-0">Loan readiness scaffolding (coming).</p>
              </div>
            </div>
            <div class="col-6">
              <div class="card mgrid-card-soft border-0 h-100 p-3">
                <div class="fw-bold mgrid-gold mb-1">M-Partner</div>
                <p class="small text-muted mb-0">Trusted services aligned to your profile.</p>
              </div>
            </div>
            <div class="col-6">
              <div class="card mgrid-card-soft border-0 h-100 p-3">
                <div class="fw-bold mgrid-gold mb-1">M-Benefits</div>
                <p class="small text-muted mb-0">Programs, grants, and learning journeys.</p>
              </div>
            </div>
            <div class="col-6">
              <div class="card mgrid-card-soft border-0 h-100 p-3">
                <div class="fw-bold mgrid-gold mb-1">Verification</div>
                <p class="small text-muted mb-0">Document uploads with review workflows.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Partners preview -->
  <section id="partners" class="py-5 bg-light">
    <div class="container text-center">
      <h2 class="mgrid-section-title display-6 mb-3">Partner preview</h2>
      <p class="text-muted col-lg-8 mx-auto mb-4">
        Institutional, financial, and civic partners meet members through structured profiles — reducing friction and
        increasing fairness. Partner modules will appear here as they go live.
      </p>
      <div class="d-flex flex-wrap justify-content-center gap-3">
        <span class="badge rounded-pill bg-white border px-4 py-3 mgrid-partner-pill">Finance</span>
        <span class="badge rounded-pill bg-white border px-4 py-3 mgrid-partner-pill">Health &amp; wellness</span>
        <span class="badge rounded-pill bg-white border px-4 py-3 mgrid-partner-pill">Skills &amp; enterprise</span>
        <span class="badge rounded-pill bg-white border px-4 py-3 mgrid-partner-pill">Media &amp; visibility</span>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section id="faq" class="py-5 py-lg-6">
    <div class="container">
      <h2 class="mgrid-section-title text-center display-6 mb-4">Frequently asked questions</h2>
      <div class="row justify-content-center">
        <div class="col-lg-8 mgrid-faq">
          <div class="accordion" id="faqAccordion">
            <div class="accordion-item">
              <h3 class="accordion-header" id="q1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#a1">What is an M-ID?</button>
              </h3>
              <div id="a1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted small">
                  M-ID is your unique Malkia Grid identifier (for example <code>M-2026-000001</code>). It is generated automatically,
                  never edited, and stays with you as programs expand.
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h3 class="accordion-header" id="q2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a2">Is M-Score available now?</button>
              </h3>
              <div id="a2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted small">
                  M-Score is a planned credibility layer. Today you will see a placeholder in your dashboard while the
                  methodology and governance are finalised.
                </div>
              </div>
            </div>
            <div class="accordion-item">
              <h3 class="accordion-header" id="q3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#a3">Who can see my information?</button>
              </h3>
              <div id="a3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted small">
                  You sign in to your own M-Profile. Administrators may access limited operational views for support and
                  compliance — always within the roles defined by the programme.
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<?php
require __DIR__ . '/includes/footer.php';
