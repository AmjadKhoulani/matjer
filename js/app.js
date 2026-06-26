import { initDashboard } from './dashboard.js';
import { initInventory, renderInventoryTable, initTransfers, initAdjustments, initCategoriesView } from './inventory.js';
import { initOrders, initCustomers } from './orders.js';
import { initSuppliers, renderSuppliersTable } from './suppliers.js';
import { initPOS } from './pos.js';
import { initAccounting, initTaxReport } from './accounting.js';
import { initReports, initBranchPerformance } from './reports.js';
import { initTeam, initAuditLogs } from './team.js';
import { initSettings } from './settings.js';
import { initApps, renderAppsMarketplace, renderInstalledApps } from './apps.js';
import { initPurchases } from './purchases.js';
import { initERPFeatures } from './erp-features.js';
import { initEcommerceDashboard, initCoupons, initReviews } from './ecommerce-dashboard.js';


import { store } from './store.js';

document.addEventListener('DOMContentLoaded', () => {
  initApp();
});

async function initApp() {
  // 0. Load all database tables into cache
  await store.loadAllData();

  // 1. Initialize Theme (Light by default, or load from localStorage)
  const savedTheme = localStorage.getItem('ns_theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);
  
  // Set theme icon accordingly
  updateThemeIcon(savedTheme);

  // 2. Setup navigation routing (SPA Tab system)
  setupNavigation();
  setupPortalSwitcher();

  // 3. Setup global event listeners (theme toggle, mobile sidebar toggler)
  setupGlobalListeners();

  // 4. Initialize dynamic content modules
  initDashboard();
  initInventory();
  initOrders();
  initSuppliers();
  initPOS();
  initAccounting();
  initReports();
  initTeam();
  initSettings();
  initApps();
  initPurchases();
  initERPFeatures();
  initOrderNotifications();
}

export function navigateToView(viewName) {
  // RBAC Navigation Restrictions
  if (window.CurrentUser) {
    const role = window.CurrentUser.role;
    const warehouseAllowed = [
      'dashboard', 'inventory', 'categories', 'add-product', 'suppliers', 
      'create-purchase', 'all-purchases', 'warehouse-transfers', 
      'inventory-adjustments', 'create-adjustment', 'opening-stock', 
      'print-labels', 'brand-management', 'units-management', 
      'batches-management', 'import-products', 'import-products-update', 
      'import-purchases'
    ];
    const salesAllowed = [
      'dashboard', 'pos', 'create-sale', 'all-sales', 'orders', 
      'customer-screen', 'realtime-counter', 'ecommerce-customers', 
      'accounting-invoices', 'create-quotation', 'all-quotations'
    ];

    if (role === 'warehouse' && !warehouseAllowed.includes(viewName)) {
      alert('ليس لديك صلاحية للوصول إلى هذا القسم كأمين مستودع.');
      return;
    }
    if (role === 'sales' && !salesAllowed.includes(viewName)) {
      alert('ليس لديك صلاحية للوصول إلى هذا القسم كموظف مبيعات.');
      return;
    }
  }

  // Handle customer screen opening in new window/tab
  if (viewName === 'customer-screen') {
    const tSlug = (window.ActiveTenant && window.ActiveTenant.slug) ? window.ActiveTenant.slug : 'demo'; window.open('storefront.php?preview=woodmart&tenant=' + tSlug, '_blank');
    return;
  }

  // Handle print labels inline intercept
  if (viewName === 'print-labels') {
    alert('طباعة ملصقات الباركود:\nالرجاء تحديد المنتجات من لائحة "كافة المنتجات" ثم النقر على طباعة ملصق.');
    navigateToView('inventory');
    return;
  }

  const submenuItems = document.querySelectorAll('.submenu-item');
  const sections = document.querySelectorAll('.view-section');
  const pageTitle = document.getElementById('active-page-title');
  const pageSubtitle = document.getElementById('active-page-subtitle');
  
  // Auto-toggle portal switcher visual state based on viewName
  const ecoOnlyViews = ['ecommerce-dashboard', 'ecommerce-coupons', 'ecommerce-reviews', 'ecommerce-integration', 'settings-payment-shipping', 'settings-themes', 'apps-marketplace', 'apps-installed', 'orders', 'ecommerce-customers', 'ecommerce-customers-detail'];
  const posOnlyViews = ['pos'];
  const erpOnlyViews = ['dashboard', 'create-purchase', 'all-purchases', 'warehouse-transfers', 'inventory-adjustments', 'accounting-finance', 'accounting-tax', 'reports-sales', 'reports-stock', 'team-employees', 'team-permissions', 'team-audit', 'settings-system', 'settings-warehouses', 'brand-management', 'units-management', 'batches-management', 'import-products', 'import-products-update', 'import-purchases', 'import-sales', 'realtime-counter'];
  const sidebar = document.querySelector('.sidebar');
  
  let activePortal = sidebar ? (sidebar.getAttribute('data-active-portal') || 'erp') : 'erp';
  if (ecoOnlyViews.includes(viewName)) {
    activePortal = 'ecommerce';
  } else if (posOnlyViews.includes(viewName)) {
    activePortal = 'pos';
  } else if (erpOnlyViews.includes(viewName)) {
    activePortal = 'erp';
  }
  
  if (sidebar) {
    sidebar.setAttribute('data-active-portal', activePortal);
    localStorage.setItem('ns_active_portal', activePortal);
  }
  
  const portalBtns = document.querySelectorAll('.portal-btn');
  portalBtns.forEach(btn => {
    if (btn.getAttribute('data-portal') === activePortal) {
      btn.classList.add('active');
    } else {
      btn.classList.remove('active');
    }
  });

  const titleMap = {
    'dashboard': { title: 'لوحة التحكم', subtitle: 'نظرة عامة على مبيعات المتجر ومخازن المستودعات' },
    'inventory': { title: 'مستودع المنتجات', subtitle: 'إدارة المنتجات، مستويات المخزون، وعمليات التعديل السريع' },
    'categories': { title: 'تصنيفات المنتجات', subtitle: 'إدارة وتعديل الفئات والتصنيفات للمنتجات في المخزون' },
    'orders': { title: 'إدارة الطلبات', subtitle: 'متابعة وتحديث طلبات العملاء وتصدير الفواتير' },
    'suppliers': { title: 'الموردون والمشتريات', subtitle: 'بيانات الموردين وتوريد شحنات المخازن الجديدة' },
    'add-product': { title: 'إضافة منتج جديد', subtitle: 'تصميم وإطلاق منتج جديد في المتجر ونظام المستودعات' },
    'pos': { title: 'نقطة البيع (POS)', subtitle: 'نظام تسجيل المبيعات المباشرة والفواتير وخصم كميات المخزون فورياً' },
    'accounting-invoices': { title: 'الفواتير والسندات', subtitle: 'إدارة فواتير المبيعات الصادرة وسندات القبض الفورية' },
    'accounting-finance': { title: 'الإيرادات والمصاريف', subtitle: 'كشف مالي بحجم المقبوضات والنفقات التشغيلية للمستودعات' },
    'reports-sales': { title: 'تقارير المبيعات وتحليل الأداء', subtitle: 'مخططات حركة الإيرادات اليومية وتوزيع مبيعات الفروع' },
    'reports-stock': { title: 'حركة المخزون اللحظية', subtitle: 'سجل عمليات التوريد والصرف وجرد المستودع اللحظي' },
    'team-employees': { title: 'إدارة موظفي الفروع والمستودعات', subtitle: 'بيانات الموظفين النشطين ومواقع عملهم في النظام' },
    'team-permissions': { title: 'الصلاحيات والأدوار الأمنية', subtitle: 'توزيع صلاحيات الوصول وتخصيص صلاحيات العمليات للمستخدمين' },
    'settings-system': { title: 'إعدادات النظام العامة', subtitle: 'تهيئة اسم المؤسسة، الشعار، العملة، ومعدلات ضريبة القيمة المضافة' },
    'settings-warehouses': { title: 'إدارة وتوزيع المستودعات الجغرافية', subtitle: 'تحديد عناوين المخازن النشطة وربطها بنقاط البيع' },
    'warehouse-transfers': { title: 'التحويل بين المستودعات والمخازن', subtitle: 'نقل كميات البضائع بين الفروع والمخازن الجغرافية وتتبع الشحنات' },
    'inventory-adjustments': { title: 'تسجيل التوالف والنواقص الجردية', subtitle: 'تسجيل وإتلاف البضائع التالفة أو المفقودة وتعديل أرصدة المخازن فورياً' },
    'ecommerce-customers': { title: 'دليل وعملاء المتجر الموحد', subtitle: 'إدارة سجلات العملاء، تفاصيل الشراء، وإحصائيات نقاط الولاء' },
    'ecommerce-integration': { title: 'ربط المنصات والمزامنة التلقائية', subtitle: 'إعداد الاتصال بالـ API الخارجي لـ WooCommerce ومزامنة الكميات والأسعار' },
    'accounting-tax': { title: 'الإقرارات الضريبية وحساب VAT', subtitle: 'توليد التقارير الربع سنوية لضريبة القيمة المضافة المحصلة والمدفوعة' },
    'reports-branches': { title: 'تقارير أداء ومبيعات الفروع', subtitle: 'مخططات بيانية لمبيعات الفروع والمخازن الجغرافية وتحليل قنوات البيع' },
    'team-audit': { title: 'سجل تدقيق الأمان والعمليات للفريق', subtitle: 'سجل زمني لعمليات الفريق ومتابعة التغييرات الأمنية على الفواتير والمخزون' },
    'settings-payment-shipping': { title: 'تهيئة بوابات الدفع وشركات الشحن', subtitle: 'تفعيل وتكوين وسائل السداد الإلكتروني ومزودي الخدمات البريدية واللوجستية' },
    'settings-themes': { title: 'مكتبة الثيمات وقوالب المتجر', subtitle: 'تهيئة وتخصيص ثيم الفرونت اند الموجه للعملاء وتفعيله كافتراضي' },
    'apps-marketplace': { title: 'سوق تطبيقات متجر الملحقة', subtitle: 'تصفح وتثبيت تطبيقات وإضافات متجر Shopify و WooCommerce لتوسيع لوحة التحكم' },
    'apps-installed': { title: 'التطبيقات المثبتة والنشطة', subtitle: 'إدارة وإعداد وحذف ملحقات متجرك الإلكتروني وتخصيص الربط' },
    'create-purchase': { title: 'سند مشتريات جديد', subtitle: 'تسجيل وإصدار فواتير شراء وتوريد بضائع جديدة للمستودع' },
    'all-purchases': { title: 'فواتير المشتريات (الموردين)', subtitle: 'متابعة كافة فواتير الشراء وسندات التوريد الصادرة للموردين' },
    'all-sales': { title: 'فواتير المبيعات (العملاء)', subtitle: 'متابعة حركة فواتير المبيعات الصادرة والتحصيل اليومي' },
    'create-sale': { title: 'فاتورة مبيعات جديدة', subtitle: 'إصدار فاتورة مبيعات جديدة للعملاء وتخفيض كميات المخازن فورياً' },
    'brand-management': { title: 'العلامات التجارية للشركات', subtitle: 'إدارة قائمة العلامات التجارية للمنتجات وتصنيفها في المخازن' },
    'units-management': { title: 'وحدات التعبئة والقياس', subtitle: 'تحديد وحدات القياس المعتمدة للمنتجات في المخزون والمبيعات' },
    'batches-management': { title: 'سجل الدفعات والشحنات الواردة', subtitle: 'تتبع كميات وتواريخ شحنات بضائع الموردين المستلمة للمستودعات' },
    'import-products': { title: 'استيراد البيانات التلقائي', subtitle: 'معالجة واستيراد ملفات الإكسيل لتحديث المنتجات والمبيعات والمشتريات' },
    'import-products-update': { title: 'استيراد البيانات التلقائي', subtitle: 'معالجة واستيراد ملفات الإكسيل لتحديث أرصدة المنتجات والمخزون' },
    'import-purchases': { title: 'استيراد البيانات التلقائي', subtitle: 'معالجة واستيراد ملفات الإكسيل لتحديث سجل فواتير الشراء' },
    'import-sales': { title: 'استيراد البيانات التلقائي', subtitle: 'معالجة واستيراد ملفات الإكسيل لتحديث سجل المبيعات والتحصيل' },
    'realtime-counter': { title: 'عداد حركات العمليات اللحظي المباشر', subtitle: 'شاشة مراقبة تفاعلية للطلبات الواردة وعمليات البيع فور حدوثها' },
    'sales-return': { title: 'مرتجع وإلغاء فواتير المبيعات الصادرة', subtitle: 'تسجيل مرتجعات العملاء وإعادة كميات البضائع للمستودع تلقائياً' },
    'ecommerce-dashboard': { title: 'إحصائيات وتحليلات المتجر', subtitle: 'نظرة عامة على مبيعات المتجر الإلكتروني ومصادر الزيارات ومعدلات التحويل' },
    'ecommerce-coupons': { title: 'إدارة كوبونات الخصم والتسويق', subtitle: 'توليد كوبونات خصم مخصصة للعملاء وتتبع أدائها ونسب الاستخدام' },
    'ecommerce-reviews': { title: 'مراجعات وتقييمات العملاء للمنتجات', subtitle: 'مراقبة وتقييم وتدقيق تعليقات ومراجعات العملاء للمنتجات بالمتجر' },
    'product-details': { title: 'تفاصيل المنتج والتحليلات', subtitle: 'عرض تفصيلي لبيانات المنتج وإحصائيات المبيعات والتقييمات' },
    'ecommerce-customers-detail': { title: 'ملف تعريفي كامل وتفاعل العميل (CRM)', subtitle: 'متابعة سجل المشتريات وملاحظات خدمة العملاء للـ CRM' },
    'create-quotation': { title: 'عرض سعر جديد', subtitle: 'إعداد وتوليد عروض أسعار للعملاء قابلة للطباعة والتحويل لفواتير' },
    'all-quotations': { title: 'عروض الأسعار الصادرة', subtitle: 'متابعة وإدارة عروض الأسعار والتحقق من صلاحيتها وتحويلها لفواتير مبيعات' }
  };

  // Update active class on submenu items
  submenuItems.forEach(item => {
    const isMatched = (item.getAttribute('data-view') === viewName) ||
                      (viewName === 'add-product' && item.getAttribute('data-view') === 'inventory');
    if (isMatched) {
      item.classList.add('active');
      
      // Auto-open parent section and close others
      const parentSection = item.closest('.menu-section');
      if (parentSection && !parentSection.classList.contains('single-link')) {
        document.querySelectorAll('.menu-section').forEach(sec => {
          if (sec !== parentSection) {
            sec.classList.remove('open');
          }
        });
        parentSection.classList.add('open');
      }
    } else {
      item.classList.remove('active');
    }
  });

  // Handle single link active highlighting
  const singleLinks = document.querySelectorAll('.menu-section.single-link');
  singleLinks.forEach(link => {
    const isMatched = link.getAttribute('data-view') === viewName;
    if (isMatched) {
      link.classList.add('active');
      // Close all collapsible sections since a single-link is active
      document.querySelectorAll('.menu-section:not(.single-link)').forEach(sec => {
        sec.classList.remove('open');
      });
    } else {
      link.classList.remove('active');
    }
  });

  // Determine physical view for section toggle
  let targetView = viewName;
  if (viewName === 'all-purchases' || viewName === 'all-sales') {
    targetView = 'accounting-invoices';
  } else if (viewName === 'create-adjustment') {
    targetView = 'inventory-adjustments';
  } else if (viewName === 'opening-stock') {
    targetView = 'inventory';
  } else if (viewName === 'import-products-update' || viewName === 'import-purchases' || viewName === 'import-sales') {
    targetView = 'import-products';
  }

  // Update section visibility
  sections.forEach(sec => {
    let checkId = targetView;
    if (targetView === 'import-products') {
      checkId = 'import-management';
    } else if (targetView === 'brand-management') {
      checkId = 'brand-management';
    } else if (targetView === 'units-management') {
      checkId = 'units-management';
    } else if (targetView === 'batches-management') {
      checkId = 'batches-management';
    } else if (targetView === 'realtime-counter') {
      checkId = 'realtime-counter';
    } else if (targetView === 'sales-return') {
      checkId = 'sales-return';
    } else if (targetView === 'ecommerce-dashboard') {
      checkId = 'ecommerce-dashboard';
    } else if (targetView === 'ecommerce-coupons') {
      checkId = 'ecommerce-coupons';
    } else if (targetView === 'ecommerce-reviews') {
      checkId = 'ecommerce-reviews';
    }

    if (sec.id === `${checkId}-section`) {
      sec.classList.add('active');
    } else {
      sec.classList.remove('active');
    }
  });

  // Handle programmatically clicking invoice tab buttons
  if (viewName === 'all-purchases') {
    const tabBtn = document.querySelector('.btn-invoice-tab[data-tab="purchases"]');
    if (tabBtn) tabBtn.click();
  } else if (viewName === 'all-sales') {
    const tabBtn = document.querySelector('.btn-invoice-tab[data-tab="sales"]');
    if (tabBtn) tabBtn.click();
  }

  // Update Header title dynamically
  if (titleMap[viewName]) {
    pageTitle.innerText = titleMap[viewName].title;
    pageSubtitle.innerText = titleMap[viewName].subtitle;
  }

  // Refresh dynamic data
  refreshViewData(targetView);
}

function refreshViewData(viewName) {
  // Dynamically refresh ERP lists on view load
  import('./erp-features.js').then(module => {
    module.initERPFeatures();
  });

  switch (viewName) {
    case 'dashboard':
      initDashboard();
      break;
    case 'inventory':
      renderInventoryTable();
      break;
    case 'categories':
      initCategoriesView();
      break;
    case 'suppliers':
      renderSuppliersTable();
      break;
    case 'warehouse-transfers':
      initTransfers();
      break;
    case 'inventory-adjustments':
      initAdjustments();
      break;
    case 'accounting-tax':
      initTaxReport();
      break;
    case 'team-audit':
      initAuditLogs();
      break;
    case 'reports-branches':
      initBranchPerformance();
      break;
    case 'apps-marketplace':
      renderAppsMarketplace();
      break;
    case 'apps-installed':
      renderInstalledApps();
      break;
    case 'create-purchase':
      initPurchases();
      break;
    case 'create-sale':
      import('./sales.js').then(module => {
        module.initSales();
      });
      break;
    case 'create-quotation':
      import('./quotations.js').then(module => {
        module.initQuotationForm();
      });
      break;
    case 'all-quotations':
      import('./quotations.js').then(module => {
        module.initQuotationsList();
      });
      break;
    case 'ecommerce-dashboard':
      initEcommerceDashboard();
      break;
    case 'ecommerce-coupons':
      initCoupons();
      break;
    case 'ecommerce-reviews':
      initReviews();
      break;
    case 'ecommerce-customers':
      initCustomers();
      break;
  }
}

function setupNavigation() {
  // Accordion toggle on section header click
  const sectionHeaders = document.querySelectorAll('.menu-section-header');
  sectionHeaders.forEach(header => {
    header.addEventListener('click', (e) => {
      const parentSection = header.parentElement;
      if (parentSection.classList.contains('single-link')) {
        const targetView = parentSection.getAttribute('data-view');
        if (targetView) {
          navigateToView(targetView);
          
          // Auto close mobile sidebar if open
          document.querySelector('.sidebar').classList.remove('mobile-open');
        }
        return;
      }
      
      const isOpen = parentSection.classList.contains('open');
      
      // Close other open sections
      document.querySelectorAll('.menu-section').forEach(sec => {
        sec.classList.remove('open');
      });
      
      if (!isOpen) {
        parentSection.classList.add('open');
      }
    });
  });

  // Submenu link router click
  const submenuItems = document.querySelectorAll('.submenu-item');
  submenuItems.forEach(item => {
    const link = item.querySelector('.submenu-link');
    if (link) {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const targetView = item.getAttribute('data-view');
        if (!targetView) return;
        navigateToView(targetView);
        
        // Auto close mobile sidebar if open
        document.querySelector('.sidebar').classList.remove('mobile-open');
      });
    }
  });
}

function setupGlobalListeners() {
  // Theme toggle click
  document.getElementById('btn-theme-toggle').addEventListener('click', () => {
    const currentTheme = document.documentElement.getAttribute('data-theme');
    const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
    
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('ns_theme', newTheme);
    updateThemeIcon(newTheme);
    
    // Dispatch custom event so Charts can redraw with theme-aware colors
    window.dispatchEvent(new Event('theme-changed'));
  });

  // Mobile sidebar toggler
  document.getElementById('btn-menu-toggle').addEventListener('click', () => {
    document.querySelector('.sidebar').classList.add('mobile-open');
  });

  // Close mobile sidebar click-outside behavior
  document.addEventListener('click', (e) => {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.getElementById('btn-menu-toggle');
    
    if (window.innerWidth <= 768) {
      if (sidebar.classList.contains('mobile-open') && 
          !sidebar.contains(e.target) && 
          !toggleBtn.contains(e.target)) {
        sidebar.classList.remove('mobile-open');
      }
    }
  });
  
  // Connect Global search in top-bar to current active views
  document.getElementById('global-search-input').addEventListener('input', (e) => {
    const searchVal = e.target.value.toLowerCase();
    const activeEl = document.querySelector('.submenu-item.active');
    const activeView = activeEl ? activeEl.getAttribute('data-view') : '';
    
    if (activeView === 'inventory') {
      const invSearch = document.getElementById('inventory-search');
      if (invSearch) {
        invSearch.value = searchVal;
        invSearch.dispatchEvent(new Event('input'));
      }
    } else if (activeView === 'orders') {
      const ordSearch = document.getElementById('orders-search');
      if (ordSearch) {
        ordSearch.value = searchVal;
        ordSearch.dispatchEvent(new Event('input'));
      }
    } else if (activeView === 'pos') {
      const posSearch = document.getElementById('pos-search-input');
      if (posSearch) {
        posSearch.value = searchVal;
        posSearch.dispatchEvent(new Event('input'));
      }
    }
  });
}

function updateThemeIcon(theme) {
  const icon = document.querySelector('#btn-theme-toggle i');
  if (icon) {
    if (theme === 'dark') {
      icon.className = 'fas fa-sun';
    } else {
      icon.className = 'fas fa-moon';
    }
  }
}

function setupPortalSwitcher() {
  const portalBtns = document.querySelectorAll('.portal-btn');
  const sidebar = document.querySelector('.sidebar');
  if (!sidebar) return;

  const currentPortal = localStorage.getItem('ns_active_portal') || 'erp';
  sidebar.setAttribute('data-active-portal', currentPortal);

  portalBtns.forEach(btn => {
    const portal = btn.getAttribute('data-portal');
    if (portal === currentPortal) {
      btn.classList.add('active');
    } else {
      btn.classList.remove('active');
    }

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const targetPortal = btn.getAttribute('data-portal');

      portalBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      sidebar.setAttribute('data-active-portal', targetPortal);
      localStorage.setItem('ns_active_portal', targetPortal);

      // Clear currently active submenu highlights from previous portal
      document.querySelectorAll('.submenu-item').forEach(item => item.classList.remove('active'));

      // Redirect to default dashboard of the selected portal
      if (targetPortal === 'erp') {
        navigateToView('dashboard');
      } else if (targetPortal === 'ecommerce') {
        navigateToView('ecommerce-dashboard');
      } else if (targetPortal === 'pos') {
        navigateToView('pos');
      }
    });
  });
}

function initOrderNotifications() {
  window.addEventListener('storage', (e) => {
    if (e.key === 'ns_orders') {
      const oldVal = e.oldValue ? JSON.parse(e.oldValue) : [];
      const newVal = e.newValue ? JSON.parse(e.newValue) : [];
      
      if (newVal.length > oldVal.length) {
        const newOrder = newVal[0]; // orders are unshifted, so index 0 is the newest order
        showOrderToastNotification(newOrder);
        
        // Refresh active views
        const activeSection = document.querySelector('.view-section.active');
        if (activeSection) {
          if (activeSection.id === 'orders-section') {
            import('./orders.js').then(module => {
              module.renderOrdersTable();
            });
          } else if (activeSection.id === 'ecommerce-dashboard-section') {
            import('./ecommerce-dashboard.js').then(module => {
              module.initEcommerceDashboard();
            });
          } else if (activeSection.id === 'dashboard-section') {
            import('./dashboard.js').then(module => {
              module.initDashboard();
            });
          }
        }
      }
    }
  });
}

function showOrderToastNotification(order) {
  let container = document.querySelector('.order-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'order-toast-container';
    document.body.appendChild(container);
  }

  const sypFactor = 15000;
  // Calculate final SYP total (including 15% tax)
  const finalSYP = Math.round(order.total * sypFactor * 1.15);

  const toast = document.createElement('div');
  toast.className = 'order-toast';
  toast.innerHTML = `
    <div class="order-toast-icon">
      <i class="fas fa-shopping-bag"></i>
    </div>
    <div class="order-toast-content">
      <div class="order-toast-title">🔔 طلب إلكتروني جديد!</div>
      <div class="order-toast-desc">
        قام العميل <strong>${order.customerName}</strong> بطلب جديد بقيمة <strong>${finalSYP.toLocaleString()} ل.س</strong> (رقم الطلب #${order.id})
      </div>
      <div class="order-toast-actions">
        <button class="order-toast-btn btn-view-order-toast" data-id="${order.id}">عرض الطلب في لوحة التحكم</button>
      </div>
    </div>
    <button class="order-toast-close">&times;</button>
  `;

  container.appendChild(toast);

  // Trigger entrance transition
  setTimeout(() => toast.classList.add('active'), 50);

  // Play audio alert
  try {
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const osc = audioCtx.createOscillator();
    const gainNode = audioCtx.createGain();
    osc.connect(gainNode);
    gainNode.connect(audioCtx.destination);
    osc.type = 'sine';
    osc.frequency.setValueAtTime(880, audioCtx.currentTime); // A5 note
    gainNode.gain.setValueAtTime(0.08, audioCtx.currentTime);
    osc.start();
    osc.stop(audioCtx.currentTime + 0.15);
  } catch (err) {
    console.warn("Could not play notification audio:", err);
  }

  // Close event
  toast.querySelector('.order-toast-close').addEventListener('click', () => {
    toast.classList.remove('active');
    setTimeout(() => toast.remove(), 400);
  });

  // Action event
  toast.querySelector('.btn-view-order-toast').addEventListener('click', () => {
    toast.classList.remove('active');
    setTimeout(() => toast.remove(), 400);

    // Switch active portal to eCommerce
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
      sidebar.setAttribute('data-active-portal', 'ecommerce');
      localStorage.setItem('ns_active_portal', 'ecommerce');
    }
    
    // Highlight correct portal button
    document.querySelectorAll('.portal-btn').forEach(btn => {
      if (btn.getAttribute('data-portal') === 'ecommerce') btn.classList.add('active');
      else btn.classList.remove('active');
    });

    // Navigate to orders list
    navigateToView('orders');

    // Open detailed invoice modal
    setTimeout(() => {
      import('./orders.js').then(module => {
        module.openOrderDetailsModal(order.id);
      });
    }, 300);
  });

  // Auto dismiss
  setTimeout(() => {
    if (toast.parentNode) {
      toast.classList.remove('active');
      setTimeout(() => toast.remove(), 400);
    }
  }, 8000);
}
