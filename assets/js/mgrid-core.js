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
    const onScroll = function () {
      if (window.scrollY > 48) {
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
