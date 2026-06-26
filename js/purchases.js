import { store } from './store.js';
import { navigateToView } from './app.js';

let purchaseItems = [];

export function initPurchases() {
  const form = document.getElementById('pur-purchase-form');
  if (!form) return;

  // Initialize Date to today
  const dateInput = document.getElementById('pur-date');
  if (dateInput && !dateInput.value) {
    const today = new Date().toISOString().split('T')[0];
    dateInput.value = today;
  }

  // Populate Suppliers & Warehouses selectors
  populateSuppliersSelect();
  populateWarehousesSelect();

  // Bind Autocomplete Product Search
  setupProductSearch();

  // Bind Cancel button
  const cancelBtn = document.getElementById('btn-pur-cancel');
  if (cancelBtn && !cancelBtn.dataset.listener) {
    cancelBtn.dataset.listener = 'true';
    cancelBtn.addEventListener('click', () => {
      resetPurchaseForm();
      navigateToView('accounting-invoices');
      // Trigger click on purchase tab to ensure it shows purchases
      const purTabBtn = document.querySelector('.btn-invoice-tab[data-tab="purchases"]');
      if (purTabBtn) purTabBtn.click();
    });
  }

  // Bind "+" Add Supplier Button
  const addSupBtn = document.getElementById('btn-pur-add-supplier');
  if (addSupBtn && !addSupBtn.dataset.listener) {
    addSupBtn.dataset.listener = 'true';
    addSupBtn.addEventListener('click', () => {
      const modal = document.getElementById('supplier-modal');
      if (modal) {
        modal.classList.add('active');
      }
    });
  }

  // Update currency symbols in form inputs
  const symbol = store.getCurrencySymbol();
  document.querySelectorAll('#pur-purchase-form .form-group span').forEach(span => {
    if (span.innerText.trim() === '$') {
      span.innerText = symbol;
    }
  });

  // Bind bottom inputs change event listeners
  const taxInput = document.getElementById('pur-order-tax');
  const discountInput = document.getElementById('pur-discount');
  const shippingInput = document.getElementById('pur-shipping');

  const recalculateCallback = () => calculateGrandTotal();
  if (taxInput) taxInput.addEventListener('input', recalculateCallback);
  if (discountInput) discountInput.addEventListener('input', recalculateCallback);
  if (shippingInput) shippingInput.addEventListener('input', recalculateCallback);

  // Bind Form Submit
  if (!form.dataset.listener) {
    form.dataset.listener = 'true';
    form.addEventListener('submit', handlePurchaseSubmit);
  }

  // Hide autocomplete results when clicking outside
  document.addEventListener('click', (e) => {
    const resultsBox = document.getElementById('pur-search-results');
    const searchInput = document.getElementById('pur-product-search');
    if (resultsBox && searchInput && !resultsBox.contains(e.target) && e.target !== searchInput) {
      resultsBox.style.display = 'none';
    }
  });

  // Initial render
  renderPurchaseItemsTable();
}

export function populateSuppliersSelect() {
  const select = document.getElementById('pur-supplier-select');
  if (!select) return;

  const suppliers = store.getSuppliers();
  const optionsHtml = suppliers.map(s => `
    <option value="${s.id}">${s.name}</option>
  `).join('');

  select.innerHTML = '<option value="" disabled selected>اختر المورد...</option>' + optionsHtml;
}

function populateWarehousesSelect() {
  const select = document.getElementById('pur-warehouse-select');
  if (!select) return;

  const defaultWarehouses = [
    { id: '1', name: 'مستودع دمشق الرئيسي' },
    { id: '2', name: 'مستودع حلب الشمالي' },
    { id: '3', name: 'معرض حمص المباشر' }
  ];
  const warehouses = JSON.parse(localStorage.getItem('ns_warehouses')) || defaultWarehouses;

  const optionsHtml = warehouses.map(w => `
    <option value="${w.id}">${w.name}</option>
  `).join('');

  select.innerHTML = '<option value="" disabled selected>اختر المستودع...</option>' + optionsHtml;
}

function setupProductSearch() {
  const searchInput = document.getElementById('pur-product-search');
  const resultsBox = document.getElementById('pur-search-results');
  if (!searchInput || !resultsBox) return;

  searchInput.addEventListener('input', () => {
    const val = searchInput.value.trim().toLowerCase();
    if (!val) {
      resultsBox.style.display = 'none';
      resultsBox.innerHTML = '';
      return;
    }

    const products = store.getProducts();
    const matches = products.filter(p => 
      p.name.toLowerCase().includes(val) || 
      p.sku.toLowerCase().includes(val)
    );

    if (matches.length === 0) {
      resultsBox.innerHTML = `<div style="padding: 10px 16px; color: var(--text-muted); font-size: 13px; font-family: var(--font-arabic);">لم يتم العثور على منتجات مطابقة</div>`;
      resultsBox.style.display = 'block';
      return;
    }

    resultsBox.innerHTML = matches.map(p => `
      <div class="pur-search-item" data-id="${p.id}" style="padding: 10px 16px; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
        <div>
          <div style="font-weight: 700; color: var(--text-primary); font-size: 13px;">${p.name}</div>
          <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${p.sku}</div>
        </div>
        <div style="text-align: right;">
          <div style="font-family: var(--font-english); font-weight: 700; color: hsla(var(--primary), 1);">${store.getCurrencySymbol()} ${p.cost}</div>
          <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-arabic);">المخزون: ${p.stock}</div>
        </div>
      </div>
    `).join('');

    resultsBox.style.display = 'block';

    // Bind click on search items
    resultsBox.querySelectorAll('.pur-search-item').forEach(item => {
      item.addEventListener('click', () => {
        const prodId = item.getAttribute('data-id');
        const prod = products.find(p => p.id === prodId);
        if (prod) {
          addProductToPurchase(prod);
        }
        searchInput.value = '';
        resultsBox.style.display = 'none';
      });
    });
  });
}

function addProductToPurchase(product) {
  const existing = purchaseItems.find(item => item.id === product.id);
  if (existing) {
    existing.qty += 1;
  } else {
    purchaseItems.push({
      id: product.id,
      name: product.name,
      sku: product.sku,
      cost: product.cost,
      stock: product.stock,
      qty: 1,
      discount: 0,
      taxPercent: 15
    });
  }

  renderPurchaseItemsTable();
}

function renderPurchaseItemsTable() {
  const tbody = document.getElementById('pur-items-tbody');
  if (!tbody) return;

  if (purchaseItems.length === 0) {
    tbody.innerHTML = `
      <tr id="pur-empty-row">
        <td colspan="9" style="text-align: center; padding: 48px; color: var(--text-muted); font-weight: 500; font-family: var(--font-arabic);">لا توجد منتجات مضافة بعد</td>
      </tr>
    `;
    calculateGrandTotal();
    return;
  }

  tbody.innerHTML = purchaseItems.map((item, index) => {
    const subtotal = (item.cost * item.qty) - item.discount;
    return `
      <tr>
        <td style="text-align: center; font-family: var(--font-english); font-weight: 600;">${index + 1}</td>
        <td>
          <div style="font-weight: 700; color: var(--text-primary); font-size: 13px;">${item.name}</div>
          <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${item.sku}</div>
        </td>
        <td>
          <input type="number" class="form-control item-cost-input" data-id="${item.id}" value="${item.cost}" min="0" step="0.01" style="width: 110px; height: 36px; font-family: var(--font-english);">
        </td>
        <td style="text-align: center; font-family: var(--font-english); font-weight: 600;">${item.stock}</td>
        <td>
          <input type="number" class="form-control item-qty-input" data-id="${item.id}" value="${item.qty}" min="1" style="width: 80px; height: 36px; text-align: center; font-family: var(--font-english);">
        </td>
        <td>
          <input type="number" class="form-control item-discount-input" data-id="${item.id}" value="${item.discount}" min="0" step="0.01" style="width: 80px; height: 36px; text-align: center; font-family: var(--font-english);">
        </td>
        <td>
          <input type="number" class="form-control item-tax-input" data-id="${item.id}" value="${item.taxPercent}" min="0" max="100" style="width: 80px; height: 36px; text-align: center; font-family: var(--font-english);">
        </td>
        <td style="text-align: right; font-family: var(--font-english); font-weight: 700; color: var(--text-primary); font-size: 14px;">
          ${store.getCurrencySymbol()} ${subtotal.toFixed(2)}
        </td>
        <td style="text-align: center;">
          <button type="button" class="btn btn-secondary btn-sm btn-item-delete" data-id="${item.id}" style="color: #fff; background-color: hsla(var(--danger), 1); border: none; border-radius: var(--border-radius-xs); width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');

  // Attach event listeners to table inputs
  tbody.querySelectorAll('.item-cost-input').forEach(input => {
    input.addEventListener('change', (e) => {
      const id = e.target.getAttribute('data-id');
      const val = parseFloat(e.target.value) || 0;
      const item = purchaseItems.find(i => i.id === id);
      if (item) {
        item.cost = val;
        renderPurchaseItemsTable();
      }
    });
  });

  tbody.querySelectorAll('.item-qty-input').forEach(input => {
    input.addEventListener('change', (e) => {
      const id = e.target.getAttribute('data-id');
      const val = parseInt(e.target.value) || 1;
      const item = purchaseItems.find(i => i.id === id);
      if (item) {
        item.qty = Math.max(1, val);
        renderPurchaseItemsTable();
      }
    });
  });

  tbody.querySelectorAll('.item-discount-input').forEach(input => {
    input.addEventListener('change', (e) => {
      const id = e.target.getAttribute('data-id');
      const val = parseFloat(e.target.value) || 0;
      const item = purchaseItems.find(i => i.id === id);
      if (item) {
        item.discount = Math.max(0, val);
        renderPurchaseItemsTable();
      }
    });
  });

  tbody.querySelectorAll('.item-tax-input').forEach(input => {
    input.addEventListener('change', (e) => {
      const id = e.target.getAttribute('data-id');
      const val = parseInt(e.target.value) || 0;
      const item = purchaseItems.find(i => i.id === id);
      if (item) {
        item.taxPercent = Math.max(0, Math.min(100, val));
        renderPurchaseItemsTable();
      }
    });
  });

  tbody.querySelectorAll('.btn-item-delete').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = btn.getAttribute('data-id');
      purchaseItems = purchaseItems.filter(i => i.id !== id);
      renderPurchaseItemsTable();
    });
  });

  calculateGrandTotal();
}

function calculateGrandTotal() {
  let subtotalSum = 0;
  purchaseItems.forEach(item => {
    subtotalSum += (item.cost * item.qty) - item.discount;
  });

  const orderTaxPct = parseFloat(document.getElementById('pur-order-tax').value) || 0;
  const bottomDiscount = parseFloat(document.getElementById('pur-discount').value) || 0;
  const bottomShipping = parseFloat(document.getElementById('pur-shipping').value) || 0;

  const orderTaxVal = (subtotalSum - bottomDiscount) * (orderTaxPct / 100);
  const total = subtotalSum - bottomDiscount + orderTaxVal + bottomShipping;

  // Render on card widget
  const summaryTax = document.getElementById('pur-summary-tax');
  const summaryDiscount = document.getElementById('pur-summary-discount');
  const summaryShipping = document.getElementById('pur-summary-shipping');
  const summaryTotal = document.getElementById('pur-summary-total');

  const symbol = store.getCurrencySymbol();
  if (summaryTax) summaryTax.innerText = `${symbol} ${orderTaxVal.toFixed(2)} (${orderTaxPct.toFixed(2)} %)`;
  if (summaryDiscount) summaryDiscount.innerText = `${symbol} ${bottomDiscount.toFixed(2)}`;
  if (summaryShipping) summaryShipping.innerText = `${symbol} ${bottomShipping.toFixed(2)}`;
  if (summaryTotal) summaryTotal.innerText = `${symbol} ${total.toFixed(2)}`;

  return total;
}

function handlePurchaseSubmit(e) {
  e.preventDefault();

  if (purchaseItems.length === 0) {
    alert('الرجاء إضافة منتج واحد على الأقل لعناصر الفاتورة.');
    return;
  }

  const supplierSelect = document.getElementById('pur-supplier-select');
  const warehouseSelect = document.getElementById('pur-warehouse-select');
  const dateInput = document.getElementById('pur-date');
  const statusSelect = document.getElementById('pur-status');
  const noteTextarea = document.getElementById('pur-note');

  if (!supplierSelect.value) {
    alert('الرجاء اختيار المورد أولاً.');
    return;
  }
  if (!warehouseSelect.value) {
    alert('الرجاء اختيار المستودع أولاً.');
    return;
  }

  const purchaseInvoices = JSON.parse(localStorage.getItem('ns_purchase_invoices')) || [];
  const invoiceId = (2000 + purchaseInvoices.length + 1).toString();
  const grandTotal = calculateGrandTotal();

  const newInvoice = {
    id: invoiceId,
    supplierId: supplierSelect.value,
    supplierName: supplierSelect.options[supplierSelect.selectedIndex].text,
    warehouseId: warehouseSelect.value,
    warehouseName: warehouseSelect.options[warehouseSelect.selectedIndex].text,
    date: new Date(dateInput.value || new Date()).toISOString(),
    total: grandTotal,
    status: statusSelect.value === 'received' ? 'Paid' : 'Pending', // Received -> Paid, others -> Pending
    items: purchaseItems.map(item => ({
      productId: item.id,
      productName: item.name,
      sku: item.sku,
      quantity: item.qty,
      cost: item.cost,
      discount: item.discount,
      taxPercent: item.taxPercent
    })),
    note: noteTextarea.value
  };

  // 1. Save to list of purchase invoices
  purchaseInvoices.unshift(newInvoice);
  localStorage.setItem('ns_purchase_invoices', JSON.stringify(purchaseInvoices));

  // 2. Adjust database stock & costs atomically
  const products = store.getProducts();
  purchaseItems.forEach(item => {
    const dbProduct = products.find(p => p.id === item.id);
    if (dbProduct) {
      // Update product cost to match the purchase receipt
      dbProduct.cost = item.cost;
      
      // Increment stock levels directly
      dbProduct.stock = Math.max(0, dbProduct.stock + item.qty);
      
      // Update status
      if (dbProduct.stock === 0) {
        dbProduct.status = 'Out of Stock';
      } else if (dbProduct.stock <= dbProduct.minStock) {
        dbProduct.status = 'Low Stock';
      } else {
        dbProduct.status = 'In Stock';
      }

      // Add audit log activity
      store.addActivity(
        'success',
        'تعديل المخزون والتكلفة',
        `تم تحديث تكلفة "${dbProduct.name}" لـ ${store.getCurrencySymbol()} ${item.cost} وزيادة المخزون بـ +${item.qty} قطعة. (سند شراء رقم PUR-${invoiceId}). الرصيد الحالي: ${dbProduct.stock}`
      );
    }
  });
  store.saveProducts(products); // Commit both cost and stock adjustments together

  // 3. Clear/Reset Form State
  resetPurchaseForm();

  // 4. Activity Logs
  store.addActivity('success', 'إنشاء سند مشتريات جديد', `تم تسجيل الفاتورة #PUR-${invoiceId} للمورد "${newInvoice.supplierName}" بقيمة ${store.getCurrencySymbol()} ${grandTotal.toFixed(2)}`);

  alert(`تم إنشاء سند الشراء #PUR-${invoiceId} بنجاح وتم تحديث كميات المخازن.`);

  // 5. Navigate back to Invoices view and show Purchases tab
  navigateToView('accounting-invoices');
  const purTabBtn = document.querySelector('.btn-invoice-tab[data-tab="purchases"]');
  if (purTabBtn) purTabBtn.click();
}

function resetPurchaseForm() {
  purchaseItems = [];
  const form = document.getElementById('pur-purchase-form');
  if (form) form.reset();

  const dateInput = document.getElementById('pur-date');
  if (dateInput) {
    dateInput.value = new Date().toISOString().split('T')[0];
  }

  renderPurchaseItemsTable();
}
