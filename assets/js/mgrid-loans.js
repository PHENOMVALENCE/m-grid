(function () {
  "use strict";
  function calc() {
    const principal = parseFloat(document.getElementById("loanAmt").value) || 0;
    const months = parseInt(document.getElementById("months").value, 10) || 12;
    const rate = 0.12;
    const repayment = (principal * (1 + rate)) / months;
    const out = document.getElementById("loanRepay");
    const amt = document.getElementById("loanAmtOut");
    if (amt) amt.textContent = "TZS " + principal.toLocaleString("en-TZ");
    if (out) out.textContent = "TZS " + Math.round(repayment).toLocaleString("en-TZ") + " / month";
  }
  document.addEventListener("DOMContentLoaded", function () {
    const r = document.getElementById("loanAmt");
    const m = document.getElementById("months");
    if (r) r.addEventListener("input", calc);
    if (m) m.addEventListener("change", calc);
    calc();
  });
})();
