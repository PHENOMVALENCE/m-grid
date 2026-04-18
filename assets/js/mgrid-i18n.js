/**
 * M-Grid — EN / SW strings (Section 7)
 */
(function () {
  "use strict";

  const MGRID_STRINGS = {
    en: {
      "hero.title": "Your Digital Passport to Economic Power",
      "hero.subtitle":
        "Malkia Grid gives every woman in Tanzania a verified identity, a credit score, and access to finance, training, and opportunities.",
      "nav.home": "Home",
      "nav.about": "About",
      "nav.partners": "Partners",
      "nav.get_mid": "Get Your M-ID",
      "cta.watch": "Watch How It Works",
      "login.welcome": "Welcome Back, Malkia",
      "login.user": "User",
      "login.admin": "Admin",
      "dash.welcome": "Karibu",
      "footer.tagline": "Women's digital identity for opportunity and trust.",
    },
    sw: {
      "hero.title": "Pasipoti Yako ya Kidijitali ya Nguvu ya Kiuchumi",
      "hero.subtitle":
        "Malkia Grid inampa kila mwanamke Tanzania utambulisho thabiti, alama ya mkopo, na ufikiaji wa fedha, mafunzo, na fursa.",
      "nav.home": "Nyumbani",
      "nav.about": "Kuhusu",
      "nav.partners": "Washirika",
      "nav.get_mid": "Pata M-ID Yako",
      "cta.watch": "Tazama Inavyofanya Kazi",
      "login.welcome": "Karibu Tena, Malkia",
      "login.user": "Mtumiaji",
      "login.admin": "Msimamizi",
      "dash.welcome": "Karibu",
      "footer.tagline": "Utambulisho wa kidijitali wa wanawake kwa fursa na uaminifu.",
    },
  };

  function applyLanguage(lang) {
    if (!MGRID_STRINGS[lang]) lang = "en";
    document.querySelectorAll("[data-i18n]").forEach(function (el) {
      const key = el.getAttribute("data-i18n");
      if (key && MGRID_STRINGS[lang][key]) {
        el.textContent = MGRID_STRINGS[lang][key];
      }
    });
    try {
      localStorage.setItem("mgrid_lang", lang);
    } catch (e) {
      /* ignore */
    }
    document.documentElement.setAttribute("lang", lang === "sw" ? "sw" : "en");
  }

  function initLangToggle() {
    const stored = (function () {
      try {
        return localStorage.getItem("mgrid_lang");
      } catch (e) {
        return null;
      }
    })();
    const initial = stored || "en";
    const radios = document.querySelectorAll('input[name="mgridLang"]');
    if (radios.length) {
      radios.forEach(function (r) {
        if (r.value === initial) r.checked = true;
        r.addEventListener("change", function () {
          applyLanguage(r.value);
        });
      });
    }
    applyLanguage(initial);
  }

  window.MGRID_STRINGS = MGRID_STRINGS;
  window.applyLanguage = applyLanguage;
  window.mgridI18nApply = initLangToggle;
})();
