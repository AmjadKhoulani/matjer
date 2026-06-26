import { store } from './store.js';
import { renderInventoryTable, initAdjustments } from './inventory.js';
import { initAccounting } from './accounting.js';
import { initDashboard } from './dashboard.js';

// Seed initial metadata if not present
const INITIAL_BRANDS = [
  { id: '1', name: 'أبل (Apple)', desc: 'الهواتف والساعات الذكية وملحقاتها' },
  { id: '2', name: 'نايكي (Nike)', desc: 'الأحذية الرياضية والملابس الكاجوال' },
  { id: '3', name: 'سوني (Sony)', desc: 'السماعات اللاسلكية وأجهزة الترفيه' },
  { id: '4', name: 'جي بي إل (JBL)', desc: 'مكبرات الصوت والأنظمة الصوتية' }
];

const INITIAL_UNITS = [
  { id: '1', name: 'قطعة (Piece)', code: 'pc' },
  { id: '2', name: 'طقم (Set)', code: 'set' },
  { id: '3', name: 'صندوق (Box)', code: 'box' }
];

const INITIAL_BATCHES = [
  { id: '2026-A', supplierName: 'مجموعة التكنولوجيا الرقمية', date: '2026-06-10', qty: 24, status: 'Active' },
  { id: '2026-B', supplierName: 'الشركة العالمية للأزياء', date: '2026-06-12', qty: 4, status: 'Active' },
  { id: '2026-C', supplierName: 'مكتبة العبيكان للتوزيع', date: '2026-06-15', qty: 40, status: 'Active' }
];

export function initERPFeatures() {
  // Initialize state
  if (!localStorage.getItem('ns_brands')) {
    localStorage.setItem('ns_brands', JSON.stringify(INITIAL_BRANDS));
  }
  if (!localStorage.getItem('ns_units')) {
    localStorage.setItem('ns_units', JSON.stringify(INITIAL_UNITS));
  }
  if (!localStorage.getItem('ns_batches')) {
    localStorage.setItem('ns_batches', JSON.stringify(INITIAL_BATCHES));
  }

  // 1. Brands Management Setup
  setupBrands();

  // 2. Units Management Setup
  setupUnits();

  // 3. Batches Management Setup
  setupBatches();

  // 4. Import Simulator Setup
  setupImportSimulator();

  // 5. Realtime Sales Counter Setup
  setupRealtimeCounter();

  // 6. Sales Return Setup
  setupSalesReturn();
}

/* ==========================================================================
   1. Brands Management
   ========================================================================== */
function setupBrands() {
  const table = document.getElementById('brands-table-body');
  if (!table) return;

  const renderBrands = () => {
    const brands = JSON.parse(localStorage.getItem('ns_brands')) || [];
    const products = store.getProducts();

    table.innerHTML = brands.map(b => {
      // count matching products
      const count = products.filter(p => p.name.includes(b.name.split(' ')[0]) || p.category.includes(b.desc.split(' ')[0])).length;
      return `
        <tr>
          <td style="font-weight: 700; color: var(--text-primary);">${b.name}</td>
          <td>${b.desc}</td>
          <td style="text-align: center; font-family: var(--font-english); font-weight: 700;">${count}</td>
          <td style="text-align: center;">
            <button class="btn btn-secondary btn-sm delete-brand-btn" data-id="${b.id}" style="color:#fff; background-color:hsla(var(--danger),1); border:none; width: 32px; height: 32px; padding:0; display: inline-flex; align-items:center; justify-content:center;">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
    }).join('');

    table.querySelectorAll('.delete-brand-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = btn.getAttribute('data-id');
        let brands = JSON.parse(localStorage.getItem('ns_brands')) || [];
        brands = brands.filter(b => b.id !== id);
        localStorage.setItem('ns_brands', JSON.stringify(brands));
        renderBrands();
      });
    });
  };

  renderBrands();

  const form = document.getElementById('add-brand-form');
  if (form && !form.dataset.listener) {
    form.dataset.listener = 'true';
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const name = document.getElementById('brand-name').value;
      const desc = document.getElementById('brand-desc').value;

      const brands = JSON.parse(localStorage.getItem('ns_brands')) || [];
      brands.push({ id: Date.now().toString(), name, desc });
      localStorage.setItem('ns_brands', JSON.stringify(brands));
      form.reset();
      renderBrands();
      store.addActivity('success', 'إضافة علامة تجارية', `تم تسجيل العلامة التجارية "${name}" بنظام المستودعات`);
    });
  }
}

/* ==========================================================================
   2. Units Management
   ========================================================================== */
function setupUnits() {
  const table = document.getElementById('units-table-body');
  if (!table) return;

  const renderUnits = () => {
    const units = JSON.parse(localStorage.getItem('ns_units')) || [];
    table.innerHTML = units.map(u => `
      <tr>
        <td style="font-weight: 700; color: var(--text-primary);">${u.name}</td>
        <td style="font-family: var(--font-english); font-weight: 700; text-align: center;">${u.code}</td>
        <td style="text-align: center;"><span class="badge badge-success">نشط</span></td>
        <td style="text-align: center;">
          <button class="btn btn-secondary btn-sm delete-unit-btn" data-id="${u.id}" style="color:#fff; background-color:hsla(var(--danger),1); border:none; width: 32px; height: 32px; padding:0; display: inline-flex; align-items:center; justify-content:center;">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `).join('');

    table.querySelectorAll('.delete-unit-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = btn.getAttribute('data-id');
        let units = JSON.parse(localStorage.getItem('ns_units')) || [];
        units = units.filter(u => u.id !== id);
        localStorage.setItem('ns_units', JSON.stringify(units));
        renderUnits();
      });
    });
  };

  renderUnits();

  const form = document.getElementById('add-unit-form');
  if (form && !form.dataset.listener) {
    form.dataset.listener = 'true';
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const name = document.getElementById('unit-name').value;
      const code = document.getElementById('unit-code').value;

      const units = JSON.parse(localStorage.getItem('ns_units')) || [];
      units.push({ id: Date.now().toString(), name, code });
      localStorage.setItem('ns_units', JSON.stringify(units));
      form.reset();
      renderUnits();
      store.addActivity('success', 'إضافة وحدة قياس', `تم تسجيل وحدة القياس الجديدة "${name}" بنظام المنتجات`);
    });
  }
}

/* ==========================================================================
   3. Batches Management
   ========================================================================== */
function setupBatches() {
  const table = document.getElementById('batches-table-body');
  if (!table) return;

  const renderBatches = () => {
    const batches = JSON.parse(localStorage.getItem('ns_batches')) || [];
    table.innerHTML = batches.map(b => `
      <tr>
        <td style="font-family: var(--font-english); font-weight: 800; color: var(--text-primary);">${b.id}</td>
        <td>${b.supplierName}</td>
        <td style="font-family: var(--font-english);">${b.date}</td>
        <td style="text-align: center; font-family: var(--font-english); font-weight: 700;">${b.qty}</td>
        <td style="text-align: center;"><span class="badge badge-success">${b.status}</span></td>
      </tr>
    `).join('');
  };

  renderBatches();
}

/* ==========================================================================
   4. Import Simulator
   ========================================================================== */
function setupImportSimulator() {
  const btn = document.getElementById('btn-import-submit');
  const uploadArea = document.getElementById('import-upload-area');
  const fileInput = document.getElementById('import-file-input');
  const typeSelect = document.getElementById('import-type-select');

  if (!btn || !uploadArea || !fileInput) return;

  uploadArea.addEventListener('click', () => {
    fileInput.click();
  });

  fileInput.addEventListener('change', () => {
    if (fileInput.files.length > 0) {
      uploadArea.innerHTML = `
        <i class="fas fa-file-excel" style="font-size: 40px; color: #2e7d32; margin-bottom: 12px;"></i>
        <div style="font-weight: 700; color: var(--text-primary);">${fileInput.files[0].name}</div>
        <div style="font-size:11px; color:var(--text-muted); margin-top: 4px;">Size: ${(fileInput.files[0].size / 1024).toFixed(1)} KB</div>
      `;
    }
  });

  btn.addEventListener('click', (e) => {
    e.preventDefault();
    if (fileInput.files.length === 0) {
      alert('الرجاء اختيار ملف Excel أو CSV أولاً للاستيراد.');
      return;
    }

    const type = typeSelect.value;
    const typeLabelMap = {
      'products': 'المنتجات',
      'products-update': 'تحديث أرصدة المخزون',
      'purchases': 'فواتير الشراء الموردة',
      'sales': 'فواتير المبيعات الصادرة'
    };

    // Show progress bar overlay
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); display:flex; align-items:center; justify-content:center; z-index:9999; backdrop-filter:blur(3px);';
    overlay.innerHTML = `
      <div style="background:var(--bg-secondary); border-radius:var(--border-radius-md); padding: 30px; width: 400px; text-align:center; box-shadow:var(--shadow-lg);">
        <i class="fas fa-spinner fa-spin" style="font-size:36px; color:hsla(var(--primary), 1); margin-bottom:16px;"></i>
        <div style="font-weight:700; font-size:16px; color:var(--text-primary); margin-bottom: 8px;">جاري معالجة واستيراد البيانات...</div>
        <div style="font-size:12px; color:var(--text-muted); margin-bottom: 16px;">الرجاء عدم إغلاق هذه الصفحة حتى تكتمل المزامنة</div>
        <div style="background:var(--bg-tertiary); border-radius:10px; height:8px; width:100%; overflow:hidden;">
          <div id="import-progress-bar" style="background:hsla(var(--primary),1); width:0%; height:100%; transition: width 0.1s;"></div>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);

    const progressBar = document.getElementById('import-progress-bar');
    let progress = 0;
    const interval = setInterval(() => {
      progress += 5;
      if (progressBar) progressBar.style.width = `${progress}%`;

      if (progress >= 100) {
        clearInterval(interval);
        document.body.removeChild(overlay);

        // Execute changes depending on type
        let importedCount = 0;
        if (type === 'products') {
          // Add a new mock product
          const products = store.getProducts();
          const newId = (products.length + 1).toString();
          const newProduct = {
            id: newId,
            sku: `NS-IMP-${newId}`,
            name: `منتج مستورد من شيت إكسيل #${newId}`,
            category: 'إلكترونيات',
            price: 150,
            cost: 95,
            stock: 0, // must default to 0 stock
            minStock: 5,
            status: 'Out of Stock',
            salesChannels: ['ecommerce', 'pos']
          };
          products.push(newProduct);
          store.saveProducts(products);
          renderInventoryTable();
          importedCount = 1;
        } else if (type === 'products-update') {
          // Add random stock to Asus Laptop
          const products = store.getProducts();
          const laptop = products.find(p => p.sku === 'NS-ASUROG-07');
          if (laptop) {
            laptop.stock += 15;
            laptop.status = 'In Stock';
            store.saveProducts(products);
            renderInventoryTable();
            importedCount = 15;
          }
        } else if (type === 'purchases') {
          const purchaseInvoices = JSON.parse(localStorage.getItem('ns_purchase_invoices')) || [];
          const newId = (2000 + purchaseInvoices.length + 1).toString();
          purchaseInvoices.unshift({
            id: newId,
            supplierId: '1',
            supplierName: 'مجموعة التكنولوجيا الرقمية',
            date: new Date().toISOString(),
            total: 3500,
            status: 'Paid',
            items: [{ productId: '4', productName: 'سماعات سوني WH-1000XM5', sku: 'NS-SNYWH-04', quantity: 10, cost: 260 }]
          });
          localStorage.setItem('ns_purchase_invoices', JSON.stringify(purchaseInvoices));
          // update stock
          store.updateProductStock('4', 10, 'وارد استيراد إكسيل خارجي');
          importedCount = 10;
        } else if (type === 'sales') {
          const orders = store.getOrders();
          const newId = (orders.length + 1001).toString();
          orders.unshift({
            id: newId,
            customerName: 'عميل مستورد من إكسيل',
            date: new Date().toISOString(),
            status: 'Delivered',
            total: 700,
            items: [{ productId: '4', quantity: 2, price: 350 }]
          });
          store.saveOrders(orders);
          // deduct stock
          store.updateProductStock('4', -2, 'مبيعات مستوردة إكسيل خارجي');
          importedCount = 2;
        }

        store.addActivity('success', 'استيراد ناجح', `تم استيراد البيانات لقسم ${typeLabelMap[type]} بنجاح من الملف`);
        alert(`تمت معالجة الملف واستيراد البيانات بنجاح!\nالنوع: ${typeLabelMap[type]}\nتم استيراد/تحديث المعاملات المتأثرة.`);
        
        // Reset file input
        fileInput.value = '';
        uploadArea.innerHTML = `
          <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: var(--text-muted); margin-bottom: 8px;"></i>
          <div style="font-weight: 700; color: var(--text-primary);">اختر ملف Excel أو CSV للاستيراد</div>
          <div style="font-size:11px; color:var(--text-muted); margin-top:4px;">أو قم بسحب وإلقاء الملف هنا مباشرة</div>
        `;

        initDashboard();
      }
    }, 100);
  });
}

/* ==========================================================================
   5. Realtime Sales Counter
   ========================================================================== */
let counterInterval = null;
let simulatedSalesTotal = 15840;
let simulatedOrdersCount = 28;

function setupRealtimeCounter() {
  const totalVal = document.getElementById('realtime-total-sales');
  const countVal = document.getElementById('realtime-orders-count');
  const list = document.getElementById('realtime-orders-feed');

  if (!totalVal || !countVal || !list) return;

  // Clear existing interval if set
  if (counterInterval) clearInterval(counterInterval);

  // Set initial text
  totalVal.innerText = `$${simulatedSalesTotal.toLocaleString()}`;
  countVal.innerText = simulatedOrdersCount;

  // incoming order customer pool
  const customers = ['هيثم الجاسر', 'رنا سليمان', 'رامي الحمصي', 'منار الخضر', 'خليل طه', 'وسيم الأخرس', 'فاديا عثمان'];
  const products = [
    { name: 'آيفون 15 برو ماكس', price: 1200 },
    { name: 'ساعة آبل الجيل التاسع', price: 420 },
    { name: 'سماعات سوني XM5', price: 350 },
    { name: 'كيندل لتصفح الكتب', price: 130 },
    { name: 'مكبر صوت جي بي إل', price: 160 }
  ];

  counterInterval = setInterval(() => {
    // Only simulate if the section is currently active
    const activeSection = document.querySelector('.view-section.active');
    if (!activeSection || activeSection.id !== 'realtime-counter-section') return;

    // Simulate new sale
    const randCust = customers[Math.floor(Math.random() * customers.length)];
    const randProd = products[Math.floor(Math.random() * products.length)];
    const randQty = Math.floor(Math.random() * 2) + 1;
    const orderTotal = randProd.price * randQty;

    simulatedSalesTotal += orderTotal;
    simulatedOrdersCount += 1;

    totalVal.innerText = `$${simulatedSalesTotal.toLocaleString()}`;
    countVal.innerText = simulatedOrdersCount;

    // Add list item with glowing animation
    const timeStr = new Date().toLocaleTimeString('ar-SA', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    const li = document.createElement('div');
    li.style.cssText = 'padding:12px; background:var(--bg-tertiary); border:1px solid var(--border-color); border-radius:var(--border-radius-xs); display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; animation: incomingOrder 0.5s ease; border-inline-start: 4px solid #6f42c1;';
    li.innerHTML = `
      <div>
        <div style="font-weight: 700; color: var(--text-primary); font-size: 13px;">طلب جديد من: ${randCust}</div>
        <div style="font-size:11px; color:var(--text-muted);">${randProd.name} (عدد ${randQty}) - ${timeStr}</div>
      </div>
      <div style="font-family: var(--font-english); font-weight: 700; color: hsla(260, 60%, 50%, 1); font-size: 14px;">+$${orderTotal}</div>
    `;

    // Prepend and limit list to 6 items
    list.insertBefore(li, list.firstChild);
    if (list.children.length > 6) {
      list.removeChild(list.lastChild);
    }

    // Dynamic animation keyframes injection if not already in document
    if (!document.getElementById('realtime-keyframe-style')) {
      const style = document.createElement('style');
      style.id = 'realtime-keyframe-style';
      style.innerHTML = `
        @keyframes incomingOrder {
          0% { opacity: 0; transform: translateY(-10px); background-color: rgba(111, 66, 193, 0.15); box-shadow: 0 0 12px rgba(111, 66, 193, 0.3); }
          100% { opacity: 1; transform: translateY(0); }
        }
      `;
      document.head.appendChild(style);
    }
  }, 7000); // simulation interval: every 7 seconds
}

/* ==========================================================================
   6. Sales Returns
   ========================================================================== */
function setupSalesReturn() {
  const tbody = document.getElementById('returns-table-body');
  if (!tbody) return;

  const renderReturnsList = () => {
    const orders = store.getOrders();
    const products = store.getProducts();

    tbody.innerHTML = orders.map(order => {
      const formattedDate = new Date(order.date).toLocaleDateString('ar-SA', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
      });

      const isCancelled = order.status === 'Cancelled';
      const statusText = isCancelled ? 'مرتجعة / ملغاة' : 'مكتملة ومقبوضة';
      const statusClass = isCancelled ? 'badge-danger' : 'badge-success';

      const actionButtonHtml = isCancelled ? `
        <span style="font-size:12px; color:var(--text-muted); font-weight:600;"><i class="fas fa-check"></i> تم الترجيع</span>
      ` : `
        <button class="btn btn-secondary btn-sm return-order-btn" data-id="${order.id}" style="color:#fff; background-color:hsla(260, 60%, 50%, 1); border:none; font-family: var(--font-arabic);">
          <i class="fas fa-undo"></i> تسجيل مرتجع
        </button>
      `;

      return `
        <tr>
          <td style="font-family: var(--font-english); font-weight:700;">#INV-${order.id}</td>
          <td>
            <div style="font-weight:600;">${order.customerName}</div>
            <div style="font-size:11px; color:var(--text-muted);">${formattedDate}</div>
          </td>
          <td style="font-family: var(--font-english); font-weight:700; color:hsla(var(--primary), 1);">$${order.total.toLocaleString()}</td>
          <td><span class="badge ${statusClass}">${statusText}</span></td>
          <td style="text-align: center;">${actionButtonHtml}</td>
        </tr>
      `;
    }).join('');

    tbody.querySelectorAll('.return-order-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = btn.getAttribute('data-id');
        if (confirm('هل أنت متأكد من رغبتك في إرجاع الفاتورة بالكامل؟ سيتم إلغاء الفاتورة وإرجاع كميات المنتجات إلى المستودع فوراً.')) {
          executeOrderReturn(id);
        }
      });
    });
  };

  renderReturnsList();

  function executeOrderReturn(orderId) {
    const orders = store.getOrders();
    const order = orders.find(o => o.id === orderId);
    if (!order) return;

    // 1. Mark status as Cancelled
    store.updateOrderStatus(orderId, 'Cancelled');

    // 2. Return quantity of items to stock
    order.items.forEach(item => {
      store.updateProductStock(item.productId, item.quantity, `إرجاع مرتجع مبيعات الفاتورة #INV-${order.id}`);
    });

    // 3. Refresh lists
    renderReturnsList();
    initAccounting();
    initDashboard();

    alert('تم تسجيل عملية المرتجع بنجاح! تم إلغاء الفاتورة وإرجاع الكميات المخصومة إلى المخازن.');
  }
}
