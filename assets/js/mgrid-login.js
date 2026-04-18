/**
 * Login — OTP-style boxes: auto-advance focus
 */
(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    const ids = ["otp1", "otp2", "otp3", "otp4", "otp5", "otp6"];
    ids.forEach(function (id, i) {
      const el = document.getElementById(id);
      if (!el) return;
      el.addEventListener("input", function () {
        if (el.value.length === 1 && ids[i + 1]) {
          document.getElementById(ids[i + 1]).focus();
        }
      });
    });
  });
})();
