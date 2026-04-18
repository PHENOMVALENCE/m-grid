/* global MgridCore */
document.addEventListener("DOMContentLoaded", function () {
  const el = document.getElementById("heroScoreDemo");
  if (el && window.MgridCore) {
    MgridCore.animateScore("heroScoreDemo", 68, 1200);
  }
});
