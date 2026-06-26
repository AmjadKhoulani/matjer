import { store } from './store.js';

let reportsSalesChart = null;
let reportsStockChart = null;
let reportsBranchesChart = null;

export function initReports() {
  renderReportCharts();
  renderStockLogsTable();
  initBranchPerformance();

  // Redraw charts on theme toggle
  window.addEventListener('theme-changed', () => {
    destroyCharts();
    renderReportCharts();
    initBranchPerformance();
  });
}

function destroyCharts() {
  if (reportsSalesChart) reportsSalesChart.destroy();
  if (reportsStockChart) reportsStockChart.destroy();
  if (reportsBranchesChart) reportsBranchesChart.destroy();
}

function renderReportCharts() {
  const salesCanvas = document.getElementById('reportsSalesChartCanvas');
  const stockCanvas = document.getElementById('reportsStockChartCanvas');
  if (!salesCanvas || !stockCanvas) return;

  if (reportsSalesChart) reportsSalesChart.destroy();
  if (reportsStockChart) reportsStockChart.destroy();

  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = isDark ? '#374151' : '#e2e8f0';
  const textColor = isDark ? '#9ca3af' : '#475569';
  const primaryColor = isDark ? '#818cf8' : '#4f46e5';
  const secondaryColor = '#06b6d4'; // Cyan

  // 1. Sales Trend (Line Chart)
  const salesCtx = salesCanvas.getContext('2d');
  reportsSalesChart = new Chart(salesCtx, {
    type: 'bar',
    data: {
      labels: ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'],
      datasets: [{
        label: 'مبيعات نقاط البيع (POS)',
        data: [120, 310, 200, 90, 1800, 450, 1300],
        backgroundColor: primaryColor,
        borderRadius: 4
      }, {
        label: 'مبيعات المتجر الإلكتروني',
        data: [30, 110, 100, 40, 600, 150, 320],
        backgroundColor: secondaryColor,
        borderRadius: 4
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: textColor, font: { family: 'Cairo', size: 11 } }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { color: textColor, font: { family: 'Cairo' } }, stacked: true },
        y: { grid: { color: gridColor }, ticks: { color: textColor, font: { family: 'Plus Jakarta Sans' } }, stacked: true }
      }
    }
  });

  // 2. Stock levels by categories (Doughnut)
  const products = store.getProducts();
  const categoryStock = {};
  products.forEach(p => {
    categoryStock[p.category] = (categoryStock[p.category] || 0) + p.stock;
  });

  const catLabels = Object.keys(categoryStock);
  const catData = Object.values(categoryStock);

  const stockCtx = stockCanvas.getContext('2d');
  reportsStockChart = new Chart(stockCtx, {
    type: 'pie',
    data: {
      labels: catLabels,
      datasets: [{
        data: catData,
        backgroundColor: [
          primaryColor,
          secondaryColor,
          '#10b981', // Emerald
          '#f59e0b', // Amber
          '#f43f5e'  // Rose
        ],
        borderWidth: isDark ? 2 : 1,
        borderColor: isDark ? '#111827' : '#ffffff'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
          labels: { color: textColor, font: { family: 'Cairo', size: 10 } }
        }
      }
    }
  });
}

function renderStockLogsTable() {
  const activities = store.getActivities();
  const tbody = document.getElementById('reports-stock-logs-tbody');
  if (!tbody) return;

  const stockActivities = activities.filter(act => 
    act.title.includes('مخزون') || 
    act.title.includes('شحنة') || 
    act.title.includes('جرد') ||
    act.desc.includes('قطعة') ||
    act.desc.includes('المستودع')
  );

  if (stockActivities.length === 0) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center" style="padding:16px; color:var(--text-muted);">لا توجد حركات مخزون سابقة مسجلة.</td></tr>`;
    return;
  }

  const badgeMap = {
    success: 'badge-success',
    warning: 'badge-warning',
    danger: 'badge-danger',
    info: 'badge-info'
  };

  tbody.innerHTML = stockActivities.map(act => `
    <tr>
      <td style="white-space: nowrap;"><span class="badge ${badgeMap[act.type] || 'badge-info'}">${act.title}</span></td>
      <td>${act.desc}</td>
      <td style="white-space: nowrap; font-size:12px; color:var(--text-muted);">${act.time}</td>
    </tr>
  `).join('');
}

export function initBranchPerformance() {
  const canvas = document.getElementById('reportsBranchesChartCanvas');
  if (!canvas) return;

  if (reportsBranchesChart) reportsBranchesChart.destroy();

  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = isDark ? '#374151' : '#e2e8f0';
  const textColor = isDark ? '#9ca3af' : '#475569';
  const primaryColor = isDark ? '#818cf8' : '#4f46e5';
  const secondaryColor = '#06b6d4'; // Cyan
  const accentColor = '#f59e0b'; // Amber

  const orders = store.getOrders();
  const totalSales = orders
    .filter(o => o.status === 'Delivered' || o.status === 'Shipped')
    .reduce((sum, o) => sum + o.total, 0);

  const damascusSales = Math.round(totalSales * 0.55);
  const aleppoSales = Math.round(totalSales * 0.30);
  const homsSales = Math.round(totalSales * 0.15);

  const ctx = canvas.getContext('2d');
  reportsBranchesChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: ['مستودع دمشق الرئيسي', 'مستودع حلب الشمالي', 'معرض حمص المباشر'],
      datasets: [{
        label: 'حجم مبيعات الفروع ($)',
        data: [damascusSales, aleppoSales, homsSales],
        backgroundColor: [primaryColor, secondaryColor, accentColor],
        borderRadius: 6,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          titleFont: { family: 'Cairo' },
          bodyFont: { family: 'Cairo' }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: textColor, font: { family: 'Cairo', size: 12 } }
        },
        y: {
          grid: { color: gridColor },
          ticks: { color: textColor, font: { family: 'Plus Jakarta Sans' } }
        }
      }
    }
  });
}
