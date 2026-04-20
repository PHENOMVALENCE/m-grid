/**
 * M-Grid — shared utilities (vanilla JS, no jQuery)
 */
(function () {
  "use strict";

  /**
   * Animate numeric score in an element (Section 4)
   * @param {string} elementId
   * @param {number} target
   * @param {number} duration
   */
  function animateScore(elementId, target, duration) {
    duration = duration === undefined ? 1500 : duration;
    const el = document.getElementById(elementId);
    if (!el) return;
    let start = 0;
    function step(timestamp) {
      if (!start) start = timestamp;
      const progress = Math.min((timestamp - start) / duration, 1);
      el.textContent = Math.floor(progress * target);
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }

  /**
   * Navbar: transparent → solid on scroll (landing)
   */
  function initNavbarScroll() {
    const nav = document.querySelector("[data-mgrid-navbar]");
    if (!nav) return;
    const premium = nav.getAttribute("data-mgrid-navbar-premium") === "1";
    const solidClass = nav.classList.contains("mgrid-nav-vanilla") ? "mgrid-nav-vanilla--solid" : "mgrid-navbar--solid";
    const onScroll = function () {
      if (premium) {
        nav.classList.toggle(solidClass, window.scrollY > 28);
      } else if (window.scrollY > 48) {
        nav.classList.add("navbar-mgrid-scrolled");
        nav.classList.remove("navbar-mgrid-transparent");
      } else {
        nav.classList.remove("navbar-mgrid-scrolled");
        nav.classList.add("navbar-mgrid-transparent");
      }
    };
    window.addEventListener("scroll", onScroll, { passive: true });
    onScroll();
  }

  /**
   * Hero background — subtle parallax on scroll
   */
  function initHeroParallax() {
    const root = document.querySelector("[data-mgrid-hero-parallax]");
    if (!root) return;
    const bg = root.querySelector(".mgrid-lp-hero__bg");
    if (!bg) return;
    const tick = function () {
      const y = window.scrollY;
      const translate = Math.min(y * 0.1, 80);
      bg.style.transform = "scale(1.06) translate3d(0, " + translate + "px, 0)";
    };
    window.addEventListener("scroll", tick, { passive: true });
    tick();
  }

  /**
   * Smooth scrolling for in-page anchors
   */
  /**
   * Mobile nav panel (vanilla public header)
   */
  function initPublicMobileNav() {
    const nav = document.querySelector(".mgrid-nav-vanilla");
    const btn = document.querySelector("[data-mgrid-nav-toggle]");
    if (!nav || !btn) return;
    btn.addEventListener("click", function (e) {
      e.stopPropagation();
      const open = nav.classList.toggle("mgrid-nav-vanilla--open");
      btn.setAttribute("aria-expanded", open ? "true" : "false");
    });
    document.addEventListener("click", function (e) {
      if (!nav.classList.contains("mgrid-nav-vanilla--open")) return;
      if (nav.contains(e.target)) return;
      nav.classList.remove("mgrid-nav-vanilla--open");
      btn.setAttribute("aria-expanded", "false");
    });
  }

  /**
   * FAQ accordion without Bootstrap
   */
  function initVanillaFaq() {
    const root = document.querySelector("[data-mgrid-faq]");
    if (!root) return;
    root.querySelectorAll(".mgrid-faq__trigger").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const item = btn.closest(".mgrid-faq__item");
        if (!item) return;
        const wasOpen = item.classList.contains("is-open");
        root.querySelectorAll(".mgrid-faq__item").forEach(function (el) {
          el.classList.remove("is-open");
          var t = el.querySelector(".mgrid-faq__trigger");
          if (t) t.setAttribute("aria-expanded", "false");
        });
        if (!wasOpen) {
          item.classList.add("is-open");
          btn.setAttribute("aria-expanded", "true");
        }
      });
    });
  }

  function initSmoothAnchorScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
      anchor.addEventListener("click", function (e) {
        const href = anchor.getAttribute("href");
        if (!href || href === "#") return;
        const target = document.querySelector(href);
        if (!target) return;
        e.preventDefault();
        target.scrollIntoView({ behavior: "smooth", block: "start" });
      });
    });
  }

  /**
   * Reveal sections progressively as users scroll
   */
  function initSectionReveal() {
    const sections = document.querySelectorAll(".mgrid-section-reveal");
    if (!sections.length) return;
    if (!("IntersectionObserver" in window)) {
      sections.forEach(function (el) {
        el.classList.add("is-visible");
      });
      return;
    }
    const observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      { rootMargin: "0px 0px -10% 0px", threshold: 0.12 }
    );
    sections.forEach(function (el) {
      observer.observe(el);
    });
  }

  /**
   * Show Bootstrap toast (mock success)
   */
  function showToast(message, variant) {
    const container = document.getElementById("mgridToastContainer");
    if (!container || typeof bootstrap === "undefined") return;
    const id = "toast-" + Date.now();
    const bg =
      variant === "danger"
        ? "text-bg-danger"
        : variant === "warning"
          ? "text-bg-warning"
          : "text-bg-success";
    const html =
      '<div id="' +
      id +
      '" class="toast align-items-center ' +
      bg +
      '" role="alert" aria-live="polite" aria-atomic="true">' +
      '<div class="d-flex"><div class="toast-body">' +
      message +
      '</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>';
    container.insertAdjacentHTML("beforeend", html);
    const el = document.getElementById(id);
    const t = new bootstrap.Toast(el, { delay: 4200 });
    t.show();
    el.addEventListener("hidden.bs.toast", function () {
      el.remove();
    });
  }

  /**
   * Form submit intercept — demo toast
   */
  function initDemoForms() {
    document.querySelectorAll("form[data-mgrid-demo]").forEach(function (form) {
      form.addEventListener(
        "submit",
        function (e) {
          if (typeof form.checkValidity === "function" && !form.checkValidity()) {
            e.preventDefault();
            form.classList.add("was-validated");
            return;
          }
          e.preventDefault();
          const msg = form.getAttribute("data-success-msg") || "Saved successfully.";
          showToast(msg, "success");
        },
        { capture: true }
      );
    });
  }

  /**
   * Password strength meter (register)
   */
  function initPasswordStrength() {
    const input = document.getElementById("regPassword");
    const bar = document.getElementById("passwordStrengthBar");
    if (!input || !bar) return;
    input.addEventListener("input", function () {
      const v = input.value;
      let score = 0;
      if (v.length >= 8) score += 25;
      if (v.length >= 12) score += 15;
      if (/[0-9]/.test(v)) score += 20;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score += 20;
      if (/[^a-zA-Z0-9]/.test(v)) score += 20;
      score = Math.min(100, score);
      bar.style.width = score + "%";
      bar.classList.toggle("bg-danger", score < 40);
      bar.classList.toggle("bg-warning", score >= 40 && score < 70);
      bar.classList.toggle("bg-success", score >= 70);
    });
  }

  /**
   * Toggle password visibility
   */
  function initPasswordToggle() {
    document.querySelectorAll("[data-mgrid-toggle-pw]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        const id = btn.getAttribute("data-mgrid-toggle-pw");
        const field = document.getElementById(id);
        if (!field) return;
        const show = field.type === "password";
        field.type = show ? "text" : "password";
        btn.setAttribute("aria-pressed", show ? "true" : "false");
      });
    });
  }

  /**
   * Login: user vs admin panels
   */
  function initLoginToggle() {
    const radios = document.querySelectorAll('input[name="loginRole"]');
    const userForm = document.getElementById("formUserLogin");
    const adminForm = document.getElementById("formAdminLogin");
    if (!radios.length || !userForm || !adminForm) return;
    function apply() {
      const role = document.querySelector('input[name="loginRole"]:checked');
      const isAdmin = role && role.value === "admin";
      userForm.classList.toggle("d-none", isAdmin);
      adminForm.classList.toggle("d-none", !isAdmin);
    }
    radios.forEach(function (r) {
      r.addEventListener("change", apply);
    });
    apply();
  }

  /**
   * Register steps
   */
  function initRegisterSteps() {
    const step1 = document.getElementById("regStep1");
    const step2 = document.getElementById("regStep2");
    const btnNext = document.getElementById("regBtnNext");
    const btnBack = document.getElementById("regBtnBack");
    if (!step1 || !step2 || !btnNext || !btnBack) return;
    btnNext.addEventListener("click", function (e) {
      e.preventDefault();
      step1.classList.add("d-none");
      step2.classList.remove("d-none");
      document.getElementById("regStepIndicator")?.setAttribute("data-step", "2");
    });
    btnBack.addEventListener("click", function (e) {
      e.preventDefault();
      step2.classList.add("d-none");
      step1.classList.remove("d-none");
      document.getElementById("regStepIndicator")?.setAttribute("data-step", "1");
    });
  }

  window.MgridCore = {
    animateScore: animateScore,
    showToast: showToast,
  };

  function initDemoButtons() {
    document.querySelectorAll("button[data-mgrid-demo]").forEach(function (btn) {
      btn.addEventListener("click", function (e) {
        e.preventDefault();
        const msg = btn.getAttribute("data-success-msg") || "Action recorded (demo).";
        showToast(msg, "success");
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initNavbarScroll();
    initPublicMobileNav();
    initVanillaFaq();
    initHeroParallax();
    initSmoothAnchorScroll();
    initSectionReveal();
    initDemoForms();
    initDemoButtons();
    initPasswordStrength();
    initPasswordToggle();
    initLoginToggle();
    initRegisterSteps();
    if (typeof window.mgridI18nApply === "function") {
      window.mgridI18nApply();
    }
  });
})();
