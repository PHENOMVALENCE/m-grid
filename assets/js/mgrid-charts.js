/**
 * M-Grid — Chart.js defaults and helpers (Section 6)
 * Depends: Chart.js 4.x loaded globally as `Chart`
 */
(function () {
  "use strict";

  const MGRID_CHART_DEFAULTS = {
    font: { family: "DM Sans", size: 12 },
    color: "#4A4A4C",
    plugins: {
      legend: {
        labels: {
          font: { family: "DM Sans", size: 12 },
          color: "#4A4A4C",
        },
      },
      tooltip: {
        backgroundColor: "#1C1C1E",
        titleFont: { family: "DM Sans", weight: "500" },
        bodyFont: { family: "DM Sans" },
        padding: 10,
        cornerRadius: 8,
      },
    },
    scales: {
      x: {
        grid: { color: "#EEEDE8" },
        ticks: { color: "#4A4A4C", font: { family: "DM Sans" } },
      },
      y: {
        grid: { color: "#EEEDE8" },
        ticks: { color: "#4A4A4C", font: { family: "DM Sans" } },
      },
    },
  };

  /**
   * Merge defaults into chart options (shallow merge of plugins/scales)
   */
  function mergeChartOptions(userOptions) {
    const o = userOptions || {};
    const base = {
      ...o,
      plugins: { ...MGRID_CHART_DEFAULTS.plugins, ...(o.plugins || {}) },
    };
    if (o.scales) {
      base.scales = {
        x: { ...MGRID_CHART_DEFAULTS.scales.x, ...(o.scales.x || {}) },
        y: { ...MGRID_CHART_DEFAULTS.scales.y, ...(o.scales.y || {}) },
      };
    }
    return base;
  }

  /**
   * Create M-Score doughnut gauge (half-circle style via rotation)
   */
  function createScoreGauge(canvasId, score, maxScore) {
    maxScore = maxScore || 100;
    const el = document.getElementById(canvasId);
    if (!el || typeof Chart === "undefined") return null;
    const rest = Math.max(0, maxScore - score);
    return new Chart(el, {
      type: "doughnut",
      data: {
        datasets: [
          {
            data: [score, rest],
            backgroundColor: ["#C9A84C", "#EEEDE8"],
            borderWidth: 0,
            circumference: 240,
            rotation: -120,
          },
        ],
      },
      options: {
        cutout: "80%",
        plugins: { legend: { display: false }, tooltip: { enabled: false } },
      },
    });
  }

  window.MGRID_CHART_DEFAULTS = MGRID_CHART_DEFAULTS;
  window.MgridCharts = {
    mergeChartOptions: mergeChartOptions,
    createScoreGauge: createScoreGauge,
  };
})();
