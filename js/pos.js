import { store } from './store.js';
import { initDashboard } from './dashboard.js';
import { renderInventoryTable } from './inventory.js';

let cart = []; // Array of { productId, quantity, price }
let activeCategory = 'all';

export function initPOS() {
  renderPOSCategories();
  renderPOSProducts();
  renderCart();
  setupPOSEventListeners();
}

function renderPOSCategories() {
  const products = store.getProducts();
  const categories = [...new Set(products.map(p => p.category))];
  const container = document.getElementById('pos-categories-nav');
  
  let html = `<button class="pos-cat-btn ${activeCategory === 'all' ? 'active' : ''}" data-cat="all">كل المنتجات</button>`;
  categories.forEach(cat => {
    html += `<button class="pos-cat-btn ${activeCategory === cat ? 'active' : ''}" data-cat="${cat}">${cat}</button>`;
  });
  
  container.innerHTML = html;
  
  // Attach filter events
  document.querySelectorAll('.pos-cat-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      document.querySelectorAll('.pos-cat-btn').forEach(b => b.classList.remove('active'));
      e.currentTarget.classList.add('active');
      activeCategory = e.currentTarget.getAttribute('data-cat');
      renderPOSProducts();
    });
  });
}

export function renderPOSProducts() {
  const products = store.getProducts();
  const grid = document.getElementById('pos-products-grid');
  
  const searchVal = document.getElementById('pos-search-input').value.toLowerCase();
  
  // Filter products that are published (or just all valid warehouse products)
  const filtered = products.filter(p => {
    const matchesSearch = p.name.toLowerCase().includes(searchVal) || p.sku.toLowerCase().includes(searchVal);
    const matchesCategory = activeCategory === 'all' || p.category === activeCategory;
    const matchesChannel = !p.salesChannels || p.salesChannels.includes('pos');
    return matchesSearch && matchesCategory && matchesChannel;
  });
  
  if (filtered.length === 0) {
    grid.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 48px; color: var(--text-muted);">لا توجد منتجات مطابقة.</div>`;
    return;
  }
  
  grid.innerHTML = filtered.map(p => {
    const isOutOfStock = p.stock === 0;
    const isLowStock = p.stock > 0 && p.stock <= p.minStock;
    
    let stockClass = 'in-stock';
    let stockText = `متوفر: ${p.stock} قطعة`;
    if (isOutOfStock) {
      stockClass = 'low-stock';
      stockText = 'نفد المخزون';
    } else if (isLowStock) {
      stockClass = 'low-stock';
      stockText = `منخفض: ${p.stock} قطعة`;
    }
    
    // Icon category mapping
    let iconClass = 'fa-box';
    if (p.category.includes('إلكترونيات') || p.category.includes('هواتف')) iconClass = 'fa-laptop';
    else if (p.category.includes('ملابس') || p.category.includes('أحذية')) iconClass = 'fa-tshirt';
    
    const thumbnail = p.imageUrl 
      ? `<img src="${p.imageUrl}" alt="${p.name}">` 
      : `<i class="fas ${iconClass}"></i>`;
      
    return `
      <div class="pos-product-card ${isOutOfStock ? 'out-of-stock' : ''}" data-id="${p.id}">
        <span class="pos-card-stock ${stockClass}">${stockText}</span>
        <div class="pos-card-thumb">
          ${thumbnail}
        </div>
        <div class="pos-card-info">
          <span class="pos-card-name">${p.name}</span>
          <span class="pos-card-price">${store.getCurrencySymbol()} ${p.price.toLocaleString()}</span>
        </div>
      </div>
    `;
  }).join('');
  
  // Attach select click handlers
  document.querySelectorAll('.pos-product-card:not(.out-of-stock)').forEach(card => {
    card.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      addToCart(id);
    });
  });
}

function setupPOSEventListeners() {
  document.getElementById('pos-search-input').addEventListener('input', renderPOSProducts);
  
  // Clear cart
  document.getElementById('btn-pos-clear-cart').addEventListener('click', () => {
    cart = [];
    renderCart();
  });
  
  // Checkout button
  document.getElementById('btn-pos-checkout').addEventListener('click', handleCheckout);
  
  // Print POS receipt modal btn
  document.getElementById('btn-print-pos-receipt').addEventListener('click', () => {
    window.print();
  });
  
  // Close receipt dialog
  document.querySelectorAll('#pos-receipt-modal .modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('pos-receipt-modal').classList.remove('active');
    });
  });
}

function addToCart(productId) {
  const products = store.getProducts();
  const product = products.find(p => p.id === productId);
  
  if (!product || product.stock === 0) return;
  
  const existingIndex = cart.findIndex(item => item.productId === productId);
  
  if (existingIndex > -1) {
    // Check if adding more exceeds stock
    if (cart[existingIndex].quantity + 1 > product.stock) {
      alert(`عذراً، لا يمكن تجاوز كمية المخزون المتاحة للمنتج (${product.stock} قطع).`);
      return;
    }
    cart[existingIndex].quantity += 1;
  } else {
    cart.push({
      productId: productId,
      quantity: 1,
      price: product.price
    });
  }
  
  renderCart();
}

function renderCart() {
  const products = store.getProducts();
  const container = document.getElementById('pos-cart-items-list');
  
  if (cart.length === 0) {
    container.innerHTML = `
      <div style="text-align: center; color: var(--text-muted); margin: auto; padding: 24px;">
        <i class="fas fa-shopping-basket" style="font-size: 32px; margin-bottom: 8px; opacity: 0.5;"></i>
        <p style="font-size: 13px;">السلة فارغة حالياً</p>
      </div>
    `;
    updateCartTotals(0);
    return;
  }
  
  container.innerHTML = cart.map(item => {
    const prod = products.find(p => p.id === item.productId);
    if (!prod) return '';
    
    const subtotal = item.quantity * item.price;
    
    return `
      <div class="pos-cart-item">
        <div class="pos-cart-item-info">
          <span class="pos-cart-item-name" title="${prod.name}">${prod.name}</span>
          <span class="pos-cart-item-price">${store.getCurrencySymbol()} ${item.price.toLocaleString()}</span>
        </div>
        <div class="pos-qty-btn-group" style="display: flex; align-items: center; gap: 8px;">
          <button type="button" class="pos-qty-btn btn-cart-plus" data-id="${item.productId}">+</button>
          <span style="font-family: var(--font-english); font-weight: 600; font-size: 13px;">${item.quantity}</span>
          <button type="button" class="pos-qty-btn btn-cart-minus" data-id="${item.productId}">-</button>
        </div>
        <span class="pos-cart-item-val">${store.getCurrencySymbol()} ${subtotal.toLocaleString()}</span>
      </div>
    `;
  }).join('');
  
  // Attach listeners
  document.querySelectorAll('.btn-cart-plus').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      addToCart(id);
    });
  });
  
  document.querySelectorAll('.btn-cart-minus').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      decreaseCartQuantity(id);
    });
  });
  
  // Calculate total
  const cartSubtotal = cart.reduce((sum, item) => sum + (item.quantity * item.price), 0);
  updateCartTotals(cartSubtotal);
}

function decreaseCartQuantity(productId) {
  const existingIndex = cart.findIndex(item => item.productId === productId);
  if (existingIndex > -1) {
    if (cart[existingIndex].quantity === 1) {
      cart.splice(existingIndex, 1);
    } else {
      cart[existingIndex].quantity -= 1;
    }
    renderCart();
  }
}

function updateCartTotals(subtotal) {
  const tax = Math.round(subtotal * 0.15); // 15% VAT
  const total = subtotal + tax;
  
  const symbol = store.getCurrencySymbol();
  document.getElementById('pos-subtotal').innerText = `${symbol} ${subtotal.toLocaleString()}`;
  document.getElementById('pos-tax').innerText = `${symbol} ${tax.toLocaleString()}`;
  document.getElementById('pos-total').innerText = `${symbol} ${total.toLocaleString()}`;
}

function handleCheckout() {
  if (cart.length === 0) {
    alert('سلة المشتريات فارغة!');
    return;
  }
  
  const products = store.getProducts();
  const orders = store.getOrders();
  
  // 1. Generate Order details
  const orderId = (1000 + orders.length + 1).toString();
  const date = new Date().toISOString();
  
  const subtotal = cart.reduce((sum, item) => sum + (item.quantity * item.price), 0);
  const tax = Math.round(subtotal * 0.15);
  const total = subtotal + tax;
  
  // 2. Perform Stock Deductions & Verify Stock Availability
  let hasStockIssue = false;
  let stockIssueMessage = '';
  
  cart.forEach(item => {
    const prod = products.find(p => p.id === item.productId);
    if (prod && prod.stock < item.quantity) {
      hasStockIssue = true;
      stockIssueMessage += `المنتج "${prod.name}" لا يحتوي على مخزون كافٍ (المتاح: ${prod.stock} قطع، المطلوب: ${item.quantity}).\n`;
    }
  });
  
  if (hasStockIssue) {
    alert(stockIssueMessage);
    return;
  }
  
  // 3. Deduct stock and commit database changes
  cart.forEach(item => {
    // Deduct stock levels (negative amount)
    store.updateProductStock(item.productId, -item.quantity, `مبيعات نقطة البيع POS (فاتورة #${orderId})`);
  });
  
  // 4. Save new Order record
  const newOrder = {
    id: orderId,
    customerName: 'عميل نقطة البيع (نقدي)',
    date: date,
    status: 'Delivered', // POS is paid and delivered instantly
    total: subtotal, // Save subtotal into total sales field
    items: cart.map(item => ({
      productId: item.productId,
      quantity: item.quantity,
      price: item.price
    }))
  };
  
  orders.unshift(newOrder); // Add to top of order history
  store.saveOrders(orders);
  
  // Record success log
  const symbol = store.getCurrencySymbol();
  store.addActivity('success', 'عملية بيع POS', `تم إتمام عملية بيع نقدي بقيمة ${symbol} ${total} فاتورة #${orderId}`);
  
  // 5. Open Receipt Modal for cashiers print preview
  showReceipt(newOrder, subtotal, tax, total);
  
  // 6. Reset POS cart and redraw POS grid with newly adjusted stocks
  cart = [];
  renderCart();
  renderPOSProducts();
  renderPOSCategories();
  
  // Sync other views if active
  if (document.getElementById('inventory-table-body')) {
    renderInventoryTable();
  }
  initDashboard(); // Recalculate KPIs
}

function showReceipt(order, subtotal, tax, total) {
  const products = store.getProducts();
  
  document.getElementById('receipt-order-id-label').innerText = order.id;
  
  const dateStr = new Date(order.date).toLocaleDateString('ar-EG', {
    year: 'numeric',
    month: 'numeric',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
  document.getElementById('receipt-date-label').innerText = dateStr;
  
  const tbody = document.getElementById('receipt-items-body');
  tbody.innerHTML = order.items.map(item => {
    const prod = products.find(p => p.id === item.productId) || { name: 'منتج مجهول' };
    const lineTotal = item.quantity * item.price;
    const symbol = store.getCurrencySymbol();
    return `
      <tr>
        <td>
          <div style="font-weight:600; line-height:1.2;">${prod.name}</div>
          <div style="font-size:9px; color:var(--text-muted); font-family:var(--font-english);">${symbol} ${item.price.toLocaleString()}</div>
        </td>
        <td style="text-align: center; font-family:var(--font-english);">${item.quantity}</td>
        <td style="text-align: left; font-family:var(--font-english); font-weight:700;">${symbol} ${lineTotal.toLocaleString()}</td>
      </tr>
    `;
  }).join('');
  
  const symbol = store.getCurrencySymbol();
  document.getElementById('receipt-subtotal-label').innerText = `${symbol} ${subtotal.toLocaleString()}`;
  document.getElementById('receipt-tax-label').innerText = `${symbol} ${tax.toLocaleString()}`;
  document.getElementById('receipt-total-label').innerText = `${symbol} ${total.toLocaleString()}`;
  
  document.getElementById('pos-receipt-modal').classList.add('active');
}
