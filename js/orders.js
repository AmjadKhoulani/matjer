import { store } from './store.js';
import { initDashboard } from './dashboard.js';

let viewingOrderId = null;

export function initOrders() {
  renderOrdersTable();
  setupEventListeners();
  initCustomers();
  initIntegration();
}

export function renderOrdersTable() {
  const orders = store.getOrders();
  const tbody = document.getElementById('orders-table-body');
  
  const searchVal = document.getElementById('orders-search').value.toLowerCase();
  const filterStatus = document.getElementById('filter-order-status').value;
  
  // Filter orders
  const filteredOrders = orders.filter(o => {
    const matchesSearch = o.customerName.toLowerCase().includes(searchVal) || o.id.includes(searchVal);
    const matchesStatus = filterStatus === 'all' || o.status === filterStatus;
    return matchesSearch && matchesStatus;
  });
  
  if (filteredOrders.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center" style="text-align: center; padding: 32px; color: var(--text-muted);">لا توجد طلبات تطابق البحث.</td></tr>`;
    return;
  }
  
  // Status maps
  const statusLabels = {
    'Pending': 'قيد الانتظار',
    'Shipped': 'تم الشحن',
    'Delivered': 'تم التوصيل',
    'Cancelled': 'ملغي'
  };
  
  const statusClasses = {
    'Pending': 'badge-warning',
    'Shipped': 'badge-info',
    'Delivered': 'badge-success',
    'Cancelled': 'badge-danger'
  };
  
  tbody.innerHTML = filteredOrders.map(o => {
    const dateStr = new Date(o.date).toLocaleDateString('ar-EG', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
    
    const itemsCount = o.items.reduce((sum, item) => sum + item.quantity, 0);
    
    return `
      <tr>
        <td style="font-family: var(--font-english); font-weight: 700; color: hsla(var(--primary), 1);">#${o.id}</td>
        <td>
          <div style="font-weight: 600;">${o.customerName}</div>
          <div style="font-size: 11px; color: var(--text-muted);">${dateStr}</div>
        </td>
        <td style="font-family: var(--font-english); font-weight: 600;">$${o.total.toLocaleString()}</td>
        <td style="font-family: var(--font-english); text-align: center;">${itemsCount} قطع</td>
        <td><span class="badge ${statusClasses[o.status]}">${statusLabels[o.status]}</span></td>
        <td>
          <div style="display: flex; gap: 6px;">
            <button class="btn btn-secondary btn-sm btn-icon btn-view-order" data-id="${o.id}" title="عرض تفاصيل الطلب والفاتورة">
              <i class="fas fa-file-invoice-dollar"></i>
            </button>
            <button class="btn btn-secondary btn-sm btn-icon btn-change-status" data-id="${o.id}" title="تغيير حالة الطلب">
              <i class="fas fa-tasks"></i>
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
  
  // Attach quick click handlers
  document.querySelectorAll('.btn-view-order').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      openOrderDetailsModal(id);
    });
  });
  
  document.querySelectorAll('.btn-change-status').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      openChangeStatusModal(id);
    });
  });
}

function setupEventListeners() {
  document.getElementById('orders-search').addEventListener('input', renderOrdersTable);
  document.getElementById('filter-order-status').addEventListener('change', renderOrdersTable);
  
  // Close modals
  document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
      closeModals();
    });
  });
  
  // Forms & Actions
  document.getElementById('status-update-form').addEventListener('submit', handleStatusUpdateSubmit);
  document.getElementById('btn-print-invoice').addEventListener('click', () => {
    window.print();
  });
}

function closeModals() {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.classList.remove('active');
  });
  viewingOrderId = null;
}

export function openOrderDetailsModal(id) {
  viewingOrderId = id;
  const orders = store.getOrders();
  const products = store.getProducts();
  const order = orders.find(o => o.id === id);
  
  if (order) {
    document.getElementById('invoice-order-id').innerText = order.id;
    document.getElementById('invoice-cust-name').innerText = order.customerName;
    
    // Format date
    const dateStr = new Date(order.date).toLocaleDateString('ar-EG', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
    document.getElementById('invoice-date').innerText = dateStr;
    
    const statusLabels = {
      'Pending': 'قيد الانتظار',
      'Shipped': 'تم الشحن',
      'Delivered': 'تم التوصيل',
      'Cancelled': 'ملغي'
    };
    document.getElementById('invoice-status').innerText = statusLabels[order.status];
    
    // Render item lines
    const itemsTbody = document.getElementById('invoice-items-body');
    itemsTbody.innerHTML = order.items.map(item => {
      const prod = products.find(p => p.id === item.productId) || { name: 'منتج غير معروف', sku: 'N/A' };
      const subtotal = item.quantity * item.price;
      return `
        <tr>
          <td>
            <div style="font-weight: 600;">${prod.name}${item.size ? ` (${item.size} / ${item.color})` : ''}</div>
            <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${prod.sku}</div>
          </td>
          <td style="font-family: var(--font-english); font-weight: 600;">$${item.price.toLocaleString()}</td>
          <td style="font-family: var(--font-english); text-align: center;">${item.quantity}</td>
          <td style="font-family: var(--font-english); font-weight: 700; color: hsla(var(--primary), 1);">$${subtotal.toLocaleString()}</td>
        </tr>
      `;
    }).join('');
    
    // Calculations
    const subtotal = order.total;
    const tax = Math.round(subtotal * 0.15); // 15% VAT
    const finalTotal = subtotal + tax;
    
    document.getElementById('invoice-subtotal').innerText = `$${subtotal.toLocaleString()}`;
    document.getElementById('invoice-tax').innerText = `$${tax.toLocaleString()}`;
    document.getElementById('invoice-total').innerText = `$${finalTotal.toLocaleString()}`;
    
    document.getElementById('order-detail-modal').classList.add('active');
  }
}

function openChangeStatusModal(id) {
  viewingOrderId = id;
  const orders = store.getOrders();
  const order = orders.find(o => o.id === id);
  
  if (order) {
    document.getElementById('change-status-order-id').innerText = order.id;
    document.getElementById('order-status-select').value = order.status;
    document.getElementById('order-status-modal').classList.add('active');
  }
}

function handleStatusUpdateSubmit(e) {
  e.preventDefault();
  
  const status = document.getElementById('order-status-select').value;
  const updated = store.updateOrderStatus(viewingOrderId, status);
  
  if (updated) {
    // If order is cancelled, we might want to return items back to stock
    if (status === 'Cancelled') {
      const products = store.getProducts();
      updated.items.forEach(item => {
        const prod = products.find(p => p.id === item.productId);
        if (prod) {
          store.updateProductStock(item.productId, item.quantity, `إلغاء الطلب رقم #${viewingOrderId}`);
        }
      });
    }
    
    closeModals();
    renderOrdersTable();
    initDashboard(); // Recalculate KPIs
  }
}

const MOCK_CUSTOMERS = [
  { name: 'عبد الله السالم', phone: '+966 50 111 2222', email: 'a.salem@gmail.com', ordersCount: 5, loyaltyPoints: 240 },
  { name: 'سارة الهلال', phone: '+966 55 222 3333', email: 'sara.h@outlook.com', ordersCount: 2, loyaltyPoints: 80 },
  { name: 'خالد الحربي', phone: '+966 56 333 4444', email: 'k.harbi@matjer.net', ordersCount: 1, loyaltyPoints: 30 },
  { name: 'مريم العتيبي', phone: '+966 54 444 5555', email: 'm.otb@gmail.com', ordersCount: 4, loyaltyPoints: 180 }
];

export function initCustomers() {
  const tbody = document.getElementById('ecommerce-customers-tbody');
  if (!tbody) return;
  
  let customers = JSON.parse(localStorage.getItem('ns_customers')) || MOCK_CUSTOMERS;
  localStorage.setItem('ns_customers', JSON.stringify(customers));
  
  tbody.innerHTML = customers.map(c => `
    <tr>
      <td>
        <a href="#" class="js-view-customer-crm" data-email="${c.email}" style="font-weight: 700; color: hsla(var(--primary), 1); text-decoration: none;">${c.name}</a>
      </td>
      <td style="font-family: var(--font-english);">${c.phone}</td>
      <td style="font-family: var(--font-english);">${c.email}</td>
      <td style="font-family: var(--font-english); font-weight: 600;">${c.ordersCount} طلبات</td>
      <td><span class="badge badge-success"><i class="fas fa-star" style="color:#f59e0b; margin-inline-end: 4px;"></i>${c.loyaltyPoints} نقطة</span></td>
    </tr>
  `).join('');

  // Bind CRM details click listeners
  tbody.querySelectorAll('.js-view-customer-crm').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const email = link.getAttribute('data-email');
      openCustomerCRMPage(email);
    });
  });
}

function initIntegration() {
  const form = document.getElementById('integration-woo-form');
  if (!form) return;
  
  // Load saved credentials
  const url = localStorage.getItem('ns_woo_url') || 'https://store.matjer.net';
  const ck = localStorage.getItem('ns_woo_ck') || 'ck_8a09f8e7b6c5d4e3f2a1';
  const cs = localStorage.getItem('ns_woo_cs') || 'cs_1a2b3c4d5e6f7g8h9i0j';
  const sync = localStorage.getItem('ns_woo_sync') || 'live';
  
  document.getElementById('integration-woo-url').value = url;
  document.getElementById('integration-woo-ck').value = ck;
  document.getElementById('integration-woo-cs').value = cs;
  document.getElementById('integration-woo-sync').value = sync;
  
  if (!form.dataset.listener) {
    form.dataset.listener = 'true';
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const newUrl = document.getElementById('integration-woo-url').value;
      const newCk = document.getElementById('integration-woo-ck').value;
      const newCs = document.getElementById('integration-woo-cs').value;
      const newSync = document.getElementById('integration-woo-sync').value;
      
      localStorage.setItem('ns_woo_url', newUrl);
      localStorage.setItem('ns_woo_ck', newCk);
      localStorage.setItem('ns_woo_cs', newCs);
      localStorage.setItem('ns_woo_sync', newSync);
      
      store.addActivity('info', 'تحديث الربط التقني', `تم تعديل مفاتيح ربط WooCommerce ومزامنة المخزون`);
      
      alert('تم حفظ إعدادات الاتصال والربط بنجاح!');
    });
    
    document.getElementById('btn-woo-test-conn').addEventListener('click', () => {
      const btn = document.getElementById('btn-woo-test-conn');
      btn.innerHTML = `<i class="fas fa-spinner fa-spin"></i> جاري الاتصال...`;
      btn.disabled = true;
      
      setTimeout(() => {
        btn.innerHTML = `<i class="fas fa-plug"></i> اختبار الاتصال بالسيرفر`;
        btn.disabled = false;
        alert('نجاح الاتصال! تم الاتصال بـ WooCommerce API بنجاح ومزامنة المنتجات الجاهزة.');
      }, 1500);
    });
  }
}

export function openCustomerCRMPage(email) {
  let customers = JSON.parse(localStorage.getItem('ns_customers')) || MOCK_CUSTOMERS;
  const customer = customers.find(c => c.email === email);
  if (!customer) return;

  // Render text fields
  document.getElementById('customer-detail-breadcrumb').innerText = `ملف العميل: ${customer.name} (CRM)`;
  document.getElementById('crm-cust-name').innerText = customer.name;
  document.getElementById('crm-cust-email').innerText = customer.email;
  document.getElementById('crm-cust-phone').innerText = customer.phone;
  document.getElementById('crm-cust-points').innerText = `${customer.loyaltyPoints} نقطة`;

  // Set tier dropdown and badge
  const tierSelect = document.getElementById('crm-cust-tier-select');
  const tierBadge = document.getElementById('crm-cust-tier');
  
  tierSelect.value = customer.tier || 'standard';
  
  // Custom tier labels and badges
  const tierLabels = {
    standard: { label: 'عميل افتراضي', class: 'badge-secondary' },
    vip: { label: 'عميل VIP 🌟', class: 'badge-warning' },
    loyal: { label: 'عميل وفي ❤️', class: 'badge-success' },
    lead: { label: 'عميل محتمل / جديد', class: 'badge-primary' }
  };
  const activeTier = tierLabels[customer.tier || 'standard'];
  tierBadge.innerText = activeTier.label;
  tierBadge.className = `badge ${activeTier.class}`;

  // On tier change
  if (!tierSelect.dataset.listenerBound) {
    tierSelect.dataset.listenerBound = 'true';
    tierSelect.addEventListener('change', () => {
      const newTier = tierSelect.value;
      updateCustomerTier(customer.email, newTier);
    });
  }

  // Calculate LTV, Average Order Value (AOV) from all system orders
  const orders = store.getOrders();
  const custOrders = orders.filter(o => 
    o.customerName.toLowerCase().includes(customer.name.toLowerCase()) || 
    customer.name.toLowerCase().includes(o.customerName.toLowerCase())
  );

  const totalSpent = custOrders.reduce((sum, o) => sum + o.total, 0);
  const totalOrders = custOrders.length;
  const aov = totalOrders > 0 ? Math.round(totalSpent / totalOrders) : 0;

  document.getElementById('crm-cust-ltv').innerText = `$${totalSpent.toLocaleString()}`;
  document.getElementById('crm-cust-orders-count').innerText = `${totalOrders} طلبات`;
  document.getElementById('crm-cust-aov').innerText = `$${aov.toLocaleString()}`;

  // Render customer orders table
  const ordersTbody = document.getElementById('crm-cust-orders-tbody');
  if (custOrders.length === 0) {
    ordersTbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: var(--text-muted);">لا يوجد فواتير مسجلة لهذا العميل.</td></tr>';
  } else {
    ordersTbody.innerHTML = custOrders.map(o => {
      let statusBadge = 'badge-secondary';
      let statusText = o.status;
      if (o.status === 'Pending') { statusBadge = 'badge-warning'; statusText = 'معلق'; }
      else if (o.status === 'Shipped') { statusBadge = 'badge-primary'; statusText = 'تم الشحن'; }
      else if (o.status === 'Delivered') { statusBadge = 'badge-success'; statusText = 'تم التوصيل'; }
      else if (o.status === 'Cancelled') { statusBadge = 'badge-danger'; statusText = 'ملغي'; }

      const dateFormatted = new Date(o.date).toLocaleDateString('ar-SY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });

      return `
        <tr>
          <td style="font-family: var(--font-english); font-weight: 700;">#${o.id}</td>
          <td style="font-family: var(--font-english);">${dateFormatted}</td>
          <td style="font-size: 11px; color: var(--text-muted);">${o.items ? o.items.length : 0} أصناف مختلفة</td>
          <td style="font-family: var(--font-english); font-weight: 700; color: hsla(var(--primary), 1);">$${o.total.toLocaleString()}</td>
          <td><span class="badge ${statusBadge}">${statusText}</span></td>
          <td>
            <button class="btn btn-secondary btn-sm js-crm-view-order" data-id="${o.id}" style="padding: 2px 8px; font-size:11px; height: 26px;">
              <i class="fas fa-file-invoice"></i> عرض الفاتورة
            </button>
          </td>
        </tr>
      `;
    }).join('');

    // Bind crm-view-order triggers
    ordersTbody.querySelectorAll('.js-crm-view-order').forEach(btn => {
      btn.addEventListener('click', () => {
        const oId = btn.getAttribute('data-id');
        openOrderDetailsModal(oId);
      });
    });
  }

  // Render CRM notes
  renderCustomerCRMNotes(customer.email);

  // Bind CRM note form listener
  const noteForm = document.getElementById('crm-add-note-form');
  if (!noteForm.dataset.listenerBound) {
    noteForm.dataset.listenerBound = 'true';
    noteForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const noteInput = document.getElementById('crm-new-note-text');
      const text = noteInput.value.trim();
      if (text) {
        addCustomerCRMNote(customer.email, text);
        noteInput.value = '';
      }
    });
  }

  // Redirect to CRM details page
  import('./app.js').then(module => {
    module.navigateToView('ecommerce-customers-detail');
  });
}

function updateCustomerTier(email, tier) {
  let customers = JSON.parse(localStorage.getItem('ns_customers')) || MOCK_CUSTOMERS;
  const index = customers.findIndex(c => c.email === email);
  if (index > -1) {
    customers[index].tier = tier;
    localStorage.setItem('ns_customers', JSON.stringify(customers));
    
    // Update badge and text dynamically
    const tierBadge = document.getElementById('crm-cust-tier');
    const tierLabels = {
      standard: { label: 'عميل افتراضي', class: 'badge-secondary' },
      vip: { label: 'عميل VIP 🌟', class: 'badge-warning' },
      loyal: { label: 'عميل وفي ❤️', class: 'badge-success' },
      lead: { label: 'عميل محتمل / جديد', class: 'badge-primary' }
    };
    const activeTier = tierLabels[tier];
    tierBadge.innerText = activeTier.label;
    tierBadge.className = `badge ${activeTier.class}`;

    // Add activity log
    store.addActivity('info', 'تحديث تصنيف عميل', `تم تغيير تصنيف العميل "${customers[index].name}" إلى ${activeTier.label}`);
    initCustomers(); // refresh catalog table
  }
}

function renderCustomerCRMNotes(email) {
  const notes = JSON.parse(localStorage.getItem('ns_customer_notes')) || [];
  const custNotes = notes.filter(n => n.customerEmail === email);

  const container = document.getElementById('crm-notes-timeline');
  if (custNotes.length === 0) {
    container.innerHTML = '<div style="font-size: 12px; color: var(--text-muted); padding: 8px 0;">لا يوجد ملاحظات مسجلة لهذا العميل. أضف ملاحظة بالنموذج أعلاه.</div>';
    return;
  }

  // Sort descending by date
  custNotes.sort((a, b) => new Date(b.date) - new Date(a.date));

  container.innerHTML = custNotes.map(n => {
    const formattedDate = new Date(n.date).toLocaleString('ar-SY', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });

    return `
      <div style="background: var(--bg-tertiary); border: 1px solid var(--border-color); padding: 10px; border-radius: 6px; margin-bottom: 8px;">
        <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted); margin-bottom: 4px;">
          <span><i class="fas fa-clock"></i> ${formattedDate}</span>
          <a href="#" class="js-delete-crm-note" data-id="${n.id}" style="color: hsla(var(--danger),1); text-decoration:none;"><i class="fas fa-trash"></i></a>
        </div>
        <p style="margin: 0; font-size: 12px; color: var(--text-primary); line-height: 1.4;">${n.text}</p>
      </div>
    `;
  }).join('');

  // Delete note listeners
  container.querySelectorAll('.js-delete-crm-note').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const nId = link.getAttribute('data-id');
      deleteCustomerCRMNote(email, nId);
    });
  });
}

function addCustomerCRMNote(email, text) {
  const notes = JSON.parse(localStorage.getItem('ns_customer_notes')) || [];
  const newNote = {
    id: Date.now().toString(),
    customerEmail: email,
    date: new Date().toISOString(),
    text: text
  };
  notes.push(newNote);
  localStorage.setItem('ns_customer_notes', JSON.stringify(notes));
  renderCustomerCRMNotes(email);
}

function deleteCustomerCRMNote(email, noteId) {
  let notes = JSON.parse(localStorage.getItem('ns_customer_notes')) || [];
  notes = notes.filter(n => n.id !== noteId);
  localStorage.setItem('ns_customer_notes', JSON.stringify(notes));
  renderCustomerCRMNotes(email);
}
