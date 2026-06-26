import { store } from './store.js';

let salesChart = null;
let categoryChart = null;

export function initDashboard() {
  renderKPIs();
  renderLowStockAlerts();
  renderRecentActivities();
  initCharts();
  
  // Listen for theme changes to redraw charts with correct colors
  window.addEventListener('theme-changed', () => {
    if (salesChart && categoryChart) {
      salesChart.destroy();
      categoryChart.destroy();
      initCharts();
    }
  });

  // Bind clear activities button
  const clearBtn = document.getElementById('btn-clear-activities');
  if (clearBtn && !clearBtn.dataset.listener) {
    clearBtn.dataset.listener = 'true';
    clearBtn.addEventListener('click', (e) => {
      e.preventDefault();
      if (confirm('هل أنت متأكد من رغبتك في مسح سجل الأنشطة والعمليات بالكامل؟')) {
        store.clearActivities()
          .then(() => {
            renderRecentActivities();
            alert('تم مسح سجل الأنشطة بنجاح');
          })
          .catch(err => {
            console.error(err);
            alert(err.message || 'حدث خطأ أثناء مسح الأنشطة.');
          });
      }
    });
  }
}

function renderKPIs() {
  const products = store.getProducts();
  const orders = store.getOrders();
  
  // Calculate Sales (Delivered & Shipped)
  const totalSales = orders
    .filter(o => o.status === 'Delivered' || o.status === 'Shipped')
    .reduce((sum, o) => sum + o.total, 0);
  
  // Calculate Total stock items in warehouse
  const totalInventory = products.reduce((sum, p) => sum + p.stock, 0);
  
  // Calculate low stock items count
  const lowStockCount = products.filter(p => p.stock <= p.minStock).length;
  
  // Set values on UI
  const currency = store.getCurrencySymbol();
  document.getElementById('kpi-sales').innerText = `${totalSales.toLocaleString()} ${currency}`;
  document.getElementById('kpi-inventory').innerText = totalInventory.toLocaleString();
  document.getElementById('kpi-orders').innerText = orders.length;
  document.getElementById('kpi-low-stock').innerText = lowStockCount;

  // Dynamic quick stats
  const totalProducts = products.length;
  const inStockProducts = products.filter(p => p.stock > 0).length;
  const stockPercent = totalProducts > 0 ? Math.round((inStockProducts / totalProducts) * 100) : 0;
  const stockPercentEl = document.getElementById('quick-stats-stock-percent');
  if (stockPercentEl) {
    stockPercentEl.innerText = `${stockPercent}% متوفر`;
    stockPercentEl.className = 'badge';
    if (stockPercent >= 80) stockPercentEl.classList.add('badge-success');
    else if (stockPercent >= 40) stockPercentEl.classList.add('badge-warning');
    else stockPercentEl.classList.add('badge-danger');
  }

  const totalCost = products.reduce((sum, p) => sum + (parseFloat(p.cost) || parseFloat(p.price) * 0.75 || 0), 0);
  const avgCost = totalProducts > 0 ? (totalCost / totalProducts) : 0;
  const avgCostEl = document.getElementById('quick-stats-avg-cost');
  if (avgCostEl) {
    avgCostEl.innerText = `${avgCost.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
  }

  const pendingOrdersCount = orders.filter(o => o.status === 'Pending').length;
  const pendingOrdersEl = document.getElementById('quick-stats-pending-orders');
  if (pendingOrdersEl) {
    pendingOrdersEl.innerText = `${pendingOrdersCount} قيد الانتظار`;
    pendingOrdersEl.className = 'badge';
    if (pendingOrdersCount > 0) pendingOrdersEl.classList.add('badge-warning');
    else pendingOrdersEl.classList.add('badge-success');
  }
}

function renderLowStockAlerts() {
  const products = store.getProducts();
  const lowStockProducts = products.filter(p => p.stock <= p.minStock);
  const container = document.getElementById('low-stock-alerts-container');
  
  if (lowStockProducts.length === 0) {
    container.innerHTML = '';
    return;
  }
  
  const alertHTML = `
    <div class="alert-banner">
      <div class="alert-message">
        <i class="fas fa-exclamation-triangle"></i>
        <span>تنبيه: يوجد عدد <strong>${lowStockProducts.length}</strong> منتجات مخزونها منخفض أو نفد تماماً. يرجى مراجعة المستودعات.</span>
      </div>
      <button class="btn btn-secondary btn-sm" id="btn-go-to-inventory">عرض المخزون</button>
    </div>
  `;
  
  container.innerHTML = alertHTML;
  
  document.getElementById('btn-go-to-inventory').addEventListener('click', () => {
    document.querySelector('[data-view="inventory"]').click();
  });
}

function renderRecentActivities() {
  const activities = store.getActivities();
  const container = document.getElementById('recent-activities-list');
  
  if (activities.length === 0) {
    container.innerHTML = '<p class="text-muted text-center">لا توجد أنشطة مؤخراً.</p>';
    return;
  }
  
  const iconMap = {
    success: 'fa-check',
    warning: 'fa-exclamation',
    danger: 'fa-times',
    info: 'fa-info'
  };
  
  container.innerHTML = activities.slice(0, 5).map(act => `
    <div class="activity-item">
      <div class="activity-marker ${act.type}">
        <i class="fas ${iconMap[act.type] || 'fa-info'}"></i>
      </div>
      <div class="activity-content">
        <span class="activity-text">${act.desc}</span>
        <span class="activity-time">${act.time}</span>
      </div>
    </div>
  `).join('');
}

function initCharts() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = isDark ? '#374151' : '#e2e8f0';
  const textColor = isDark ? '#9ca3af' : '#475569';
  const primaryColor = isDark ? '#818cf8' : '#4f46e5'; // Indigo
  const secondaryColor = '#06b6d4'; // Cyan
  
  // Sales Chart logic (Last 7 days dynamic calculation)
  const currency = store.getCurrencySymbol();
  const orders = store.getOrders();
  
  const dailySalesData = [0, 0, 0, 0, 0, 0, 0];
  const completedOrders = orders.filter(o => o.status === 'Delivered' || o.status === 'Shipped');
  
  completedOrders.forEach(o => {
    if (o.date) {
      const d = new Date(o.date.replace(' ', 'T'));
      const day = d.getDay(); // 0: Sunday, 1: Monday, ... 6: Saturday
      let idx = 0;
      if (day === 6) idx = 0; // Saturday
      else idx = day + 1;     // Sunday -> 1, Monday -> 2, etc.
      dailySalesData[idx] += o.total || 0;
    }
  });

  const salesCtx = document.getElementById('salesChart').getContext('2d');
  salesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
      labels: ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'],
      datasets: [{
        label: `المبيعات اليومية (${currency})`,
        data: dailySalesData,
        borderColor: primaryColor,
        backgroundColor: isDark ? 'rgba(129, 140, 248, 0.15)' : 'rgba(79, 70, 229, 0.05)',
        fill: true,
        tension: 0.4,
        borderWidth: 3,
        pointBackgroundColor: primaryColor
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            color: textColor,
            font: {
              family: 'Cairo'
            }
          }
        },
        y: {
          grid: {
            color: gridColor
          },
          ticks: {
            color: textColor,
            font: {
              family: 'Plus Jakarta Sans'
            }
          }
        }
      }
    }
  });

  // Category chart (Products count by category)
  const products = store.getProducts();
  const categoryCounts = {};
  products.forEach(p => {
    categoryCounts[p.category] = (categoryCounts[p.category] || 0) + p.stock;
  });

  const catLabels = Object.keys(categoryCounts);
  const catData = Object.values(categoryCounts);
  
  const categoryCtx = document.getElementById('categoryChart').getContext('2d');
  categoryChart = new Chart(categoryCtx, {
    type: 'doughnut',
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
          labels: {
            color: textColor,
            font: {
              family: 'Cairo',
              size: 11
            },
            padding: 15
          }
        }
      },
      cutout: '70%'
    }
  });
}
