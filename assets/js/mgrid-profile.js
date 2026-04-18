(function () {
  "use strict";
  document.addEventListener("DOMContentLoaded", function () {
    const edit = document.getElementById("editProfile");
    const save = document.getElementById("saveProfile");
    const fields = document.querySelectorAll("#t1 input, #t1 textarea");
    if (!edit || !save) return;
    edit.addEventListener("click", function () {
      fields.forEach(function (f) {
        f.removeAttribute("readonly");
      });
      save.classList.remove("d-none");
    });
    const dl = document.getElementById("btnDownloadMid");
    if (dl && window.MgridCore) {
      dl.addEventListener("click", function () {
        MgridCore.showToast("PDF download would start (demo).", "success");
      });
    }
  });
})();
