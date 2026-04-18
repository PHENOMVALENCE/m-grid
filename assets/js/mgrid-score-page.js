/* global Chart, MgridCharts, MgridCore */
document.addEventListener("DOMContentLoaded", function () {
  if (window.MgridCore && document.getElementById("scoreMain")) {
    MgridCore.animateScore("scoreMain", 68, 1500);
  }
  if (window.MgridCharts && typeof Chart !== "undefined") {
    MgridCharts.createScoreGauge("scoreGauge", 68, 100);
    const ctx = document.getElementById("scoreLineChart");
    if (ctx) {
      const opts = MgridCharts.mergeChartOptions({
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
          x: { display: true },
          y: { display: true, min: 40, max: 80 },
        },
      });
      new Chart(ctx, {
        type: "line",
        data: {
          labels: ["Oct", "Nov", "Dec", "Jan", "Feb", "Mar"],
          datasets: [
            {
              label: "M-Score",
              data: [52, 55, 58, 61, 65, 68],
              borderColor: "#C9A84C",
              backgroundColor: "rgba(201,168,76,0.12)",
              fill: true,
              tension: 0.35,
            },
          ],
        },
        options: opts,
      });
    }
  }
});
