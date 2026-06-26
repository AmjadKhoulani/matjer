import { store } from './store.js';

export function initAccounting() {
  renderInvoices();
  renderPurchaseInvoices();
  renderFinanceLedger();
  setupInvoiceSearch();
  initTaxReport();
  setupInvoiceTabs();
}

function renderInvoices() {
  const orders = store.getOrders();
  const tbody = document.getElementById('accounting-invoices-table-body');
  if (!tbody) return;

  if (orders.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" class="text-center" style="padding:20px; color:var(--text-muted);">لا توجد فواتير صادرة بعد.</td></tr>`;
    return;
  }

  tbody.innerHTML = orders.map(order => {
    const formattedDate = new Date(order.date).toLocaleDateString('ar-SA', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
    
    // Status badges translation
    const statusMap = {
      'Pending': { text: 'بانتظار التحصيل', class: 'badge-warning' },
      'Shipped': { text: 'مقبوضة (تم الشحن)', class: 'badge-info' },
      'Delivered': { text: 'مقبوضة (مكتملة)', class: 'badge-success' },
      'Cancelled': { text: 'مرتجعة / ملغاة', class: 'badge-danger' }
    };
    
    const status = statusMap[order.status] || { text: order.status, class: 'badge-info' };

    return `
      <tr>
        <td style="font-family: var(--font-english); font-weight:700;">#INV-${order.id}</td>
        <td>
          <div style="font-weight:600;">${order.customerName}</div>
          <div style="font-size:11px; color:var(--text-muted);">${formattedDate}</div>
        </td>
        <td style="font-family: var(--font-english); font-weight:700; color:hsla(var(--primary), 1);">$${order.total.toLocaleString()}</td>
        <td><span class="badge ${status.class}">${status.text}</span></td>
        <td>
          <button class="btn btn-secondary btn-sm btn-print-inv" data-id="${order.id}">
            <i class="fas fa-print"></i> طباعة الفاتورة
          </button>
        </td>
      </tr>
    `;
  }).join('');

  // Bind print event listener
  tbody.querySelectorAll('.btn-print-inv').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const orderId = e.currentTarget.getAttribute('data-id');
      triggerInvoicePrint(orderId);
    });
  });
}

function triggerInvoicePrint(orderId) {
  // Reset custom purchase invoice labels to default sales view
  const titleLabel = document.getElementById('invoice-title-label');
  const typeLabel = document.getElementById('invoice-type-label');
  if (titleLabel) titleLabel.innerText = 'فاتورة مبيعات معتمدة';
  if (typeLabel) typeLabel.innerText = 'العميل';

  // Leverage existing invoice modal in store-manager.php
  const orders = store.getOrders();
  const order = orders.find(o => o.id === orderId);
  if (!order) return;
  
  const products = store.getProducts();
  const dateObj = new Date(order.date);
  const formattedDate = dateObj.toLocaleDateString('ar-SA') + ' ' + dateObj.toLocaleTimeString('ar-SA');
  
  document.getElementById('invoice-order-id').innerText = order.id;
  document.getElementById('invoice-date').innerText = formattedDate;
  document.getElementById('invoice-cust-name').innerText = order.customerName;
  
  const statusMap = {
    'Pending': 'قيد المراجعة',
    'Shipped': 'تم الشحن',
    'Delivered': 'مكتمل ومقبوض',
    'Cancelled': 'ملغي / مرتجع'
  };
  document.getElementById('invoice-status').innerText = statusMap[order.status] || order.status;
  document.getElementById('invoice-status').className = `badge ` + (
    order.status === 'Delivered' ? 'badge-success' :
    order.status === 'Pending' ? 'badge-warning' :
    order.status === 'Cancelled' ? 'badge-danger' : 'badge-info'
  );

  const itemsBody = document.getElementById('invoice-items-body');
  itemsBody.innerHTML = order.items.map(item => {
    const product = products.find(p => p.id === item.productId) || { name: 'منتج غير معروف', sku: 'N/A' };
    return `
      <tr>
        <td>
          <div style="font-weight: 700; color: var(--text-primary);">${product.name}</div>
          <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${product.sku}</div>
        </td>
        <td style="font-family: var(--font-english);">$${item.price}</td>
        <td style="text-align: center; font-family: var(--font-english);">${item.quantity}</td>
        <td style="font-family: var(--font-english); font-weight: 700;">$${(item.price * item.quantity)}</td>
      </tr>
    `;
  }).join('');

  // Tax computation
  const subtotal = order.total / 1.15;
  const tax = order.total - subtotal;
  
  document.getElementById('invoice-subtotal').innerText = `$${subtotal.toFixed(2)}`;
  document.getElementById('invoice-tax').innerText = `$${tax.toFixed(2)}`;
  document.getElementById('invoice-total').innerText = `$${order.total.toFixed(2)}`;

  // Show modal
  const modal = document.getElementById('order-detail-modal');
  if (modal) {
    modal.classList.add('active');
  }
}

function renderFinanceLedger() {
  const orders = store.getOrders();
  const purchaseInvoices = JSON.parse(localStorage.getItem('ns_purchase_invoices')) || [];

  // Revenues: Sum of Completed or Shipped orders
  const revenue = orders
    .filter(o => o.status === 'Delivered' || o.status === 'Shipped')
    .reduce((sum, o) => sum + o.total, 0);

  // Expenses: Sum of all purchase invoices + mock logistics cost
  const purchaseExpenses = purchaseInvoices.reduce((sum, inv) => sum + inv.total, 0);
  const logisticsMockCost = 450; // Mock warehouse electric bills, internet, rent
  const expenses = purchaseExpenses + logisticsMockCost;

  const netProfit = revenue - expenses;

  // Set metrics on screen
  const revEl = document.getElementById('finance-total-revenue');
  const expEl = document.getElementById('finance-total-expenses');
  const profitEl = document.getElementById('finance-net-profit');

  if (revEl) revEl.innerText = `$${revenue.toLocaleString()}`;
  if (expEl) expEl.innerText = `$${expenses.toLocaleString()}`;
  if (profitEl) {
    profitEl.innerText = `$${netProfit.toLocaleString()}`;
    if (netProfit < 0) {
      profitEl.style.color = 'hsla(var(--danger), 1)';
    } else {
      profitEl.style.color = 'hsla(var(--success), 1)';
    }
  }
}

function setupInvoiceSearch() {
  const searchInput = document.getElementById('accounting-invoices-search');
  if (!searchInput) return;

  searchInput.addEventListener('input', () => {
    const val = searchInput.value.toLowerCase();
    const rows = document.querySelectorAll('#accounting-invoices-table-body tr');
    
    rows.forEach(row => {
      const text = row.innerText.toLowerCase();
      if (text.includes(val)) {
        row.style.display = '';
      } else {
        row.style.display = 'none';
      }
    });
  });
}

export function initTaxReport() {
  const orders = store.getOrders();
  const purchaseInvoices = JSON.parse(localStorage.getItem('ns_purchase_invoices')) || [];

  // Output tax: 15% of all Delivered or Shipped orders
  const revenue = orders
    .filter(o => o.status === 'Delivered' || o.status === 'Shipped')
    .reduce((sum, o) => sum + o.total, 0);
  const outputTax = revenue * 0.15;

  // Input tax: 15% of all purchase invoice values from suppliers
  const totalPurchasesCost = purchaseInvoices.reduce((sum, inv) => sum + inv.total, 0);
  const inputTax = totalPurchasesCost * 0.15;

  const netTax = outputTax - inputTax;

  const outEl = document.getElementById('tax-output-val');
  const inEl = document.getElementById('tax-input-val');
  const netEl = document.getElementById('tax-net-val');
  
  if (outEl) outEl.innerText = `$${outputTax.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  if (inEl) inEl.innerText = `$${inputTax.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
  if (netEl) {
    netEl.innerText = `$${netTax.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    if (netTax < 0) {
      netEl.style.color = 'hsla(var(--danger), 1)';
    } else {
      netEl.style.color = 'hsla(var(--success), 1)';
    }
  }

  // Load and update filing status
  const filingStatusEl = document.getElementById('tax-filing-status');
  const btnFileTax = document.getElementById('btn-file-tax-return');

  if (filingStatusEl) {
    const isFiled = localStorage.getItem('ns_tax_filed') === 'true';
    if (isFiled) {
      filingStatusEl.innerText = 'تم تقديم الإقرار واعتماده';
      filingStatusEl.className = 'badge badge-success';
      if (btnFileTax) {
        btnFileTax.disabled = true;
        btnFileTax.innerHTML = `<i class="fas fa-check"></i> تم تقديم الإقرار الضريبي بنجاح`;
      }
    } else {
      filingStatusEl.innerText = 'مسودة جارية (غير مقدم بعد)';
      filingStatusEl.className = 'badge badge-warning';
      if (btnFileTax) {
        btnFileTax.disabled = false;
        btnFileTax.innerHTML = `<i class="fas fa-paper-plane"></i> تقديم الإقرار الضريبي واعتماده رسمياً`;
      }
    }
  }

  if (btnFileTax && !btnFileTax.dataset.listener) {
    btnFileTax.dataset.listener = 'true';
    btnFileTax.addEventListener('click', () => {
      if (confirm('هل أنت متأكد من رغبتك في تقديم الإقرار الضريبي الحالي لهيئة الزكاة والضريبة والجمارك؟ لا يمكن التعديل عليه بعد الاعتماد.')) {
        localStorage.setItem('ns_tax_filed', 'true');
        store.addActivity('success', 'تقديم الإقرار الضريبي', `تم تقديم الإقرار الضريبي للربع الثاني من عام 2026م بنجاح. صافي الضريبة: $${netTax.toFixed(2)}`);
        alert('تم تقديم الإقرار الضريبي واعتماده رسمياً بنجاح لدى الهيئة!');
        initTaxReport();
      }
    });
  }
}

/* --- PURCHASE INVOICES LEDGER & TABS HANDLERS --- */

function renderPurchaseInvoices() {
  let purchases = JSON.parse(localStorage.getItem('ns_purchase_invoices'));
  if (!purchases) {
    purchases = [
      { id: '1001', supplierId: '1', supplierName: 'مجموعة التكنولوجيا الرقمية', date: '2026-06-10T10:00:00Z', total: 22800, status: 'Paid', items: [{ productId: '1', productName: 'آيفون 15 برو ماكس 256 جيجا', sku: 'NS-IPH15-01', quantity: 24, cost: 950 }] },
      { id: '1002', supplierId: '2', supplierName: 'الشركة العالمية للأزياء', date: '2026-06-12T14:30:00Z', total: 1280, status: 'Paid', items: [{ productId: '2', productName: 'ساعة آبل الجيل التاسع', sku: 'NS-APW09-02', quantity: 4, cost: 320 }] },
      { id: '1003', supplierId: '3', supplierName: 'مكتبة العبيكان للتوزيع', date: '2026-06-15T09:15:00Z', total: 3800, status: 'Paid', items: [{ productId: '5', productName: 'جهاز كيندل لتصفح الكتب الإلكترونية', sku: 'NS-KNDLE-05', quantity: 40, cost: 95 }] }
    ];
    localStorage.setItem('ns_purchase_invoices', JSON.stringify(purchases));
  }
  
  const tbody = document.getElementById('accounting-purchases-table-body');
  if (!tbody) return;

  if (purchases.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" class="text-center" style="padding:20px; color:var(--text-muted);">لا توجد فواتير شراء مسجلة بعد.</td></tr>`;
    return;
  }

  tbody.innerHTML = purchases.map(inv => {
    const formattedDate = new Date(inv.date).toLocaleDateString('ar-SA', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
    
    const statusMap = {
      'Paid': { text: 'تم السداد', class: 'badge-success' },
      'Pending': { text: 'مستحقة الدفع (آجل)', class: 'badge-warning' }
    };
    
    const status = statusMap[inv.status] || { text: inv.status, class: 'badge-info' };

    return `
      <tr>
        <td style="font-family: var(--font-english); font-weight:700;">#PUR-${inv.id}</td>
        <td>
          <div style="font-weight:600;">${inv.supplierName}</div>
          <div style="font-size:11px; color:var(--text-muted);">${formattedDate}</div>
        </td>
        <td style="font-family: var(--font-english); font-weight:700; color:hsla(var(--danger), 1);">$${inv.total.toLocaleString()}</td>
        <td><span class="badge ${status.class}">${status.text}</span></td>
        <td>
          <button class="btn btn-secondary btn-sm btn-print-purchase-inv" data-id="${inv.id}">
            <i class="fas fa-print"></i> طباعة الفاتورة
          </button>
        </td>
      </tr>
    `;
  }).join('');

  // Bind print event listener
  tbody.querySelectorAll('.btn-print-purchase-inv').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const invId = e.currentTarget.getAttribute('data-id');
      triggerPurchaseInvoicePrint(invId);
    });
  });
}

function triggerPurchaseInvoicePrint(invId) {
  const purchases = JSON.parse(localStorage.getItem('ns_purchase_invoices')) || [];
  const inv = purchases.find(i => i.id === invId);
  if (!inv) return;
  
  const dateObj = new Date(inv.date);
  const formattedDate = dateObj.toLocaleDateString('ar-SA') + ' ' + dateObj.toLocaleTimeString('ar-SA');
  
  document.getElementById('invoice-order-id').innerText = `PUR-${inv.id}`;
  document.getElementById('invoice-date').innerText = formattedDate;
  
  // Set custom purchase labels
  const titleLabel = document.getElementById('invoice-title-label');
  const typeLabel = document.getElementById('invoice-type-label');
  if (titleLabel) titleLabel.innerText = 'فاتورة شراء معتمدة (وارد مستودع)';
  if (typeLabel) typeLabel.innerText = 'المورد المعتمد';
  
  document.getElementById('invoice-cust-name').innerText = inv.supplierName;
  
  document.getElementById('invoice-status').innerText = inv.status === 'Paid' ? 'مدفوعة نقداً' : 'مستحقة (آجل)';
  document.getElementById('invoice-status').className = `badge ` + (inv.status === 'Paid' ? 'badge-success' : 'badge-warning');

  const itemsBody = document.getElementById('invoice-items-body');
  itemsBody.innerHTML = inv.items.map(item => {
    return `
      <tr>
        <td>
          <div style="font-weight: 700; color: var(--text-primary);">${item.productName}</div>
          <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${item.sku}</div>
        </td>
        <td style="font-family: var(--font-english);">$${item.cost}</td>
        <td style="text-align: center; font-family: var(--font-english);">${item.quantity}</td>
        <td style="font-family: var(--font-english); font-weight: 700;">$${(item.cost * item.quantity)}</td>
      </tr>
    `;
  }).join('');

  // Tax computation
  const subtotal = inv.total / 1.15;
  const tax = inv.total - subtotal;
  
  document.getElementById('invoice-subtotal').innerText = `$${subtotal.toFixed(2)}`;
  document.getElementById('invoice-tax').innerText = `$${tax.toFixed(2)}`;
  document.getElementById('invoice-total').innerText = `$${inv.total.toFixed(2)}`;

  // Show modal
  const modal = document.getElementById('order-detail-modal');
  if (modal) {
    modal.classList.add('active');
  }
}

function setupInvoiceTabs() {
  const tabBtns = document.querySelectorAll('.btn-invoice-tab');
  tabBtns.forEach(btn => {
    if (btn.dataset.listener) return;
    btn.dataset.listener = 'true';
    
    btn.addEventListener('click', (e) => {
      tabBtns.forEach(b => {
        b.classList.remove('active');
        b.classList.remove('btn-primary');
        b.classList.add('btn-secondary');
      });
      
      btn.classList.add('active');
      btn.classList.remove('btn-secondary');
      btn.classList.add('btn-primary');
      
      const targetTab = btn.getAttribute('data-tab');
      const panels = document.querySelectorAll('.invoice-tab-panel');
      
      panels.forEach(panel => {
        if (panel.id === `${targetTab}-invoices-container`) {
          panel.style.display = 'block';
        } else {
          panel.style.display = 'none';
        }
      });
    });
  });
}
