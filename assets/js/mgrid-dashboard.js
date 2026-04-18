/* global MgridCore */
document.addEventListener("DOMContentLoaded", function () {
  if (window.MgridCore && document.getElementById("dashScore")) {
    MgridCore.animateScore("dashScore", 68, 1400);
  }
});
