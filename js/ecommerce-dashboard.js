import { store } from './store.js';

let ecommerceSalesChart = null;
let trafficSourceChart = null;

// Initial Review Seeds
const INITIAL_REVIEWS = [
  { id: '1', customerName: 'أحمد الخطيب', productSku: 'NS-ASUROG-07', productName: 'لابتوب أسوس روج ستريكس G16 للألعاب', rating: 5, date: '2026-06-20T10:30:00Z', text: 'لابتوب ألعاب خارق جداً، الأداء ممتاز والحرارة مقبولة مع الألعاب الثقيلة وتصميم رائع جداً.', status: 'Approved' },
  { id: '2', customerName: 'سارة الزعبي', productSku: 'NS-IPDM4-09', productName: 'آيباد برو M4 شاشة أوليد 13 بوصة', rating: 5, date: '2026-06-21T14:15:00Z', text: 'شاشة الآيباد أوليد خرافية والألوان مشبعة بشكل لا يصدق، ونحافته مذهلة، أنصح به بشدة للدراسة والتصميم.', status: 'Approved' },
  { id: '3', customerName: 'محمد حمصي', productSku: 'NS-GOP12-10', productName: 'كاميرا جوبرو هيرو 12 بلاك الرياضية', rating: 4, date: '2026-06-22T09:40:00Z', text: 'كاميرا ممتازة للتصوير تحت الماء والرحلات البرية، التثبيت البصري رائع ولكن البطارية تفرغ بسرعة في الجودة العالية.', status: 'Approved' },
  { id: '4', customerName: 'خالد الحربي', productSku: 'NS-SAMS24-08', productName: 'هاتف سامسونج جالكسي S24 ألترا 512 جيجا', rating: 4, date: '2026-06-23T11:20:00Z', text: 'الهاتف ممتاز والبطارية تدوم طويلاً، لكن كاميرا التقريب البصري تحتاج إضاءة جيدة لتظهر تفاصيل واضحة.', status: 'Pending' }
];

// Initial Coupon Seeds
const INITIAL_COUPONS = [
  { id: '1', code: 'EID2026', type: 'percentage', value: 20, minPurchase: 100, expiry: '2026-12-31', usageCount: 45, status: 'Active' },
  { id: '2', code: 'WELCOME10', type: 'percentage', value: 10, minPurchase: 50, expiry: '2026-12-31', usageCount: 124, status: 'Active' },
  { id: '3', code: 'SHIPFREE', type: 'fixed', value: 15, minPurchase: 200, expiry: '2026-09-30', usageCount: 89, status: 'Active' }
];

export function initEcommerceDashboard() {
  renderEcommerceKPIs();
  initEcommerceCharts();
  renderTopViewedProducts();
  renderTopCoupons();

  // Redraw charts on theme change
  window.addEventListener('theme-changed', () => {
    if (ecommerceSalesChart && trafficSourceChart) {
      ecommerceSalesChart.destroy();
      trafficSourceChart.destroy();
      initEcommerceCharts();
    }
  });
}

function renderEcommerceKPIs() {
  const orders = store.getOrders();
  
  // Calculate eCommerce sales (all online orders in system are considered eCommerce)
  // Let's filter orders that are Delivered or Shipped
  const onlineSales = orders
    .filter(o => o.status === 'Delivered' || o.status === 'Shipped')
    .reduce((sum, o) => sum + o.total, 0);

  // Set KPIs on UI
  const salesEl = document.getElementById('eco-kpi-sales');
  const aovEl = document.getElementById('eco-kpi-aov');
  const convEl = document.getElementById('eco-kpi-conversion');
  const abanEl = document.getElementById('eco-kpi-abandoned');
  const trafficEl = document.getElementById('eco-kpi-traffic');

  const deliveredCount = orders.filter(o => o.status === 'Delivered' || o.status === 'Shipped').length;
  const aov = deliveredCount > 0 ? (onlineSales / deliveredCount) : 0;
  const currency = store.getCurrencySymbol();

  if (salesEl) salesEl.innerText = `${onlineSales.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
  if (aovEl) aovEl.innerText = `${aov.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${currency}`;
  if (convEl) convEl.innerText = '2.85%';
  if (abanEl) abanEl.innerText = '64.2%';
  if (trafficEl) trafficEl.innerText = '12,850';
}

function initEcommerceCharts() {
  const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  const gridColor = isDark ? '#374151' : '#e2e8f0';
  const textColor = isDark ? '#9ca3af' : '#475569';
  const primaryColor = 'hsla(260, 60%, 50%, 1)'; // Purple theme for eCommerce
  const secondaryColor = '#06b6d4'; // Cyan

  const salesCanvas = document.getElementById('ecommerceSalesChart');
  const trafficCanvas = document.getElementById('trafficSourceChart');

  if (!salesCanvas || !trafficCanvas) return;

  const salesCtx = salesCanvas.getContext('2d');
  const currency = store.getCurrencySymbol();
  ecommerceSalesChart = new Chart(salesCtx, {
    type: 'line',
    data: {
      labels: ['السبت', 'الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة'],
      datasets: [
        {
          label: `المبيعات الإلكترونية (${currency})`,
          data: [420, 850, 600, 1100, 3200, 1400, 2850],
          borderColor: primaryColor,
          backgroundColor: isDark ? 'rgba(111, 66, 193, 0.15)' : 'rgba(111, 66, 193, 0.05)',
          fill: true,
          tension: 0.4,
          borderWidth: 3,
          pointBackgroundColor: primaryColor,
          yAxisID: 'y'
        },
        {
          label: 'معدل التحويل (%)',
          data: [2.1, 2.4, 2.0, 2.9, 3.8, 2.7, 3.2],
          borderColor: '#10b981',
          backgroundColor: 'transparent',
          fill: false,
          tension: 0.3,
          borderWidth: 2,
          pointBackgroundColor: '#10b981',
          yAxisID: 'y1'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            color: textColor,
            font: { family: 'Cairo', size: 12 }
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { color: textColor, font: { family: 'Cairo' } }
        },
        y: {
          type: 'linear',
          display: true,
          position: 'right',
          grid: { color: gridColor },
          ticks: { color: textColor }
        },
        y1: {
          type: 'linear',
          display: true,
          position: 'left',
          grid: { drawOnChartArea: false },
          ticks: { 
            color: textColor,
            callback: function(value) { return value + '%'; }
          }
        }
      }
    }
  });

  const trafficCtx = trafficCanvas.getContext('2d');
  trafficSourceChart = new Chart(trafficCtx, {
    type: 'doughnut',
    data: {
      labels: ['منصات التواصل الاجتماعي', 'محركات البحث SEO', 'زيارات مباشرة Direct', 'الحملات الإعلانية المدفوعة', 'البريد الإلكتروني'],
      datasets: [{
        data: [45, 25, 15, 10, 5],
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
            font: { family: 'Cairo', size: 11 },
            padding: 10
          }
        }
      },
      cutout: '70%'
    }
  });
}

function renderTopViewedProducts() {
  const tbody = document.getElementById('eco-top-viewed-tbody');
  if (!tbody) return;

  const products = store.getProducts().slice(0, 4);
  const views = [4580, 3120, 2450, 1890];
  const conversionRates = ['3.15%', '2.84%', '3.50%', '2.10%'];

  tbody.innerHTML = products.map((p, i) => `
    <tr>
      <td>
        <div style="font-weight: 700; color: var(--text-primary);">${p.name}</div>
        <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${p.sku}</div>
      </td>
      <td style="font-family: var(--font-english); text-align: center; font-weight: 600;">${views[i].toLocaleString()}</td>
      <td style="font-family: var(--font-english); text-align: center; color: #10b981; font-weight: 700;">${conversionRates[i]}</td>
      <td>
        <span class="badge ${p.stock > p.minStock ? 'badge-success' : 'badge-warning'}">
          ${p.stock > 0 ? `متوفر (${p.stock})` : 'نفد'}
        </span>
      </td>
    </tr>
  `).join('');
}

function renderTopCoupons() {
  const tbody = document.getElementById('eco-top-coupons-tbody');
  if (!tbody) return;

  const coupons = JSON.parse(localStorage.getItem('ns_coupons')) || INITIAL_COUPONS;
  const currency = store.getCurrencySymbol();
  
  tbody.innerHTML = coupons.map(c => {
    const discountText = c.type === 'percentage' ? `${c.value}%` : `${c.value} ${currency}`;
    return `
      <tr>
        <td><strong style="font-family: var(--font-english); color: hsla(260, 60%, 50%, 1);">${c.code}</strong></td>
        <td style="font-family: var(--font-english); text-align: center; font-weight: 700;">${discountText}</td>
        <td style="font-family: var(--font-english); text-align: center; font-weight: 600;">${c.usageCount} استخدام</td>
        <td><span class="badge badge-success">نشط</span></td>
      </tr>
    `;
  }).join('');
}

/* ==========================================================================
   Marketing & Coupons Management
   ========================================================================== */
export function initCoupons() {
  if (!localStorage.getItem('ns_coupons')) {
    localStorage.setItem('ns_coupons', JSON.stringify(INITIAL_COUPONS));
  }

  renderCouponsTable();

  const form = document.getElementById('add-coupon-form');
  if (form && !form.dataset.listener) {
    form.dataset.listener = 'true';
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const code = document.getElementById('coupon-code-input').value.trim().toUpperCase();
      const type = document.getElementById('coupon-type-select').value;
      const value = parseFloat(document.getElementById('coupon-value-input').value) || 0;
      const minPurchase = parseFloat(document.getElementById('coupon-min-input').value) || 0;
      const expiry = document.getElementById('coupon-expiry-input').value;

      const coupons = JSON.parse(localStorage.getItem('ns_coupons')) || [];
      
      // Avoid duplicate codes
      if (coupons.some(c => c.code === code)) {
        alert('رمز الكوبون هذا مسجل بالفعل!');
        return;
      }

      const newCoupon = {
        id: Date.now().toString(),
        code,
        type,
        value,
        minPurchase,
        expiry: expiry || '2026-12-31',
        usageCount: 0,
        status: 'Active'
      };

      coupons.push(newCoupon);
      localStorage.setItem('ns_coupons', JSON.stringify(coupons));
      
      form.reset();
      renderCouponsTable();
      store.addActivity('success', 'إضافة كوبون خصم', `تم إنشاء كوبون الخصم الجديد "${code}" في متجر التجارة الإلكترونية`);
    });
  }
}

function renderCouponsTable() {
  const tbody = document.getElementById('eco-coupons-table-body');
  if (!tbody) return;

  const coupons = JSON.parse(localStorage.getItem('ns_coupons')) || [];

  if (coupons.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center" style="padding: 30px; color: var(--text-muted);">لا توجد كوبونات خصم حالية.</td></tr>`;
    return;
  }

  const currency = store.getCurrencySymbol();
  tbody.innerHTML = coupons.map(c => {
    const discountText = c.type === 'percentage' ? `${c.value}%` : `${c.value} ${currency}`;
    const minText = c.minPurchase > 0 ? `${c.minPurchase} ${currency}` : 'لا يوجد';
    
    return `
      <tr>
        <td><strong style="font-family: var(--font-english); color: hsla(260, 60%, 50%, 1); font-size: 15px;">${c.code}</strong></td>
        <td>${c.type === 'percentage' ? 'نسبة مئوية' : 'مبلغ ثابت'}</td>
        <td style="font-family: var(--font-english); font-weight: 700; text-align: center;">${discountText}</td>
        <td style="font-family: var(--font-english); text-align: center;">${minText}</td>
        <td style="font-family: var(--font-english); text-align: center;">${c.expiry}</td>
        <td style="font-family: var(--font-english); text-align: center; font-weight: 700;">${c.usageCount}</td>
        <td style="text-align: center;">
          <button class="btn btn-secondary btn-sm delete-coupon-btn" data-id="${c.id}" style="color:#fff; background-color:hsla(var(--danger),1); border:none; width: 32px; height: 32px; padding:0; display: inline-flex; align-items:center; justify-content:center;">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');

  tbody.querySelectorAll('.delete-coupon-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = btn.getAttribute('data-id');
      let coupons = JSON.parse(localStorage.getItem('ns_coupons')) || [];
      const deleted = coupons.find(c => c.id === id);
      coupons = coupons.filter(c => c.id !== id);
      localStorage.setItem('ns_coupons', JSON.stringify(coupons));
      renderCouponsTable();
      if (deleted) {
        store.addActivity('warning', 'حذف كوبون خصم', `تم إيقاف وحذف كوبون الخصم "${deleted.code}" من المتجر`);
      }
    });
  });
}

/* ==========================================================================
   Customer Feedback & Review Moderation
   ========================================================================== */
export function initReviews() {
  if (!localStorage.getItem('ns_reviews')) {
    localStorage.setItem('ns_reviews', JSON.stringify(INITIAL_REVIEWS));
  }

  renderReviewsTable();
}

function renderReviewsTable() {
  const tbody = document.getElementById('eco-reviews-table-body');
  if (!tbody) return;

  const reviews = JSON.parse(localStorage.getItem('ns_reviews')) || [];

  if (reviews.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center" style="padding: 30px; color: var(--text-muted);">لا توجد مراجعات مضافة حالياً.</td></tr>`;
    return;
  }

  tbody.innerHTML = reviews.map(r => {
    const dateFormatted = new Date(r.date).toLocaleDateString('ar-SA');
    const stars = Array(5).fill(0).map((_, i) => `
      <i class="fas fa-star" style="color: ${i < r.rating ? '#f59e0b' : '#cbd5e1'}; font-size: 12px;"></i>
    `).join('');

    const statusBadgeClass = r.status === 'Approved' ? 'badge-success' : (r.status === 'Pending' ? 'badge-warning' : 'badge-danger');
    const statusText = r.status === 'Approved' ? 'معتمد' : (r.status === 'Pending' ? 'قيد الانتظار' : 'سبام / مرفوض');

    return `
      <tr>
        <td>
          <div style="font-weight: 700; color: var(--text-primary);">${r.customerName}</div>
          <div style="font-size: 11px; color: var(--text-muted);">${dateFormatted}</div>
        </td>
        <td>
          <div style="font-weight: 600; font-size: 12px;">${r.productName}</div>
          <div style="font-size: 10px; color: var(--text-muted); font-family: var(--font-english);">${r.productSku}</div>
        </td>
        <td style="text-align: center;">${stars}</td>
        <td style="max-width: 250px; font-size: 13px; line-height: 1.4;">${r.text}</td>
        <td><span class="badge ${statusBadgeClass}">${statusText}</span></td>
        <td>
          <div style="display: flex; gap: 6px;">
            ${r.status !== 'Approved' ? `
              <button class="btn btn-secondary btn-sm approve-review-btn" data-id="${r.id}" style="color:#fff; background-color:#10b981; border:none; height:30px; padding:0 8px; font-size:11px;">
                <i class="fas fa-check"></i> اعتماد
              </button>
            ` : ''}
            ${r.status !== 'Spam' ? `
              <button class="btn btn-secondary btn-sm spam-review-btn" data-id="${r.id}" style="color:#fff; background-color:#ef4444; border:none; height:30px; padding:0 8px; font-size:11px;">
                <i class="fas fa-ban"></i> حظر
              </button>
            ` : ''}
          </div>
        </td>
      </tr>
    `;
  }).join('');

  tbody.querySelectorAll('.approve-review-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      updateReviewStatus(id, 'Approved');
    });
  });

  tbody.querySelectorAll('.spam-review-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-id');
      updateReviewStatus(id, 'Spam');
    });
  });
}

function updateReviewStatus(id, status) {
  const reviews = JSON.parse(localStorage.getItem('ns_reviews')) || [];
  const r = reviews.find(item => item.id === id);
  if (r) {
    r.status = status;
    localStorage.setItem('ns_reviews', JSON.stringify(reviews));
    renderReviewsTable();
    store.addActivity(status === 'Approved' ? 'success' : 'warning', 'مراجعات المنتجات', `تم ${status === 'Approved' ? 'اعتماد ونشر' : 'حظر وإيقاف'} تقييم العميل "${r.customerName}"`);
  }
}
