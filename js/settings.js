import { store } from './store.js';

function getTenantSlug() {
  if (window.ActiveTenant && window.ActiveTenant.slug) {
    return window.ActiveTenant.slug;
  }
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('tenant') || '';
}

export function initSettings() {
  loadStoreSettings();
  loadWarehouses();
  setupSettingsForm();
  setupDomainSettingsForm();
  setupAddWarehouse();
  initPaymentShipping();
  initThemesSettings();
}

async function loadStoreSettings() {
  try {
    const tenant = getTenantSlug();
    const res = await fetch(`api/settings.php?tenant=${tenant}`);
    const data = await res.json();
    if (data.success && data.settings) {
      const settings = data.settings;
      const nameInput = document.getElementById('settings-store-name-input');
      const currSelect = document.getElementById('settings-currency-select');
      const taxInput = document.getElementById('settings-tax-rate-input');

      const slugInput = document.getElementById('settings-store-slug-input');
      const customDomainInput = document.getElementById('settings-store-custom-domain-input');

      if (nameInput) nameInput.value = settings.ns_settings_store_name || '';
      if (currSelect) currSelect.value = settings.ns_settings_currency || 'SYP (ل.س)';
      if (taxInput) taxInput.value = settings.ns_settings_tax_rate || '15%';

      if (slugInput) slugInput.value = settings.ns_tenant_slug || '';
      if (customDomainInput) customDomainInput.value = settings.ns_tenant_custom_domain || '';

      // Backwards compatibility fallbacks
      localStorage.setItem('ns_settings_store_name', settings.ns_settings_store_name || '');
      localStorage.setItem('ns_settings_currency', settings.ns_settings_currency || '');
      localStorage.setItem('ns_settings_tax_rate', settings.ns_settings_tax_rate || '');
      localStorage.setItem('ns_active_theme', settings.ns_active_theme || 'jasmine');
    }
  } catch (err) {
    console.warn('Failed loading store settings:', err);
  }
}

function setupSettingsForm() {
  const form = document.getElementById('store-settings-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const name = document.getElementById('settings-store-name-input').value;
    const currency = document.getElementById('settings-currency-select').value;
    const taxRate = document.getElementById('settings-tax-rate-input').value;

    const payload = {
      ns_settings_store_name: name,
      ns_settings_currency: currency,
      ns_settings_tax_rate: taxRate,
      ns_tenant_name: name
    };

    try {
      const tenant = getTenantSlug();
      const res = await fetch(`api/settings.php?tenant=${tenant}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const result = await res.json();
      if (result.success) {
        localStorage.setItem('ns_settings_store_name', name);
        localStorage.setItem('ns_settings_currency', currency);
        localStorage.setItem('ns_settings_tax_rate', taxRate);
        
        // Update logo texts
        document.querySelectorAll('.logo-text').forEach(el => {
          el.innerText = name;
        });

        alert('تم حفظ إعدادات المتجر العامة بنجاح بقاعدة البيانات!');
      } else {
        alert('خطأ: ' + result.message);
      }
    } catch (err) {
      alert('فشل حفظ الإعدادات على الخادم.');
    }
  });
}

function setupDomainSettingsForm() {
  const form = document.getElementById('store-domain-settings-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const slug = document.getElementById('settings-store-slug-input').value.trim();
    const customDomain = document.getElementById('settings-store-custom-domain-input').value.trim();

    const payload = {
      ns_tenant_slug: slug,
      ns_tenant_custom_domain: customDomain
    };

    try {
      const tenant = getTenantSlug();
      const res = await fetch(`api/settings.php?tenant=${tenant}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const result = await res.json();
      if (result.success) {
        if (window.ActiveTenant) {
          window.ActiveTenant.slug = slug;
          window.ActiveTenant.custom_domain = customDomain || null;
        }

        // Keep page session query param aligned with new slug
        const url = new URL(window.location.href);
        if (url.searchParams.has('tenant')) {
          url.searchParams.set('tenant', slug);
          window.history.replaceState({}, '', url.toString());
        }

        store.addActivity('info', 'تحديث إعدادات الدومين', `تم تحديث النطاق الفرعي إلى (${slug}) والدومين المخصص إلى (${customDomain || 'لاشيء'})`);
        alert('تم حفظ إعدادات الدومين بنجاح!');
        loadStoreSettings();
      } else {
        alert('خطأ: ' + result.message);
      }
    } catch (err) {
      alert('فشل حفظ إعدادات الدومين على الخادم.');
    }
  });
}

async function loadWarehouses() {
  const grid = document.getElementById('settings-warehouses-grid');
  if (!grid) return;

  try {
    const tenant = getTenantSlug();
    const res = await fetch(`api/warehouses.php?tenant=${tenant}`);
    const warehouses = await res.json();

    if (warehouses.length === 0) {
      grid.innerHTML = `<div class="text-center" style="grid-column:1/-1; padding:24px; color:var(--text-muted);">لا توجد مستودعات مضافة بعد.</div>`;
      return;
    }

    grid.innerHTML = warehouses.map(wh => `
      <div class="warehouse-loc-card">
        <div class="warehouse-loc-header">
          <h4 class="warehouse-loc-title">${wh.name}</h4>
          <span class="badge ${wh.capacity === '90%' ? 'badge-danger' : wh.capacity === '45%' ? 'badge-warning' : 'badge-success'}">${wh.capacity} سعة</span>
        </div>
        <div class="warehouse-loc-body">
          <div><strong>العنوان:</strong> ${wh.address}</div>
          <div><strong>المسؤول:</strong> ${wh.contact || 'غير محدد'}</div>
          <div><strong>الهاتف:</strong> ${wh.phone || 'غير محدد'}</div>
        </div>
        <button class="btn btn-secondary btn-sm delete-warehouse-btn" data-id="${wh.id}" style="color:var(--text-on-primary); background-color:hsla(var(--danger), 1); border:none; margin-top: 10px; width: 100%;">
          <i class="fas fa-trash"></i> حذف المستودع
        </button>
      </div>
    `).join('');

    // Bind delete buttons
    grid.querySelectorAll('.delete-warehouse-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        const id = e.currentTarget.getAttribute('data-id');
        deleteWarehouse(id);
      });
    });
  } catch (err) {
    console.warn('Failed to load warehouses:', err);
  }
}

async function deleteWarehouse(id) {
  if (!confirm('هل أنت متأكد من حذف هذا المستودع نهائياً؟')) return;

  try {
    const tenant = getTenantSlug();
    const res = await fetch(`api/warehouses.php?action=delete&id=${id}&tenant=${tenant}`, {
      method: 'DELETE'
    });
    const result = await res.json();
    if (result.success) {
      loadWarehouses();
    } else {
      alert(result.message);
    }
  } catch (err) {
    alert('فشل حذف المستودع.');
  }
}

function setupAddWarehouse() {
  const form = document.getElementById('add-warehouse-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const name = document.getElementById('wh-name').value;
    const address = document.getElementById('wh-address').value;
    const contact = document.getElementById('wh-contact').value;
    const phone = document.getElementById('wh-phone').value;

    const payload = {
      name,
      address,
      contact,
      phone,
      capacity: '5% ممتلئ'
    };

    try {
      const tenant = getTenantSlug();
      const res = await fetch(`api/warehouses.php?tenant=${tenant}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const result = await res.json();
      if (result.success) {
        loadWarehouses();
        form.reset();
      } else {
        alert(result.message);
      }
    } catch (err) {
      alert('فشل إضافة المستودع.');
    }
  });
}

async function initPaymentShipping() {
  const pList = document.getElementById('payment-gateways-list');
  const sList = document.getElementById('shipping-carriers-list');
  if (!pList || !sList) return;

  try {
    const tenant = getTenantSlug();
    const res = await fetch(`api/settings.php?tenant=${tenant}`);
    const data = await res.json();
    
    let gateways = { mada: true, visa: true, applepay: true, cod: false };
    let carriers = { aramex: true, smsa: true, dhl: false, pickup: true };

    if (data.success && data.settings) {
      if (data.settings.ns_settings_gateways) {
        gateways = JSON.parse(data.settings.ns_settings_gateways);
      }
      if (data.settings.ns_settings_carriers) {
        carriers = JSON.parse(data.settings.ns_settings_carriers);
      }
    }

    const gatewayConfigs = [
      { key: 'mada', title: 'شبكة مدى الوطنية (Mada)', desc: 'الخصم المباشر من الحساب البنكي للعميل ببطاقة مدى المحلية' },
      { key: 'visa', title: 'البطاقات الائتمانية (Visa / Mastercard)', desc: 'قبول الدفع بالبطاقات الدولية والمحلية بنسب تنافسية' },
      { key: 'applepay', title: 'أبل باي (Apple Pay)', desc: 'الدفع السريع بلمسة زر عبر أجهزة iOS و macOS' },
      { key: 'cod', title: 'الدفع نقداً عند الاستلام (COD)', desc: 'الدفع لأمين التوصيل أو الكابتن عند استلام البضائع' }
    ];

    pList.innerHTML = gatewayConfigs.map(g => {
      const checked = gateways[g.key] ? 'checked' : '';
      return `
        <label class="permission-checkbox-item" style="padding:10px; background:var(--bg-tertiary); border:1px solid var(--border-color); border-radius:var(--border-radius-sm); margin-bottom:8px; display:flex; align-items:flex-start; gap:10px;">
          <input type="checkbox" class="gateway-chk" data-key="${g.key}" ${checked} style="margin-top:3px;">
          <div style="display:flex; flex-direction:column; gap:2px;">
            <span style="font-weight:700; font-size:13px; color:var(--text-primary);">${g.title}</span>
            <span style="font-size:11px; color:var(--text-muted);">${g.desc}</span>
          </div>
        </label>
      `;
    }).join('');

    const carrierConfigs = [
      { key: 'aramex', title: 'أرامكس (Aramex Express)', desc: 'التوصيل المحلي والخليجي في غضون 2-4 أيام عمل مع تتبع تلقائي' },
      { key: 'smsa', title: 'سمسا إكسبريس (SMSA Express)', desc: 'تغطية واسعة لكافة مدن وقرى المملكة مع تسليم سريع' },
      { key: 'dhl', title: 'دي إتش إل العالمية (DHL Express)', desc: 'الشحن الدولي السريع للمستندات والطرود والجمارك' },
      { key: 'pickup', title: 'الاستلام المباشر من فروع المعارض', desc: 'تجهيز الشحنة للاستلام اليدوي من المعرض' }
    ];

    sList.innerHTML = carrierConfigs.map(c => {
      const checked = carriers[c.key] ? 'checked' : '';
      return `
        <label class="permission-checkbox-item" style="padding:10px; background:var(--bg-tertiary); border:1px solid var(--border-color); border-radius:var(--border-radius-sm); margin-bottom:8px; display:flex; align-items:flex-start; gap:10px;">
          <input type="checkbox" class="carrier-chk" data-key="${c.key}" ${checked} style="margin-top:3px;">
          <div style="display:flex; flex-direction:column; gap:2px;">
            <span style="font-weight:700; font-size:13px; color:var(--text-primary);">${c.title}</span>
            <span style="font-size:11px; color:var(--text-muted);">${c.desc}</span>
          </div>
        </label>
      `;
    }).join('');

    const btnSaveGateways = document.getElementById('btn-save-gateways');
    if (btnSaveGateways && !btnSaveGateways.dataset.listener) {
      btnSaveGateways.dataset.listener = 'true';
      btnSaveGateways.addEventListener('click', async () => {
        const chks = document.querySelectorAll('.gateway-chk');
        const updated = {};
        chks.forEach(chk => {
          updated[chk.getAttribute('data-key')] = chk.checked;
        });

        // Save to DB
        try {
          const res = await fetch(`api/settings.php?tenant=${tenant}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ns_settings_gateways: updated })
          });
          const result = await res.json();
          if (result.success) {
            localStorage.setItem('ns_settings_gateways', JSON.stringify(updated));
            store.addActivity('info', 'تعديل بوابات الدفع', 'تم تحديث بوابات الدفع الإلكتروني النشطة في المتجر بقاعدة البيانات');
            alert('تم حفظ إعدادات بوابات الدفع بنجاح!');
          }
        } catch(err) {
          alert('فشل حفظ البوابات.');
        }
      });
    }

    const btnSaveCarriers = document.getElementById('btn-save-carriers');
    if (btnSaveCarriers && !btnSaveCarriers.dataset.listener) {
      btnSaveCarriers.dataset.listener = 'true';
      btnSaveCarriers.addEventListener('click', async () => {
        const chks = document.querySelectorAll('.carrier-chk');
        const updated = {};
        chks.forEach(chk => {
          updated[chk.getAttribute('data-key')] = chk.checked;
        });

        // Save to DB
        try {
          const res = await fetch(`api/settings.php?tenant=${tenant}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ ns_settings_carriers: updated })
          });
          const result = await res.json();
          if (result.success) {
            localStorage.setItem('ns_settings_carriers', JSON.stringify(updated));
            store.addActivity('info', 'تعديل شركات الشحن', 'تم تحديث خيارات شركات الشحن واللوجستيات المعتمدة بقاعدة البيانات');
            alert('تم حفظ إعدادات شركات الشحن بنجاح!');
          }
        } catch(err) {
          alert('فشل حفظ خيارات الشحن.');
        }
      });
    }

  } catch(err) {
    console.warn('Failed to load gateways/carriers:', err);
  }
}

export function initThemesSettings() {
  const jasmineCard = document.getElementById('theme-card-jasmine');
  const ellaCard = document.getElementById('theme-card-ella');
  const woodmartCard = document.getElementById('theme-card-woodmart');
  if (!jasmineCard || !ellaCard) return;

  const btnActivateJasmine = document.getElementById('btn-activate-jasmine');
  const btnActivateElla = document.getElementById('btn-activate-ella');
  const btnActivateWoodmart = document.getElementById('btn-activate-woodmart');

  const badgeJasmine = document.getElementById('badge-jasmine');
  const badgeElla = document.getElementById('badge-ella');
  const badgeWoodmart = document.getElementById('badge-woodmart');

  const updateUI = () => {
    const activeTheme = localStorage.getItem('ns_active_theme') || 'jasmine';

    if (activeTheme === 'woodmart') {
      jasmineCard.classList.remove('active');
      jasmineCard.style.borderColor = 'var(--border-color)';
      badgeJasmine.style.display = 'none';
      if (btnActivateJasmine) {
        btnActivateJasmine.disabled = false;
        btnActivateJasmine.innerHTML = '<i class="fas fa-toggle-on"></i> تفعيل كافتراضي للمتجر';
      }

      ellaCard.classList.remove('active');
      ellaCard.style.borderColor = 'var(--border-color)';
      badgeElla.style.display = 'none';
      if (btnActivateElla) {
        btnActivateElla.disabled = false;
        btnActivateElla.innerHTML = '<i class="fas fa-toggle-on"></i> تفعيل كافتراضي للمتجر';
      }

      if (woodmartCard) {
        woodmartCard.classList.add('active');
        woodmartCard.style.borderColor = '#ffcc00';
      }
      if (badgeWoodmart) badgeWoodmart.style.display = 'inline-block';
      if (btnActivateWoodmart) {
        btnActivateWoodmart.disabled = true;
        btnActivateWoodmart.innerHTML = '<i class="fas fa-check"></i> تم التفعيل كافتراضي';
      }
    } else if (activeTheme === 'ella') {
      jasmineCard.classList.remove('active');
      jasmineCard.style.borderColor = 'var(--border-color)';
      badgeJasmine.style.display = 'none';
      if (btnActivateJasmine) {
        btnActivateJasmine.disabled = false;
        btnActivateJasmine.innerHTML = '<i class="fas fa-toggle-on"></i> تفعيل كافتراضي للمتجر';
      }

      ellaCard.classList.add('active');
      ellaCard.style.borderColor = '#d12442';
      badgeElla.style.display = 'inline-block';
      if (btnActivateElla) {
        btnActivateElla.disabled = true;
        btnActivateElla.innerHTML = '<i class="fas fa-check"></i> تم التفعيل كافتراضي';
      }

      if (woodmartCard) {
        woodmartCard.classList.remove('active');
        woodmartCard.style.borderColor = 'var(--border-color)';
      }
      if (badgeWoodmart) badgeWoodmart.style.display = 'none';
      if (btnActivateWoodmart) {
        btnActivateWoodmart.disabled = false;
        btnActivateWoodmart.innerHTML = '<i class="fas fa-toggle-on"></i> تفعيل كافتراضي للمتجر';
      }
    } else {
      ellaCard.classList.remove('active');
      ellaCard.style.borderColor = 'var(--border-color)';
      badgeElla.style.display = 'none';
      if (btnActivateElla) {
        btnActivateElla.disabled = false;
        btnActivateElla.innerHTML = '<i class="fas fa-toggle-on"></i> تفعيل كافتراضي للمتجر';
      }

      jasmineCard.classList.add('active');
      jasmineCard.style.borderColor = 'var(--secondary-color)';
      badgeJasmine.style.display = 'inline-block';
      if (btnActivateJasmine) {
        btnActivateJasmine.disabled = true;
        btnActivateJasmine.innerHTML = '<i class="fas fa-check"></i> تم التفعيل كافتراضي';
      }

      if (woodmartCard) {
        woodmartCard.classList.remove('active');
        woodmartCard.style.borderColor = 'var(--border-color)';
      }
      if (badgeWoodmart) badgeWoodmart.style.display = 'none';
      if (btnActivateWoodmart) {
        btnActivateWoodmart.disabled = false;
        btnActivateWoodmart.innerHTML = '<i class="fas fa-toggle-on"></i> تفعيل كافتراضي للمتجر';
      }
    }
  };

  const saveThemeToDB = async (themeName) => {
    try {
      const tenant = getTenantSlug();
      localStorage.setItem('ns_active_theme', themeName);
      await fetch(`api/settings.php?tenant=${tenant}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ ns_active_theme: themeName })
      });
      store.addActivity('info', 'تحديث ثيم المتجر', `تم تفعيل قالب (${themeName}) كافتراضي للمتجر`);
      updateUI();
    } catch(err) {
      console.warn('Failed to save theme setting to DB:', err);
    }
  };

  if (btnActivateJasmine && !btnActivateJasmine.dataset.listener) {
    btnActivateJasmine.dataset.listener = 'true';
    btnActivateJasmine.addEventListener('click', () => {
      saveThemeToDB('jasmine');
      alert('تم تفعيل ثيم الياسمين الدمشقي بنجاح لفرونت اند متجرك!');
    });
  }

  if (btnActivateElla && !btnActivateElla.dataset.listener) {
    btnActivateElla.dataset.listener = 'true';
    btnActivateElla.addEventListener('click', () => {
      saveThemeToDB('ella');
      alert('تم تفعيل ثيم إيلا للألبسة (Ella Theme) بنجاح لفرونت اند متجرك!');
    });
  }

  if (btnActivateWoodmart && !btnActivateWoodmart.dataset.listener) {
    btnActivateWoodmart.dataset.listener = 'true';
    btnActivateWoodmart.addEventListener('click', () => {
      saveThemeToDB('woodmart');
      alert('تم تفعيل ثيم وودمارت للإلكترونيات (WoodMart Theme) بنجاح لفرونت اند متجرك!');
    });
  }

  updateUI();
}
