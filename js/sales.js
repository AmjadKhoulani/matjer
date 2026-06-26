import { navigateToView } from './app.js';
import { store } from './store.js';

let saleItems = [];
let exchangeRate = 15000; // Default fallback

export function initSales() {
  const form = document.getElementById('sale-invoice-form');
  if (!form) return;

  // Initialize Date to today
  const dateInput = document.getElementById('sale-date');
  if (dateInput && !dateInput.value) {
    const today = new Date().toISOString().split('T')[0];
    dateInput.value = today;
  }

  // Load Customers
  loadCustomers();
  
  // Load Exchange Rate
  loadExchangeRate();

  // Setup Product Search
  setupProductSearch();

  // Bind Cancel button
  const cancelBtn = document.getElementById('btn-sale-cancel');
  if (cancelBtn && !cancelBtn.dataset.listener) {
    cancelBtn.dataset.listener = 'true';
    cancelBtn.addEventListener('click', () => {
      resetSaleForm();
      navigateToView('all-sales');
    });
  }

  // Recalculate totals on input changes
  const discountInput = document.getElementById('sale-discount');
  if (discountInput) {
    discountInput.addEventListener('input', () => calculateTotals());
  }

  // Bind Form Submit
  if (!form.dataset.listener) {
    form.dataset.listener = 'true';
    form.addEventListener('submit', handleSaleSubmit);
  }

  // Hide search suggestions on click outside
  document.addEventListener('click', (e) => {
    const resultsBox = document.getElementById('sale-search-results');
    const searchInput = document.getElementById('sale-product-search');
    if (resultsBox && searchInput && !resultsBox.contains(e.target) && e.target !== searchInput) {
      resultsBox.style.display = 'none';
    }
  });

  // Render initial empty table
  renderSaleItemsTable();
}

function loadExchangeRate() {
  fetch('api/currency_sync.php')
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const syp = data.currencies.find(c => c.code === 'SYP');
        if (syp) {
          exchangeRate = parseFloat(syp.rate);
        }
      }
    })
    .catch(err => console.error('Error fetching exchange rate:', err));
}

function loadCustomers() {
  const select = document.getElementById('sale-customer-select');
  if (!select) return;

  fetch('api/customers.php')
    .then(res => res.json())
    .then(customers => {
      const optionsHtml = customers.map(c => `
        <option value="${c.id}">${c.name} (${c.phone || 'بدون هاتف'})</option>
      `).join('');
      select.innerHTML = '<option value="" disabled selected>اختر العميل...</option>' + optionsHtml;
    })
    .catch(err => console.error('Error loading customers:', err));
}

function setupProductSearch() {
  const searchInput = document.getElementById('sale-product-search');
  const resultsBox = document.getElementById('sale-search-results');
  if (!searchInput || !resultsBox) return;

  searchInput.addEventListener('input', () => {
    const val = searchInput.value.trim().toLowerCase();
    if (!val) {
      resultsBox.style.display = 'none';
      resultsBox.innerHTML = '';
      return;
    }

    fetch(`api/products.php`)
      .then(res => res.json())
      .then(products => {
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
          <div class="sale-search-item" data-id="${p.id}" style="padding: 10px 16px; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-weight: 700; color: var(--text-primary); font-size: 13px;">${p.name}</div>
              <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${p.sku}</div>
            </div>
            <div style="text-align: right;">
              <div style="font-family: var(--font-english); font-weight: 700; color: hsla(var(--primary), 1);">${store.getCurrencySymbol()} ${parseFloat(p.price_usd).toLocaleString()}</div>
              <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-arabic);">المستودع: ${p.stock} قطعة</div>
            </div>
          </div>
        `).join('');

        resultsBox.style.display = 'block';

        // Bind clicks
        resultsBox.querySelectorAll('.sale-search-item').forEach(item => {
          item.addEventListener('click', () => {
            const prodId = item.getAttribute('data-id');
            const prod = products.find(p => p.id == prodId);
            if (prod) {
              addProductToSale(prod);
            }
            searchInput.value = '';
            resultsBox.style.display = 'none';
          });
        });
      })
      .catch(err => console.error('Error searching products:', err));
  });
}

function addProductToSale(product) {
  if (parseInt(product.stock) <= 0) {
    alert('عذراً، هذا المنتج خارج المخزون تماماً.');
    return;
  }

  const existing = saleItems.find(item => item.id == product.id);
  if (existing) {
    if (existing.qty + 1 > parseInt(product.stock)) {
      alert(`عذراً، لا يمكن تجاوز الكمية المتاحة في المستودع (${product.stock} قطع).`);
      return;
    }
    existing.qty += 1;
  } else {
    saleItems.push({
      id: product.id,
      name: product.name,
      sku: product.sku,
      price: parseFloat(product.price_usd),
      stock: parseInt(product.stock),
      qty: 1
    });
  }

  renderSaleItemsTable();
}

function renderSaleItemsTable() {
  const tbody = document.getElementById('sale-items-tbody');
  if (!tbody) return;

  if (saleItems.length === 0) {
    tbody.innerHTML = `
      <tr id="sale-empty-row">
        <td colspan="7" style="text-align: center; padding: 48px; color: var(--text-muted); font-weight: 500; font-family: var(--font-arabic);">لا توجد أصناف مضافة بعد</td>
      </tr>
    `;
    calculateTotals();
    return;
  }

  tbody.innerHTML = saleItems.map((item, index) => {
    const subtotal = item.price * item.qty;
    return `
      <tr>
        <td style="text-align: center; font-family: var(--font-english); font-weight: 600;">${index + 1}</td>
        <td>
          <div style="font-weight: 700; color: var(--text-primary); font-size: 13px;">${item.name}</div>
          <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${item.sku}</div>
        </td>
        <td>
          <input type="number" class="form-control item-price-input" data-id="${item.id}" value="${item.price}" min="0" step="0.01" style="width: 110px; height: 36px; font-family: var(--font-english);">
        </td>
        <td style="text-align: center; font-family: var(--font-english); font-weight: 600;">${item.stock}</td>
        <td>
          <input type="number" class="form-control item-qty-input" data-id="${item.id}" value="${item.qty}" min="1" max="${item.stock}" style="width: 80px; height: 36px; text-align: center; font-family: var(--font-english);">
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

  // Bind input change listeners
  tbody.querySelectorAll('.item-price-input').forEach(input => {
    input.addEventListener('change', (e) => {
      const id = e.target.getAttribute('data-id');
      const val = parseFloat(e.target.value) || 0;
      const item = saleItems.find(i => i.id == id);
      if (item) {
        item.price = val;
        renderSaleItemsTable();
      }
    });
  });

  tbody.querySelectorAll('.item-qty-input').forEach(input => {
    input.addEventListener('change', (e) => {
      const id = e.target.getAttribute('data-id');
      const val = parseInt(e.target.value) || 1;
      const item = saleItems.find(i => i.id == id);
      if (item) {
        if (val > item.stock) {
          alert(`عذراً، الكمية المتاحة في المخازن هي ${item.stock} قطعة فقط.`);
          e.target.value = item.qty;
          return;
        }
        item.qty = Math.max(1, val);
        renderSaleItemsTable();
      }
    });
  });

  tbody.querySelectorAll('.btn-item-delete').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = btn.getAttribute('data-id');
      saleItems = saleItems.filter(i => i.id != id);
      renderSaleItemsTable();
    });
  });

  calculateTotals();
}

function calculateTotals() {
  let subtotalSum = 0;
  saleItems.forEach(item => {
    subtotalSum += item.price * item.qty;
  });

  const bottomDiscount = parseFloat(document.getElementById('sale-discount').value) || 0;
  const taxVal = (subtotalSum - bottomDiscount) * 0.15; // 15% VAT
  const total = Math.max(0, subtotalSum - bottomDiscount + taxVal);

  const subEl = document.getElementById('sale-summary-subtotal');
  const disEl = document.getElementById('sale-summary-discount');
  const taxEl = document.getElementById('sale-summary-tax');
  const totEl = document.getElementById('sale-summary-total');

  const symbol = store.getCurrencySymbol();
  if (subEl) subEl.innerText = `${symbol} ${subtotalSum.toFixed(2)}`;
  if (disEl) disEl.innerText = `${symbol} ${bottomDiscount.toFixed(2)}`;
  if (taxEl) taxEl.innerText = `${symbol} ${taxVal.toFixed(2)}`;
  if (totEl) totEl.innerText = `${symbol} ${total.toFixed(2)}`;

  return total;
}

function handleSaleSubmit(e) {
  e.preventDefault();

  if (saleItems.length === 0) {
    alert('الرجاء إضافة صنف واحد على الأقل لإصدار الفاتورة.');
    return;
  }

  const customerSelect = document.getElementById('sale-customer-select');
  const dateInput = document.getElementById('sale-date');
  const methodSelect = document.getElementById('sale-payment-method');
  const statusSelect = document.getElementById('sale-payment-status');
  const noteTextarea = document.getElementById('sale-note');
  const bottomDiscount = parseFloat(document.getElementById('sale-discount').value) || 0;
  const couponCode = document.getElementById('sale-coupon').value.trim();

  if (!customerSelect.value) {
    alert('الرجاء اختيار العميل الموجهة له الفاتورة.');
    return;
  }

  const grandTotal = calculateTotals();
  const dateVal = dateInput.value || new Date().toISOString().split('T')[0];

  const payload = {
    customer_id: customerSelect.value,
    customer_name: customerSelect.options[customerSelect.selectedIndex].text.split(' (')[0],
    total_usd: grandTotal,
    exchange_rate: exchangeRate,
    coupon_code: couponCode,
    discount_usd: bottomDiscount,
    source: 'invoice',
    payment_status: statusSelect.value,
    payment_method: methodSelect.value,
    notes: noteTextarea.value,
    items: saleItems.map(item => ({
      product_id: item.id,
      quantity: item.qty,
      price_usd: item.price
    }))
  };

  fetch('api/orders.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(`تم إصدار فاتورة المبيعات #${data.order_id} بنجاح وحسم المنتجات من المستودع.`);
      resetSaleForm();
      
      // Navigate to all-sales view
      navigateToView('all-sales');
    } else {
      alert(`خطأ: ${data.message}`);
    }
  })
  .catch(err => {
    console.error('Submit sale invoice error:', err);
    alert('فشل الاتصال بالخادم لحفظ فاتورة المبيعات.');
  });
}

function resetSaleForm() {
  saleItems = [];
  const form = document.getElementById('sale-invoice-form');
  if (form) form.reset();

  const dateInput = document.getElementById('sale-date');
  if (dateInput) {
    dateInput.value = new Date().toISOString().split('T')[0];
  }

  renderSaleItemsTable();
}
