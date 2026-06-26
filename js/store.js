// store.js - Database Synchronization Layer with Synchronous Memory Cache (Tenant-Aware)

let _products = [];
let _orders = [];
let _suppliers = [];
let _activities = [];
let _purchaseInvoices = [];
let _reviews = [];
let _customers = [];
let _currencySymbol = 'ل.س';
let _settings = {};

// Helper to get active tenant slug
function getTenantSlug() {
  if (window.ActiveTenant && window.ActiveTenant.slug) {
    return window.ActiveTenant.slug;
  }
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('tenant') || '';
}

export const store = {
  // Parallel asynchronous load from backend PHP APIs with tenant routing
  async loadAllData() {
    try {
      const tenant = getTenantSlug();
      const urls = [
        'api/products.php',
        'api/orders.php',
        'api/suppliers.php',
        'api/activities.php',
        'api/purchases.php',
        'api/reviews.php',
        'api/customers.php',
        'api/currency_sync.php',
        'api/settings.php'
      ];
      
      const headers = {};
      if (tenant) {
        headers['X-Tenant'] = tenant;
      }

      const responses = await Promise.all(
        urls.map(url => {
          const separator = url.includes('?') ? '&' : '?';
          const urlWithTenant = tenant ? `${url}${separator}tenant=${tenant}` : url;
          return fetch(urlWithTenant, { headers }).then(res => {
            if (!res.ok) throw new Error(`HTTP error fetching ${url}`);
            return res.json();
          }).catch(err => {
            console.warn('Failed fetching data table:', url, err);
            return [];
          });
        })
      );
      
      _products = Array.isArray(responses[0]) ? responses[0] : [];
      _orders = Array.isArray(responses[1]) ? responses[1] : [];
      _suppliers = Array.isArray(responses[2]) ? responses[2] : [];
      _activities = Array.isArray(responses[3]) ? responses[3] : [];
      _purchaseInvoices = Array.isArray(responses[4]) ? responses[4] : [];
      _reviews = Array.isArray(responses[5]) ? responses[5] : [];
      _customers = Array.isArray(responses[6]) ? responses[6] : [];
      
      // Handle currency rates response
      const currencyData = responses[7];
      
      // Load settings
      const settingsData = responses[8];
      _settings = (settingsData && settingsData.success) ? settingsData.settings : {};
      
      // Extract currency symbol from settings (e.g., "SYP (ل.س)")
      const settingCurrency = _settings.ns_settings_currency || '';
      const matchSymbol = settingCurrency.match(/\(([^)]+)\)/);
      if (matchSymbol && matchSymbol[1]) {
        _currencySymbol = matchSymbol[1];
      } else if (currencyData && currencyData.success && Array.isArray(currencyData.currencies)) {
        const activeCode = (window.ActiveTenant && window.ActiveTenant.plan === 'Starter') ? 'SAR' : 'SYP';
        const cur = currencyData.currencies.find(c => c.code === activeCode) || currencyData.currencies[0];
        if (cur) {
          _currencySymbol = cur.symbol || 'ل.س';
        }
      }
      
      console.log('All backend data tables cached successfully.', {
        products: _products.length,
        orders: _orders.length,
        suppliers: _suppliers.length,
        activities: _activities.length
      });
      
    } catch (err) {
      console.error('Critical: loadAllData failed. Database might not be installed yet.', err);
    }
  },

  getProducts() {
    return _products.map(p => ({
      id: p.id.toString(),
      sku: p.sku,
      name: p.name,
      category: p.category,
      price: parseFloat(p.price_usd),
      cost: parseFloat(p.cost_usd),
      stock: parseInt(p.stock),
      minStock: parseInt(p.min_stock),
      status: p.status,
      imageUrl: p.image_url,
      description: p.description,
      shortDescription: p.short_description,
      salesChannels: p.sales_channels ? p.sales_channels.split(',') : ['ecommerce', 'pos'],
      publishStatus: p.publish_status,
      seoTitle: p.seo_title || '',
      seoDescription: p.seo_description || '',
      seoKeywords: p.seo_keywords || '',
      ogTitle: p.og_title || '',
      ogDescription: p.og_description || '',
      ogImage: p.og_image || '',
      googleProductCategory: p.google_product_category || '',
      gtin: p.gtin || '',
      mpn: p.mpn || ''
    }));
  },

  saveProducts(products) {
    _products = products;
  },

  async saveProduct(productData) {
    const tenant = getTenantSlug();
    const url = tenant ? `api/products.php?tenant=${tenant}` : 'api/products.php';
    
    // Map JS properties to database properties expected by api/products.php
    const payload = {
      id: productData.id ? parseInt(productData.id) : 0,
      sku: productData.sku,
      name: productData.name,
      category: productData.category,
      price_usd: productData.price,
      cost_usd: productData.cost,
      stock: productData.stock,
      min_stock: productData.minStock,
      image_url: productData.imageUrl,
      description: productData.description,
      short_description: productData.shortDescription,
      sales_channels: productData.salesChannels,
      publish_status: productData.publishStatus,
      seo_title: productData.seoTitle || null,
      seo_description: productData.seoDescription || null,
      seo_keywords: productData.seoKeywords || null,
      og_title: productData.ogTitle || null,
      og_description: productData.ogDescription || null,
      og_image: productData.ogImage || null,
      google_product_category: productData.googleProductCategory || null,
      gtin: productData.gtin || null,
      mpn: productData.mpn || null
    };

    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Tenant': tenant
      },
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (!data.success) {
      throw new Error(data.message || 'خطأ في حفظ المنتج');
    }
    // Reload all data to sync local cache
    await this.loadAllData();
    return data;
  },

  async deleteProduct(productId) {
    const tenant = getTenantSlug();
    const url = tenant ? `api/products.php?id=${productId}&tenant=${tenant}` : `api/products.php?id=${productId}`;
    const res = await fetch(url, {
      method: 'DELETE',
      headers: {
        'X-Tenant': tenant
      }
    });
    const data = await res.json();
    if (!data.success) {
      throw new Error(data.message || 'خطأ في حذف المنتج');
    }
    // Reload all data to sync local cache
    await this.loadAllData();
    return data;
  },

  updateProductStock(productId, amount, reason = '') {
    const tenant = getTenantSlug();
    const pIndex = _products.findIndex(p => p.id == productId);
    if (pIndex > -1) {
      const product = _products[pIndex];
      product.stock = Math.max(0, parseInt(product.stock) + amount);
      
      if (product.stock === 0) product.status = 'Out of Stock';
      else if (product.stock <= product.min_stock) product.status = 'Low Stock';
      else product.status = 'In Stock';
      
      _products[pIndex] = product;

      // Sync backend with tenant headers
      const url = tenant ? `api/products.php?action=adjust_stock&tenant=${tenant}` : 'api/products.php?action=adjust_stock';
      fetch(url, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-Tenant': tenant
        },
        body: JSON.stringify({ id: productId, amount: amount, reason: reason })
      }).catch(err => console.error('Failed to sync stock adjustment with DB:', err));
      
      return product;
    }
    return null;
  },

  getOrders() {
    return _orders.map(o => ({
      id: o.id.toString(),
      customerName: o.customer_name,
      date: o.date,
      status: o.status,
      total: parseFloat(o.total_usd),
      items: o.items ? o.items.map(item => ({
        productId: item.product_id.toString(),
        quantity: parseInt(item.quantity),
        price: parseFloat(item.price_usd)
      })) : []
    }));
  },

  saveOrders(orders) {
    _orders = orders;
  },

  updateOrderStatus(orderId, status) {
    const tenant = getTenantSlug();
    const oIndex = _orders.findIndex(o => o.id == orderId);
    if (oIndex > -1) {
      _orders[oIndex].status = status;
      
      // Sync backend
      const url = tenant ? `api/orders.php?action=update_status&tenant=${tenant}` : 'api/orders.php?action=update_status';
      fetch(url, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'X-Tenant': tenant
        },
        body: JSON.stringify({ id: orderId, status: status })
      }).catch(err => console.error('Failed to sync order status with DB:', err));
      
      return _orders[oIndex];
    }
    return null;
  },

  getSuppliers() {
    return _suppliers;
  },

  saveSuppliers(suppliers) {
    _suppliers = suppliers;
  },

  getActivities() {
    return _activities;
  },

  saveActivities(activities) {
    _activities = activities;
  },

  addActivity(type, title, desc) {
    const tenant = getTenantSlug();
    const newActivity = {
      id: Date.now().toString(),
      type,
      title,
      desc,
      time: 'الآن'
    };
    _activities.unshift(newActivity);
    
    // Sync backend
    const url = tenant ? `api/activities.php?tenant=${tenant}` : 'api/activities.php';
    fetch(url, {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'X-Tenant': tenant
      },
      body: JSON.stringify({ type, title, desc })
    }).catch(err => console.error('Failed to sync activity log:', err));
  },

  async clearActivities() {
    const tenant = getTenantSlug();
    const url = tenant ? `api/activities.php?tenant=${tenant}` : 'api/activities.php';
    const response = await fetch(url, {
      method: 'DELETE',
      headers: {
        'X-Tenant': tenant
      }
    });
    if (!response.ok) {
      throw new Error('فشل مسح سجل الأنشطة من الخادم');
    }
    
    // Clear local array and insert the placeholder record
    _activities = [{
      id: Date.now().toString(),
      type: 'warning',
      title: 'مسح السجل الكلي',
      desc: 'تم إفراغ سجل الأنشطة والعمليات بالكامل',
      time: 'الآن'
    }];
  },

  getCategories() {
    const tenant = getTenantSlug() || 'default';
    let customCategories = [];
    try {
      const stored = localStorage.getItem('ns_custom_categories_' + tenant);
      if (stored) {
        customCategories = JSON.parse(stored);
      }
    } catch(e) {
      console.error('Error reading custom categories:', e);
    }

    // Extracted categories from products
    const productCategories = [...new Set(_products.map(p => p.category))].filter(Boolean);

    // Merge categories
    const allCategories = [];
    const seen = new Set();

    // Map product categories to standard structure
    const productCatsList = productCategories.map(name => ({
      id: name,
      name: name,
      slug: name,
      description: `جميع منتجات قسم ${name}`
    }));

    // Add product categories first
    productCatsList.forEach(c => {
      const lowerName = c.name.toLowerCase();
      if (!seen.has(lowerName)) {
        seen.add(lowerName);
        allCategories.push(c);
      }
    });

    // Add custom categories that are not already present
    if (Array.isArray(customCategories)) {
      customCategories.forEach(c => {
        if (c && c.name) {
          const lowerName = c.name.toLowerCase();
          if (!seen.has(lowerName)) {
            seen.add(lowerName);
            allCategories.push(c);
          }
        }
      });
    }

    return allCategories;
  },

  saveCategories(categories) {
    const tenant = getTenantSlug() || 'default';
    try {
      localStorage.setItem('ns_custom_categories_' + tenant, JSON.stringify(categories));
    } catch(e) {
      console.error('Error saving custom categories:', e);
    }
  },

  getCurrencySymbol() {
    return _currencySymbol;
  },

  getReviews() {
    return _reviews.map(r => ({
      id: r.id.toString(),
      customerName: r.customer_name,
      productId: r.product_id ? r.product_id.toString() : '',
      productSku: r.product_sku,
      productName: r.product_name,
      rating: parseInt(r.rating),
      date: r.date,
      text: r.text,
      status: r.status
    }));
  },

  getPurchaseInvoices() {
    return _purchaseInvoices;
  },

  getCustomers() {
    return _customers;
  }
};
