import { navigateToView } from './app.js';
import { store } from './store.js';

let quoItems = [];
let exchangeRate = 15000; // Default fallback

export function initQuotationForm() {
  const form = document.getElementById('quo-quotation-form');
  if (!form) return;

  // Initialize Dates
  const dateInput = document.getElementById('quo-date');
  const validUntilInput = document.getElementById('quo-valid-until');
  
  if (dateInput && !dateInput.value) {
    const today = new Date().toISOString().split('T')[0];
    dateInput.value = today;
    
    // Default valid until: 7 days from today
    if (validUntilInput && !validUntilInput.value) {
      const nextWeek = new Date();
      nextWeek.setDate(nextWeek.getDate() + 7);
      validUntilInput.value = nextWeek.toISOString().split('T')[0];
    }
  }

  // Load Customers
  loadCustomersSelect('quo-customer-select');
  
  // Load Exchange Rate
  loadExchangeRate();

  // Setup Product Search
  setupQuotationProductSearch();

  // Bind Cancel button
  const cancelBtn = document.getElementById('btn-quo-cancel');
  if (cancelBtn && !cancelBtn.dataset.listener) {
    cancelBtn.dataset.listener = 'true';
    cancelBtn.addEventListener('click', () => {
      resetQuotationForm();
      navigateToView('all-quotations');
    });
  }

  // Recalculate totals on discount input
  const discountInput = document.getElementById('quo-discount');
  if (discountInput) {
    discountInput.addEventListener('input', () => calculateQuotationTotals());
  }

  // Bind Form Submit
  if (!form.dataset.listener) {
    form.dataset.listener = 'true';
    form.addEventListener('submit', handleQuotationSubmit);
  }

  // Hide search suggestions on click outside
  document.addEventListener('click', (e) => {
    const resultsBox = document.getElementById('quo-search-results');
    const searchInput = document.getElementById('quo-product-search');
    if (resultsBox && searchInput && !resultsBox.contains(e.target) && e.target !== searchInput) {
      resultsBox.style.display = 'none';
    }
  });

  // Render initial empty table
  renderQuotationItemsTable();
}

export function initQuotationsList() {
  loadQuotationsTable();
  
  // Search filter
  const searchInput = document.getElementById('quo-search-input');
  if (searchInput) {
    searchInput.addEventListener('input', () => {
      const val = searchInput.value.toLowerCase();
      const rows = document.querySelectorAll('#quo-table-body tr');
      rows.forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(val) ? '' : 'none';
      });
    });
  }

  // Add quotation navigation btn
  const goCreateBtn = document.getElementById('btn-quo-go-create');
  if (goCreateBtn && !goCreateBtn.dataset.listener) {
    goCreateBtn.dataset.listener = 'true';
    goCreateBtn.addEventListener('click', () => {
      navigateToView('create-quotation');
    });
  }

  // Bind modal closing
  document.querySelectorAll('#quotation-detail-modal .modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('quotation-detail-modal').classList.remove('active');
    });
  });

  // Print button inside modal
  const printBtn = document.getElementById('btn-print-quotation');
  if (printBtn && !printBtn.dataset.listener) {
    printBtn.dataset.listener = 'true';
    printBtn.addEventListener('click', () => {
      window.print();
    });
  }
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

function loadCustomersSelect(elementId) {
  const select = document.getElementById(elementId);
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

function setupQuotationProductSearch() {
  const searchInput = document.getElementById('quo-product-search');
  const resultsBox = document.getElementById('quo-search-results');
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
          <div class="quo-search-item" data-id="${p.id}" style="padding: 10px 16px; cursor: pointer; transition: background 0.2s; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
            <div>
              <div style="font-weight: 700; color: var(--text-primary); font-size: 13px;">${p.name}</div>
              <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${p.sku}</div>
            </div>
            <div style="text-align: right;">
              <div style="font-family: var(--font-english); font-weight: 700; color: hsla(200, 95%, 45%, 1);">${store.getCurrencySymbol()} ${parseFloat(p.price_usd).toLocaleString()}</div>
              <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-arabic);">المستودع: ${p.stock} قطعة</div>
            </div>
          </div>
        `).join('');

        resultsBox.style.display = 'block';

        // Bind clicks
        resultsBox.querySelectorAll('.quo-search-item').forEach(item => {
          item.addEventListener('click', () => {
            const prodId = item.getAttribute('data-id');
            const prod = products.find(p => p.id == prodId);
            if (prod) {
              addProductToQuotation(prod);
            }
            searchInput.value = '';
            resultsBox.style.display = 'none';
          });
        });
      })
      .catch(err => console.error('Error searching products:', err));
  });
}

function addProductToQuotation(product) {
  const existing = quoItems.find(item => item.id == product.id);
  if (existing) {
    existing.qty += 1;
  } else {
    quoItems.push({
      id: product.id,
      name: product.name,
      sku: product.sku,
      price: parseFloat(product.price_usd),
      stock: parseInt(product.stock),
      qty: 1
    });
  }

  renderQuotationItemsTable();
}

function renderQuotationItemsTable() {
  const tbody = document.getElementById('quo-items-tbody');
  if (!tbody) return;

  if (quoItems.length === 0) {
    tbody.innerHTML = `
      <tr id="quo-empty-row">
        <td colspan="7" style="text-align: center; padding: 48px; color: var(--text-muted); font-weight: 500; font-family: var(--font-arabic);">لا توجد بنود مضافة بعد</td>
      </tr>
    `;
    calculateQuotationTotals();
    return;
  }

  tbody.innerHTML = quoItems.map((item, index) => {
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
          <input type="number" class="form-control item-qty-input" data-id="${item.id}" value="${item.qty}" min="1" style="width: 80px; height: 36px; text-align: center; font-family: var(--font-english);">
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

  // Bind change listeners
  tbody.querySelectorAll('.item-price-input').forEach(input => {
    input.addEventListener('change', (e) => {
      const id = e.target.getAttribute('data-id');
      const val = parseFloat(e.target.value) || 0;
      const item = quoItems.find(i => i.id == id);
      if (item) {
        item.price = val;
        renderQuotationItemsTable();
      }
    });
  });

  tbody.querySelectorAll('.item-qty-input').forEach(input => {
    input.addEventListener('change', (e) => {
      const id = e.target.getAttribute('data-id');
      const val = parseInt(e.target.value) || 1;
      const item = quoItems.find(i => i.id == id);
      if (item) {
        item.qty = Math.max(1, val);
        renderQuotationItemsTable();
      }
    });
  });

  tbody.querySelectorAll('.btn-item-delete').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = btn.getAttribute('data-id');
      quoItems = quoItems.filter(i => i.id != id);
      renderQuotationItemsTable();
    });
  });

  calculateQuotationTotals();
}

function calculateQuotationTotals() {
  let subtotalSum = 0;
  quoItems.forEach(item => {
    subtotalSum += item.price * item.qty;
  });

  const bottomDiscount = parseFloat(document.getElementById('quo-discount').value) || 0;
  const taxVal = (subtotalSum - bottomDiscount) * 0.15; // 15% VAT
  const total = Math.max(0, subtotalSum - bottomDiscount + taxVal);

  const subEl = document.getElementById('quo-summary-subtotal');
  const disEl = document.getElementById('quo-summary-discount');
  const taxEl = document.getElementById('quo-summary-tax');
  const totEl = document.getElementById('quo-summary-total');

  const symbol = store.getCurrencySymbol();
  if (subEl) subEl.innerText = `${symbol} ${subtotalSum.toFixed(2)}`;
  if (disEl) disEl.innerText = `${symbol} ${bottomDiscount.toFixed(2)}`;
  if (taxEl) taxEl.innerText = `${symbol} ${taxVal.toFixed(2)}`;
  if (totEl) totEl.innerText = `${symbol} ${total.toFixed(2)}`;

  return total;
}

function handleQuotationSubmit(e) {
  e.preventDefault();

  if (quoItems.length === 0) {
    alert('الرجاء إضافة عنصر واحد على الأقل لعرض السعر.');
    return;
  }

  const customerSelect = document.getElementById('quo-customer-select');
  const dateInput = document.getElementById('quo-date');
  const validUntilInput = document.getElementById('quo-valid-until');
  const noteTextarea = document.getElementById('quo-note');
  const bottomDiscount = parseFloat(document.getElementById('quo-discount').value) || 0;

  if (!customerSelect.value) {
    alert('الرجاء اختيار العميل.');
    return;
  }

  const grandTotal = calculateQuotationTotals();

  const payload = {
    customer_id: customerSelect.value,
    customer_name: customerSelect.options[customerSelect.selectedIndex].text.split(' (')[0],
    total_usd: grandTotal,
    exchange_rate: exchangeRate,
    discount_usd: bottomDiscount,
    valid_until: validUntilInput.value,
    notes: noteTextarea.value,
    items: quoItems.map(item => ({
      product_id: item.id,
      quantity: item.qty,
      price_usd: item.price
    }))
  };

  fetch('api/quotations.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(`تم تسجيل عرض السعر بنجاح (رقم العرض #QT-${data.quotation_id}).`);
      resetQuotationForm();
      navigateToView('all-quotations');
    } else {
      alert(`خطأ: ${data.message}`);
    }
  })
  .catch(err => {
    console.error('Submit quotation error:', err);
    alert('فشل الاتصال بالخادم لحفظ عرض السعر.');
  });
}

function resetQuotationForm() {
  quoItems = [];
  const form = document.getElementById('quo-quotation-form');
  if (form) form.reset();

  const dateInput = document.getElementById('quo-date');
  const validUntilInput = document.getElementById('quo-valid-until');
  if (dateInput) {
    dateInput.value = new Date().toISOString().split('T')[0];
    if (validUntilInput) {
      const nextWeek = new Date();
      nextWeek.setDate(nextWeek.getDate() + 7);
      validUntilInput.value = nextWeek.toISOString().split('T')[0];
    }
  }

  renderQuotationItemsTable();
}

/* --- LOAD LISTINGS & CONVERSION --- */

function loadQuotationsTable() {
  const tbody = document.getElementById('quo-table-body');
  if (!tbody) return;

  fetch('api/quotations.php')
    .then(res => res.json())
    .then(quotations => {
      if (quotations.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center" style="padding:20px; color:var(--text-muted);">لا توجد عروض أسعار مسجلة بعد.</td></tr>`;
        return;
      }

      tbody.innerHTML = quotations.map(q => {
        const formattedDate = new Date(q.date).toLocaleDateString('ar-SA', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
        
        const validDate = q.valid_until ? new Date(q.valid_until).toLocaleDateString('ar-SA', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        }) : 'غير محدد';

        const statusMap = {
          'Draft': { text: 'مسودة', class: 'badge-info' },
          'Sent': { text: 'تم الإرسال', class: 'badge-warning' },
          'Accepted': { text: 'مقبول', class: 'badge-success' },
          'Rejected': { text: 'مرفوض', class: 'badge-danger' },
          'Converted': { text: 'تم التحويل لفاتورة', class: 'badge-success' }
        };
        const status = statusMap[q.status] || { text: q.status, class: 'badge-info' };

        return `
          <tr>
            <td style="font-family: var(--font-english); font-weight:700;">#QT-${q.id}</td>
            <td>
              <div style="font-weight:600;">${q.customer_name}</div>
              <div style="font-size:11px; color:var(--text-muted);">${formattedDate}</div>
            </td>
            <td style="font-family: var(--font-english); font-weight: 600;">${validDate}</td>
            <td style="font-family: var(--font-english); font-weight:700; color:hsla(200, 95%, 45%, 1);">${store.getCurrencySymbol()} ${parseFloat(q.total_usd).toLocaleString()}</td>
            <td><span class="badge ${status.class}">${status.text}</span></td>
            <td>
              <div style="display:flex; gap:6px;">
                <button class="btn btn-secondary btn-sm btn-preview-quo" data-id="${q.id}">
                  <i class="fas fa-eye"></i> معاينة العرض
                </button>
                ${q.status !== 'Converted' ? `
                  <button class="btn btn-success btn-sm btn-convert-quo" data-id="${q.id}" style="background-color: hsla(142, 70%, 45%, 1); border:none;">
                    <i class="fas fa-file-invoice"></i> تحويل لفاتورة
                  </button>
                ` : ''}
              </div>
            </td>
          </tr>
        `;
      }).join('');

      // Bind actions
      tbody.querySelectorAll('.btn-preview-quo').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const id = e.currentTarget.getAttribute('data-id');
          previewQuotation(id);
        });
      });

      tbody.querySelectorAll('.btn-convert-quo').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const id = e.currentTarget.getAttribute('data-id');
          if (confirm(`هل أنت متأكد من تحويل عرض السعر #QT-${id} إلى فاتورة مبيعات معتمدة؟ سيؤدي ذلك إلى خصم كمية الأصناف من المخازن فورياً.`)) {
            convertQuotationToInvoice(id);
          }
        });
      });

    })
    .catch(err => console.error('Error loading quotations:', err));
}

function previewQuotation(id) {
  fetch(`api/quotations.php?id=${id}`)
    .then(res => res.json())
    .then(q => {
      document.getElementById('quo-detail-id').innerText = `QT-${q.id}`;
      
      const qDate = new Date(q.date).toLocaleDateString('ar-SA') + ' ' + new Date(q.date).toLocaleTimeString('ar-SA');
      document.getElementById('quo-detail-date').innerText = qDate;
      document.getElementById('quo-detail-cust-name').innerText = q.customer_name;
      
      const validDate = q.valid_until ? new Date(q.valid_until).toLocaleDateString('ar-SA') : 'غير محدد';
      document.getElementById('quo-detail-valid-until').innerText = validDate;

      // Populate items
      const tbody = document.getElementById('quo-detail-items-body');
      tbody.innerHTML = q.items.map(item => {
        const lineTotal = item.quantity * item.price_usd;
        return `
          <tr>
            <td>
              <div style="font-weight: 700; color: var(--text-primary);">${item.product_name}</div>
              <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${item.product_sku}</div>
            </td>
            <td style="font-family: var(--font-english);">${store.getCurrencySymbol()} ${parseFloat(item.price_usd).toFixed(2)}</td>
            <td style="text-align: center; font-family: var(--font-english);">${item.quantity}</td>
            <td style="font-family: var(--font-english); font-weight: 700;">${store.getCurrencySymbol()} ${lineTotal.toFixed(2)}</td>
          </tr>
        `;
      }).join('');

      // Calculations
      const subtotal = q.total_usd / 1.15; // simple tax reverse calc
      const tax = q.total_usd - subtotal;
      
      const symbol = store.getCurrencySymbol();
      document.getElementById('quo-detail-subtotal').innerText = `${symbol} ${subtotal.toFixed(2)}`;
      document.getElementById('quo-detail-discount').innerText = `${symbol} ${parseFloat(q.discount_usd).toFixed(2)}`;
      document.getElementById('quo-detail-tax').innerText = `${symbol} ${tax.toFixed(2)}`;
      document.getElementById('quo-detail-total').innerText = `${symbol} ${parseFloat(q.total_usd).toFixed(2)}`;

      // Notes
      const notesContainer = document.getElementById('quo-detail-notes-container');
      if (q.notes) {
        document.getElementById('quo-detail-notes').innerText = q.notes;
        notesContainer.style.display = 'block';
      } else {
        notesContainer.style.display = 'none';
      }

      // Convert button in modal
      const convertBtn = document.getElementById('btn-convert-quotation-to-invoice');
      if (q.status === 'Converted') {
        convertBtn.style.display = 'none';
      } else {
        convertBtn.style.display = 'flex';
        // Clean listeners
        const newConvertBtn = convertBtn.cloneNode(true);
        convertBtn.parentNode.replaceChild(newConvertBtn, convertBtn);
        
        newConvertBtn.addEventListener('click', () => {
          if (confirm(`هل أنت متأكد من تحويل عرض السعر #QT-${q.id} إلى فاتورة مبيعات معتمدة؟ سيؤدي ذلك إلى خصم كمية الأصناف من المخازن فورياً.`)) {
            convertQuotationToInvoice(q.id);
            document.getElementById('quotation-detail-modal').classList.remove('active');
          }
        });
      }

      // Show modal
      document.getElementById('quotation-detail-modal').classList.add('active');
    })
    .catch(err => console.error('Error fetching quotation details:', err));
}

function convertQuotationToInvoice(id) {
  fetch('api/quotations.php?action=convert', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ id: id })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(`تم تحويل عرض السعر بنجاح إلى فاتورة مبيعات معتمدة رقم #${data.order_id} وتم خصم البضائع من المستودع.`);
      loadQuotationsTable();
    } else {
      alert(`فشل التحويل: ${data.message}`);
    }
  })
  .catch(err => {
    console.error('Convert quotation error:', err);
    alert('فشل الاتصال بالخادم لتحويل عرض السعر.');
  });
}
