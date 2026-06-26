import { store } from './store.js';
import { initDashboard } from './dashboard.js';
import { navigateToView } from './app.js';

let editingProductId = null;
let adjustingProductId = null;

const MOCK_IMAGES = [
  'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400', // Headphones
  'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400', // Watch
  'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400', // Sneaker
  'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=400'  // Glasses
];
let selectedImageUrl = null;

export function initInventory() {
  renderInventoryTable();
  populateCategoriesFilter();
  setupEventListeners();
  initTransfers();
  initAdjustments();
}

function populateCategoriesFilter() {
  const products = store.getProducts();
  const categories = [...new Set(products.map(p => p.category))];
  const select = document.getElementById('filter-category');
  
  select.innerHTML = '<option value="all">كل الفئات</option>';
  categories.forEach(cat => {
    select.innerHTML += `<option value="${cat}">${cat}</option>`;
  });
}

export function renderInventoryTable() {
  const products = store.getProducts();
  const tbody = document.getElementById('inventory-table-body');
  
  const searchVal = document.getElementById('inventory-search').value.toLowerCase();
  const catVal = document.getElementById('filter-category').value;
  const stockVal = document.getElementById('filter-stock').value;
  
  const sidebar = document.querySelector('.sidebar');
  const activePortal = sidebar ? (sidebar.getAttribute('data-active-portal') || 'erp') : 'erp';

  // Filter products
  const filteredProducts = products.filter(p => {
    const matchesSearch = p.name.toLowerCase().includes(searchVal) || p.sku.toLowerCase().includes(searchVal);
    const matchesCategory = catVal === 'all' || p.category === catVal;
    
    let matchesStock = true;
    if (stockVal === 'instock') matchesStock = p.stock > p.minStock;
    else if (stockVal === 'lowstock') matchesStock = p.stock > 0 && p.stock <= p.minStock;
    else if (stockVal === 'outofstock') matchesStock = p.stock === 0;
    
    let matchesPortalChannel = true;
    if (activePortal === 'ecommerce') {
      matchesPortalChannel = p.salesChannels && p.salesChannels.includes('ecommerce');
    } else if (activePortal === 'pos') {
      matchesPortalChannel = p.salesChannels && p.salesChannels.includes('pos');
    }
    
    return matchesSearch && matchesCategory && matchesStock && matchesPortalChannel;
  });
  
  const btnAddProduct = document.getElementById('btn-add-product');
  if (btnAddProduct) {
    if (activePortal === 'erp') {
      btnAddProduct.style.display = 'inline-flex';
    } else {
      btnAddProduct.style.display = 'none';
    }
  }

  if (filteredProducts.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center" style="text-align: center; padding: 32px; color: var(--text-muted);">لا توجد منتجات تطابق البحث.</td></tr>`;
    return;
  }
  
  tbody.innerHTML = filteredProducts.map(p => {
    let statusText = 'متوفر';
    let badgeClass = 'badge-success';
    if (p.stock === 0) {
      statusText = 'نفد المخزون';
      badgeClass = 'badge-danger';
    } else if (p.stock <= p.minStock) {
      statusText = 'مخزون منخفض';
      badgeClass = 'badge-warning';
    }
    
    const isERP = activePortal === 'erp';
    const isEco = activePortal === 'ecommerce';
    let actionButtons = '';
    
    if (isERP) {
      actionButtons = `
        <div style="display: flex; gap: 6px;">
          <button class="btn btn-secondary btn-sm btn-icon btn-adjust-stock" data-id="${p.id}" title="تعديل سريع للمخزون">
            <i class="fas fa-boxes"></i>
          </button>
          <button class="btn btn-secondary btn-sm btn-icon btn-edit-product" data-id="${p.id}" title="تعديل تفاصيل المنتج">
            <i class="fas fa-edit"></i>
          </button>
          <button class="btn btn-secondary btn-sm btn-icon btn-delete-product" data-id="${p.id}" style="color: hsla(var(--danger), 1);" title="حذف المنتج">
            <i class="fas fa-trash"></i>
          </button>
        </div>
      `;
    } else if (isEco) {
      actionButtons = `
        <div style="display: flex; gap: 6px;">
          <button class="btn btn-secondary btn-sm btn-edit-product" data-id="${p.id}" style="padding: 0 8px; font-size: 11px; height: 30px; display: inline-flex; align-items: center; gap: 4px;" title="تعديل تفاصيل المنتج">
            <i class="fas fa-edit"></i> تعديل المنتج
          </button>
        </div>
      `;
    } else {
      actionButtons = `<span style="font-size: 11px; color: var(--text-muted); font-style: italic; background: var(--bg-tertiary); padding: 4px 8px; border-radius: 4px; border: 1px solid var(--border-color);">عرض فقط</span>`;
    }
    
    // Choose icon depending on category
    let iconClass = 'fa-box';
    if (p.category.includes('إلكترونيات') || p.category.includes('هواتف')) {
      iconClass = 'fa-laptop';
    } else if (p.category.includes('ملابس') || p.category.includes('أحذية') || p.category.includes('أزياء')) {
      iconClass = 'fa-tshirt';
    } else if (p.category.includes('كتب') || p.category.includes('مكتبة')) {
      iconClass = 'fa-book-open';
    }
    
    // Check if custom image URL exists
    const hasImage = p.imageUrl ? true : false;
    const thumbnailMarkup = hasImage 
      ? `<img src="${p.imageUrl}" style="width: 40px; height: 40px; border-radius: var(--border-radius-xs); object-fit: cover; border: 1px solid var(--border-color);">`
      : `<div style="width: 40px; height: 40px; border-radius: var(--border-radius-xs); background-color: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; color: hsla(var(--primary), 1); font-size: 18px;">
          <i class="fas ${iconClass}"></i>
         </div>`;
    
    const channels = p.salesChannels || [];
    const channelBadges = channels.map(c => {
      if (c === 'ecommerce') return '<span style="background: rgba(111,66,193,0.1); color: hsla(260,60%,50%,1); border: 1px solid rgba(111,66,193,0.25); padding: 1px 4px; border-radius: 4px; font-size: 9px; font-weight: 700; white-space: nowrap;">متجر</span>';
      if (c === 'pos') return '<span style="background: rgba(6,182,212,0.1); color: rgb(6,182,212); border: 1px solid rgba(6,182,212,0.25); padding: 1px 4px; border-radius: 4px; font-size: 9px; font-weight: 700; white-space: nowrap;">POS</span>';
      if (c === 'wholesale') return '<span style="background: rgba(16,185,129,0.1); color: rgb(16,185,129); border: 1px solid rgba(16,185,129,0.25); padding: 1px 4px; border-radius: 4px; font-size: 9px; font-weight: 700; white-space: nowrap;">جملة</span>';
      if (c === 'social') return '<span style="background: rgba(245,158,11,0.1); color: rgb(245,158,11); border: 1px solid rgba(245,158,11,0.25); padding: 1px 4px; border-radius: 4px; font-size: 9px; font-weight: 700; white-space: nowrap;">سوشيال</span>';
      return '';
    }).join(' ');

    return `
      <tr>
        <td>
          <div style="display: flex; align-items: center; gap: 12px;">
            ${thumbnailMarkup}
            <div style="display: flex; flex-direction: column;">
              <a href="#" class="js-view-product-details" data-id="${p.id}" style="font-weight: 600; color: hsla(var(--primary), 1); text-decoration: none;">${p.name}</a>
              <div style="display: flex; align-items: center; gap: 8px; margin-top: 3px; flex-wrap: wrap;">
                <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english); margin-bottom: 0;">${p.sku}</span>
                <div style="display: flex; gap: 3px; align-items: center;">${channelBadges}</div>
              </div>
            </div>
          </div>
        </td>
        <td>${p.category}</td>
        <td style="font-family: var(--font-english); font-weight: 600;">$${p.price.toLocaleString()}</td>
        <td style="font-family: var(--font-english); color: var(--text-muted);">$${p.cost.toLocaleString()}</td>
        <td style="font-family: var(--font-english); font-weight: 700;">
          <span style="color: ${p.stock === 0 ? 'hsla(var(--danger), 1)' : p.stock <= p.minStock ? 'hsla(var(--warning), 1)' : 'inherit'}">${p.stock}</span>
        </td>
        <td><span class="badge ${badgeClass}">${statusText}</span></td>
        <td>
          ${actionButtons}
        </td>
      </tr>
    `;
  }).join('');
  
  // Attach quick handlers
  document.querySelectorAll('.btn-adjust-stock').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      openAdjustStockModal(id);
    });
  });
  
  document.querySelectorAll('.btn-edit-product').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      openEditProductModal(id);
    });
  });

  document.querySelectorAll('.btn-delete-product').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      deleteProduct(id);
    });
  });

  document.querySelectorAll('.js-view-product-details').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault();
      const id = e.currentTarget.getAttribute('data-id');
      openProductDetailsPage(id);
    });
  });
}

function setupEventListeners() {
  // Search & Filter listeners
  document.getElementById('inventory-search').addEventListener('input', renderInventoryTable);
  document.getElementById('filter-category').addEventListener('change', renderInventoryTable);
  document.getElementById('filter-stock').addEventListener('change', renderInventoryTable);
  
  // Navigate to Add Product (WooCommerce full page)
  document.getElementById('btn-add-product').addEventListener('click', () => {
    openAddProductModal();
  });
  
  // Close modals (only for fast adjust stock modal now)
  document.querySelectorAll('.modal-close').forEach(btn => {
    btn.addEventListener('click', () => {
      closeModals();
    });
  });
  
  // Stock adjustments modal form
  document.getElementById('stock-adjust-form').addEventListener('submit', handleStockAdjustmentSubmit);

  /* --- WOOCOMMERCE FORM EVENT LISTENERS --- */
  
  // Cancel / Return button
  document.getElementById('btn-woo-cancel').addEventListener('click', () => {
    navigateToView('inventory');
  });
  
  // WooCommerce Tabs Navigation toggling
  const tabBtns = document.querySelectorAll('.woo-tab-btn');
  tabBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      tabBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      
      const targetId = btn.getAttribute('data-target');
      const panels = document.querySelectorAll('.woo-panel');
      panels.forEach(panel => {
        if (panel.id === targetId) {
          panel.classList.add('active');
        } else {
          panel.classList.remove('active');
        }
      });
    });
  });

  // Adding category inline
  document.getElementById('btn-woo-add-category').addEventListener('click', () => {
    const input = document.getElementById('woo-new-category-input');
    const catName = input.value.trim();
    if (catName) {
      addCategoryChecklistItem(catName, true);
      input.value = '';
      
      // Update the main general category input field automatically
      document.getElementById('woo-prod-category').value = catName;
    }
  });

  // Sync general category input with checklist selection
  document.getElementById('woo-prod-category').addEventListener('input', (e) => {
    const val = e.target.value.trim().toLowerCase();
    document.querySelectorAll('input[name="woo-category-check"]').forEach(cb => {
      cb.checked = (cb.value.toLowerCase() === val);
    });
  });

  // Image upload selector simulation
  document.getElementById('woo-image-picker-box').addEventListener('click', () => {
    // Cycles through mock images
    const index = Math.floor(Math.random() * MOCK_IMAGES.length);
    selectedImageUrl = MOCK_IMAGES[index];
    
    const previewImg = document.getElementById('woo-image-preview-img');
    const uploadIcon = document.querySelector('#woo-image-picker-box i');
    const uploadText = document.querySelector('#woo-image-picker-box span');
    
    previewImg.src = selectedImageUrl;
    previewImg.style.display = 'block';
    
    if (uploadIcon) uploadIcon.style.display = 'none';
    if (uploadText) uploadText.style.display = 'none';
  });

  // Save Draft WooCommerce button
  document.getElementById('btn-woo-save-draft').addEventListener('click', (e) => {
    e.preventDefault();
    saveWooProduct(true);
  });

  // Publish Form WooCommerce submit
  document.getElementById('woo-product-form').addEventListener('submit', (e) => {
    e.preventDefault();
    saveWooProduct(false);
  });
}

function closeModals() {
  document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.classList.remove('active');
  });
  adjustingProductId = null;
}

function openAdjustStockModal(id) {
  adjustingProductId = id;
  const products = store.getProducts();
  const product = products.find(p => p.id === id);
  
  if (product) {
    document.getElementById('adjust-prod-name').innerText = product.name;
    document.getElementById('adjust-current-stock').innerText = product.stock;
    document.getElementById('adjust-amount').value = 1;
    document.getElementById('adjust-reason').value = '';
    
    document.getElementById('stock-adjust-modal').classList.add('active');
  }
}

function handleStockAdjustmentSubmit(e) {
  e.preventDefault();
  
  const type = document.getElementById('adjust-type').value;
  let amount = parseInt(document.getElementById('adjust-amount').value);
  const reason = document.getElementById('adjust-reason').value;
  
  if (type === 'subtract') {
    amount = -amount;
  }
  
  const updated = store.updateProductStock(adjustingProductId, amount, reason);
  
  if (updated) {
    closeModals();
    renderInventoryTable();
    initDashboard();
  }
}

/* --- WOOCOMMERCE FORM OPERATIONS --- */

function renderCategoriesChecklist(selectedCategory = '') {
  const categories = store.getCategories();
  const listContainer = document.getElementById('woo-categories-list');
  
  listContainer.innerHTML = categories.map(cat => `
    <label class="woo-category-item">
      <input type="checkbox" name="woo-category-check" value="${cat.name}" ${cat.name === selectedCategory ? 'checked' : ''}>
      <span>${cat.name}</span>
    </label>
  `).join('');
  
  // Set listener on checkboxes to auto-populate Category input in General panel
  document.querySelectorAll('input[name="woo-category-check"]').forEach(checkbox => {
    checkbox.addEventListener('change', (e) => {
      if (e.target.checked) {
        // Uncheck others (WooCommerce usually allows multiple, but for our database we support one category per product)
        document.querySelectorAll('input[name="woo-category-check"]').forEach(cb => {
          if (cb !== e.target) cb.checked = false;
        });
        document.getElementById('woo-prod-category').value = e.target.value;
      }
    });
  });
}

function addCategoryChecklistItem(name, checked = false) {
  const listContainer = document.getElementById('woo-categories-list');
  
  // Check if category already exists in storage, if not, add it
  const categories = store.getCategories();
  const existingInStorage = categories.find(c => c.name.toLowerCase() === name.toLowerCase());
  
  if (!existingInStorage) {
    categories.push({
      id: Date.now().toString(),
      name: name,
      slug: name,
      description: `تصنيف مضاف من محرر المنتجات`
    });
    store.saveCategories(categories);
    populateCategoriesFilter();
  }
  
  // Render full checklist again to keep sync
  renderCategoriesChecklist(name);
}

function resetImagePicker() {
  selectedImageUrl = null;
  const previewImg = document.getElementById('woo-image-preview-img');
  const uploadIcon = document.querySelector('#woo-image-picker-box i');
  const uploadText = document.querySelector('#woo-image-picker-box span');
  
  previewImg.src = '';
  previewImg.style.display = 'none';
  if (uploadIcon) uploadIcon.style.display = 'block';
  if (uploadText) uploadText.style.display = 'block';
}

function openAddProductModal() {
  editingProductId = null;
  
  // Reset forms and view attributes
  document.getElementById('woo-product-form').reset();
  document.getElementById('woo-editor-breadcrumb').innerText = 'إضافة منتج جديد';
  document.getElementById('woo-status-label').innerText = 'مسودة';
  document.getElementById('woo-prod-stock').value = 0;
  document.getElementById('woo-prod-stock-status').value = 'outofstock';
  resetImagePicker();
  
  // Reset tabs to General
  document.querySelector('.woo-tab-btn[data-target="panel-general"]').click();
  
  // Auto SKU
  const rand = Math.floor(1000 + Math.random() * 9000);
  document.getElementById('woo-prod-sku').value = `NS-NEW-${rand}`;
  
  // Reset sales channels checkboxes to default (eCommerce and POS active)
  document.getElementById('woo-channel-ecommerce').checked = true;
  document.getElementById('woo-channel-pos').checked = true;
  document.getElementById('woo-channel-wholesale').checked = false;
  document.getElementById('woo-channel-social').checked = false;
  
  // Render categories checkboxes
  renderCategoriesChecklist();
  
  // Go to view
  navigateToView('add-product');
}

function openEditProductModal(id) {
  editingProductId = id;
  const products = store.getProducts();
  const product = products.find(p => p.id === id);
  
  if (product) {
    document.getElementById('woo-editor-breadcrumb').innerText = `تعديل المنتج: ${product.name}`;
    document.getElementById('woo-status-label').innerText = 'منشور';
    
    // Fill main properties
    document.getElementById('woo-prod-name').value = product.name;
    document.getElementById('woo-prod-desc').value = product.description || '';
    
    // General Tab
    document.getElementById('woo-prod-price').value = product.price;
    document.getElementById('woo-prod-sale-price').value = product.salePrice || '';
    document.getElementById('woo-prod-category').value = product.category;
    
    // Inventory Tab
    document.getElementById('woo-prod-sku').value = product.sku;
    document.getElementById('woo-prod-min-stock').value = product.minStock;
    document.getElementById('woo-prod-stock').value = product.stock;
    document.getElementById('woo-prod-stock-status').value = product.stock > 0 ? 'instock' : 'outofstock';
    
    // Shipping Tab
    document.getElementById('woo-prod-weight').value = product.weight || '';
    document.getElementById('woo-prod-shipping-class').value = product.shippingClass || 'standard';
    
    // SEO & Ecommerce Tab
    document.getElementById('woo-prod-seo-title').value = product.seoTitle || '';
    document.getElementById('woo-prod-seo-desc').value = product.seoDescription || '';
    document.getElementById('woo-prod-seo-keywords').value = product.seoKeywords || '';
    document.getElementById('woo-prod-og-title').value = product.ogTitle || '';
    document.getElementById('woo-prod-og-desc').value = product.ogDescription || '';
    document.getElementById('woo-prod-og-image').value = product.ogImage || '';
    document.getElementById('woo-prod-google-category').value = product.googleProductCategory || '';
    document.getElementById('woo-prod-gtin').value = product.gtin || '';
    document.getElementById('woo-prod-mpn').value = product.mpn || '';
    
    // Short Description
    document.getElementById('woo-prod-short-desc').value = product.shortDescription || '';
    
    // Populate Sales Channels
    const channels = product.salesChannels || ['ecommerce', 'pos'];
    document.getElementById('woo-channel-ecommerce').checked = channels.includes('ecommerce');
    document.getElementById('woo-channel-pos').checked = channels.includes('pos');
    document.getElementById('woo-channel-wholesale').checked = channels.includes('wholesale');
    document.getElementById('woo-channel-social').checked = channels.includes('social');
    
    // Image Preview
    resetImagePicker();
    if (product.imageUrl) {
      selectedImageUrl = product.imageUrl;
      const previewImg = document.getElementById('woo-image-preview-img');
      const uploadIcon = document.querySelector('#woo-image-picker-box i');
      const uploadText = document.querySelector('#woo-image-picker-box span');
      
      previewImg.src = selectedImageUrl;
      previewImg.style.display = 'block';
      if (uploadIcon) uploadIcon.style.display = 'none';
      if (uploadText) uploadText.style.display = 'none';
    }
    
    // Reset tabs to General
    document.querySelector('.woo-tab-btn[data-target="panel-general"]').click();
    
    // Render categories checkboxes (and check active one)
    renderCategoriesChecklist(product.category);
    
    // Go to view
    navigateToView('add-product');
  }
}

function saveWooProduct(isDraft = false) {
  const name = document.getElementById('woo-prod-name').value.trim();
  const sku = document.getElementById('woo-prod-sku').value.trim();
  const category = document.getElementById('woo-prod-category').value.trim();
  const price = parseFloat(document.getElementById('woo-prod-price').value) || 0;
  const existingProduct = editingProductId ? store.getProducts().find(p => p.id === editingProductId) : null;
  const cost = existingProduct ? (existingProduct.cost || price * 0.75) : price * 0.75;
  const stock = existingProduct ? existingProduct.stock : 0;
  const minStock = parseInt(document.getElementById('woo-prod-min-stock').value) || 5;
  
  // Expanded fields
  const description = document.getElementById('woo-prod-desc').value;
  const shortDescription = document.getElementById('woo-prod-short-desc').value;
  const salePrice = parseFloat(document.getElementById('woo-prod-sale-price').value) || null;
  const weight = parseFloat(document.getElementById('woo-prod-weight').value) || null;
  const shippingClass = document.getElementById('woo-prod-shipping-class').value;
  
  // SEO & Ecommerce fields
  const seoTitle = document.getElementById('woo-prod-seo-title').value.trim();
  const seoDescription = document.getElementById('woo-prod-seo-desc').value.trim();
  const seoKeywords = document.getElementById('woo-prod-seo-keywords').value.trim();
  const ogTitle = document.getElementById('woo-prod-og-title').value.trim();
  const ogDescription = document.getElementById('woo-prod-og-desc').value.trim();
  const ogImage = document.getElementById('woo-prod-og-image').value.trim();
  const googleProductCategory = document.getElementById('woo-prod-google-category').value.trim();
  const gtin = document.getElementById('woo-prod-gtin').value.trim();
  const mpn = document.getElementById('woo-prod-mpn').value.trim();
  
  // Capture checked Sales Channels
  const salesChannels = [];
  if (document.getElementById('woo-channel-ecommerce').checked) salesChannels.push('ecommerce');
  if (document.getElementById('woo-channel-pos').checked) salesChannels.push('pos');
  if (document.getElementById('woo-channel-wholesale').checked) salesChannels.push('wholesale');
  if (document.getElementById('woo-channel-social').checked) salesChannels.push('social');
  
  if (!name || !sku || !category) {
    alert('يرجى ملء الحقول الأساسية: اسم المنتج، الرمز SKU، والفئة.');
    return;
  }
  
  // Calculate status based on stock
  let status = 'In Stock';
  if (stock === 0) status = 'Out of Stock';
  else if (stock <= minStock) status = 'Low Stock';
  
  const productData = {
    name,
    sku,
    category,
    price,
    cost,
    stock,
    minStock,
    status,
    description,
    shortDescription,
    salePrice,
    weight,
    shippingClass,
    imageUrl: selectedImageUrl,
    publishStatus: isDraft ? 'Draft' : 'Publish',
    salesChannels,
    seoTitle,
    seoDescription,
    seoKeywords,
    ogTitle,
    ogDescription,
    ogImage,
    googleProductCategory,
    gtin,
    mpn
  };

  if (editingProductId) {
    productData.id = editingProductId;
  }

  // Disable buttons while saving
  const submitBtn = document.getElementById('btn-woo-publish');
  const draftBtn = document.getElementById('btn-woo-save-draft');
  if (submitBtn) submitBtn.disabled = true;
  if (draftBtn) draftBtn.disabled = true;

  store.saveProduct(productData)
    .then(() => {
      if (editingProductId) {
        store.addActivity('info', 'تعديل تفاصيل منتج', `تم تعديل بيانات المنتج "${name}" بنمط ووكومرس`);
      } else {
        store.addActivity('success', 'إضافة منتج جديد', `تم إضافة منتج جديد "${name}" بنمط ووكومرس بمخزون ابتدائي ${stock} قطعة`);
      }
      
      // Go back to list and refresh tables
      navigateToView('inventory');
      renderInventoryTable();
      populateCategoriesFilter();
      initDashboard();
    })
    .catch(err => {
      console.error(err);
      alert(err.message || 'حدث خطأ أثناء حفظ المنتج في قاعدة البيانات.');
    })
    .finally(() => {
      if (submitBtn) submitBtn.disabled = false;
      if (draftBtn) draftBtn.disabled = false;
    });
}

function deleteProduct(id) {
  const products = store.getProducts();
  const product = products.find(p => p.id === id);
  
  if (!product) return;
  
  if (confirm(`هل أنت متأكد من حذف المنتج "${product.name}"؟ لا يمكن التراجع عن هذا الإجراء.`)) {
    store.deleteProduct(id)
      .then(() => {
        store.addActivity('danger', 'حذف منتج', `تم إزالة المنتج "${product.name}" (#${product.sku}) نهائياً من المستودع`);
        renderInventoryTable();
        populateCategoriesFilter();
        initDashboard();
      })
      .catch(err => {
        console.error(err);
        alert(err.message || 'حدث خطأ أثناء حذف المنتج من قاعدة البيانات.');
      });
  }
}

export function initTransfers() {
  const select = document.getElementById('trans-product-select');
  if (!select) return;
  
  // Load products in select dropdown
  const products = store.getProducts();
  select.innerHTML = products.map(p => `<option value="${p.id}">${p.name} (المتوفر: ${p.stock} قطع)</option>`).join('');
  
  const form = document.getElementById('warehouse-transfer-form');
  if (form && !form.dataset.listener) {
    form.dataset.listener = 'true';
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const sourceWh = document.getElementById('trans-source-wh').options[document.getElementById('trans-source-wh').selectedIndex].text;
      const targetWh = document.getElementById('trans-target-wh').options[document.getElementById('trans-target-wh').selectedIndex].text;
      const prodId = document.getElementById('trans-product-select').value;
      const qty = parseInt(document.getElementById('trans-qty').value);
      
      const currentProducts = store.getProducts();
      const product = currentProducts.find(p => p.id === prodId);
      if (!product) return;
      
      if (qty > product.stock) {
        alert('خطأ: الكمية المراد نقلها أكبر من المتوفر بالمستودع المصدر!');
        return;
      }
      
      if (document.getElementById('trans-source-wh').value === document.getElementById('trans-target-wh').value) {
        alert('خطأ: لا يمكن النقل لنفس المستودع!');
        return;
      }
      
      // Save transfer log
      const transfers = JSON.parse(localStorage.getItem('ns_transfers')) || [];
      const newTransfer = {
        id: Date.now().toString(),
        productName: product.name,
        qty,
        source: sourceWh,
        target: targetWh,
        date: new Date().toLocaleTimeString('ar-SA') + ' - ' + new Date().toLocaleDateString('ar-SA')
      };
      transfers.unshift(newTransfer);
      localStorage.setItem('ns_transfers', JSON.stringify(transfers));
      
      // Log as activity
      store.addActivity('success', 'نقل بين المستودعات', `تم نقل ${qty} قطعة من "${product.name}" من ${sourceWh} إلى ${targetWh}`);
      
      alert(`نجاح: تم نقل الشحنة بنجاح من ${sourceWh} إلى ${targetWh}.`);
      
      // Refresh
      renderTransfersList();
      initTransfers(); // Refresh select capacities
      renderInventoryTable();
    });
  }
  
  renderTransfersList();
}

function renderTransfersList() {
  const list = document.getElementById('transfers-history-list');
  if (!list) return;
  
  const transfers = JSON.parse(localStorage.getItem('ns_transfers')) || [];
  if (transfers.length === 0) {
    list.innerHTML = `<div style="text-align:center; padding:12px; color:var(--text-muted); font-size:12px;">لا توجد عمليات نقل سابقة.</div>`;
    return;
  }
  
  list.innerHTML = transfers.slice(0, 5).map(tr => `
    <div style="padding:12px; background-color:var(--bg-tertiary); border:1px solid var(--border-color); border-radius:var(--border-radius-sm); font-size:12px; margin-bottom:8px;">
      <div style="display:flex; justify-content:space-between; font-weight:700; margin-bottom:4px;">
        <span>شحنة نقل #TR-${tr.id.slice(-4)}</span>
        <span style="color:#10b981;">مكتمل</span>
      </div>
      <div>نقل عدد ${tr.qty} "${tr.productName}" من ${tr.source} إلى ${tr.target}.</div>
      <div style="color:var(--text-muted); font-size:10px; margin-top:4px;">${tr.date} - بواسطة أمين المستودع</div>
    </div>
  `).join('');
}

export function initAdjustments() {
  const select = document.getElementById('adj-product-select');
  if (!select) return;
  
  const products = store.getProducts();
  select.innerHTML = products.map(p => `<option value="${p.id}">${p.name} (المتوفر: ${p.stock} قطع)</option>`).join('');
  
  const form = document.getElementById('inventory-adjustment-form');
  if (form && !form.dataset.listener) {
    form.dataset.listener = 'true';
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const prodId = document.getElementById('adj-product-select').value;
      const qty = parseInt(document.getElementById('adj-qty').value);
      const adjType = document.getElementById('adj-type').value;
      const reason = document.getElementById('adj-reason').value;
      
      const currentProducts = store.getProducts();
      const product = currentProducts.find(p => p.id === prodId);
      if (!product) return;
      
      if (qty > product.stock) {
        alert('خطأ: الكمية المراد إتلافها أكبر من المتوفر!');
        return;
      }
      
      // Deduct from stock
      store.updateProductStock(prodId, -qty, `إتلاف/فقد: ${reason}`);
      
      const typeLabel = adjType === 'damage' ? 'إتلاف بضاعة تالفة' : 'فقدان مخزون أثناء الجرد';
      alert(`تم تسجيل (${typeLabel}) بنجاح وخصم عدد ${qty} قطعة من الرصيد.`);
      
      form.reset();
      initAdjustments();
      renderInventoryTable();
      initDashboard();
    });
  }
}

/* --- PRODUCT CATEGORIES VIEW MANAGEMENT --- */

export function initCategoriesView() {
  renderCategoriesTable();
  setupCategoriesEventListeners();
}

function renderCategoriesTable() {
  const categories = store.getCategories();
  const products = store.getProducts();
  const tbody = document.getElementById('categories-table-body');
  if (!tbody) return;
  
  if (categories.length === 0) {
    tbody.innerHTML = `<tr><td colspan="5" style="text-align:center; padding:24px; color:var(--text-muted);">لا توجد تصنيفات حالياً.</td></tr>`;
    return;
  }
  
  tbody.innerHTML = categories.map(cat => {
    const productCount = products.filter(p => p.category === cat.name).length;
    return `
      <tr>
        <td style="font-weight: 700; color: var(--text-primary);">${cat.name}</td>
        <td style="font-family: var(--font-english); font-size: 13px; color: var(--text-muted);">${cat.slug || cat.name}</td>
        <td>${cat.description || 'لا يوجد وصف'}</td>
        <td style="text-align: center; font-family: var(--font-english); font-weight: 700;">${productCount}</td>
        <td>
          <button class="btn btn-secondary btn-sm btn-icon btn-delete-category" data-id="${cat.id}" style="color: hsla(var(--danger), 1);" title="حذف التصنيف" ${productCount > 0 ? 'disabled style="opacity:0.4; cursor:not-allowed;"' : ''}>
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
  
  // Bind delete category button clicks
  document.querySelectorAll('.btn-delete-category').forEach(btn => {
    if (btn.disabled) return;
    btn.addEventListener('click', (e) => {
      const id = e.currentTarget.getAttribute('data-id');
      deleteCategory(id);
    });
  });
}

function setupCategoriesEventListeners() {
  const form = document.getElementById('add-category-form');
  if (!form) return;
  
  if (form.dataset.listener) return;
  form.dataset.listener = 'true';
  
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const nameInput = document.getElementById('cat-name-input');
    const slugInput = document.getElementById('cat-slug-input');
    const descInput = document.getElementById('cat-desc-input');
    
    const name = nameInput.value.trim();
    const slug = slugInput.value.trim() || name;
    const description = descInput.value.trim();
    
    if (!name) return;
    
    const categories = store.getCategories();
    
    // Check if category already exists
    if (categories.some(c => c.name.toLowerCase() === name.toLowerCase())) {
      alert('هذا التصنيف موجود بالفعل!');
      return;
    }
    
    const newCat = {
      id: Date.now().toString(),
      name,
      slug,
      description
    };
    
    categories.push(newCat);
    store.saveCategories(categories);
    
    store.addActivity('success', 'إضافة تصنيف جديد', `تم إضافة تصنيف منتجات جديد: "${name}"`);
    
    // Reset form
    form.reset();
    
    // Refresh tables & filters
    renderCategoriesTable();
    populateCategoriesFilter();
  });
}

function deleteCategory(id) {
  let categories = store.getCategories();
  const cat = categories.find(c => c.id === id);
  if (!cat) return;
  
  if (confirm(`هل أنت متأكد من حذف تصنيف "${cat.name}"؟`)) {
    categories = categories.filter(c => c.id !== id);
    store.saveCategories(categories);
    store.addActivity('danger', 'حذف تصنيف', `تم حذف تصنيف المنتجات "${cat.name}"`);
    
    renderCategoriesTable();
    populateCategoriesFilter();
  }
}

export function openProductDetailsPage(id) {
  const products = store.getProducts();
  const product = products.find(p => p.id === id);
  if (!product) return;

  // Set breadcrumb & general text
  document.getElementById('prod-detail-breadcrumb').innerText = `تفاصيل المنتج: ${product.name}`;
  document.getElementById('admin-prod-detail-name').innerText = product.name;
  document.getElementById('admin-prod-detail-sku').innerText = `SKU: ${product.sku}`;
  document.getElementById('admin-prod-detail-cat').innerText = product.category;
  
  // Price & Cost
  document.getElementById('admin-prod-detail-price').innerText = `$${product.price.toLocaleString()}`;
  document.getElementById('admin-prod-detail-cost').innerText = `$${product.cost.toLocaleString()}`;
  
  // Profit margin calculation
  const margin = product.price - product.cost;
  const marginPercent = product.price > 0 ? Math.round((margin / product.price) * 100) : 0;
  document.getElementById('admin-prod-detail-margin').innerText = `$${margin.toLocaleString()} (${marginPercent}%)`;

  // Active channels rendering
  const channels = product.salesChannels || [];
  const channelBadges = channels.map(c => {
    if (c === 'ecommerce') return '<span style="background: rgba(111,66,193,0.1); color: hsla(260,60%,50%,1); border: 1px solid rgba(111,66,193,0.25); padding: 1px 4px; border-radius: 4px; font-size: 9px; font-weight: 700;">متجر</span>';
    if (c === 'pos') return '<span style="background: rgba(6,182,212,0.1); color: rgb(6,182,212); border: 1px solid rgba(6,182,212,0.25); padding: 1px 4px; border-radius: 4px; font-size: 9px; font-weight: 700;">POS</span>';
    if (c === 'wholesale') return '<span style="background: rgba(16,185,129,0.1); color: rgb(16,185,129); border: 1px solid rgba(16,185,129,0.25); padding: 1px 4px; border-radius: 4px; font-size: 9px; font-weight: 700;">جملة</span>';
    if (c === 'social') return '<span style="background: rgba(245,158,11,0.1); color: rgb(245,158,11); border: 1px solid rgba(245,158,11,0.25); padding: 1px 4px; border-radius: 4px; font-size: 9px; font-weight: 700;">سوشيال</span>';
    return '';
  }).join(' ');
  document.getElementById('admin-prod-detail-channels').innerHTML = channelBadges;

  // Image Rendering
  const imgBox = document.getElementById('admin-prod-detail-img-box');
  let iconClass = 'fa-box';
  if (product.category.includes('إلكترونيات') || product.category.includes('هواتف')) iconClass = 'fa-laptop';
  else if (product.category.includes('ملابس') || product.category.includes('أحذية')) iconClass = 'fa-tshirt';
  imgBox.innerHTML = product.imageUrl 
    ? `<img src="${product.imageUrl}" alt="${product.name}" style="max-width: 100%; max-height: 100%; object-fit: cover;">` 
    : `<div style="font-size: 48px; color: var(--text-muted);"><i class="fas ${iconClass}"></i></div>`;

  // Descriptions
  document.getElementById('admin-prod-detail-short-desc').innerText = product.shortDescription || 'لا يوجد وصف قصير لهذا المنتج.';
  document.getElementById('admin-prod-detail-full-desc').innerText = product.description || 'لا يوجد وصف تفصيلي لهذا المنتج.';

  // Fetch orders containing this product to calculate sales statistics
  const orders = store.getOrders();
  const productSalesOrders = orders.filter(o => o.items && o.items.some(item => item.productId === id));
  
  let totalQtySold = 0;
  let totalRevenue = 0;
  
  productSalesOrders.forEach(o => {
    const pItem = o.items.find(item => item.productId === id);
    if (pItem) {
      totalQtySold += pItem.quantity;
      totalRevenue += pItem.quantity * pItem.price;
    }
  });

  document.getElementById('admin-prod-kpi-sales-count').innerText = `${totalQtySold} قطع`;
  document.getElementById('admin-prod-kpi-revenue').innerText = `$${totalRevenue.toLocaleString()}`;
  document.getElementById('admin-prod-kpi-stock').innerText = product.stock;

  // Render sales history table
  const salesTbody = document.getElementById('admin-prod-detail-sales-tbody');
  if (productSalesOrders.length === 0) {
    salesTbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--text-muted);">لم يتم بيع هذا المنتج في أي طلب حتى الآن.</td></tr>';
  } else {
    salesTbody.innerHTML = productSalesOrders.map(o => {
      const pItem = o.items.find(item => item.productId === id);
      const qty = pItem ? pItem.quantity : 0;
      const itemPrice = pItem ? pItem.price : 0;
      const total = qty * itemPrice;
      
      let statusBadge = 'badge-secondary';
      let statusTextAr = o.status;
      if (o.status === 'Pending') { statusBadge = 'badge-warning'; statusTextAr = 'معلق'; }
      else if (o.status === 'Shipped') { statusBadge = 'badge-primary'; statusTextAr = 'تم الشحن'; }
      else if (o.status === 'Delivered') { statusBadge = 'badge-success'; statusTextAr = 'تم التوصيل'; }
      else if (o.status === 'Cancelled') { statusBadge = 'badge-danger'; statusTextAr = 'ملغي'; }

      const dateFormatted = new Date(o.date).toLocaleDateString('ar-SY', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });

      return `
        <tr>
          <td style="font-family: var(--font-english); font-weight: 700;">#${o.id}</td>
          <td style="font-family: var(--font-english);">${dateFormatted}</td>
          <td><div style="font-weight: 600;">${o.customerName}</div></td>
          <td style="font-family: var(--font-english);">${qty}</td>
          <td style="font-family: var(--font-english); font-weight: 600;">$${itemPrice.toLocaleString()}</td>
          <td style="font-family: var(--font-english); font-weight: 700; color: hsla(var(--primary), 1);">$${total.toLocaleString()}</td>
          <td><span class="badge ${statusBadge}">${statusTextAr}</span></td>
        </tr>
      `;
    }).join('');
  }

  // Render reviews list for this product
  const reviews = JSON.parse(localStorage.getItem('ns_reviews')) || [];
  const prodReviews = reviews.filter(r => r.productSku === product.sku);

  const reviewsContainer = document.getElementById('admin-prod-detail-reviews-list');
  if (prodReviews.length === 0) {
    reviewsContainer.innerHTML = '<div style="font-size: 13px; color: var(--text-muted); text-align: center; padding: 12px;">لا توجد مراجعات أو تقييمات مكتوبة لهذا المنتج بعد.</div>';
  } else {
    reviewsContainer.innerHTML = prodReviews.map(r => {
      let stars = '';
      for (let i = 1; i <= 5; i++) {
        stars += `<i class="${i <= r.rating ? 'fas' : 'far'} fa-star" style="color: #f59e0b; font-size: 11px;"></i>`;
      }
      
      let statusBadge = 'badge-secondary';
      let statusText = 'غير معروف';
      if (r.status === 'Pending') { statusBadge = 'badge-warning'; statusText = 'قيد المراجعة'; }
      else if (r.status === 'Approved') { statusBadge = 'badge-success'; statusText = 'معتمد'; }
      else if (r.status === 'Spam') { statusBadge = 'badge-danger'; statusText = 'مرفوض/سبام'; }

      return `
        <div style="border: 1px solid var(--border-color); padding: 12px; border-radius: 6px; background-color: var(--bg-primary); margin-bottom: 8px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 6px;">
            <div>
              <strong style="font-size: 13px; color: var(--text-primary);">${r.customerName}</strong>
              <span style="font-size: 11px; margin-inline-start: 8px;">${stars}</span>
            </div>
            <span class="badge ${statusBadge}">${statusText}</span>
          </div>
          <p style="margin: 0 0 8px 0; font-size: 12px; color: var(--text-muted); line-height: 1.4;">${r.text}</p>
          
          ${r.status === 'Pending' ? `
            <div style="display:flex; gap:6px;">
              <button type="button" class="btn btn-secondary btn-sm js-admin-approve-review" data-id="${r.id}" style="padding: 2px 8px; font-size:10px; height:24px; color: hsla(var(--success),1);">
                <i class="fas fa-check"></i> موافقة
              </button>
              <button type="button" class="btn btn-secondary btn-sm js-admin-spam-review" data-id="${r.id}" style="padding: 2px 8px; font-size:10px; height:24px; color: hsla(var(--danger),1);">
                <i class="fas fa-ban"></i> رفض
              </button>
            </div>
          ` : ''}
        </div>
      `;
    }).join('');

    // Bind review moderation triggers
    reviewsContainer.querySelectorAll('.js-admin-approve-review').forEach(btn => {
      btn.addEventListener('click', () => {
        const rId = btn.getAttribute('data-id');
        updateReviewStatusFromProductPage(rId, 'Approved', id);
      });
    });
    reviewsContainer.querySelectorAll('.js-admin-spam-review').forEach(btn => {
      btn.addEventListener('click', () => {
        const rId = btn.getAttribute('data-id');
        updateReviewStatusFromProductPage(rId, 'Spam', id);
      });
    });
  }

  // Redirect to product-details navigation view
  import('./app.js').then(module => {
    module.navigateToView('product-details');
  });
}

function updateReviewStatusFromProductPage(reviewId, status, productId) {
  const reviews = JSON.parse(localStorage.getItem('ns_reviews')) || [];
  const rIndex = reviews.findIndex(item => item.id === reviewId);
  if (rIndex > -1) {
    reviews[rIndex].status = status;
    localStorage.setItem('ns_reviews', JSON.stringify(reviews));
    alert(status === 'Approved' ? 'تمت الموافقة على المراجعة ونشرها بالمتجر بنجاح!' : 'تم رفض المراجعة ووسمها كـ Spam.');
    openProductDetailsPage(productId); // reload info
    
    // Check if we need to reload review table as well
    const ecoReviewsTable = document.getElementById('eco-reviews-table-body');
    if (ecoReviewsTable) {
      import('./ecommerce-dashboard.js').then(module => {
        module.initReviews();
      });
    }
  }
}
