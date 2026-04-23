<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

$mgrid_page_title = mgrid_title('title.landing');
$mgrid_layout = 'public';
$mgrid_body_extra_class = 'mgrid-landing-premium';
$mgrid_navbar_premium = true;
$mgrid_public_vanilla = true;

require __DIR__ . '/includes/header.php';
?>

<main class="mgrid-lp">
  <!-- Hero: full-bleed imagery + product preview -->
  <section class="mgrid-lp-hero" data-mgrid-hero-parallax>
    <div class="mgrid-lp-hero__bg" aria-hidden="true"></div>
    <div class="mgrid-lp-hero__overlay" aria-hidden="true"></div>
    <div class="mgrid-container mgrid-lp-hero__inner">
      <div class="mgrid-lp-hero-grid">
        <div>
          <p class="mgrid-lp-hero__eyebrow mgrid-lp-hero__anim" data-i18n="landing.hero_eyebrow">Malkia wa Nguvu · Clouds Media Group</p>
          <h1 class="mgrid-lp-hero__title mgrid-lp-hero__anim mgrid-lp-hero__anim--2" data-i18n-html="landing.hero_title">
            Economic identity,<br><span class="mgrid-lp-hero__accent">built for national scale.</span>
          </h1>
          <p class="mgrid-lp-hero__lead mgrid-lp-hero__anim mgrid-lp-hero__anim--3" data-i18n-html="landing.hero_lead">
            <strong>M GRID</strong> is Tanzania’s structured digital layer for women-led enterprise — verified <strong>M-ID</strong>,
            living <strong>M-Profile</strong>, and a governed path to <strong>M-Score</strong> and partner programmes.
          </p>
          <div class="mgrid-lp-hero__actions mgrid-lp-hero__anim mgrid-lp-hero__anim--4">
            <a class="mgrid-lp-btn mgrid-lp-btn--primary" href="<?= e(url('register.php')) ?>" data-i18n="landing.cta_register">Register for M-ID</a>
            <a class="mgrid-lp-btn mgrid-lp-btn--ghost" href="#how" data-i18n="landing.cta_learn">Learn how it works</a>
          </div>
        </div>
        <div>
          <div class="mgrid-lp-dash mgrid-lp-hero__anim mgrid-lp-hero__anim--4" aria-label="Member console preview">
            <div class="mgrid-lp-dash__head">
              <span class="mgrid-lp-dash__head-title" data-i18n="landing.dash_console">Member console</span>
              <span class="mgrid-lp-dash__live" data-i18n="landing.dash_live">Live</span>
            </div>
            <div class="mgrid-lp-dash__body">
              <div class="mgrid-lp-dash__row">
                <span class="mgrid-lp-dash__label">M-ID</span>
                <span class="mgrid-lp-dash__value">M-2026-004821</span>
              </div>
              <div class="mgrid-lp-dash__row">
                <span class="mgrid-lp-dash__label" data-i18n="landing.dash_profile_label">Profile</span>
                <div>
                  <div class="mgrid-lp-dash-completion"><span data-i18n="landing.dash_completion">Completion</span><span>72%</span></div>
                  <div class="mgrid-lp-progress" role="presentation"><div class="mgrid-lp-progress__bar"></div></div>
                </div>
              </div>
              <div class="mgrid-lp-dash__row">
                <span class="mgrid-lp-dash__label" data-i18n="landing.dash_verify_label">Verification</span>
                <span class="mgrid-lp-dash__status"><i class="ti ti-clock-hour-4" aria-hidden="true"></i> <span data-i18n="landing.dash_pending">Pending review</span></span>
              </div>
              <div class="mgrid-lp-dash__row">
                <span class="mgrid-lp-dash__label">M-Score</span>
                <span><span class="mgrid-lp-dash__value">68</span><span class="mgrid-lp-dash__tier" data-i18n="landing.dash_tier">· Gold tier</span></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Trust bar -->
  <section id="trust" class="mgrid-lp-trust mgrid-section-reveal" aria-label="Programme alignment">
    <div class="mgrid-container">
      <div class="mgrid-lp-trust__panel">
        <p class="mgrid-lp-trust__label" data-i18n="landing.trust_label">Trusted programme architecture</p>
        <ul class="mgrid-lp-trust__grid mgrid-list-plain">
          <li class="mgrid-lp-trust__cell"><span class="mgrid-lp-trust__name" data-i18n="landing.trust_1">Malkia wa Nguvu</span></li>
          <li class="mgrid-lp-trust__cell"><span class="mgrid-lp-trust__name" data-i18n="landing.trust_2">Clouds Media Group</span></li>
          <li class="mgrid-lp-trust__cell"><span class="mgrid-lp-trust__name" data-i18n="landing.trust_3">Governed data practice</span></li>
          <li class="mgrid-lp-trust__cell"><span class="mgrid-lp-trust__name" data-i18n="landing.trust_4">National rollout design</span></li>
        </ul>
      </div>
    </div>
  </section>

  <!-- What is M GRID -->
  <section id="about" class="mgrid-lp-section mgrid-section-reveal">
    <div class="mgrid-container">
      <div class="mgrid-lp-about-grid">
        <header class="mgrid-lp-about-intro">
          <p class="mgrid-lp-kicker" data-i18n="landing.what_kicker">What is M GRID</p>
          <h2 class="mgrid-lp-h2" data-i18n="about.title">About Malkia Grid</h2>
          <p class="mgrid-lp-lead mgrid-mt-3" data-i18n="about.lead">
            Malkia Grid is built for national scale: clear governance, secure sessions, and a single credible profile
            you can carry into partnerships and programmes.
          </p>
          <div class="mgrid-lp-rule mgrid-mt-4" aria-hidden="true"></div>
        </header>
        <div class="mgrid-lp-pillars-grid">
          <article class="mgrid-lp-pillar">
            <div class="mgrid-lp-pillar__top">
              <span class="mgrid-lp-pillar__index">01</span>
              <div class="mgrid-lp-pillar__icon"><i class="ti ti-heart-handshake" aria-hidden="true"></i></div>
            </div>
            <h3 class="mgrid-lp-pillar__title" data-i18n="about.card1_title">Dignity first</h3>
            <p class="mgrid-lp-pillar__text" data-i18n="about.card1_body">Your story and data are treated with respect. Clear roles, secure sessions, and room to grow.</p>
          </article>
          <article class="mgrid-lp-pillar">
            <div class="mgrid-lp-pillar__top">
              <span class="mgrid-lp-pillar__index">02</span>
              <div class="mgrid-lp-pillar__icon"><i class="ti ti-building-bank" aria-hidden="true"></i></div>
            </div>
            <h3 class="mgrid-lp-pillar__title" data-i18n="about.card2_title">Built for partnerships</h3>
            <p class="mgrid-lp-pillar__text" data-i18n="about.card2_body">Structured profiles and future verification modules help institutions say “yes” with confidence.</p>
          </article>
          <article class="mgrid-lp-pillar">
            <div class="mgrid-lp-pillar__top">
              <span class="mgrid-lp-pillar__index">03</span>
              <div class="mgrid-lp-pillar__icon"><i class="ti ti-chart-arrows-vertical" aria-hidden="true"></i></div>
            </div>
            <h3 class="mgrid-lp-pillar__title" data-i18n="about.card3_title">Designed to scale</h3>
            <p class="mgrid-lp-pillar__text" data-i18n="about.card3_body">M-Fund, M-Partner, and M-Benefits can plug in when you are ready — without rebuilding your identity.</p>
          </article>
        </div>
      </div>
    </div>
  </section>

  <!-- How it works -->
  <section id="how" class="mgrid-lp-section mgrid-lp-section--muted mgrid-section-reveal">
    <div class="mgrid-container">
      <header class="mgrid-lp-section-header mgrid-lp-section-header--center">
        <p class="mgrid-lp-kicker" data-i18n="landing.how_kicker">Process</p>
        <h2 class="mgrid-lp-h2" data-i18n="how.title">How it works</h2>
        <p class="mgrid-lp-lead mgrid-lp-section-header__sub" data-i18n="landing.how_sub">Four disciplined steps from registration to opportunity access.</p>
      </header>
      <div class="mgrid-lp-process-row" role="list">
        <div role="listitem">
          <article class="mgrid-lp-process__item">
            <div class="mgrid-lp-process__marker" aria-hidden="true"><span>1</span></div>
            <div class="mgrid-lp-process__body">
              <h3 class="mgrid-lp-process__title" data-i18n="how.s1_title">Register</h3>
              <p class="mgrid-lp-process__text" data-i18n="how.s1_body">Create your account with a few accurate details.</p>
            </div>
          </article>
        </div>
        <div role="listitem">
          <article class="mgrid-lp-process__item">
            <div class="mgrid-lp-process__marker" aria-hidden="true"><span>2</span></div>
            <div class="mgrid-lp-process__body">
              <h3 class="mgrid-lp-process__title" data-i18n="how.s2_title">Receive M-ID</h3>
              <p class="mgrid-lp-process__text" data-i18n="how.s2_body">Your permanent identifier is issued automatically.</p>
            </div>
          </article>
        </div>
        <div role="listitem">
          <article class="mgrid-lp-process__item">
            <div class="mgrid-lp-process__marker" aria-hidden="true"><span>3</span></div>
            <div class="mgrid-lp-process__body">
              <h3 class="mgrid-lp-process__title" data-i18n="how.s3_title">Grow M-Profile</h3>
              <p class="mgrid-lp-process__text" data-i18n="how.s3_body">Complete your profile as modules go live.</p>
            </div>
          </article>
        </div>
        <div role="listitem">
          <article class="mgrid-lp-process__item">
            <div class="mgrid-lp-process__marker" aria-hidden="true"><span>4</span></div>
            <div class="mgrid-lp-process__body">
              <h3 class="mgrid-lp-process__title" data-i18n="how.s4_title">Access opportunity</h3>
              <p class="mgrid-lp-process__text" data-i18n="how.s4_body">M-Score, benefits, and partners connect here over time.</p>
            </div>
          </article>
        </div>
      </div>
    </div>
  </section>

  <!-- Core features -->
  <section id="features" class="mgrid-lp-section mgrid-section-reveal">
    <div class="mgrid-container">
      <header class="mgrid-lp-section-header mgrid-lp-section-header--split">
        <div class="mgrid-lp-section-header__main">
          <p class="mgrid-lp-kicker" data-i18n="landing.features_kicker">Core platform</p>
          <h2 class="mgrid-lp-h2 mgrid-mb-0" data-i18n="landing.features_title">Identity, profile, score, and capital readiness</h2>
        </div>
        <p class="mgrid-lp-lead mgrid-lp-section-header__aside mgrid-mb-0" data-i18n="landing.features_lead">Modular capabilities that compound over time — each layer adds signal without fragmenting your record.</p>
      </header>
      <div class="mgrid-lp-feature-grid">
        <article class="mgrid-lp-feature">
          <div class="mgrid-lp-feature__head">
            <i class="ti ti-id mgrid-lp-feature__glyph" aria-hidden="true"></i>
            <span class="mgrid-lp-feature__code">M-ID</span>
          </div>
          <p class="mgrid-lp-feature__copy" data-i18n="landing.feature_mid">A single, permanent identifier issued by the platform — portable across programmes and partners.</p>
        </article>
        <article class="mgrid-lp-feature">
          <div class="mgrid-lp-feature__head">
            <i class="ti ti-user-scan mgrid-lp-feature__glyph" aria-hidden="true"></i>
            <span class="mgrid-lp-feature__code">M-Profile</span>
          </div>
          <p class="mgrid-lp-feature__copy" data-i18n="landing.feature_profile">Structured member record with verification workflows, documents, and controlled visibility.</p>
        </article>
        <article class="mgrid-lp-feature">
          <div class="mgrid-lp-feature__head">
            <i class="ti ti-chart-dots-3 mgrid-lp-feature__glyph" aria-hidden="true"></i>
            <span class="mgrid-lp-feature__code">M-Score</span>
          </div>
          <p class="mgrid-lp-feature__copy" data-i18n="landing.feature_score">Credibility layer with transparent milestones — methodology and governance published as modules mature.</p>
        </article>
        <article class="mgrid-lp-feature">
          <div class="mgrid-lp-feature__head">
            <i class="ti ti-currency-dollar mgrid-lp-feature__glyph" aria-hidden="true"></i>
            <span class="mgrid-lp-feature__code">M-Fund</span>
          </div>
          <p class="mgrid-lp-feature__copy" data-i18n="landing.feature_fund">Planned finance-readiness pathways — optional applications when you choose to engage.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- Benefits -->
  <section id="benefits" class="mgrid-lp-section mgrid-lp-section--muted mgrid-section-reveal">
    <div class="mgrid-container">
      <div class="mgrid-lp-benefits-grid">
        <div>
          <p class="mgrid-lp-kicker" data-i18n="landing.benefits_kicker">Outcomes</p>
          <h2 class="mgrid-lp-h2" data-i18n="benefits.title">Benefits &amp; opportunities</h2>
          <p class="mgrid-lp-lead mgrid-mt-3" data-i18n="benefits.lead">
            Malkia Grid is phased on purpose: first identity and trust, then credibility scoring, then curated pathways to
            finance, services, and benefits — always with clarity and consent.
          </p>
          <ul class="mgrid-lp-checklist">
            <li data-i18n-html="benefits.li1_html">A single <strong>M-ID</strong> you can reference across programs.</li>
            <li data-i18n-html="benefits.li2_html">A dashboard that grows with <strong>documents, offers, and verification</strong>.</li>
            <li data-i18n-html="benefits.li3_html">Future <strong>M-Score</strong> tiering with transparent milestones.</li>
            <li data-i18n-html="benefits.li4_html"><strong>English</strong> as the primary experience today; <strong>Kiswahili</strong> layers in as translations and modules expand.</li>
          </ul>
        </div>
        <div class="mgrid-lp-mod-grid">
          <article class="mgrid-lp-mod">
            <span class="mgrid-lp-mod__label" data-i18n="landing.module_label">Module</span>
            <h3 class="mgrid-lp-mod__title">M-Fund</h3>
            <p class="mgrid-lp-mod__text" data-i18n="benefits.mfund">Loan readiness pathways (planned).</p>
          </article>
          <article class="mgrid-lp-mod">
            <span class="mgrid-lp-mod__label" data-i18n="landing.module_label">Module</span>
            <h3 class="mgrid-lp-mod__title">M-Partner</h3>
            <p class="mgrid-lp-mod__text" data-i18n="benefits.mpartner">Trusted services aligned to your profile.</p>
          </article>
          <article class="mgrid-lp-mod">
            <span class="mgrid-lp-mod__label" data-i18n="landing.module_label">Module</span>
            <h3 class="mgrid-lp-mod__title">M-Benefits</h3>
            <p class="mgrid-lp-mod__text" data-i18n="benefits.mbenefits">Programs, grants, and learning journeys.</p>
          </article>
          <article class="mgrid-lp-mod">
            <span class="mgrid-lp-mod__label" data-i18n="landing.module_label">Module</span>
            <h3 class="mgrid-lp-mod__title" data-i18n="landing.benefit_verify_title">Verification</h3>
            <p class="mgrid-lp-mod__text" data-i18n="benefits.verify">Document uploads with review workflows.</p>
          </article>
        </div>
      </div>
    </div>
  </section>

  <!-- Partners -->
  <section id="partners" class="mgrid-lp-section mgrid-section-reveal">
    <div class="mgrid-container">
      <header class="mgrid-lp-section-header mgrid-lp-section-header--center mgrid-lp-partners__head">
        <p class="mgrid-lp-kicker" data-i18n="landing.partners_kicker">Ecosystem</p>
        <h2 class="mgrid-lp-h2" data-i18n="partners.title">Partner ecosystem</h2>
        <p class="mgrid-lp-lead mgrid-lp-section-header__sub" data-i18n="partners.lead">
          Financial, health, skills, and media partners connect through structured profiles — reducing friction and
          supporting fair access. Categories below reflect planned integration areas.
        </p>
      </header>
      <div class="mgrid-lp-partner-row">
        <article class="mgrid-lp-partner-card">
          <span class="mgrid-lp-partner-card__eyebrow" data-i18n="landing.partner_track">Integration track</span>
          <h3 class="mgrid-lp-partner-card__title" data-i18n="partners.pill1">Finance</h3>
          <p class="mgrid-lp-partner-card__meta" data-i18n="landing.partner_meta_finance">Credit, savings, and insurance partners connect through governed data contracts.</p>
        </article>
        <article class="mgrid-lp-partner-card">
          <span class="mgrid-lp-partner-card__eyebrow" data-i18n="landing.partner_track">Integration track</span>
          <h3 class="mgrid-lp-partner-card__title" data-i18n="partners.pill2">Health &amp; wellness</h3>
          <p class="mgrid-lp-partner-card__meta" data-i18n="landing.partner_meta_health">Wellness and care programmes with privacy-preserving profile signals.</p>
        </article>
        <article class="mgrid-lp-partner-card">
          <span class="mgrid-lp-partner-card__eyebrow" data-i18n="landing.partner_track">Integration track</span>
          <h3 class="mgrid-lp-partner-card__title" data-i18n="partners.pill3">Skills &amp; enterprise</h3>
          <p class="mgrid-lp-partner-card__meta" data-i18n="landing.partner_meta_skills">Training, certification, and enterprise support mapped to your journey.</p>
        </article>
        <article class="mgrid-lp-partner-card">
          <span class="mgrid-lp-partner-card__eyebrow" data-i18n="landing.partner_track">Integration track</span>
          <h3 class="mgrid-lp-partner-card__title" data-i18n="partners.pill4">Media &amp; visibility</h3>
          <p class="mgrid-lp-partner-card__meta" data-i18n="landing.partner_meta_media">Reach and storytelling that respect consent and programme standards.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section id="cta" class="mgrid-lp-cta mgrid-section-reveal">
    <div class="mgrid-container">
      <div class="mgrid-lp-cta__panel">
        <div class="mgrid-lp-cta-grid">
          <div>
            <p class="mgrid-lp-cta__eyebrow" data-i18n="landing.cta_eyebrow">Get started</p>
            <h2 class="mgrid-lp-cta__title" data-i18n="landing.cta_title">Join the national register for women-led enterprise.</h2>
            <p class="mgrid-lp-cta__text mgrid-mb-0" data-i18n="landing.cta_sub">
              Receive your M-ID, activate your M-Profile, and participate in a governed pathway toward credibility and opportunity.
            </p>
          </div>
          <div>
            <div class="mgrid-lp-cta__actions">
              <a class="mgrid-lp-btn mgrid-lp-btn--primary mgrid-lp-btn--block" href="<?= e(url('register.php')) ?>" data-i18n="landing.cta_register">Register for M-ID</a>
              <a class="mgrid-lp-btn mgrid-lp-btn--outline-dark mgrid-lp-btn--block" href="<?= e(url('login.php')) ?>" data-i18n="landing.cta_signin_link">Member sign-in</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section id="faq" class="mgrid-lp-section mgrid-section-reveal">
    <div class="mgrid-container">
      <div class="mgrid-lp-faq-grid">
        <div>
          <p class="mgrid-lp-kicker" data-i18n="landing.faq_kicker">Clarity</p>
          <h2 class="mgrid-lp-h2" data-i18n="faq.title">Frequently asked questions</h2>
          <p class="mgrid-lp-lead mgrid-mt-3" data-i18n="landing.faq_supporting">Straight answers on identity, scoring, and how your data is handled inside the programme.</p>
        </div>
        <div>
          <div class="mgrid-lp-faq mgrid-lp-faq--panel" data-mgrid-faq>
            <div class="mgrid-faq">
              <div class="mgrid-faq__item is-open">
                <button class="mgrid-faq__trigger" type="button" aria-expanded="true" id="faq-q1">
                  <span data-i18n="faq.q1">What is an M-ID?</span>
                  <span class="mgrid-faq__chevron" aria-hidden="true"></span>
                </button>
                <div class="mgrid-faq__panel" role="region" aria-labelledby="faq-q1" data-i18n-html="faq.a1">
                  M-ID is your unique Malkia Grid identifier (for example <code>M-2026-000001</code>). It is generated automatically,
                  never edited, and stays with you as programs expand.
                </div>
              </div>
              <div class="mgrid-faq__item">
                <button class="mgrid-faq__trigger" type="button" aria-expanded="false" id="faq-q2">
                  <span data-i18n="faq.q2">Is M-Score available now?</span>
                  <span class="mgrid-faq__chevron" aria-hidden="true"></span>
                </button>
                <div class="mgrid-faq__panel" role="region" aria-labelledby="faq-q2" data-i18n-html="faq.a2">
                  M-Score is a planned credibility layer. Today you will see a placeholder in your dashboard while the
                  methodology and governance are finalised.
                </div>
              </div>
              <div class="mgrid-faq__item">
                <button class="mgrid-faq__trigger" type="button" aria-expanded="false" id="faq-q3">
                  <span data-i18n="faq.q3">Who can see my information?</span>
                  <span class="mgrid-faq__chevron" aria-hidden="true"></span>
                </button>
                <div class="mgrid-faq__panel" role="region" aria-labelledby="faq-q3" data-i18n-html="faq.a3">
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
