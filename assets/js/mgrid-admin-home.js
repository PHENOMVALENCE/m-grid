/* global Chart, MgridCharts */
document.addEventListener("DOMContentLoaded", function () {
  if (typeof Chart === "undefined" || !window.MgridCharts) return;
  const lineEl = document.getElementById("admLine");
  if (lineEl) {
    const opts = MgridCharts.mergeChartOptions({
      plugins: { legend: { display: false } },
      scales: {
        x: { display: true },
        y: { display: true, beginAtZero: true },
      },
    });
    new Chart(lineEl, {
      type: "line",
      data: {
        labels: ["Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec", "Jan", "Feb", "Mar"],
        datasets: [{ label: "Registrations", data: [120, 190, 210, 240, 260, 300, 320, 340, 360, 400, 420, 450], borderColor: "#C9A84C", tension: 0.3, fill: false }],
      },
      options: opts,
    });
  }
  const dEl = document.getElementById("admDonut");
  if (dEl) {
    new Chart(dEl, {
      type: "doughnut",
      data: {
        labels: ["Bronze", "Silver", "Gold", "Diamond"],
        datasets: [{ data: [1200, 2100, 1200, 321], backgroundColor: ["#CD7F32", "#A8A9AD", "#C9A84C", "#5B8DEF"] }],
      },
      options: MgridCharts.mergeChartOptions({ plugins: { legend: { position: "bottom" } } }),
    });
  }
});
