<?php
require_once __DIR__ . '/api/config.php';

// Enforce authentication
if (!isset($_SESSION['user_id'])) {
    $tenant_slug = isset($_GET['tenant']) ? trim($_GET['tenant']) : '';
    header("Location: login.php?tenant=" . urlencode($tenant_slug));
    exit;
}

// Redirect super admin to SaaS dashboard
if ($_SESSION['tenant_id'] === null) {
    header("Location: dashboard.php");
    exit;
}

$tenant_id = $_SESSION['tenant_id'];
$tenant = get_active_tenant_details();
$store_name = $tenant ? $tenant['name'] : 'المتجر النموذجي';
$tenant_slug = $tenant ? $tenant['slug'] : '';
$theme_color = $tenant ? $tenant['theme_color'] : '#4f46e5';

// Helper to convert hex to HSL for CSS variables
function hexToHsl($hex) {
    $hex = str_replace('#', '', $hex);
    if(strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    $r /= 255; $g /= 255; $b /= 255;
    $max = max($r, $g, $b); $min = min($r, $g, $b);
    $l = ($max + $min) / 2;
    if ($max == $min) {
        $h = $s = 0;
    } else {
        $d = $max - $min;
        $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
        switch($max){
            case $r: $h = ($g - $b) / $d + ($g < $b ? 6 : 0); break;
            case $g: $h = ($b - $r) / $d + 2; break;
            case $b: $h = ($r - $g) / $d + 4; break;
        }
        $h /= 6;
    }
    return [round($h * 360), round($s * 100), round($l * 100)];
}

$hsl = hexToHsl($theme_color);
$hsl_str = $hsl[0] . ", " . $hsl[1] . "%, " . $hsl[2] . "%";
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة التحكم | <?php echo htmlspecialchars($store_name); ?></title>
  
  <style>
    body { display: none !important; }
    
    /* CSS for hiding options based on roles */
    html.role-warehouse .role-hide-warehouse,
    html.role-warehouse [data-portal="pos"],
    html.role-warehouse [data-portal="ecommerce"],
    html.role-warehouse [data-view="accounting-finance"],
    html.role-warehouse [data-view="accounting-tax"],
    html.role-warehouse [data-view="team-employees"],
    html.role-warehouse [data-view="team-permissions"],
    html.role-warehouse [data-view="team-audit"],
    html.role-warehouse [data-view="settings-system"],
    html.role-warehouse #accounting-finance-section,
    html.role-warehouse #reports-sales-section,
    html.role-warehouse #accounting-tax-section,
    html.role-warehouse #team-employees-section,
    html.role-warehouse #settings-system-section {
        display: none !important;
    }
    
    html.role-sales .role-hide-sales,
    html.role-sales [data-portal="erp"],
    html.role-sales [data-portal="ecommerce"],
    html.role-sales [data-view="team-employees"],
    html.role-sales [data-view="team-permissions"],
    html.role-sales [data-view="team-audit"],
    html.role-sales [data-view="settings-system"],
    html.role-sales [data-view="settings-warehouses"],
    html.role-sales #inventory-section,
    html.role-sales #categories-section,
    html.role-sales #suppliers-section,
    html.role-sales #create-purchase-section,
    html.role-sales #accounting-finance-section,
    html.role-sales #reports-sales-section,
    html.role-sales #reports-stock-section,
    html.role-sales #team-employees-section,
    html.role-sales #settings-system-section,
    html.role-sales #settings-warehouses-section,
    html.role-sales #warehouse-transfers-section,
    html.role-sales #inventory-adjustments-section {
        display: none !important;
    }
  </style>
  <script>
    (function() {
      // Injected directly via server session
      window.CurrentUser = <?php echo json_encode([
          'username' => $_SESSION['username'],
          'fullname' => $_SESSION['fullname'],
          'role' => $_SESSION['role'],
          'tenant_id' => $_SESSION['tenant_id']
      ]); ?>;
      window.ActiveTenant = <?php echo json_encode($tenant); ?>;
      
      document.documentElement.classList.add('role-' + window.CurrentUser.role);
      
      // Inject display styles to override body display none
      const style = document.createElement('style');
      style.innerHTML = 'body { display: block !important; }';
      document.head.appendChild(style);
    })();
  </script>
  
  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="css/variables.css?v=3.0">
  <link rel="stylesheet" href="css/style.css?v=3.0">
  <link rel="stylesheet" href="css/components.css?v=3.0">
  
  <!-- FontAwesome Icons for modern dashboard elements -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Chart.js for charts -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    :root, [data-theme="light"], [data-theme="dark"] {
      --primary: <?php echo $hsl_str; ?>;
      --primary-hover: <?php echo $hsl[0] . ", " . $hsl[1] . "%, " . max(0, $hsl[2] - 10) . "%"; ?>;
      --primary-light: <?php echo $hsl[0] . ", " . $hsl[1] . "%, 95%"; ?>;
    }
  </style>
</head>
<body>

  <div class="app-container">
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <a href="#" class="logo-container">
          <div class="logo-icon">M</div>
          <span class="logo-text"><?php echo htmlspecialchars($store_name); ?></span>
        </a>
      </div>
      
      <!-- Portal Switcher -->
      <div class="portal-switcher">
        <button class="portal-btn active" id="portal-btn-erp" data-portal="erp" type="button">
          <i class="fas fa-warehouse"></i>
          <span>بوابة المستودعات ERP</span>
        </button>
        <button class="portal-btn" id="portal-btn-ecommerce" data-portal="ecommerce" type="button">
          <i class="fas fa-globe"></i>
          <span>بوابة المتجر الإلكتروني</span>
        </button>
        <button class="portal-btn" id="portal-btn-pos" data-portal="pos" type="button">
          <i class="fas fa-cash-register"></i>
          <span>بوابة نقاط البيع POS</span>
        </button>
      </div>
      
      <ul class="sidebar-menu-sections">
        
        <!-- ==================== ERP PORTAL SECTIONS ==================== -->
        
        <!-- Section 0: General Dashboard -->
        <li class="menu-section single-link active" data-view="dashboard" data-portal="erp">
          <div class="menu-section-header">
            <i class="fas fa-chart-pie"></i>
            <span>لوحة التحكم العامة</span>
          </div>
        </li>
        
        <!-- Section 1: Products (المنتجات) -->
        <li class="menu-section" data-portal="erp">
          <div class="menu-section-header">
            <i class="fas fa-book"></i>
            <span>المنتجات</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="add-product">
              <a class="submenu-link"><i class="far fa-file-alt" style="margin-inline-end: 8px;"></i>إضافة منتج</a>
            </li>
            <li class="submenu-item" data-view="inventory">
              <a class="submenu-link"><i class="far fa-copy" style="margin-inline-end: 8px;"></i>كافة المنتجات</a>
            </li>
            <li class="submenu-item" data-view="import-products">
              <a class="submenu-link"><i class="fas fa-file-import" style="margin-inline-end: 8px;"></i>استيراد منتجات</a>
            </li>
            <li class="submenu-item" data-view="import-products-update">
              <a class="submenu-link"><i class="fas fa-edit" style="margin-inline-end: 8px;"></i>استيراد (تحديث فقط)</a>
            </li>
            <li class="submenu-item" data-view="opening-stock">
              <a class="submenu-link"><i class="far fa-plus-square" style="margin-inline-end: 8px;"></i>الرصيد الافتتاحي</a>
            </li>
            <li class="submenu-item" data-view="print-labels">
              <a class="submenu-link"><i class="fas fa-barcode" style="margin-inline-end: 8px;"></i>طباعة الملصقات</a>
            </li>
            <li class="submenu-item" data-view="inventory-adjustments">
              <a class="submenu-link"><i class="fas fa-check-double" style="margin-inline-end: 8px;"></i>جرد المخزون</a>
            </li>
            <li class="submenu-item" data-view="categories">
              <a class="submenu-link"><i class="far fa-folder" style="margin-inline-end: 8px;"></i>التصنيفات</a>
            </li>
            <li class="submenu-item" data-view="brand-management">
              <a class="submenu-link"><i class="far fa-bookmark" style="margin-inline-end: 8px;"></i>العلامات التجارية</a>
            </li>
            <li class="submenu-item" data-view="units-management">
              <a class="submenu-link"><i class="fas fa-quote-left" style="margin-inline-end: 8px;"></i>الوحدات</a>
            </li>
            <li class="submenu-item" data-view="batches-management">
              <a class="submenu-link"><i class="fas fa-heartbeat" style="margin-inline-end: 8px;"></i>شحنات/دفعات</a>
            </li>
          </ul>
        </li>

        <!-- Section 2: Adjustment (التسويات) -->
        <li class="menu-section" data-portal="erp">
          <div class="menu-section-header">
            <i class="fas fa-map-marker-alt"></i>
            <span>التسويات</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="create-adjustment">
              <a class="submenu-link"><i class="far fa-file-alt" style="margin-inline-end: 8px;"></i>تسجيل تسوية</a>
            </li>
            <li class="submenu-item" data-view="inventory-adjustments">
              <a class="submenu-link"><i class="far fa-copy" style="margin-inline-end: 8px;"></i>كافة التسويات</a>
            </li>
          </ul>
        </li>

        <!-- Section 2.5: Purchases (المشتريات) -->
        <li class="menu-section" data-portal="erp">
          <div class="menu-section-header">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>المشتريات</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="create-purchase">
              <a class="submenu-link"><i class="far fa-file-alt" style="margin-inline-end: 8px;"></i>سند مشتريات جديد</a>
            </li>
            <li class="submenu-item" data-view="all-purchases">
              <a class="submenu-link"><i class="far fa-copy" style="margin-inline-end: 8px;"></i>كافة المشتريات</a>
            </li>
            <li class="submenu-item" data-view="import-purchases">
              <a class="submenu-link"><i class="far fa-file-alt" style="margin-inline-end: 8px;"></i>استيراد مشتريات</a>
            </li>
            <li class="submenu-item" data-view="suppliers">
              <a class="submenu-link"><i class="fas fa-truck-loading" style="margin-inline-end: 8px;"></i>دليل الموردين</a>
            </li>
            <li class="submenu-item" data-view="warehouse-transfers">
              <a class="submenu-link"><i class="fas fa-exchange-alt" style="margin-inline-end: 8px;"></i>التحويل بين المستودعات</a>
            </li>
          </ul>
        </li>

        <!-- Section 2.7: Sales (المبيعات) -->
        <li class="menu-section" data-portal="erp">
          <div class="menu-section-header">
            <i class="fas fa-shopping-cart"></i>
            <span>المبيعات</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="create-sale">
              <a class="submenu-link"><i class="far fa-file-alt" style="margin-inline-end: 8px;"></i>فاتورة مبيعات جديدة</a>
            </li>
            <li class="submenu-item" data-view="create-quotation">
              <a class="submenu-link"><i class="far fa-file" style="margin-inline-end: 8px;"></i>عرض سعر جديد</a>
            </li>
            <li class="submenu-item" data-view="all-quotations">
              <a class="submenu-link"><i class="far fa-copy" style="margin-inline-end: 8px;"></i>عروض الأسعار</a>
            </li>
            <li class="submenu-item" data-view="all-sales">
              <a class="submenu-link"><i class="fas fa-file-invoice" style="margin-inline-end: 8px;"></i>كافة فواتير المبيعات</a>
            </li>
            <li class="submenu-item" data-view="pos">
              <a class="submenu-link"><i class="fas fa-cash-register" style="margin-inline-end: 8px;"></i>نقطة البيع (POS)</a>
            </li>
            <li class="submenu-item" data-view="customer-screen">
              <a class="submenu-link"><i class="fas fa-desktop" style="margin-inline-end: 8px;"></i>شاشة العميل</a>
            </li>
            <li class="submenu-item" data-view="realtime-counter">
              <a class="submenu-link"><i class="fas fa-stopwatch" style="margin-inline-end: 8px;"></i>عداد المبيعات الفوري</a>
            </li>
          </ul>
        </li>

        <!-- Section 3: Accounting & Finance -->
        <li class="menu-section" data-portal="erp">
          <div class="menu-section-header">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>المحاسبة والمالية</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="accounting-invoices">
              <a class="submenu-link">الفواتير والسندات</a>
            </li>
            <li class="submenu-item" data-view="accounting-finance">
              <a class="submenu-link">الإيرادات والمصاريف</a>
            </li>
            <li class="submenu-item" data-view="accounting-tax">
              <a class="submenu-link">الإقرارات الضريبية VAT</a>
            </li>
          </ul>
        </li>

        <!-- Section 4: Reports -->
        <li class="menu-section" data-portal="erp">
          <div class="menu-section-header">
            <i class="fas fa-chart-bar"></i>
            <span>التقارير</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="reports-sales">
              <a class="submenu-link">تقارير المبيعات</a>
            </li>
            <li class="submenu-item" data-view="reports-stock">
              <a class="submenu-link">حركة المخزون اللحظية</a>
            </li>
            <li class="submenu-item" data-view="reports-branches">
              <a class="submenu-link">أداء الفروع والمبيعات</a>
            </li>
          </ul>
        </li>

        <!-- Section 5: Team Management -->
        <li class="menu-section" data-portal="erp">
          <div class="menu-section-header">
            <i class="fas fa-users"></i>
            <span>إدارة الفريق</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="team-employees">
              <a class="submenu-link">قائمة الموظفين</a>
            </li>
            <li class="submenu-item" data-view="team-permissions">
              <a class="submenu-link">الصلاحيات والأدوار</a>
            </li>
            <li class="submenu-item" data-view="team-audit">
              <a class="submenu-link">سجل الأنشطة والأمان</a>
            </li>
          </ul>
        </li>

        <!-- Section 6: ERP Settings -->
        <li class="menu-section" data-portal="erp">
          <div class="menu-section-header">
            <i class="fas fa-cogs"></i>
            <span>الإعدادات والتهيئة</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="settings-system">
              <a class="submenu-link">إعدادات المتجر العامة</a>
            </li>
            <li class="submenu-item" data-view="settings-warehouses">
              <a class="submenu-link">إعدادات المستودعات</a>
            </li>
          </ul>
        </li>

        <!-- ==================== ECOMMERCE PORTAL SECTIONS ==================== -->

        <!-- Section: eCommerce Dashboard -->
        <li class="menu-section single-link" data-view="ecommerce-dashboard" data-portal="ecommerce">
          <div class="menu-section-header">
            <i class="fas fa-tachometer-alt"></i>
            <span>إحصائيات وتحليلات المتجر</span>
          </div>
        </li>

        <!-- Section: eCommerce Products -->
        <li class="menu-section single-link" data-view="inventory" data-portal="ecommerce">
          <div class="menu-section-header">
            <i class="fas fa-boxes"></i>
            <span>منتجات المتجر</span>
          </div>
        </li>

        <!-- Section: eCommerce Orders -->
        <li class="menu-section" data-portal="ecommerce">
          <div class="menu-section-header">
            <i class="fas fa-shopping-bag"></i>
            <span>الطلبات والشحنات</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="orders">
              <a class="submenu-link"><i class="fas fa-truck" style="margin-inline-end: 8px;"></i>طلبات العملاء</a>
            </li>
            <li class="submenu-item" data-view="sales-return">
              <a class="submenu-link"><i class="fas fa-undo" style="margin-inline-end: 8px;"></i>طلبات الاسترجاع</a>
            </li>
          </ul>
        </li>

        <!-- Section: eCommerce Customers & Reviews -->
        <li class="menu-section" data-portal="ecommerce">
          <div class="menu-section-header">
            <i class="fas fa-users-cog"></i>
            <span>العملاء والتفاعلات</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="ecommerce-customers">
              <a class="submenu-link"><i class="far fa-user" style="margin-inline-end: 8px;"></i>دليل عملاء المتجر</a>
            </li>
            <li class="submenu-item" data-view="ecommerce-reviews">
              <a class="submenu-link"><i class="far fa-star" style="margin-inline-end: 8px;"></i>مراجعات وتقييمات المنتجات</a>
            </li>
          </ul>
        </li>

        <!-- Section: eCommerce Coupons & Marketing -->
        <li class="menu-section" data-portal="ecommerce">
          <div class="menu-section-header">
            <i class="fas fa-bullhorn"></i>
            <span>التسويق والعروض</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="ecommerce-coupons">
              <a class="submenu-link"><i class="fas fa-ticket-alt" style="margin-inline-end: 8px;"></i>إدارة كوبونات الخصم</a>
            </li>
          </ul>
        </li>

        <!-- Section: eCommerce Settings & Integration -->
        <li class="menu-section" data-portal="ecommerce">
          <div class="menu-section-header">
            <i class="fas fa-sliders-h"></i>
            <span>إعدادات المتجر والقوالب</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="ecommerce-integration">
              <a class="submenu-link"><i class="fab fa-wordpress" style="margin-inline-end: 8px;"></i>ربط WooCommerce</a>
            </li>
            <li class="submenu-item" data-view="settings-payment-shipping">
              <a class="submenu-link"><i class="fas fa-credit-card" style="margin-inline-end: 8px;"></i>بوابات الدفع والشحن</a>
            </li>
            <li class="submenu-item" data-view="settings-themes">
              <a class="submenu-link"><i class="fas fa-palette" style="margin-inline-end: 8px;"></i>مكتبة الثيمات والقوالب</a>
            </li>
          </ul>
        </li>

        <!-- Section: eCommerce Apps Marketplace -->
        <li class="menu-section" data-portal="ecommerce">
          <div class="menu-section-header">
            <i class="fas fa-cubes"></i>
            <span>التطبيقات والملحقات</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="apps-marketplace">
              <a class="submenu-link">سوق التطبيقات والملحقات</a>
            </li>
            <li class="submenu-item" data-view="apps-installed">
              <a class="submenu-link">التطبيقات المثبتة والنشطة</a>
            </li>
          </ul>
        </li>

        <!-- ==================== POS PORTAL SECTIONS ==================== -->
        
        <!-- Section: POS terminal -->
        <li class="menu-section single-link" data-view="pos" data-portal="pos">
          <div class="menu-section-header">
            <i class="fas fa-cash-register"></i>
            <span>شاشة البيع السريع (POS)</span>
          </div>
        </li>

        <!-- Section: POS Sales & Returns -->
        <li class="menu-section" data-portal="pos">
          <div class="menu-section-header">
            <i class="fas fa-file-invoice-dollar"></i>
            <span>العمليات والفواتير</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="all-sales">
              <a class="submenu-link"><i class="fas fa-receipt" style="margin-inline-end: 8px;"></i>فواتير المبيعات المباشرة</a>
            </li>
            <li class="submenu-item" data-view="sales-return">
              <a class="submenu-link"><i class="fas fa-undo" style="margin-inline-end: 8px;"></i>مرتجع المبيعات</a>
            </li>
          </ul>
        </li>

        <!-- Section: POS Inventory & Customers -->
        <li class="menu-section" data-portal="pos">
          <div class="menu-section-header">
            <i class="fas fa-boxes"></i>
            <span>المخزون والعملاء</span>
            <i class="fas fa-chevron-down toggle-icon"></i>
          </div>
          <ul class="submenu">
            <li class="submenu-item" data-view="inventory">
              <a class="submenu-link"><i class="fas fa-list-ul" style="margin-inline-end: 8px;"></i>كافة المنتجات المتاحة</a>
            </li>
            <li class="submenu-item" data-view="ecommerce-customers">
              <a class="submenu-link"><i class="fas fa-user-friends" style="margin-inline-end: 8px;"></i>دليل عملاء البيع</a>
            </li>
            <li class="submenu-item" data-view="customer-screen">
              <a class="submenu-link"><i class="fas fa-desktop" style="margin-inline-end: 8px;"></i>فتح شاشة العميل</a>
            </li>
          </ul>
        </li>
      </ul>
      
      <div class="sidebar-footer" style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
        <div class="user-profile-summary" style="flex-grow: 1;">
          <div class="user-avatar">مد</div>
          <div class="user-info">
            <span class="user-name">مدير النظام</span>
            <span class="user-role">الوصول الكامل</span>
          </div>
        </div>
        <button class="header-action-btn btn-logout-action" title="تسجيل الخروج" style="color: hsla(var(--danger), 1); width: 32px; height: 32px; border-radius: var(--border-radius-full); display: flex; align-items: center; justify-content: center; background: none; border: none; cursor: pointer;">
          <i class="fas fa-sign-out-alt"></i>
        </button>
      </div>
    </aside>
    
    <!-- Main Content Wrapper -->
    <div class="main-wrapper">
      
      <!-- Main Top Header -->
      <header class="main-header">
        <div class="header-left">
          <button class="menu-toggle-btn" id="btn-menu-toggle">
            <i class="fas fa-bars"></i>
          </button>
          <div class="page-title-area">
            <h1 class="page-title" id="active-page-title">لوحة التحكم</h1>
            <p class="page-subtitle" id="active-page-subtitle">نظرة عامة على مبيعات المتجر ومخازن المستودعات</p>
          </div>
        </div>
        
        <div class="header-right">
          <!-- Search Bar -->
          <div class="header-search">
            <i class="fas fa-search"></i>
            <input type="text" id="global-search-input" placeholder="بحث في النظام...">
          </div>
          
          <!-- Theme Switcher Button -->
          <button class="header-action-btn theme-toggle-btn" id="btn-theme-toggle" title="تبديل المظهر">
            <i class="fas fa-sun"></i>
          </button>
          
          <!-- Notifications (Mock Icon) -->
          <button class="header-action-btn" title="الإشعارات">
            <i class="fas fa-bell"></i>
            <span class="btn-badge">3</span>
          </button>
        </div>
      </header>
      
      <!-- Main Content Dynamic Container -->
      <main class="main-content">
        
        <!-- SECTION 1: DASHBOARD -->
        <section id="dashboard-section" class="view-section active">
          <!-- Low Stock Alert Banner Placeholder -->
          <div id="low-stock-alerts-container"></div>
          
          <!-- KPI Statistics Cards -->
          <div class="stats-grid">
            <div class="stat-card">
              <div class="stat-info">
                <span class="stat-title">إجمالي المبيعات</span>
                <span class="stat-value" id="kpi-sales">$0</span>
                <span class="stat-trend trend-up">
                  <i class="fas fa-arrow-up"></i> 12.5%
                </span>
              </div>
              <div class="stat-icon success">
                <i class="fas fa-wallet"></i>
              </div>
            </div>
            
            <div class="stat-card">
              <div class="stat-info">
                <span class="stat-title">قطع المستودع</span>
                <span class="stat-value" id="kpi-inventory">0</span>
                <span class="stat-trend trend-up">
                  <i class="fas fa-arrow-up"></i> 4.2%
                </span>
              </div>
              <div class="stat-icon primary">
                <i class="fas fa-boxes"></i>
              </div>
            </div>
            
            <div class="stat-card">
              <div class="stat-info">
                <span class="stat-title">إجمالي الطلبات</span>
                <span class="stat-value" id="kpi-orders">0</span>
                <span class="stat-trend trend-up">
                  <i class="fas fa-arrow-up"></i> 8.1%
                </span>
              </div>
              <div class="stat-icon info">
                <i class="fas fa-shopping-cart"></i>
              </div>
            </div>
            
            <div class="stat-card">
              <div class="stat-info">
                <span class="stat-title">مخزون منخفض/نفد</span>
                <span class="stat-value" id="kpi-low-stock" style="color: hsla(var(--warning), 1);">0</span>
                <span class="stat-trend trend-down">
                  تنبيه عاجل
                </span>
              </div>
              <div class="stat-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
              </div>
            </div>
          </div>
          
          <!-- Charts and Timelines Grid -->
          <div class="dashboard-grid">
            <!-- Sales Line Chart -->
            <div class="dashboard-card">
              <div class="card-header">
                <h3 class="card-title">
                  <i class="fas fa-chart-line" style="color: hsla(var(--primary), 1);"></i>
                  مؤشر حركة المبيعات الأسبوعية
                </h3>
              </div>
              <div class="card-body" style="position: relative; height: 320px;">
                <canvas id="salesChart"></canvas>
              </div>
            </div>
            
            <!-- Category Pie/Doughnut Chart -->
            <div class="dashboard-card">
              <div class="card-header">
                <h3 class="card-title">
                  <i class="fas fa-chart-pie" style="color: #06b6d4;"></i>
                  توزيع المخزون بحسب الفئة
                </h3>
              </div>
              <div class="card-body" style="position: relative; height: 320px;">
                <canvas id="categoryChart"></canvas>
              </div>
            </div>
          </div>
          
          <!-- Recent Orders and Activities list -->
          <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
            <!-- Recent Activities -->
            <div class="dashboard-card">
              <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                <h3 class="card-title" style="margin: 0;">
                  <i class="fas fa-history" style="color: hsla(var(--primary), 1);"></i>
                  سجل الأنشطة والمخزون الأخير
                </h3>
                <a href="#" id="btn-clear-activities" style="font-size: 12px; color: hsla(var(--danger), 1); text-decoration: none; font-weight: 600; font-family: var(--font-arabic);"><i class="fas fa-trash-alt" style="margin-inline-end: 4px;"></i>مسح السجل</a>
              </div>
              <div class="card-body">
                <div class="activity-list" id="recent-activities-list">
                  <!-- Dynamic logs injected here -->
                </div>
              </div>
            </div>
            
            <!-- Quick System Notes -->
            <div class="dashboard-card">
              <div class="card-header">
                <h3 class="card-title">
                  <i class="fas fa-info-circle" style="color: hsla(var(--info), 1);"></i>
                  إحصائيات سريعة للعمليات
                </h3>
              </div>
              <div class="card-body" style="display: flex; flex-direction: column; justify-content: center; gap: 20px;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background-color: var(--bg-tertiary); border-radius: var(--border-radius-sm);">
                  <span style="font-weight: 600;">نسبة المنتجات المتوفرة بالمخازن</span>
                  <span class="badge badge-success" id="quick-stats-stock-percent" style="font-size: 13px;">0% متوفر</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background-color: var(--bg-tertiary); border-radius: var(--border-radius-sm);">
                  <span style="font-weight: 600;">متوسط تكلفة البضائع المتاحة</span>
                  <span id="quick-stats-avg-cost" style="font-family: var(--font-english); font-weight: 700;">0.00 ل.س</span>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; background-color: var(--bg-tertiary); border-radius: var(--border-radius-sm);">
                  <span style="font-weight: 600;">الطلبات قيد المراجعة الفورية</span>
                  <span class="badge badge-warning" id="quick-stats-pending-orders" style="font-size: 13px;">0 قيد الانتظار</span>
                </div>
              </div>
            </div>
          </div>
        </section>
        
        <!-- SECTION 2: WAREHOUSE / INVENTORY -->
        <section id="inventory-section" class="view-section">
          <div class="toolbar">
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input type="text" class="form-control" id="inventory-search" placeholder="بحث بالاسم أو الكود (SKU)...">
            </div>
            
            <div class="filters-group">
              <select class="filter-select" id="filter-category">
                <option value="all">كل الفئات</option>
                <!-- Dynamic categories loaded in js -->
              </select>
              <select class="filter-select" id="filter-stock">
                <option value="all">حالة المخزون</option>
                <option value="instock">متوفر</option>
                <option value="lowstock">منخفض المخزون</option>
                <option value="outofstock">نفد من المخزون</option>
              </select>
              
              <button class="btn btn-primary" id="btn-add-product">
                <i class="fas fa-plus"></i>
                <span>إضافة منتج</span>
              </button>
            </div>
          </div>
          
          <div class="dashboard-card">
            <div class="table-container">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>المنتج والكود</th>
                    <th>الفئة</th>
                    <th>سعر البيع</th>
                    <th>التكلفة</th>
                    <th>الكمية المتاحة</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                  </tr>
                </thead>
                <tbody id="inventory-table-body">
                  <!-- Dynamic product rows -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION: PRODUCT CATEGORIES MANAGEMENT -->
        <section id="categories-section" class="view-section">
          <div class="toolbar" style="margin-bottom: 20px;">
            <div class="page-title-area">
              <h3 style="font-weight: 700; color: var(--text-primary); font-size: 20px;">تصنيفات المنتجات</h3>
              <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">إدارة الفئات والأقسام لتنظيم المنتجات في المخزون وقنوات البيع</p>
            </div>
          </div>
          
          <div class="categories-layout" style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
            <!-- Right Column: Add Category Form -->
            <div class="dashboard-card" style="padding: 24px;">
              <h4 style="font-weight: 700; margin-bottom: 16px; font-size: 15px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">إضافة تصنيف جديد</h4>
              <form id="add-category-form">
                <div class="form-group">
                  <label class="form-label">اسم التصنيف <span style="color: hsla(var(--danger), 1);">*</span></label>
                  <input type="text" class="form-control" id="cat-name-input" placeholder="مثال: إلكترونيات، ملابس، حقائب..." required autocomplete="off">
                </div>
                <div class="form-group">
                  <label class="form-label">الاسم اللطيف بالرابط (Slug)</label>
                  <input type="text" class="form-control" id="cat-slug-input" placeholder="مثال: electronics (اختياري)" autocomplete="off">
                </div>
                <div class="form-group">
                  <label class="form-label">الوصف</label>
                  <textarea class="form-control" id="cat-desc-input" style="height: 100px; resize: vertical;" placeholder="اكتب وصفاً موجزاً للتصنيف يظهر للعملاء..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 700; padding: 12px 20px;">
                  <i class="fas fa-plus"></i> إضافة تصنيف جديد
                </button>
              </form>
            </div>
            
            <!-- Left Column: Categories List Table -->
            <div class="dashboard-card">
              <div class="card-header">
                <h4 class="card-title"><i class="fas fa-list" style="color: hsla(var(--primary), 1);"></i> جميع التصنيفات المسجلة</h4>
              </div>
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>اسم التصنيف</th>
                      <th>الاسم اللطيف (Slug)</th>
                      <th>الوصف</th>
                      <th style="text-align: center;">عدد المنتجات</th>
                      <th>إجراءات</th>
                    </tr>
                  </thead>
                  <tbody id="categories-table-body">
                    <!-- Dynamic categories loaded in js -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 3: ORDERS -->
        <section id="orders-section" class="view-section">
          <div class="toolbar">
            <div class="search-box">
              <i class="fas fa-search"></i>
              <input type="text" class="form-control" id="orders-search" placeholder="بحث برقم الطلب أو اسم العميل...">
            </div>
            
            <div class="filters-group">
              <select class="filter-select" id="filter-order-status">
                <option value="all">كل حالات الطلبات</option>
                <option value="Pending">قيد الانتظار</option>
                <option value="Shipped">تم الشحن</option>
                <option value="Delivered">تم التوصيل</option>
                <option value="Cancelled">ملغي</option>
              </select>
            </div>
          </div>
          
          <div class="dashboard-card">
            <div class="table-container">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>رقم الطلب</th>
                    <th>العميل والتاريخ</th>
                    <th>قيمة الطلب</th>
                    <th style="text-align: center;">الكمية الإجمالية</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                  </tr>
                </thead>
                <tbody id="orders-table-body">
                  <!-- Dynamic order rows -->
                </tbody>
              </table>
            </div>
          </div>
        </section>
        
        <!-- SECTION 4: SUPPLIERS -->
        <section id="suppliers-section" class="view-section">
          <div class="toolbar">
            <h3 style="font-weight: 700; color: var(--text-primary);">دليل الموردين النشطين</h3>
            <button class="btn btn-primary" id="btn-add-supplier">
              <i class="fas fa-plus"></i>
              <span>إضافة مورد</span>
            </button>
          </div>
          
          <div class="dashboard-card">
            <div class="table-container">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>اسم المورد</th>
                    <th>المسؤول والمنتجات</th>
                    <th>رقم الهاتف</th>
                    <th>البريد الإلكتروني</th>
                    <th>العنوان</th>
                    <th>توريد شحنة</th>
                  </tr>
                </thead>
                <tbody id="suppliers-table-body">
                  <!-- Dynamic supplier rows -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION 6: POS TERMINAL -->
        <section id="pos-section" class="view-section">
          <div class="pos-container">
            <!-- Left Column: Catalog (65%) -->
            <div class="pos-catalog-col">
              <!-- Search box and category filter navbar -->
              <div style="display: flex; gap: 16px; margin-bottom: 12px; flex-wrap: wrap;">
                <div class="search-box" style="flex-grow: 1; min-width: 250px;">
                  <i class="fas fa-search"></i>
                  <input type="text" class="form-control" id="pos-search-input" placeholder="بحث عن منتج بالاسم أو كود SKU...">
                </div>
              </div>
              <div class="pos-categories-nav" id="pos-categories-nav">
                <!-- Categories buttons are dynamically populated -->
              </div>
              
              <!-- Catalog Grid -->
              <div class="pos-products-grid" id="pos-products-grid">
                <!-- Dynamic product cards will load here -->
              </div>
            </div>
            
            <!-- Right Column: Cart (35%) -->
            <div class="pos-cart-col">
              <div class="pos-cart-header">
                <h3 style="font-weight: 700; font-size: 16px; display: flex; align-items: center; gap: 8px;">
                  <i class="fas fa-shopping-basket" style="color: hsla(var(--primary), 1);"></i>
                  سلة المشتريات
                </h3>
                <button class="btn btn-secondary btn-sm" id="btn-pos-clear-cart" type="button" style="padding: 4px 8px; font-size: 11px;">تفريغ</button>
              </div>
              
              <!-- Cart items wrapper -->
              <div class="pos-cart-items" id="pos-cart-items-list">
                <!-- Dynamic cart list items -->
              </div>
              
              <!-- Summary Pricing Details -->
              <div class="pos-cart-summary">
                <div class="pos-summary-row">
                  <span>المجموع الفرعي</span>
                  <strong id="pos-subtotal" style="font-family: var(--font-english);">$0.00</strong>
                </div>
                <div class="pos-summary-row">
                  <span>ضريبة القيمة المضافة (15%)</span>
                  <strong id="pos-tax" style="font-family: var(--font-english);">$0.00</strong>
                </div>
                <div class="pos-summary-row total">
                  <span>الإجمالي الكلي</span>
                  <strong id="pos-total" style="font-family: var(--font-english);">$0.00</strong>
                </div>
              </div>
              
              <!-- Checkout Buttons -->
              <div class="pos-cart-actions">
                <button class="btn btn-primary" id="btn-pos-checkout" type="button" style="flex-grow: 1; font-weight: 700; padding: 12px 20px;">
                  <i class="fas fa-check-circle"></i>
                  دفع وتأكيد البيع
                </button>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 5: ADD / EDIT PRODUCT (WOOCOMMERCE STYLE) -->
        <section id="add-product-section" class="view-section">
          <!-- Navigation header with Back link -->
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 14px; color: var(--text-muted);">المنتجات</span>
              <i class="fas fa-chevron-left" style="font-size: 11px; color: var(--text-muted);"></i>
              <span style="font-weight: 700; color: var(--text-primary);" id="woo-editor-breadcrumb">إضافة منتج جديد</span>
            </div>
            <button class="btn btn-secondary btn-sm" id="btn-woo-cancel" type="button">
              <i class="fas fa-arrow-right"></i> إلغاء والعودة
            </button>
          </div>

          <form id="woo-product-form">
            <div class="woo-editor-container">
              
              <!-- Main Column (70%) -->
              <div class="woo-main-col">
                <!-- Product Title Input -->
                <input type="text" class="woo-title-input" id="woo-prod-name" placeholder="أدخل اسم المنتج هنا..." required autocomplete="off">
                
                <!-- Product Description Box -->
                <div class="woo-postbox">
                  <div class="woo-postbox-header">
                    <span>الوصف الكامل للمنتج</span>
                    <span style="font-size: 12px; color: var(--text-muted);"><i class="fas fa-keyboard"></i> المحرر الأساسي</span>
                  </div>
                  <div class="woo-postbox-body">
                    <textarea class="woo-editor-textarea" id="woo-prod-desc" placeholder="اكتب تفاصيل المنتج ومميزاته الفنية هنا..."></textarea>
                  </div>
                </div>

                <!-- Product Data Metabox (Tabbed Layout) -->
                <div class="woo-postbox">
                  <div class="woo-postbox-header">
                    <span>بيانات المنتج</span>
                  </div>
                  <div class="woo-product-data">
                    <!-- Tabs Navigation -->
                    <div class="woo-tabs-nav">
                      <button type="button" class="woo-tab-btn active" data-target="panel-general">
                        <i class="fas fa-cog"></i><span>عام</span>
                      </button>
                      <button type="button" class="woo-tab-btn" data-target="panel-inventory">
                        <i class="fas fa-boxes"></i><span>المخزون</span>
                      </button>
                      <button type="button" class="woo-tab-btn" data-target="panel-shipping">
                        <i class="fas fa-shipping-fast"></i><span>الشحن</span>
                      </button>
                      <button type="button" class="woo-tab-btn" data-target="panel-seo">
                        <i class="fas fa-globe"></i><span>المتجر والتسويق (SEO)</span>
                      </button>
                    </div>

                    <!-- Tabs Panels -->
                    <div class="woo-panels">
                      <!-- Panel: General (عام) -->
                      <div class="woo-panel active" id="panel-general">
                        <div class="form-row">
                          <div class="form-group">
                            <label class="form-label">السعر الافتراضي</label>
                            <input type="number" class="form-control" id="woo-prod-price" required min="0" step="0.01" placeholder="0.00">
                          </div>
                          <div class="form-group">
                            <label class="form-label">سعر التخفيض</label>
                            <input type="number" class="form-control" id="woo-prod-sale-price" min="0" step="0.01" placeholder="سعر العرض (اختياري)">
                          </div>
                        </div>
                        <div class="form-group">
                          <label class="form-label">الفئة الأساسية للمنتج</label>
                          <input type="text" class="form-control" id="woo-prod-category" required placeholder="مثال: إلكترونيات، ملابس">
                        </div>
                      </div>

                      <!-- Panel: Inventory (المخزون) -->
                      <div class="woo-panel" id="panel-inventory">
                        <div class="form-row">
                          <div class="form-group">
                            <label class="form-label">رمز SKU للمنتج</label>
                            <input type="text" class="form-control" id="woo-prod-sku" required placeholder="NS-CODE">
                          </div>
                          <div class="form-group">
                            <label class="form-label">الحد الأدنى للتنبيه (مخزون منخفض)</label>
                            <input type="number" class="form-control" id="woo-prod-min-stock" required min="1" value="5">
                          </div>
                        </div>
                        <div class="form-row">
                          <div class="form-group">
                            <label class="form-label">كمية المخزون الحالية</label>
                            <input type="number" class="form-control" id="woo-prod-stock" readonly style="background-color: var(--bg-tertiary); cursor: not-allowed;" required min="0" value="0">
                            <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">(يتم تحديث الرصيد تلقائياً عبر فواتير الشراء والتسويات)</span>
                          </div>
                          <div class="form-group">
                            <label class="form-label">حالة المخزون</label>
                            <select class="form-control" id="woo-prod-stock-status" disabled style="background-color: var(--bg-tertiary); cursor: not-allowed;">
                              <option value="instock">متوفر بالمخازن</option>
                              <option value="outofstock">غير متوفر (نفد)</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <!-- Panel: Shipping (الشحن) -->
                      <div class="woo-panel" id="panel-shipping">
                        <div class="form-row">
                          <div class="form-group">
                            <label class="form-label">الوزن (كجم)</label>
                            <input type="number" class="form-control" id="woo-prod-weight" min="0" step="0.1" placeholder="0.0">
                          </div>
                          <div class="form-group">
                            <label class="form-label">فئة الشحن</label>
                            <select class="form-control" id="woo-prod-shipping-class">
                              <option value="standard">شحن قياسي سريع</option>
                              <option value="heavy">بضائع ثقيلة وزنًا</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <!-- Panel: SEO & eCommerce Marketing (المتجر والتسويق SEO) -->
                      <div class="woo-panel" id="panel-seo">
                        <!-- Disclaimer Notice / Alert Banner -->
                        <div style="background-color: hsla(210, 100%, 96%, 1); border: 1px solid hsla(210, 100%, 80%, 1); border-radius: var(--border-radius-sm); padding: 16px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px;">
                          <i class="fas fa-info-circle" style="color: hsla(210, 100%, 40%, 1); font-size: 18px; margin-top: 2px;"></i>
                          <div>
                            <h5 style="margin: 0 0 6px 0; font-weight: 700; color: hsla(210, 100%, 25%, 1); font-size: 14px; font-family: var(--font-arabic);">لاحقاً من أجل المتجر الإلكتروني</h5>
                            <p style="margin: 0; font-size: 12.5px; color: hsla(210, 100%, 30%, 1); line-height: 1.6; font-family: var(--font-arabic);">هذه الحقول اختيارية تماماً وغير إجبارية. تُستخدم لتهيئة متجرك للظهور بشكل ممتاز في محركات البحث (Google)، وتوافق منتجاتك مع Google Shopping، والعرض المنسق لبيانات المنتج وتفرده عند مشاركتها على وسائل التواصل الاجتماعي المختلفة.</p>
                          </div>
                        </div>

                        <h4 style="font-weight: 700; font-size: 14px; color: var(--text-primary); margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; font-family: var(--font-arabic);">تحسين محركات البحث (SEO Meta Tags)</h4>
                        <div class="form-group" style="margin-bottom: 16px;">
                          <label class="form-label" style="font-family: var(--font-arabic);">عنوان الميتا المخصص (Meta Title)</label>
                          <input type="text" class="form-control" id="woo-prod-seo-title" placeholder="اتركه فارغاً للاعتماد على الاسم التلقائي للمنتج">
                          <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; font-family: var(--font-arabic);">العنوان التعريفي للرابط في صفحات نتائج محرك البحث (يُفضل ألا يتجاوز 60 حرفاً).</span>
                        </div>
                        <div class="form-group" style="margin-bottom: 16px;">
                          <label class="form-label" style="font-family: var(--font-arabic);">وصف الميتا المخصص (Meta Description)</label>
                          <textarea class="form-control" id="woo-prod-seo-desc" style="height: 80px; resize: vertical;" placeholder="اتركه فارغاً للاعتماد على مقتطف الوصف القصير"></textarea>
                          <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; font-family: var(--font-arabic);">موجز مبسط يصف محتوى السلعة ويجذب الزوار (يُفضل ألا يتجاوز 160 حرفاً).</span>
                        </div>
                        <div class="form-group" style="margin-bottom: 24px;">
                          <label class="form-label" style="font-family: var(--font-arabic);">الكلمات الدلالية المفتاحية (Meta Keywords)</label>
                          <input type="text" class="form-control" id="woo-prod-seo-keywords" placeholder="مثال: ملابس، أزياء، فستان، صيفي">
                          <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; font-family: var(--font-arabic);">كلمات مفتاحية تساعد خوارزميات الفهرسة، تفصل بينها بفاصلة (،).</span>
                        </div>

                        <h4 style="font-weight: 700; font-size: 14px; color: var(--text-primary); margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; font-family: var(--font-arabic);">شبكات التواصل الاجتماعي والمشاركة (Open Graph / Social)</h4>
                        <div class="form-row" style="margin-bottom: 16px;">
                          <div class="form-group">
                            <label class="form-label" style="font-family: var(--font-arabic);">عنوان المشاركة الاجتماعي (OG Title)</label>
                            <input type="text" class="form-control" id="woo-prod-og-title" placeholder="العنوان للمشاركة على منصات السوشيال ميديا">
                          </div>
                          <div class="form-group">
                            <label class="form-label" style="font-family: var(--font-arabic);">وصف المشاركة الاجتماعي (OG Description)</label>
                            <input type="text" class="form-control" id="woo-prod-og-desc" placeholder="الوصف الذي يظهر عند إرسال الرابط">
                          </div>
                        </div>
                        <div class="form-group" style="margin-bottom: 24px;">
                          <label class="form-label" style="font-family: var(--font-arabic);">رابط صورة المشاركة الخاصة (OG Image URL)</label>
                          <input type="text" class="form-control" id="woo-prod-og-image" placeholder="رابط صورة مخصصة تظهر عند مشاركة المنتج (اختياري)">
                        </div>

                        <h4 style="font-weight: 700; font-size: 14px; color: var(--text-primary); margin-bottom: 16px; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; font-family: var(--font-arabic);">جوجل للتسوق والمواصفات المعيارية (Google Shopping & Identifiers)</h4>
                        <div class="form-group" style="margin-bottom: 16px;">
                          <label class="form-label" style="font-family: var(--font-arabic);">تصنيف المنتج في دليل جوجل للتسوق (Google Product Category)</label>
                          <input type="text" class="form-control" id="woo-prod-google-category" placeholder="مثال: Apparel & Accessories > Clothing > Dresses">
                          <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block; font-family: var(--font-arabic);">الفئة الرسمية من تصنيفات قوقل لتنظيم وعرض السلع في منصة Google Shopping.</span>
                        </div>
                        <div class="form-row">
                          <div class="form-group">
                            <label class="form-label" style="font-family: var(--font-arabic);">رقم التجارة العالمي للمنتج GTIN (Barcode/EAN/UPC)</label>
                            <input type="text" class="form-control" id="woo-prod-gtin" placeholder="رقم كود الباركود الدولي للمنتج">
                          </div>
                          <div class="form-group">
                            <label class="form-label" style="font-family: var(--font-arabic);">رقم القطعة لدى المصنع MPN (Part Number)</label>
                            <input type="text" class="form-control" id="woo-prod-mpn" placeholder="الرقم التعريفي للمصنع">
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Product Short Description -->
                <div class="woo-postbox">
                  <div class="woo-postbox-header">
                    <span>مقتطف وصف قصير للمنتج</span>
                  </div>
                  <div class="woo-postbox-body">
                    <textarea class="form-control" id="woo-prod-short-desc" style="height: 100px; resize: vertical;" placeholder="أدخل ملخصًا موجزًا يظهر للعملاء بجوار صورة المنتج..."></textarea>
                  </div>
                </div>
              </div>

              <!-- Sidebar Column (30%) -->
              <div class="woo-side-col">
                <!-- Publish Box -->
                <div class="woo-postbox">
                  <div class="woo-postbox-header">
                    <span>نشر المنتج</span>
                  </div>
                  <div class="woo-postbox-body">
                    <div class="woo-publish-row">
                      <i class="fas fa-map-marker-alt"></i>
                      <span>الحالة: <strong id="woo-status-label">مسودة</strong></span>
                    </div>
                    <div class="woo-publish-row">
                      <i class="fas fa-eye"></i>
                      <span>الظهور: <strong>علني للمتجر</strong></span>
                    </div>
                    <div class="woo-publish-row">
                      <i class="fas fa-calendar-alt"></i>
                      <span>النشر: <strong>فوري</strong></span>
                    </div>
                    
                    <div class="woo-publish-actions">
                      <button type="button" class="btn btn-secondary btn-sm" id="btn-woo-save-draft">حفظ مسودة</button>
                      <button type="submit" class="btn btn-primary btn-sm" id="btn-woo-publish">نشر المنتج</button>
                    </div>
                  </div>
                </div>

                <!-- Categories Checkbox widget -->
                <div class="woo-postbox">
                  <div class="woo-postbox-header">
                    <span>تصنيفات المنتجات</span>
                  </div>
                  <div class="woo-postbox-body">
                    <div class="woo-categories-checklist" id="woo-categories-list">
                      <!-- Dynamic list of categories checkboxes will be loaded in JS -->
                    </div>
                    <div style="display: flex; gap: 6px; margin-top: 8px;">
                      <input type="text" class="form-control" id="woo-new-category-input" style="padding: 6px 10px; font-size: 12px;" placeholder="فئة جديدة...">
                      <button type="button" class="btn btn-secondary btn-sm" id="btn-woo-add-category" style="padding: 6px 12px;"><i class="fas fa-plus"></i></button>
                    </div>
                  </div>
                </div>

                <!-- Sales Channels Checkbox widget -->
                <div class="woo-postbox">
                  <div class="woo-postbox-header">
                    <span>قنوات البيع والنشاط</span>
                  </div>
                  <div class="woo-postbox-body">
                    <div style="display: flex; flex-direction: column; gap: 10px; padding: 4px;">
                      <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; cursor: pointer; color: var(--text-primary);">
                        <input type="checkbox" id="woo-channel-ecommerce" value="ecommerce" checked style="width: 15px; height: 15px; accent-color: hsla(var(--primary), 1);">
                        <span>المتجر الإلكتروني العام</span>
                      </label>
                      <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; cursor: pointer; color: var(--text-primary);">
                        <input type="checkbox" id="woo-channel-pos" value="pos" checked style="width: 15px; height: 15px; accent-color: hsla(var(--primary), 1);">
                        <span>نقاط البيع الفورية (POS)</span>
                      </label>
                      <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; cursor: pointer; color: var(--text-primary);">
                        <input type="checkbox" id="woo-channel-wholesale" value="wholesale" style="width: 15px; height: 15px; accent-color: hsla(var(--primary), 1);">
                        <span>البيع بالجملة / B2B</span>
                      </label>
                      <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; cursor: pointer; color: var(--text-primary);">
                        <input type="checkbox" id="woo-channel-social" value="social" style="width: 15px; height: 15px; accent-color: hsla(var(--primary), 1);">
                        <span>منصات التواصل الاجتماعي</span>
                      </label>
                    </div>
                  </div>
                </div>

                <!-- Product Image Widget -->
                <div class="woo-postbox">
                  <div class="woo-postbox-header">
                    <span>صورة المنتج بارزة</span>
                  </div>
                  <div class="woo-postbox-body">
                    <div class="woo-image-selector" id="woo-image-picker-box">
                      <i class="fas fa-image"></i>
                      <span style="font-size: 12px; font-weight: 600;">تعيين صورة المنتج</span>
                      <img class="woo-image-preview" id="woo-image-preview-img" alt="معاينة">
                    </div>
                  </div>
                </div>
              </div>
              
            </div>
          </form>
        </section>

        <!-- SECTION 6.5: CREATE PURCHASE -->
        <section id="create-purchase-section" class="view-section">
          <!-- Navigation header / Breadcrumbs -->
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
              <h2 style="font-weight: 800; color: var(--text-primary); margin: 0; font-size: 24px; font-family: var(--font-arabic);">إنشاء سند مشتريات جديد</h2>
              <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); font-family: var(--font-arabic);">
                <span>كافة المشتريات</span>
                <span>/</span>
                <span style="color: hsla(260, 60%, 50%, 1); font-weight: 600;">إنشاء سند مشتريات</span>
              </div>
            </div>
            <button class="btn btn-secondary btn-sm" id="btn-pur-cancel" type="button" style="font-family: var(--font-arabic);">
              <i class="fas fa-arrow-right"></i> إلغاء والعودة
            </button>
          </div>

          <form id="pur-purchase-form">
            <!-- Row 1: Date, Supplier, Warehouse -->
            <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 24px;">
              <div class="form-group">
                <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">التاريخ *</label>
                <input type="date" class="form-control" id="pur-date" required style="width: 100%; height: 42px; border-radius: var(--border-radius-xs);">
              </div>
              <div class="form-group">
                <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">المورد المعتمد *</label>
                <div style="display: flex; gap: 8px; align-items: center;">
                  <select class="form-control" id="pur-supplier-select" required style="flex-grow: 1; height: 42px; border-radius: var(--border-radius-xs); font-family: var(--font-arabic);">
                    <option value="" disabled selected>اختر المورد...</option>
                  </select>
                  <button class="btn btn-primary" type="button" id="btn-pur-add-supplier" style="background-color: hsla(260, 60%, 50%, 1); border: none; height: 42px; padding: 0 16px; border-radius: var(--border-radius-xs); color: #fff; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">المستودع المستلم *</label>
                <select class="form-control" id="pur-warehouse-select" required style="width: 100%; height: 42px; border-radius: var(--border-radius-xs); font-family: var(--font-arabic);">
                  <option value="" disabled selected>اختر المستودع...</option>
                </select>
              </div>
            </div>

            <!-- Row 2: Product Name Autocomplete Search Bar -->
            <div class="form-group" style="position: relative; margin-bottom: 24px;">
              <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">اسم أو رمز المنتج</label>
              <div style="position: relative; display: flex; align-items: center;">
                <span style="position: absolute; left: 16px; color: var(--text-muted); display: flex; align-items: center; gap: 12px; pointer-events: none;">
                  <i class="fas fa-barcode" style="font-size: 18px; border-inline-end: 1px solid var(--border-color); padding-inline-end: 12px;"></i>
                  <i class="fas fa-search"></i>
                </span>
                <input type="text" class="form-control" id="pur-product-search" placeholder="ابحث عن المنتج بالاسم أو رمز SKU أو امسح الباركود..." style="padding-inline-start: 60px; width: 100%; height: 48px; border-radius: var(--border-radius-sm); font-family: var(--font-arabic);">
              </div>
              <!-- Autocomplete Suggestion Box -->
              <div id="pur-search-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); max-height: 280px; overflow-y: auto; z-index: 1000; box-shadow: var(--shadow-lg); margin-top: 6px; padding: 6px 0;">
                <!-- Suggestions items -->
              </div>
            </div>

            <!-- Row 3: Order Items Table -->
            <div class="form-group" style="margin-bottom: 24px;">
              <label class="form-label" style="font-weight: 700; color: var(--text-primary); margin-bottom: 12px; display: block; font-family: var(--font-arabic);">عناصر السند والمنتجات الواردة *</label>
              <div class="table-container" style="border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); overflow: hidden; background: var(--bg-secondary);">
                <table class="custom-table" style="width: 100%; border-collapse: collapse;">
                  <thead style="background: var(--bg-tertiary);">
                    <tr>
                      <th style="width: 50px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">#</th>
                      <th style="color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">اسم المنتج</th>
                      <th style="width: 140px; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">تكلفة الوحدة الصافية</th>
                      <th style="width: 120px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">المخزون الحالي</th>
                      <th style="width: 100px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">الكمية</th>
                      <th style="width: 100px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">الخصم</th>
                      <th style="width: 100px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">الضريبة %</th>
                      <th style="width: 140px; text-align: right; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">المجموع الفرعي</th>
                      <th style="width: 65px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">إجراء</th>
                    </tr>
                  </thead>
                  <tbody id="pur-items-tbody">
                    <tr id="pur-empty-row">
                      <td colspan="9" style="text-align: center; padding: 48px; color: var(--text-muted); font-weight: 500; font-family: var(--font-arabic);">لا توجد منتجات مضافة بعد</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Row 4: Pricing Inputs & Calculations Card -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 24px; align-items: start;">
              <!-- Inputs Panel -->
              <div style="display: flex; flex-direction: column; gap: 20px;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                  <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">ضريبة السند</label>
                    <div style="position: relative; display: flex; align-items: center;">
                      <input type="number" class="form-control" id="pur-order-tax" value="0" min="0" max="100" style="width: 100%; height: 42px; border-radius: var(--border-radius-xs); padding-right: 36px;">
                      <span style="position: absolute; right: 12px; color: var(--text-muted); font-weight: 600;">%</span>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">الخصم الكلي</label>
                    <div style="position: relative; display: flex; align-items: center;">
                      <input type="number" class="form-control" id="pur-discount" value="0" min="0" step="0.01" style="width: 100%; height: 42px; border-radius: var(--border-radius-xs); padding-right: 36px;">
                      <span style="position: absolute; right: 12px; color: var(--text-muted); font-weight: 600;">$</span>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">تكاليف الشحن</label>
                    <div style="position: relative; display: flex; align-items: center;">
                      <input type="number" class="form-control" id="pur-shipping" value="0" min="0" step="0.01" style="width: 100%; height: 42px; border-radius: var(--border-radius-xs); padding-right: 36px;">
                      <span style="position: absolute; right: 12px; color: var(--text-muted); font-weight: 600;">$</span>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">حالة السند والتحصيل *</label>
                  <select class="form-control" id="pur-status" required style="width: 100%; height: 42px; border-radius: var(--border-radius-xs); font-family: var(--font-arabic);">
                    <option value="received" selected>تم الاستلام والسداد</option>
                    <option value="ordered">تم الطلب</option>
                    <option value="pending">قيد التعليق والمراجعة</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">ملاحظات إضافية</label>
                  <textarea class="form-control" id="pur-note" placeholder="اكتب تفاصيل أو ملاحظات إضافية عن السند هنا..." style="height: 100px; width: 100%; border-radius: var(--border-radius-sm); padding: 12px; resize: none; font-family: var(--font-arabic);"></textarea>
                </div>
              </div>

              <!-- Pricing Summary Widget -->
              <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 24px; box-shadow: var(--shadow-sm);">
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 18px;">
                  <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-secondary); font-weight: 600;">ضريبة السند</span>
                    <span id="pur-summary-tax" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-english);">$ 0.00 (0.00 %)</span>
                  </li>
                  <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-secondary); font-weight: 600;">الخصم الإضافي</span>
                    <span id="pur-summary-discount" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                  <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-secondary); font-weight: 600;">تكاليف الشحن</span>
                    <span id="pur-summary-shipping" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                  <li style="display: flex; justify-content: space-between; padding-top: 10px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-primary); font-weight: 800; font-size: 16px;">المجموع النهائي</span>
                    <span id="pur-summary-total" style="font-weight: 800; font-size: 20px; color: hsla(260, 60%, 50%, 1); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Submit Button Row -->
            <div style="display: flex; gap: 12px; align-items: center; margin-top: 24px;">
              <button class="btn btn-primary" type="submit" id="btn-pur-submit" style="background-color: hsla(260, 60%, 50%, 1); border: none; padding: 12px 28px; border-radius: var(--border-radius-xs); color: #fff; font-weight: 700; display: flex; align-items: center; gap: 8px; font-family: var(--font-arabic);">
                <i class="fas fa-check"></i>
                <span>حفظ وتأكيد السند</span>
              </button>
            </div>
          </form>

          <!-- Decorative purple Floating Settings Button from the screenshot -->
          <div id="pur-floating-settings-btn" style="position: fixed; bottom: 24px; right: 24px; width: 48px; height: 48px; border-radius: 50%; background: hsla(260, 60%, 50%, 1); display: flex; align-items: center; justify-content: center; color: #fff; box-shadow: var(--shadow-lg); cursor: pointer; z-index: 999;">
            <i class="fas fa-cog"></i>
          </div>
        </section>

        <!-- Section: Create Sales Invoice (New Direct Sales Invoice) -->
        <section id="create-sale-section" class="view-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
              <h2 style="font-weight: 800; color: var(--text-primary); margin: 0; font-size: 24px; font-family: var(--font-arabic);">إنشاء فاتورة مبيعات جديدة</h2>
              <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); font-family: var(--font-arabic);">
                <span>المبيعات</span>
                <span>/</span>
                <span style="color: hsla(var(--primary), 1); font-weight: 600;">فاتورة مبيعات جديدة</span>
              </div>
            </div>
            <button class="btn btn-secondary btn-sm" id="btn-sale-cancel" type="button" style="font-family: var(--font-arabic);">
              <i class="fas fa-arrow-right"></i> إلغاء والعودة
            </button>
          </div>

          <form id="sale-invoice-form">
            <!-- Row 1: Date, Customer, Payment Status & Method -->
            <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 24px;">
              <div class="form-group">
                <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">التاريخ *</label>
                <input type="date" class="form-control" id="sale-date" required style="width: 100%; height: 42px; border-radius: var(--border-radius-xs);">
              </div>
              <div class="form-group">
                <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">العميل المعتمد *</label>
                <select class="form-control" id="sale-customer-select" required style="width: 100%; height: 42px; border-radius: var(--border-radius-xs); font-family: var(--font-arabic);">
                  <option value="" disabled selected>اختر العميل...</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">طريقة الدفع *</label>
                <select class="form-control" id="sale-payment-method" required style="width: 100%; height: 42px; border-radius: var(--border-radius-xs); font-family: var(--font-arabic);">
                  <option value="Cash" selected>نقدي (Cash)</option>
                  <option value="Bank">تحويل بنكي / بطاقة</option>
                  <option value="Debt">ذمم (آجل)</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">حالة السداد *</label>
                <select class="form-control" id="sale-payment-status" required style="width: 100%; height: 42px; border-radius: var(--border-radius-xs); font-family: var(--font-arabic);">
                  <option value="Paid" selected>مدفوعة بالكامل</option>
                  <option value="Partially Paid">مدفوعة جزئياً</option>
                  <option value="Unpaid">غير مدفوعة (ذمم)</option>
                </select>
              </div>
            </div>

            <!-- Row 2: Product Search Autocomplete -->
            <div class="form-group" style="position: relative; margin-bottom: 24px;">
              <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">اسم أو رمز المنتج</label>
              <div style="position: relative; display: flex; align-items: center;">
                <span style="position: absolute; left: 16px; color: var(--text-muted); display: flex; align-items: center; gap: 12px; pointer-events: none;">
                  <i class="fas fa-search"></i>
                </span>
                <input type="text" class="form-control" id="sale-product-search" placeholder="ابحث عن المنتج بالاسم أو رمز SKU..." style="padding-inline-start: 45px; width: 100%; height: 48px; border-radius: var(--border-radius-sm); font-family: var(--font-arabic);">
              </div>
              <div id="sale-search-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); max-height: 280px; overflow-y: auto; z-index: 1000; box-shadow: var(--shadow-lg); margin-top: 6px; padding: 6px 0;">
                <!-- Suggestions injected here -->
              </div>
            </div>

            <!-- Row 3: Items Table -->
            <div class="form-group" style="margin-bottom: 24px;">
              <label class="form-label" style="font-weight: 700; color: var(--text-primary); margin-bottom: 12px; display: block; font-family: var(--font-arabic);">أصناف الفاتورة *</label>
              <div class="table-container" style="border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); overflow: hidden; background: var(--bg-secondary);">
                <table class="custom-table" style="width: 100%; border-collapse: collapse;">
                  <thead style="background: var(--bg-tertiary);">
                    <tr>
                      <th style="width: 50px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">#</th>
                      <th style="color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">اسم المنتج</th>
                      <th style="width: 140px; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">سعر الوحدة</th>
                      <th style="width: 120px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">المخزون المتوفر</th>
                      <th style="width: 100px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">الكمية</th>
                      <th style="width: 140px; text-align: right; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">المجموع الفرعي</th>
                      <th style="width: 65px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">إجراء</th>
                    </tr>
                  </thead>
                  <tbody id="sale-items-tbody">
                    <tr id="sale-empty-row">
                      <td colspan="7" style="text-align: center; padding: 48px; color: var(--text-muted); font-weight: 500; font-family: var(--font-arabic);">لا توجد أصناف مضافة بعد</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Row 4: Totals and Notes -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 24px; align-items: start;">
              <div style="display: flex; flex-direction: column; gap: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                  <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">الخصم الإضافي</label>
                    <input type="number" class="form-control" id="sale-discount" value="0" min="0" step="0.01" style="height: 42px; border-radius: var(--border-radius-xs);">
                  </div>
                  <div class="form-group">
                    <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">كوبون الخصم (اختياري)</label>
                    <input type="text" class="form-control" id="sale-coupon" placeholder="أدخل رمز الكوبون" style="height: 42px; border-radius: var(--border-radius-xs);">
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">شروط وملاحظات الفاتورة</label>
                  <textarea class="form-control" id="sale-note" placeholder="اكتب شروط الدفع، تفاصيل التسليم أو أي ملاحظات أخرى هنا..." style="height: 100px; resize: none; border-radius: var(--border-radius-sm); padding: 12px;"></textarea>
                </div>
              </div>

              <!-- Summary Widget -->
              <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 24px; box-shadow: var(--shadow-sm);">
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 18px;">
                  <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-secondary); font-weight: 600;">المجموع الفرعي</span>
                    <span id="sale-summary-subtotal" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                  <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-secondary); font-weight: 600;">الخصم المطبق</span>
                    <span id="sale-summary-discount" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                  <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-secondary); font-weight: 600;">الضريبة المضافة (15%)</span>
                    <span id="sale-summary-tax" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                  <li style="display: flex; justify-content: space-between; padding-top: 10px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-primary); font-weight: 800; font-size: 16px;">المجموع الإجمالي</span>
                    <span id="sale-summary-total" style="font-weight: 800; font-size: 20px; color: hsla(var(--primary), 1); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Submit buttons -->
            <div style="display: flex; gap: 12px; align-items: center; margin-top: 24px;">
              <button class="btn btn-primary" type="submit" id="btn-sale-submit" style="background-color: hsla(var(--primary), 1); border: none; padding: 12px 28px; border-radius: var(--border-radius-xs); color: #fff; font-weight: 700; display: flex; align-items: center; gap: 8px; font-family: var(--font-arabic);">
                <i class="fas fa-check"></i>
                <span>حفظ وإصدار الفاتورة</span>
              </button>
            </div>
          </form>
        </section>


        <!-- Section: Create Price Quotation -->
        <section id="create-quotation-section" class="view-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
              <h2 style="font-weight: 800; color: var(--text-primary); margin: 0; font-size: 24px; font-family: var(--font-arabic);">إنشاء عرض سعر جديد</h2>
              <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); font-family: var(--font-arabic);">
                <span>المبيعات</span>
                <span>/</span>
                <span style="color: hsla(var(--primary), 1); font-weight: 600;">عرض سعر جديد</span>
              </div>
            </div>
            <button class="btn btn-secondary btn-sm" id="btn-quo-cancel" type="button" style="font-family: var(--font-arabic);">
              <i class="fas fa-arrow-right"></i> إلغاء والعودة
            </button>
          </div>

          <form id="quo-quotation-form">
            <!-- Row 1: Date, Customer, Valid Until -->
            <div class="form-row" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 24px;">
              <div class="form-group">
                <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">التاريخ *</label>
                <input type="date" class="form-control" id="quo-date" required style="width: 100%; height: 42px; border-radius: var(--border-radius-xs);">
              </div>
              <div class="form-group">
                <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">العميل الموجه له العرض *</label>
                <select class="form-control" id="quo-customer-select" required style="width: 100%; height: 42px; border-radius: var(--border-radius-xs); font-family: var(--font-arabic);">
                  <option value="" disabled selected>اختر العميل...</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">صلاحية العرض لغاية *</label>
                <input type="date" class="form-control" id="quo-valid-until" required style="width: 100%; height: 42px; border-radius: var(--border-radius-xs);">
              </div>
            </div>

            <!-- Row 2: Product Search Autocomplete -->
            <div class="form-group" style="position: relative; margin-bottom: 24px;">
              <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">ابحث عن المنتج</label>
              <div style="position: relative; display: flex; align-items: center;">
                <span style="position: absolute; left: 16px; color: var(--text-muted); display: flex; align-items: center; gap: 12px; pointer-events: none;">
                  <i class="fas fa-search"></i>
                </span>
                <input type="text" class="form-control" id="quo-product-search" placeholder="ابحث بالاسم أو SKU لإضافة بنود العرض..." style="padding-inline-start: 45px; width: 100%; height: 48px; border-radius: var(--border-radius-sm); font-family: var(--font-arabic);">
              </div>
              <div id="quo-search-results" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); max-height: 280px; overflow-y: auto; z-index: 1000; box-shadow: var(--shadow-lg); margin-top: 6px; padding: 6px 0;">
                <!-- Suggestions injected here -->
              </div>
            </div>

            <!-- Row 3: Items Table -->
            <div class="form-group" style="margin-bottom: 24px;">
              <label class="form-label" style="font-weight: 700; color: var(--text-primary); margin-bottom: 12px; display: block; font-family: var(--font-arabic);">أصناف العرض المقترحة *</label>
              <div class="table-container" style="border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); overflow: hidden; background: var(--bg-secondary);">
                <table class="custom-table" style="width: 100%; border-collapse: collapse;">
                  <thead style="background: var(--bg-tertiary);">
                    <tr>
                      <th style="width: 50px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">#</th>
                      <th style="color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">اسم المنتج</th>
                      <th style="width: 140px; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">سعر العرض للوحدة</th>
                      <th style="width: 120px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">المخزون المتاح</th>
                      <th style="width: 100px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">الكمية</th>
                      <th style="width: 140px; text-align: right; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">المجموع الفرعي</th>
                      <th style="width: 65px; text-align: center; color: var(--text-primary); font-weight: 700; font-family: var(--font-arabic);">إجراء</th>
                    </tr>
                  </thead>
                  <tbody id="quo-items-tbody">
                    <tr id="quo-empty-row">
                      <td colspan="7" style="text-align: center; padding: 48px; color: var(--text-muted); font-weight: 500; font-family: var(--font-arabic);">لا توجد بنود مضافة بعد</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Row 4: Pricing and Notes -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-bottom: 24px; align-items: start;">
              <div style="display: flex; flex-direction: column; gap: 20px;">
                <div class="form-group">
                  <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">الخصم الكلي الممنوح</label>
                  <input type="number" class="form-control" id="quo-discount" value="0" min="0" step="0.01" style="height: 42px; border-radius: var(--border-radius-xs); width: 200px;">
                </div>
                <div class="form-group">
                  <label class="form-label" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-arabic);">ملاحظات وشروط العرض</label>
                  <textarea class="form-control" id="quo-note" placeholder="اكتب شروط تسليم البضائع، صلاحية الأسعار أو أي تفاصيل تسويقية أخرى..." style="height: 100px; resize: none; border-radius: var(--border-radius-sm); padding: 12px;"></textarea>
                </div>
              </div>

              <!-- Summary Widget -->
              <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 24px; box-shadow: var(--shadow-sm);">
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 18px;">
                  <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-secondary); font-weight: 600;">المجموع الفرعي</span>
                    <span id="quo-summary-subtotal" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                  <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-secondary); font-weight: 600;">الخصم المقترح</span>
                    <span id="quo-summary-discount" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                  <li style="display: flex; justify-content: space-between; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-secondary); font-weight: 600;">الضريبة (15%)</span>
                    <span id="quo-summary-tax" style="font-weight: 700; color: var(--text-primary); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                  <li style="display: flex; justify-content: space-between; padding-top: 10px; font-family: var(--font-arabic);">
                    <span style="color: var(--text-primary); font-weight: 800; font-size: 16px;">الإجمالي المتوقع</span>
                    <span id="quo-summary-total" style="font-weight: 800; font-size: 20px; color: hsla(200, 95%, 45%, 1); font-family: var(--font-english);">$ 0.00</span>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Submit buttons -->
            <div style="display: flex; gap: 12px; align-items: center; margin-top: 24px;">
              <button class="btn btn-primary" type="submit" id="btn-quo-submit" style="background-color: hsla(200, 95%, 45%, 1); border: none; padding: 12px 28px; border-radius: var(--border-radius-xs); color: #fff; font-weight: 700; display: flex; align-items: center; gap: 8px; font-family: var(--font-arabic);">
                <i class="fas fa-check"></i>
                <span>حفظ وتوليد عرض السعر</span>
              </button>
            </div>
          </form>
        </section>


        <!-- Section: Quotations List (All Quotations) -->
        <section id="all-quotations-section" class="view-section">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
            <div style="display: flex; flex-direction: column; gap: 4px;">
              <h2 style="font-weight: 800; color: var(--text-primary); margin: 0; font-size: 24px; font-family: var(--font-arabic);">عروض الأسعار الصادرة</h2>
              <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); font-family: var(--font-arabic);">
                <span>المبيعات</span>
                <span>/</span>
                <span style="color: hsla(var(--primary), 1); font-weight: 600;">عروض الأسعار</span>
              </div>
            </div>
            <button class="btn btn-primary btn-sm" id="btn-quo-go-create" type="button" style="background-color: hsla(200, 95%, 45%, 1); border: none; font-family: var(--font-arabic);">
              <i class="fas fa-plus"></i> عرض سعر جديد
            </button>
          </div>

          <div class="toolbar" style="margin-bottom: 20px;">
            <div class="search-box" style="flex-grow:1;">
              <i class="fas fa-search"></i>
              <input type="text" class="form-control" id="quo-search-input" placeholder="بحث باسم العميل أو رمز العرض...">
            </div>
          </div>

          <div class="dashboard-card">
            <div class="table-container">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>رمز العرض</th>
                    <th>العميل والتاريخ</th>
                    <th>صلاحية العرض لغاية</th>
                    <th>الإجمالي المتوقع</th>
                    <th>الحالة</th>
                    <th>إجراءات</th>
                  </tr>
                </thead>
                <tbody id="quo-table-body">
                  <!-- Dynamic quotation rows injected here -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION 7: ACCOUNTING INVOICES -->
        <section id="accounting-invoices-section" class="view-section">
          <div class="toolbar" style="margin-bottom: 20px;">
            <div class="search-box" style="flex-grow:1;">
              <i class="fas fa-search"></i>
              <input type="text" class="form-control" id="accounting-invoices-search" placeholder="بحث برقم الفاتورة أو الاسم...">
            </div>
          </div>

          <!-- Tabs for Invoices Category -->
          <div style="display: flex; gap: 12px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">
            <button class="btn btn-primary btn-sm btn-invoice-tab active" data-tab="sales" style="font-weight: 700;">فواتير المبيعات (العملاء)</button>
            <button class="btn btn-secondary btn-sm btn-invoice-tab" data-tab="purchases" style="font-weight: 700;">فواتير المشتريات (الموردين)</button>
          </div>
          
          <!-- Sales Invoices Container -->
          <div id="sales-invoices-container" class="invoice-tab-panel">
            <div class="dashboard-card">
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>رقم الفاتورة</th>
                      <th>العميل والتاريخ</th>
                      <th>القيمة الإجمالية (شامل الضريبة)</th>
                      <th>حالة التحصيل</th>
                      <th>إجراءات</th>
                    </tr>
                  </thead>
                  <tbody id="accounting-invoices-table-body">
                    <!-- Dynamic invoice rows -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Purchase Invoices Container -->
          <div id="purchase-invoices-container" class="invoice-tab-panel" style="display: none;">
            <div class="dashboard-card">
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>رقم الفاتورة</th>
                      <th>المورد والتاريخ</th>
                      <th>القيمة الإجمالية للشراء</th>
                      <th>حالة السداد للمورد</th>
                      <th>إجراءات</th>
                    </tr>
                  </thead>
                  <tbody id="accounting-purchases-table-body">
                    <!-- Dynamic purchase invoice rows loaded via JS -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 8: ACCOUNTING FINANCE -->
        <section id="accounting-finance-section" class="view-section">
          <div class="cashflow-summary-grid">
            <div class="cashflow-card">
              <span style="font-size:13px; color:var(--text-muted); font-weight:700;">المقبوضات والإيرادات الكلية</span>
              <span id="finance-total-revenue" style="font-size:28px; font-weight:800; color:hsla(var(--success), 1); font-family:var(--font-english);">$0</span>
              <span style="font-size:11px; color:var(--text-muted);">شاملة مبيعات المعارض ونقاط البيع الفورية</span>
            </div>
            <div class="cashflow-card">
              <span style="font-size:13px; color:var(--text-muted); font-weight:700;">النفقات وتكاليف المشتريات</span>
              <span id="finance-total-expenses" style="font-size:28px; font-weight:800; color:hsla(var(--danger), 1); font-family:var(--font-english);">$0</span>
              <span style="font-size:11px; color:var(--text-muted);">تشمل تكلفة شراء البضائع والمصاريف التشغيلية للمستودع</span>
            </div>
            <div class="cashflow-card">
              <span style="font-size:13px; color:var(--text-muted); font-weight:700;">صافي الأرباح التشغيلية</span>
              <span id="finance-net-profit" style="font-size:28px; font-weight:800; font-family:var(--font-english);">$0</span>
              <span style="font-size:11px; color:var(--text-muted);">العائد المالي الصافي للمنشأة قبل الضرائب المباشرة</span>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-file-invoice-dollar" style="color:hsla(var(--primary), 1);"></i> دفتر الأستاذ والمقبوضات الأخير</h3>
            </div>
            <div class="table-container">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>رقم القيد</th>
                    <th>البيان والتفاصيل</th>
                    <th>النوع</th>
                    <th>القيمة المالية</th>
                    <th>الحالة</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td style="font-family: var(--font-english); font-weight:700;">#TX-3011</td>
                    <td>تسوية إيرادات مبيعات نقطة البيع (POS) اليومية</td>
                    <td>إيرادات المقبوضات</td>
                    <td style="color:#10b981; font-family:var(--font-english); font-weight:700;">+$2,450.00</td>
                    <td><span class="badge badge-success">مكتمل</span></td>
                  </tr>
                  <tr>
                    <td style="font-family: var(--font-english); font-weight:700;">#TX-3012</td>
                    <td>شراء شحنة هواتف ذكية من مجموعة التكنولوجيا الرقمية</td>
                    <td>مصاريف وتكلفة شراء</td>
                    <td style="color:#f43f5e; font-family:var(--font-english); font-weight:700;">-$9,500.00</td>
                    <td><span class="badge badge-success">مدفوع</span></td>
                  </tr>
                  <tr>
                    <td style="font-family: var(--font-english); font-weight:700;">#TX-3013</td>
                    <td>فاتورة كهرباء وإنترنت مستودع دمشق الرئيسي د/6</td>
                    <td>مصاريف تشغيلية</td>
                    <td style="color:#f43f5e; font-family:var(--font-english); font-weight:700;">-$450.00</td>
                    <td><span class="badge badge-success">مدفوع</span></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION 9: REPORTS SALES -->
        <section id="reports-sales-section" class="view-section">
          <div class="dashboard-grid">
            <div class="dashboard-card" style="grid-column: 1/-1;">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line" style="color:hsla(var(--primary), 1);"></i> تحليل المبيعات الموحد وقنوات البيع</h3>
              </div>
              <div class="card-body" style="height: 350px; position: relative;">
                <canvas id="reportsSalesChartCanvas"></canvas>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 10: REPORTS STOCK -->
        <section id="reports-stock-section" class="view-section">
          <div class="dashboard-grid">
            <div class="dashboard-card">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-pie" style="color:#06b6d4;"></i> توزيع حجم المخزون بحسب الفئات</h3>
              </div>
              <div class="card-body" style="height: 300px; position: relative;">
                <canvas id="reportsStockChartCanvas"></canvas>
              </div>
            </div>

            <div class="dashboard-card">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history" style="color:hsla(var(--primary), 1);"></i> سجل حركات المخزون والعمليات اللحظية</h3>
              </div>
              <div class="card-body" style="overflow-y: auto; max-height: 300px; padding: 0;">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>نوع الحركة</th>
                      <th>البيان والتأثير</th>
                      <th>الوقت</th>
                    </tr>
                  </thead>
                  <tbody id="reports-stock-logs-tbody">
                    <!-- Dynamic logs injected here -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 11: TEAM EMPLOYEES -->
        <section id="team-employees-section" class="view-section">
          <div class="settings-grid">
            <div class="settings-card">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:16px;">الموظفين النشطين في الفروع والمخازن</h3>
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>اسم الموظف</th>
                      <th>الدور الوظيفي</th>
                      <th>المستودع/الفرع</th>
                      <th>الحالة</th>
                      <th>إجراءات</th>
                    </tr>
                  </thead>
                  <tbody id="team-employees-table-body">
                    <!-- Dynamic employee list -->
                  </tbody>
                </table>
              </div>
            </div>

            <div class="settings-card">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:16px;">تسجيل موظف جديد</h3>
              <form id="add-employee-form" style="display:flex; flex-direction:column; gap:16px;">
                <div class="form-group" style="margin-bottom:0;">
                  <label class="form-label">الاسم الكامل للموظف</label>
                  <input type="text" class="form-control" id="emp-name" required placeholder="مثال: صالح عبد الرحمن العتيبي">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <label class="form-label">البريد الإلكتروني</label>
                  <input type="email" class="form-control" id="emp-email" required placeholder="مثال: employee@matjer.net">
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <label class="form-label">الدور والمسؤولية في النظام</label>
                  <select class="form-control" id="emp-role">
                    <option value="مدير المتجر">مدير المتجر</option>
                    <option value="أمين المستودع">أمين المستودع</option>
                    <option value="كاشير مبيعات">كاشير مبيعات</option>
                  </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                  <label class="form-label">مكان العمل الأساسي (المستودع)</label>
                  <select class="form-control" id="emp-branch">
                    <option value="مستودع دمشق الرئيسي">مستودع دمشق الرئيسي</option>
                    <option value="مستودع حلب الشمالي">مستودع حلب الشمالي</option>
                    <option value="معرض حمص المباشر">معرض حمص المباشر</option>
                  </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:10px;"><i class="fas fa-user-plus"></i> إضافة وتعميم الموظف</button>
              </form>
            </div>
          </div>
        </section>

        <!-- SECTION 12: TEAM PERMISSIONS -->
        <section id="team-permissions-section" class="view-section">
          <div class="permissions-container">
            <div class="landing-hero-badge" style="width:fit-content;">الصلاحيات والأدوار الأمنية</div>
            <h3 style="font-weight:700; color:var(--text-primary); margin-top:-10px;">إدارة الصلاحيات لكل دور وظيفي بالمتجر</h3>
            
            <div class="permissions-grid" id="team-permissions-grid">
              <!-- Rendered dynamically in team.js -->
            </div>
          </div>
        </section>

        <!-- SECTION 13: SETTINGS SYSTEM -->
        <section id="settings-system-section" class="view-section">
          <div class="settings-grid">
            <div class="settings-card" style="grid-column: 1/-1;">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:16px;"><i class="fas fa-sliders-h" style="color:hsla(var(--primary),1);"></i> تهيئة وتكوين المتجر العام</h3>
              <form id="store-settings-form" style="display:flex; flex-direction:column; gap:20px;">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">اسم المتجر / العلامة التجارية</label>
                    <input type="text" class="form-control" id="settings-store-name-input" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">العملة الافتراضية</label>
                    <select class="form-control" id="settings-currency-select">
                      <option value="SYP (ل.س)">الليرة السورية (ل.س) - SYP</option>
                      <option value="USD ($)">الدولار الأمريكي ($) - USD</option>
                      <option value="SAR (ر.س)">الريال السعودي (ر.س) - SAR</option>
                      <option value="AED (د.إ)">الدرهم الإماراتي (د.إ) - AED</option>
                    </select>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">نسبة ضريبة القيمة المضافة VAT (%)</label>
                    <input type="text" class="form-control" id="settings-tax-rate-input" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">الرقم الضريبي المعتمد (المملكة العربية السعودية)</label>
                    <input type="text" class="form-control" value="300109923800003" disabled style="background-color: var(--bg-tertiary);">
                  </div>
                </div>
                <button type="submit" class="btn btn-primary" style="align-self:flex-start;"><i class="fas fa-save"></i> حفظ التغييرات العامة</button>
              </form>
            </div>
          </div>
        </section>

        <!-- SECTION 14: SETTINGS WAREHOUSES -->
        <section id="settings-warehouses-section" class="view-section">
          <div class="settings-grid">
            <div class="settings-card" style="grid-column: 1/-1;">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:16px;"><i class="fas fa-warehouse" style="color:hsla(var(--primary),1);"></i> إدارة المستودعات الجغرافية النشطة</h3>
              
              <div class="warehouses-grid" id="settings-warehouses-grid">
                <!-- Loaded dynamically in settings.js -->
              </div>
            </div>

            <div class="settings-card" style="grid-column: 1/-1;">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:16px;"><i class="fas fa-plus" style="color:hsla(var(--success),1);"></i> إضافة موقع مستودع جغرافي جديد</h3>
              <form id="add-warehouse-form" style="display:flex; flex-direction:column; gap:16px;">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">اسم المستودع الجديد</label>
                    <input type="text" class="form-control" id="wh-name" required placeholder="مثال: مستودع المنطقة الشرقية بالدمام">
                  </div>
                  <div class="form-group">
                    <label class="form-label">العنوان الجغرافي التفصيلي</label>
                    <input type="text" class="form-control" id="wh-address" required placeholder="مثال: حي الكورنيش، الدمام">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">اسم المسؤول عن المستودع</label>
                    <input type="text" class="form-control" id="wh-contact" required placeholder="مثال: م. عبد العزيز الدوسري">
                  </div>
                  <div class="form-group">
                    <label class="form-label">رقم الهاتف للتواصل</label>
                    <input type="text" class="form-control" id="wh-phone" required placeholder="مثال: +966 53 555 7777">
                  </div>
                </div>
                <button type="submit" class="btn btn-primary" style="align-self:flex-start;"><i class="fas fa-plus"></i> تأكيد إضافة المستودع</button>
              </form>
            </div>
          </div>
        </section>

        <!-- SECTION 15: WAREHOUSE TRANSFERS -->
        <section id="warehouse-transfers-section" class="view-section">
          <div class="settings-grid">
            <div class="settings-card">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:16px;"><i class="fas fa-exchange-alt" style="color:hsla(var(--primary),1);"></i> طلب نقل بضائع بين المستودعات</h3>
              <form id="warehouse-transfer-form" style="display:flex; flex-direction:column; gap:16px;">
                <div class="form-row">
                  <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">المستودع المصدر (من)</label>
                    <select class="form-control" id="trans-source-wh" required>
                      <option value="1">مستودع دمشق الرئيسي</option>
                      <option value="2">مستودع حلب الشمالي</option>
                      <option value="3">معرض حمص المباشر</option>
                    </select>
                  </div>
                  <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">المستودع الهدف (إلى)</label>
                    <select class="form-control" id="trans-target-wh" required>
                      <option value="2">مستودع حلب الشمالي</option>
                      <option value="1">مستودع دمشق الرئيسي</option>
                      <option value="3">معرض حمص المباشر</option>
                    </select>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">المنتج المراد نقله</label>
                    <select class="form-control" id="trans-product-select" required>
                      <!-- Loaded dynamically -->
                    </select>
                  </div>
                  <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">الكمية المراد نقلها</label>
                    <input type="number" class="form-control" id="trans-qty" min="1" value="5" required>
                  </div>
                </div>
                <button type="submit" class="btn btn-primary" style="align-self:flex-start;"><i class="fas fa-shipping-fast"></i> تأكيد وترحيل شحنة النقل</button>
              </form>
            </div>
            <div class="settings-card">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:16px;">حالة شحنات النقل الجارية</h3>
              <div style="display:flex; flex-direction:column; gap:12px;" id="transfers-history-list">
                <!-- Dynamically populated -->
                <div style="padding:12px; background-color:var(--bg-tertiary); border:1px solid var(--border-color); border-radius:var(--border-radius-sm); font-size:12px;">
                  <div style="display:flex; justify-content:space-between; font-weight:700; margin-bottom:4px;">
                    <span>شحنة رقم #TR-9801</span>
                    <span style="color:#10b981;">مكتمل</span>
                  </div>
                  <div>نقل عدد 10 "ساعة آبل" من مستودع دمشق إلى مستودع حلب.</div>
                  <div style="color:var(--text-muted); font-size:10px; margin-top:4px;">منذ ساعتين - بواسطة أمين المستودع</div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 16: INVENTORY ADJUSTMENTS (DAMAGED) -->
        <section id="inventory-adjustments-section" class="view-section">
          <div class="settings-grid">
            <div class="settings-card" style="grid-column:1/-1;">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:16px;"><i class="fas fa-trash-alt" style="color:hsla(var(--danger),1);"></i> تسجيل وإتلاف كميات بضائع تالفة / مفقودة</h3>
              <form id="inventory-adjustment-form" style="display:flex; flex-direction:column; gap:16px;">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">المنتج المتأثر</label>
                    <select class="form-control" id="adj-product-select" required>
                      <!-- Loaded dynamically -->
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">كمية التالف (تخصم من المخزون)</label>
                    <input type="number" class="form-control" id="adj-qty" min="1" value="1" required>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">نوع الحركة والتأثير</label>
                    <select class="form-control" id="adj-type" required>
                      <option value="damage">إتلاف بضاعة مكسورة/تالفة (-)</option>
                      <option value="lost">فقدان كمية أثناء الجرد (-)</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">السبب / تفاصيل التدقيق</label>
                    <input type="text" class="form-control" id="adj-reason" required placeholder="مثال: تلف أثناء شحن المورد، خطأ جرد أسبوعي...">
                  </div>
                </div>
                <button type="submit" class="btn btn-primary" style="align-self:flex-start; background-color:hsla(var(--danger),1); border:none;"><i class="fas fa-exclamation-triangle"></i> تسجيل التالف وخصم الرصيد</button>
              </form>
            </div>
          </div>
        </section>

        <!-- SECTION 17: ECOMMERCE CUSTOMERS -->
        <section id="ecommerce-customers-section" class="view-section">
          <div class="toolbar">
            <h3 style="font-weight:700; color:var(--text-primary); font-size:16px;">دليل عملاء المتجر الفردي والشركات</h3>
          </div>
          <div class="dashboard-card">
            <div class="table-container">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>اسم العميل</th>
                    <th>رقم الجوال</th>
                    <th>البريد الإلكتروني</th>
                    <th>إجمالي الطلبات</th>
                    <th>نقاط الولاء</th>
                  </tr>
                </thead>
                <tbody id="ecommerce-customers-tbody">
                  <!-- Injected dynamically -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION 18: ECOMMERCE INTEGRATION -->
        <section id="ecommerce-integration-section" class="view-section">
          <div class="settings-grid">
            <div class="settings-card" style="grid-column:1/-1;">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:16px;"><i class="fab fa-wordpress" style="color:#21759b;"></i> ربط ومزامنة متجر WooCommerce الإلكتروني</h3>
              <form id="integration-woo-form" style="display:flex; flex-direction:column; gap:16px;">
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">رابط متجر WooCommerce (URL)</label>
                    <input type="text" class="form-control" id="integration-woo-url" value="https://store.matjer.net" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">مفتاح المستهلك (Consumer Key)</label>
                    <input type="password" class="form-control" id="integration-woo-ck" value="ck_8a09f8e7b6c5d4e3f2a1" required>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">مفتاح السر للمستهلك (Consumer Secret)</label>
                    <input type="password" class="form-control" id="integration-woo-cs" value="cs_1a2b3c4d5e6f7g8h9i0j" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">حالة المزامنة التلقائية للمخزون</label>
                    <select class="form-control" id="integration-woo-sync">
                      <option value="live">مزامنة فورية عند أي مبيعات POS</option>
                      <option value="daily">مزامنة يومية مجدولة</option>
                      <option value="manual">تعطيل المزامنة التلقائية</option>
                    </select>
                  </div>
                </div>
                <div style="display:flex; gap:12px;">
                  <button type="submit" class="btn btn-primary"><i class="fas fa-link"></i> حفظ إعدادات الاتصال والربط</button>
                  <button type="button" class="btn btn-secondary" id="btn-woo-test-conn"><i class="fas fa-plug"></i> اختبار الاتصال بالسيرفر</button>
                </div>
              </form>
            </div>
          </div>
        </section>

        <!-- SECTION 19: ACCOUNTING TAX VAT -->
        <section id="accounting-tax-section" class="view-section">
          <div class="cashflow-summary-grid">
            <div class="cashflow-card">
              <span style="font-size:13px; color:var(--text-muted); font-weight:700;">ضريبة المبيعات المحصلة (المخرجات)</span>
              <span id="tax-output-val" style="font-size:24px; font-weight:800; color:hsla(var(--primary),1); font-family:var(--font-english);">$0.00</span>
              <span style="font-size:11px; color:var(--text-muted);">ضريبة 15% محصلة من الفواتير المباعة</span>
            </div>
            <div class="cashflow-card">
              <span style="font-size:13px; color:var(--text-muted); font-weight:700;">ضريبة المشتريات المخصومة (المدخلات)</span>
              <span id="tax-input-val" style="font-size:24px; font-weight:800; color:hsla(var(--info),1); font-family:var(--font-english);">$0.00</span>
              <span style="font-size:11px; color:var(--text-muted);">ضريبة 15% مدفوعة للموردين عند التوريد</span>
            </div>
            <div class="cashflow-card">
              <span style="font-size:13px; color:var(--text-muted); font-weight:700;">صافي الضريبة المستحقة للهيئة</span>
              <span id="tax-net-val" style="font-size:24px; font-weight:800; color:hsla(var(--warning),1); font-family:var(--font-english);">$0.00</span>
              <span style="font-size:11px; color:var(--text-muted);">المبلغ الصافي المطلوب ترحيله لهيئة الزكاة والضريبة</span>
            </div>
          </div>

          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-file-contract" style="color:hsla(var(--primary),1);"></i> كشف حساب الإقرار الضريبي الجاري</h3>
            </div>
            <div class="card-body">
              <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border-color); padding:12px 0;">
                <span>الفترة الضريبية الحالية</span>
                <strong>الربع الثاني من عام 2026م (أبريل - يونيو)</strong>
              </div>
              <div style="display:flex; justify-content:space-between; border-bottom:1px solid var(--border-color); padding:12px 0;">
                <span>تاريخ الاستحقاق لتقديم الإقرار</span>
                <strong style="color:hsla(var(--danger),1);">30 يوليو 2026م</strong>
              </div>
              <div style="display:flex; justify-content:space-between; padding:12px 0;">
                <span>حالة تقديم الإقرار</span>
                <span class="badge badge-warning" id="tax-filing-status">مسودة جارية (غير مقدم بعد)</span>
              </div>
              <button class="btn btn-primary" id="btn-file-tax-return" style="margin-top:20px; width:100%;"><i class="fas fa-paper-plane"></i> تقديم الإقرار الضريبي واعتماده رسمياً</button>
            </div>
          </div>
        </section>

        <!-- SECTION 20: REPORTS BRANCHES -->
        <section id="reports-branches-section" class="view-section">
          <div class="dashboard-grid">
            <div class="dashboard-card" style="grid-column:1/-1;">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-store" style="color:hsla(var(--primary),1);"></i> توزيع إيرادات المبيعات بحسب الفروع والمعارض الجغرافية</h3>
              </div>
              <div class="card-body" style="height:320px; position:relative;">
                <canvas id="reportsBranchesChartCanvas"></canvas>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 21: TEAM AUDIT SECURITY LOG -->
        <section id="team-audit-section" class="view-section">
          <div class="dashboard-card">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-shield-alt" style="color:hsla(var(--primary),1);"></i> سجل تدقيق الأمان ومراقبة العمليات للفريق (Audit Log)</h3>
            </div>
            <div class="card-body">
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>الوقت والتاريخ</th>
                      <th>المستخدم</th>
                      <th>العملية والتعديل</th>
                      <th>عنوان الـ IP</th>
                    </tr>
                  </thead>
                  <tbody id="team-audit-table-body">
                    <!-- Loaded dynamically -->
</tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 23.1: BRAND MANAGEMENT -->
        <section id="brand-management-section" class="view-section">
          <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
            <!-- Add Brand Form -->
            <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 20px;">
              <h3 style="font-weight: 700; color: var(--text-primary); font-size: 15px; margin-bottom: 16px;"><i class="far fa-bookmark" style="color: hsla(var(--primary), 1); margin-inline-end: 8px;"></i>إضافة علامة تجارية جديدة</h3>
              <form id="add-brand-form" style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" style="font-weight: 600;">اسم العلامة التجارية *</label>
                  <input type="text" class="form-control" id="brand-name" required placeholder="مثال: Apple, Nike...">
                </div>
                <div class="form-group">
                  <label class="form-label" style="font-weight: 600;">الوصف والتصنيف الأساسي</label>
                  <input type="text" class="form-control" id="brand-desc" placeholder="مثال: هواتف وساعات ذكية...">
                </div>
                <button type="submit" class="btn btn-primary" style="background-color: hsla(260, 60%, 50%, 1); border: none; font-weight: 600; width: 100%; height: 40px;">حفظ العلامة التجارية</button>
              </form>
            </div>

            <!-- Brands List Table -->
            <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 20px;">
              <h3 style="font-weight: 700; color: var(--text-primary); font-size: 15px; margin-bottom: 16px;">العلامات التجارية المسجلة</h3>
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>العلامة التجارية</th>
                      <th>الوصف</th>
                      <th style="text-align: center; width: 120px;">عدد المنتجات</th>
                      <th style="text-align: center; width: 80px;">إجراء</th>
                    </tr>
                  </thead>
                  <tbody id="brands-table-body">
                    <!-- Populated dynamically -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 23.2: UNITS MANAGEMENT -->
        <section id="units-management-section" class="view-section">
          <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
            <!-- Add Unit Form -->
            <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 20px;">
              <h3 style="font-weight: 700; color: var(--text-primary); font-size: 15px; margin-bottom: 16px;"><i class="fas fa-quote-left" style="color: hsla(var(--primary), 1); margin-inline-end: 8px;"></i>إضافة وحدة قياس جديدة</h3>
              <form id="add-unit-form" style="display: flex; flex-direction: column; gap: 16px;">
                <div class="form-group">
                  <label class="form-label" style="font-weight: 600;">اسم وحدة القياس *</label>
                  <input type="text" class="form-control" id="unit-name" required placeholder="مثال: قطعة، صندوق، طقم...">
                </div>
                <div class="form-group">
                  <label class="form-label" style="font-weight: 600;">الرمز القصير *</label>
                  <input type="text" class="form-control" id="unit-code" required placeholder="مثال: pc, box, set...">
                </div>
                <button type="submit" class="btn btn-primary" style="background-color: hsla(260, 60%, 50%, 1); border: none; font-weight: 600; width: 100%; height: 40px;">حفظ الوحدة</button>
              </form>
            </div>

            <!-- Units List Table -->
            <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 20px;">
              <h3 style="font-weight: 700; color: var(--text-primary); font-size: 15px; margin-bottom: 16px;">وحدات القياس المتاحة</h3>
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>اسم الوحدة</th>
                      <th style="text-align: center; width: 120px;">الرمز القصير</th>
                      <th style="text-align: center; width: 100px;">الحالة</th>
                      <th style="text-align: center; width: 80px;">إجراء</th>
                    </tr>
                  </thead>
                  <tbody id="units-table-body">
                    <!-- Populated dynamically -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 23.3: BATCHES MANAGEMENT -->
        <section id="batches-management-section" class="view-section">
          <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 20px;">
            <h3 style="font-weight: 700; color: var(--text-primary); font-size: 15px; margin-bottom: 16px;"><i class="fas fa-heartbeat" style="color: hsla(var(--primary), 1); margin-inline-end: 8px;"></i>دليل الشحنات والدفعات الواردة (Batches)</h3>
            <div class="table-container">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>رمز الشحنة (Batch Number)</th>
                    <th>المورد المصدر</th>
                    <th>تاريخ الاستلام</th>
                    <th style="text-align: center; width: 120px;">الكمية المستلمة</th>
                    <th style="text-align: center; width: 120px;">حالة النشاط</th>
                  </tr>
                </thead>
                <tbody id="batches-table-body">
                  <!-- Populated dynamically -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION 23.4: DATA IMPORT MANAGER -->
        <section id="import-management-section" class="view-section">
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
            <!-- Import Wizard Inputs -->
            <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 24px;">
              <h3 style="font-weight: 800; color: var(--text-primary); font-size: 16px; margin-bottom: 8px;"><i class="fas fa-file-import" style="color: hsla(var(--primary), 1); margin-inline-end: 8px;"></i>استيراد البيانات التلقائي (CSV / Excel Import)</h3>
              <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 24px;">قم باختيار نوع الجدول والملف المنسق لاستيراد كميات كبيرة من البيانات بنقرة واحدة.</p>

              <form id="import-data-form" style="display: flex; flex-direction: column; gap: 20px;">
                <div class="form-group">
                  <label class="form-label" style="font-weight: 700; color: var(--text-primary);">نوع البيانات المستهدفة للاستيراد *</label>
                  <select class="form-control" id="import-type-select" required style="height: 42px; border-radius: var(--border-radius-xs);">
                    <option value="products">استيراد منتجات جديدة كلياً (Products List)</option>
                    <option value="products-update">تحديث أرصدة المخزون فقط (Stock Levels Update)</option>
                    <option value="purchases">استيراد فواتير مشتريات (Purchase Invoices Import)</option>
                    <option value="sales">استيراد فواتير مبيعات (Sales Invoices Import)</option>
                  </select>
                </div>

                <!-- Drag & Drop Upload Area -->
                <div class="form-group">
                  <label class="form-label" style="font-weight: 700; color: var(--text-primary);">تحميل الملف *</label>
                  <input type="file" id="import-file-input" style="display: none;" accept=".csv,.xlsx,.xls">
                  <div id="import-upload-area" style="border: 2px dashed var(--border-color); border-radius: var(--border-radius-sm); padding: 32px; text-align: center; cursor: pointer; transition: border-color 0.2s; background: var(--bg-tertiary);">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: var(--text-muted); margin-bottom: 8px;"></i>
                    <div style="font-weight: 700; color: var(--text-primary);">اختر ملف Excel أو CSV للاستيراد</div>
                    <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">أو قم بسحب وإلقاء الملف هنا مباشرة</div>
                  </div>
                </div>

                <button type="button" class="btn btn-primary" id="btn-import-submit" style="background-color: hsla(260, 60%, 50%, 1); border: none; font-weight: 700; height: 44px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                  <i class="fas fa-file-import"></i>
                  <span>بدء معالجة واستيراد البيانات</span>
                </button>
              </form>
            </div>

            <!-- Import Instruction Card -->
            <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 24px; box-shadow: var(--shadow-sm);">
              <h3 style="font-weight: 700; color: var(--text-primary); font-size: 15px; margin-bottom: 12px;">تعليمات تنسيق ملفات الاستيراد</h3>
              <ul style="padding-inline-start: 20px; font-size: 13px; color: var(--text-secondary); display: flex; flex-direction: column; gap: 12px; line-height: 1.6;">
                <li>يجب أن يتضمن الصف الأول في ملف الـ Excel أسماء الأعمدة بدقة (SKU, Name, Price, Cost, Category).</li>
                <li>يتم تجاهل الصفوف المكررة برمز الـ SKU للحد من الأخطاء وتجنب الكتابة فوق المنتجات دون إذن.</li>
                <li>تأكد من تنسيق الأرقام كأعداد عشرية صحيحة في عمودي السعر والتكلفة وتفادي استخدام علامات العملات ($ أو ل.س).</li>
                <li>يمكنك تحميل ملف نموذج جاهز لتعبئة البيانات ومطابقتها مع خوادم متجر.</li>
              </ul>
              <button class="btn btn-secondary btn-sm" style="margin-top: 20px; font-family: var(--font-arabic); display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-file-download"></i> تحميل قالب إكسيل النموذجي
              </button>
            </div>
          </div>
        </section>

        <!-- SECTION 23.5: REALTIME SALES COUNTER -->
        <section id="realtime-counter-section" class="view-section">
          <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
            <!-- Metric Summary Panel -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
              <!-- Sales volume metric -->
              <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 24px; display: flex; align-items: center; gap: 16px;">
                <div style="width: 52px; height: 52px; border-radius: var(--border-radius-sm); background: rgba(111, 66, 193, 0.1); display: flex; align-items: center; justify-content: center; color: hsla(260, 60%, 50%, 1); font-size: 20px;">
                  <i class="fas fa-stopwatch"></i>
                </div>
                <div>
                  <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; margin-bottom: 2px;">مجموع المبيعات الفورية اليوم</div>
                  <h2 id="realtime-total-sales" style="font-family: var(--font-english); font-weight: 800; font-size: 24px; color: var(--text-primary); margin: 0;">$15,840</h2>
                </div>
              </div>

              <!-- Orders count metric -->
              <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 24px; display: flex; align-items: center; gap: 16px;">
                <div style="width: 52px; height: 52px; border-radius: var(--border-radius-sm); background: rgba(46, 125, 50, 0.1); display: flex; align-items: center; justify-content: center; color: #2e7d32; font-size: 20px;">
                  <i class="fas fa-shopping-bag"></i>
                </div>
                <div>
                  <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; margin-bottom: 2px;">عدد المعاملات والطلبات الفورية</div>
                  <h2 id="realtime-orders-count" style="font-family: var(--font-english); font-weight: 800; font-size: 24px; color: var(--text-primary); margin: 0;">28</h2>
                </div>
              </div>
            </div>

            <!-- Live Feed Panel -->
            <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 24px;">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-weight: 800; color: var(--text-primary); font-size: 15px; margin: 0; display: flex; align-items: center; gap: 8px;">
                  <span style="width: 8px; height: 8px; border-radius: 50%; background: #2e7d32; display: inline-block; animation: pulseGlow 1.5s infinite;"></span>
                  <span>تلقيم حركة العمليات والطلبات اللحظي (Live Counter)</span>
                </h3>
              </div>
              
              <!-- Live incoming feed -->
              <div id="realtime-orders-feed" style="max-height: 380px; overflow-y: auto; padding-inline-end: 4px;">
                <!-- Populated dynamically via interval -->
                <div style="padding:12px; background:var(--bg-tertiary); border:1px solid var(--border-color); border-radius:var(--border-radius-xs); display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-inline-start: 4px solid #6f42c1;">
                  <div>
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 13px;">طلب جديد من: أحمد العتيبي</div>
                    <div style="font-size:11px; color:var(--text-muted);">آيفون 15 برو ماكس (عدد 1) - منذ قليل</div>
                  </div>
                  <div style="font-family: var(--font-english); font-weight: 700; color: hsla(260, 60%, 50%, 1); font-size: 14px;">+$1,200</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pulse Animation CSS -->
          <style>
            @keyframes pulseGlow {
              0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(46, 125, 50, 0.7); }
              70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(46, 125, 50, 0); }
              100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(46, 125, 50, 0); }
            }
          </style>
        </section>

        <!-- SECTION 23.6: SALES RETURN SECTION -->
        <section id="sales-return-section" class="view-section">
          <div class="dashboard-card" style="background: var(--bg-secondary); border: 1px solid var(--border-color); border-radius: var(--border-radius-sm); padding: 24px;">
            <h3 style="font-weight: 800; color: var(--text-primary); font-size: 16px; margin-bottom: 8px;"><i class="fas fa-undo" style="color: hsla(260, 60%, 50%, 1); margin-inline-end: 8px;"></i>إرجاع وإلغاء فواتير المبيعات الصادرة (Sales Return Ledger)</h3>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 24px;">اختر فاتورة المبيعات الصادرة المسجلة لإلغاء تحصيلها وإرجاع كمياتها المباعة للمستودعات تلقائياً.</p>

            <div class="table-container">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>رقم الفاتورة</th>
                    <th>العميل والتاريخ</th>
                    <th>القيمة الإجمالية الصادرة</th>
                    <th>حالة التحصيل الحالية</th>
                    <th style="text-align: center; width: 180px;">العمليات المتاحة</th>
                  </tr>
                </thead>
                <tbody id="returns-table-body">
                  <!-- Populated dynamically via JS -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <section id="settings-payment-shipping-section" class="view-section">
          <div class="settings-grid">
            <!-- Payment Gateways -->
            <div class="settings-card">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:15px;"><i class="fas fa-credit-card" style="color:hsla(var(--primary),1);"></i> تفعيل بوابات الدفع الإلكتروني</h3>
              <div class="permission-list" id="payment-gateways-list">
                <!-- Checkboxes -->
              </div>
              <button class="btn btn-primary btn-sm" id="btn-save-gateways" style="width:100%;">حفظ بوابات الدفع</button>
            </div>
            
            <!-- Shipping Carriers -->
            <div class="settings-card">
              <h3 style="font-weight:700; color:var(--text-primary); font-size:15px;"><i class="fas fa-shipping-fast" style="color:hsla(var(--info),1);"></i> تفعيل شركات الشحن وتوليد البوالص</h3>
              <div class="permission-list" id="shipping-carriers-list">
                <!-- Checkboxes -->
              </div>
              <button class="btn btn-primary btn-sm" id="btn-save-carriers" style="width:100%;">حفظ شركات الشحن</button>
            </div>
          </div>
        </section>

        <!-- SECTION 23: SETTINGS THEMES -->
        <section id="settings-themes-section" class="view-section">
          <div class="settings-grid">
            <div class="settings-card" style="grid-column:1/-1; display:flex; flex-direction:column; gap:20px;">
              <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-color); padding-bottom:15px; flex-wrap:wrap; gap:12px;">
                <div>
                  <h3 style="font-weight:800; color:var(--text-primary); font-size:18px;"><i class="fas fa-palette" style="color:hsla(var(--primary),1); margin-inline-end:8px;"></i> مكتبة قوالب وثيمات المتاجر الفرونت اند</h3>
                  <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">اختر مظهر متجرك الإلكتروني الموجه للعملاء (Storefront) وقم بتهيئة طابعه البصري</p>
                </div>
                <a href="storefront.html" target="_blank" class="btn btn-primary" style="display:flex; align-items:center; gap:8px;"><i class="fas fa-external-link-alt"></i> معاينة واجهة المتجر (Frontend)</a>
              </div>

              <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap:20px; margin-top:10px;">
                <!-- Jasmine Theme Card -->
                <div style="border:1px solid var(--border-color); border-radius:var(--border-radius); overflow:hidden; background-color:var(--bg-secondary); display:flex; flex-direction:column; box-shadow:0 4px 6px rgba(0,0,0,0.05); transition:transform 0.2s; cursor:pointer;" class="theme-card active" id="theme-card-jasmine">
                  <!-- Visual Preview Mockup -->
                  <div style="height:160px; background: linear-gradient(135deg, #1b4d3e 0%, #004d40 100%); padding:20px; display:flex; flex-direction:column; justify-content:space-between; color:#fdfbf7; position:relative;">
                    <div style="font-family:'Amiri', serif; font-size:24px; font-weight:700; border-bottom:1px solid rgba(253,251,247,0.2); padding-bottom:5px;">متجر الياسمين الدمشقي</div>
                    <div style="font-size:11px; opacity:0.8; line-height:1.4;">
                      • خطوط سريـف كلاسيكية مريحة<br>
                      • ألوان مستوحاة من البيوت الدمشقية التراثية<br>
                      • تجربة تسوق سريعة وموجهة للسوق السورية
                    </div>
                    <span class="badge badge-success" id="badge-jasmine" style="position:absolute; top:15px; left:15px; background-color:#c5a880; color:#1b4d3e; font-weight:700;">نشط حالياً</span>
                  </div>
                  
                  <!-- Info Footer -->
                  <div style="padding:16px; display:flex; flex-direction:column; gap:12px; flex-grow:1; justify-content:space-between;">
                    <div>
                      <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h4 style="font-weight:700; font-size:15px; color:var(--text-primary);">ثيم الياسمين الدمشقي (Jasmine Theme)</h4>
                        <span style="font-size:11px; color:#10b981; font-weight:700;">متاح مجاناً</span>
                      </div>
                      <p style="font-size:12px; color:var(--text-muted); margin-top:6px; line-height:1.4;">القالب الافتراضي المخصص للسوق السورية. يعتمد على درجات الأخضر الياسميني الفاخر مع لون البيج الهادئ واللمسات الذهبية العتيقة.</p>
                    </div>
                    
                    <div style="display:flex; gap:10px; border-top:1px solid var(--border-color); padding-top:12px; align-items:center;">
                      <button class="btn btn-primary btn-sm" id="btn-activate-jasmine" style="flex:2;"><i class="fas fa-check"></i> تم التفعيل كافتراضي</button>
                      <a href="storefront.html?preview=jasmine" target="_blank" class="btn btn-secondary btn-sm" style="flex:1.2; display:flex; align-items:center; justify-content:center; gap:6px; text-decoration:none; height:36px; padding:0 8px; font-size:12px; font-weight:600;"><i class="fas fa-eye"></i> معاينة</a>
                      <button class="btn btn-secondary btn-sm btn-icon" title="خصائص المظهر والتخصيص"><i class="fas fa-cog"></i></button>
                    </div>
                  </div>
                </div>

                <!-- Ella Theme Card -->
                <div style="border:1px solid var(--border-color); border-radius:var(--border-radius); overflow:hidden; background-color:var(--bg-secondary); display:flex; flex-direction:column; box-shadow:0 4px 6px rgba(0,0,0,0.05); transition:transform 0.2s; cursor:pointer;" class="theme-card" id="theme-card-ella">
                  <!-- Visual Preview Mockup -->
                  <div style="height:160px; background: linear-gradient(135deg, #111111 0%, #232323 100%); padding:20px; display:flex; flex-direction:column; justify-content:space-between; color:#ffffff; position:relative;">
                    <div style="font-family:'Jost', sans-serif; font-size:24px; font-weight:800; border-bottom:1px solid rgba(255,255,255,0.2); padding-bottom:5px; text-transform:uppercase; letter-spacing:1px;">ELLA FASHION STORE</div>
                    <div style="font-size:11px; opacity:0.8; line-height:1.4;">
                      • تصميم مسطح وعصري عالي التباين<br>
                      • متوافق بالكامل مع متجر Ella 7 العالمي<br>
                      • خيارات عرض مقاسات وألوان وتصفية ذكية
                    </div>
                    <span class="badge badge-success" id="badge-ella" style="position:absolute; top:15px; left:15px; background-color:#d12442; color:#ffffff; font-weight:700; display:none;">نشط حالياً</span>
                  </div>
                  
                  <!-- Info Footer -->
                  <div style="padding:16px; display:flex; flex-direction:column; gap:12px; flex-grow:1; justify-content:space-between;">
                    <div>
                      <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h4 style="font-weight:700; font-size:15px; color:var(--text-primary);">ثيم إيلا للألبسة والموضة (Ella Theme)</h4>
                        <span style="font-size:11px; color:#10b981; font-weight:700;">متاح مجاناً</span>
                      </div>
                      <p style="font-size:12px; color:var(--text-muted); margin-top:6px; line-height:1.4;">التصميم العالمي الشهير للملابس والأزياء. يعتمد على تبويبات متباينة باللون الأسود والأبيض الجريء مع خطوط Jost وعرض أنيق للمقاسات.</p>
                    </div>
                    
                    <div style="display:flex; gap:10px; border-top:1px solid var(--border-color); padding-top:12px; align-items:center;">
                      <button class="btn btn-primary btn-sm" id="btn-activate-ella" style="flex:2;"><i class="fas fa-toggle-on"></i> تفعيل كافتراضي للمتجر</button>
                      <a href="storefront.html?preview=ella" target="_blank" class="btn btn-secondary btn-sm" style="flex:1.2; display:flex; align-items:center; justify-content:center; gap:6px; text-decoration:none; height:36px; padding:0 8px; font-size:12px; font-weight:600;"><i class="fas fa-eye"></i> معاينة</a>
                      <button class="btn btn-secondary btn-sm btn-icon" title="خصائص المظهر والتخصيص"><i class="fas fa-cog"></i></button>
                    </div>
                  </div>
                </div>

                <!-- WoodMart Theme Card -->
                <div style="border:1px solid var(--border-color); border-radius:var(--border-radius); overflow:hidden; background-color:var(--bg-secondary); display:flex; flex-direction:column; box-shadow:0 4px 6px rgba(0,0,0,0.05); transition:transform 0.2s; cursor:pointer;" class="theme-card" id="theme-card-woodmart">
                  <!-- Visual Preview Mockup -->
                  <div style="height:160px; background: linear-gradient(135deg, #0b1329 0%, #1c2541 100%); padding:20px; display:flex; flex-direction:column; justify-content:space-between; color:#ffffff; position:relative;">
                    <div style="font-family:'Outfit', sans-serif; font-size:24px; font-weight:900; color:#ffcc00; border-bottom:1px solid rgba(255,255,255,0.2); padding-bottom:5px; text-transform:uppercase; letter-spacing:1px;">WOODMART MEGA ELECTRONICS</div>
                    <div style="font-size:11px; opacity:0.9; line-height:1.4; color: #e0e0e0;">
                      • شريط جانبي دائم للأقسام والفئات الإلكترونية<br>
                      • شريط بحث عريض مع فلترة ذكية وتصاميم حديثة للمنتجات<br>
                      • عرض تفاعلي لمستوى المخزون وتقييمات المراجعات النجمية
                    </div>
                    <span class="badge badge-success" id="badge-woodmart" style="position:absolute; top:15px; left:15px; background-color:#ffcc00; color:#0b1329; font-weight:700; display:none;">نشط حالياً</span>
                  </div>
                  
                  <!-- Info Footer -->
                  <div style="padding:16px; display:flex; flex-direction:column; gap:12px; flex-grow:1; justify-content:space-between;">
                    <div>
                      <div style="display:flex; justify-content:space-between; align-items:center;">
                        <h4 style="font-weight:700; font-size:15px; color:var(--text-primary);">ثيم وودمارت للإلكترونيات (WoodMart Theme)</h4>
                        <span style="font-size:11px; color:#10b981; font-weight:700;">متاح مجاناً</span>
                      </div>
                      <p style="font-size:12px; color:var(--text-muted); margin-top:6px; line-height:1.4;">قالب التجارة الإلكترونية الأكثر مبيعاً للإلكترونيات والأجهزة المنزلية. يعتمد على تصميم رصين باللون الكحلي الداكن والأصفر الساطع، ومثالي لعرض مواصفات الأجهزة وتقييماتها ومخزونها.</p>
                    </div>
                    
                    <div style="display:flex; gap:10px; border-top:1px solid var(--border-color); padding-top:12px; align-items:center;">
                      <button class="btn btn-primary btn-sm" id="btn-activate-woodmart" style="flex:2;"><i class="fas fa-toggle-on"></i> تفعيل كافتراضي للمتجر</button>
                      <a href="storefront.html?preview=woodmart" target="_blank" class="btn btn-secondary btn-sm" style="flex:1.2; display:flex; align-items:center; justify-content:center; gap:6px; text-decoration:none; height:36px; padding:0 8px; font-size:12px; font-weight:600;"><i class="fas fa-eye"></i> معاينة</a>
                      <button class="btn btn-secondary btn-sm btn-icon" title="خصائص المظهر والتخصيص"><i class="fas fa-cog"></i></button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 24: APPS MARKETPLACE -->
        <section id="apps-marketplace-section" class="view-section">
          <div class="settings-grid">
            <div class="settings-card" style="grid-column:1/-1; display:flex; flex-direction:column; gap:20px;">
              <div style="border-bottom:1px solid var(--border-color); padding-bottom:15px;">
                <h3 style="font-weight:800; color:var(--text-primary); font-size:18px;"><i class="fas fa-cubes" style="color:hsla(var(--primary),1); margin-inline-end:8px;"></i> سوق ملحقات وتطبيقات متجر (Apps Marketplace)</h3>
                <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">تصفح وثبت تطبيقات إضافية لربط المتاجر الإلكترونية وتوسيع وظائف نظام الـ ERP والمخازن</p>
              </div>

              <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:25px;" id="apps-marketplace-list">
                <!-- Dynamically loaded app cards -->
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 25: INSTALLED APPS -->
        <section id="apps-installed-section" class="view-section">
          <div class="settings-grid">
            <div class="settings-card" style="grid-column:1/-1; display:flex; flex-direction:column; gap:20px;">
              <div style="border-bottom:1px solid var(--border-color); padding-bottom:15px;">
                <h3 style="font-weight:800; color:var(--text-primary); font-size:18px;"><i class="fas fa-check-double" style="color:hsla(var(--primary),1); margin-inline-end:8px;"></i> تطبيقاتك النشطة والمثبتة (Installed Apps)</h3>
                <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">إدارة وحذف وتهيئة الإضافات النشطة في نظام متجرك الإلكتروني</p>
              </div>

              <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:25px;" id="apps-installed-list">
                <!-- Dynamically loaded installed app cards -->
              </div>
            </div>
          </div>
        </section>
        
        <!-- SECTION 26: ECOMMERCE DASHBOARD -->
        <section id="ecommerce-dashboard-section" class="view-section">
          <!-- KPIs Statistics Cards -->
          <div class="stats-grid">
            <div class="stat-card" style="border-bottom: 4px solid hsla(260, 60%, 50%, 1);">
              <div class="stat-info">
                <span class="stat-title">إيرادات المتجر الإلكتروني</span>
                <span class="stat-value" id="eco-kpi-sales">$0</span>
                <span class="stat-trend trend-up">
                  <i class="fas fa-arrow-up"></i> 18.3%
                </span>
              </div>
              <div class="stat-icon" style="color: hsla(260, 60%, 50%, 1); background-color: hsla(260, 60%, 50%, 0.1);">
                <i class="fas fa-wallet"></i>
              </div>
            </div>
            
            <div class="stat-card" style="border-bottom: 4px solid #10b981;">
              <div class="stat-info">
                <span class="stat-title">متوسط قيمة السلة (AOV)</span>
                <span class="stat-value" id="eco-kpi-aov">$0</span>
                <span class="stat-trend trend-up">
                  <i class="fas fa-arrow-up"></i> 5.2%
                </span>
              </div>
              <div class="stat-icon success">
                <i class="fas fa-shopping-basket"></i>
              </div>
            </div>
            
            <div class="stat-card" style="border-bottom: 4px solid #06b6d4;">
              <div class="stat-info">
                <span class="stat-title">معدل التحويل (CR)</span>
                <span class="stat-value" id="eco-kpi-conversion">2.85%</span>
                <span class="stat-trend trend-up">
                  <i class="fas fa-arrow-up"></i> 0.4%
                </span>
              </div>
              <div class="stat-icon info">
                <i class="fas fa-percentage"></i>
              </div>
            </div>
            
            <div class="stat-card" style="border-bottom: 4px solid #f59e0b;">
              <div class="stat-info">
                <span class="stat-title">نسبة السلات المتروكة</span>
                <span class="stat-value" id="eco-kpi-abandoned">64.2%</span>
                <span class="stat-trend trend-down" style="color: #ef4444; background-color: rgba(239, 68, 68, 0.1);">
                  تحتاج تحسين
                </span>
              </div>
              <div class="stat-icon warning">
                <i class="fas fa-shopping-cart"></i>
              </div>
            </div>

            <div class="stat-card" style="border-bottom: 4px solid #f43f5e;">
              <div class="stat-info">
                <span class="stat-title">إجمالي زيارات الموقع</span>
                <span class="stat-value" id="eco-kpi-traffic">12,850</span>
                <span class="stat-trend trend-up" style="color: #10b981; background-color: rgba(16, 185, 129, 0.1);">
                  +14% هذا الأسبوع
                </span>
              </div>
              <div class="stat-icon danger">
                <i class="fas fa-eye"></i>
              </div>
            </div>
          </div>
          
          <!-- Charts and Analytics Grid -->
          <div class="dashboard-grid">
            <!-- Line Chart: Sales and CR -->
            <div class="dashboard-card">
              <div class="card-header">
                <h3 class="card-title">
                  <i class="fas fa-chart-line" style="color: hsla(260, 60%, 50%, 1);"></i>
                  مخطط حركة المبيعات ومعدل التحويل اليومي للمتجر
                </h3>
              </div>
              <div class="card-body" style="position: relative; height: 320px;">
                <canvas id="ecommerceSalesChart"></canvas>
              </div>
            </div>
            
            <!-- Doughnut Chart: Traffic sources -->
            <div class="dashboard-card">
              <div class="card-header">
                <h3 class="card-title">
                  <i class="fas fa-chart-pie" style="color: #06b6d4;"></i>
                  مصادر ترافيك وزيارات العملاء للمتجر
                </h3>
              </div>
              <div class="card-body" style="position: relative; height: 320px;">
                <canvas id="trafficSourceChart"></canvas>
              </div>
            </div>
          </div>
          
          <!-- Bottom Details Tables -->
          <div class="dashboard-grid" style="grid-template-columns: 1fr 1fr;">
            <!-- Top Viewed Products -->
            <div class="dashboard-card">
              <div class="card-header">
                <h3 class="card-title">
                  <i class="fas fa-fire" style="color: #f43f5e;"></i>
                  المنتجات الأكثر مشاهدة وتفضيلاً بالمتجر
                </h3>
              </div>
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>المنتج والكود</th>
                      <th style="text-align: center;">عدد المشاهدات</th>
                      <th style="text-align: center;">معدل الشراء</th>
                      <th>حالة التوفر</th>
                    </tr>
                  </thead>
                  <tbody id="eco-top-viewed-tbody">
                    <!-- Loaded dynamically -->
                  </tbody>
                </table>
              </div>
            </div>
            
            <!-- Top Coupons Used -->
            <div class="dashboard-card">
              <div class="card-header">
                <h3 class="card-title">
                  <i class="fas fa-ticket-alt" style="color: hsla(260, 60%, 50%, 1);"></i>
                  كوبونات الخصم الأكثر فعالية واستخداماً
                </h3>
              </div>
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>رمز الكوبون</th>
                      <th style="text-align: center;">قيمة الخصم</th>
                      <th style="text-align: center;">مرات الاستخدام</th>
                      <th>الحالة</th>
                    </tr>
                  </thead>
                  <tbody id="eco-top-coupons-tbody">
                    <!-- Loaded dynamically -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 27: ECOMMERCE COUPONS -->
        <section id="ecommerce-coupons-section" class="view-section">
          <div class="toolbar" style="margin-bottom: 20px;">
            <div class="page-title-area">
              <h3 style="font-weight: 700; color: var(--text-primary); font-size: 20px;">إدارة كوبونات الخصم والتسويق</h3>
              <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">توليد كوبونات خصم مخصصة للعملاء لزيادة مبيعات المتجر الإلكتروني وتتبع استخدامها</p>
            </div>
          </div>
          
          <div class="categories-layout" style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
            <!-- Left Side: Add Coupon Form -->
            <div class="dashboard-card" style="padding: 24px;">
              <h4 style="font-weight: 700; margin-bottom: 16px; font-size: 15px; color: var(--text-primary); border-bottom: 1px solid var(--border-color); padding-bottom: 8px;">إضافة كوبون جديد</h4>
              <form id="add-coupon-form">
                <div class="form-group">
                  <label class="form-label">كود الكوبون <span style="color: hsla(var(--danger), 1);">*</span></label>
                  <input type="text" class="form-control" id="coupon-code-input" placeholder="مثال: WELCOME10" required autocomplete="off" style="text-transform: uppercase;">
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">نوع الخصم</label>
                    <select class="form-control" id="coupon-type-select">
                      <option value="percentage">نسبة مئوية (%)</option>
                      <option value="fixed">مبلغ ثابت ($)</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label class="form-label">قيمة الخصم <span style="color: hsla(var(--danger), 1);">*</span></label>
                    <input type="number" class="form-control" id="coupon-value-input" required min="1" step="0.01" placeholder="10">
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-group">
                    <label class="form-label">الحد الأدنى للشراء ($)</label>
                    <input type="number" class="form-control" id="coupon-min-input" min="0" value="0" placeholder="0">
                  </div>
                  <div class="form-group">
                    <label class="form-label">تاريخ الانتهاء</label>
                    <input type="date" class="form-control" id="coupon-expiry-input" value="2026-12-31">
                  </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; font-weight: 700; padding: 12px 20px; background-color: hsla(260, 60%, 50%, 1); border: none;">
                  <i class="fas fa-plus"></i> إضافة الكوبون وتفعيله
                </button>
              </form>
            </div>
            
            <!-- Right Side: Coupon list Table -->
            <div class="dashboard-card">
              <div class="card-header">
                <h4 class="card-title"><i class="fas fa-ticket-alt" style="color: hsla(260, 60%, 50%, 1);"></i> كوبونات الخصم النشطة بالمتجر</h4>
              </div>
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>رمز الكوبون</th>
                      <th>نوع الخصم</th>
                      <th style="text-align: center;">قيمة الخصم</th>
                      <th style="text-align: center;">الحد الأدنى للشراء</th>
                      <th style="text-align: center;">تاريخ الصلاحية</th>
                      <th style="text-align: center;">مرات الاستخدام</th>
                      <th>إجراءات</th>
                    </tr>
                  </thead>
                  <tbody id="eco-coupons-table-body">
                    <!-- Dynamic coupons rows loaded dynamically -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 28: ECOMMERCE REVIEWS -->
        <section id="ecommerce-reviews-section" class="view-section">
          <div class="toolbar" style="margin-bottom: 20px;">
            <div class="page-title-area">
              <h3 style="font-weight: 700; color: var(--text-primary); font-size: 20px;">مراجعات وتقييمات العملاء للمنتجات</h3>
              <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">مراجعة وتقييم وتدقيق التعليقات المكتوبة من قبل عملاء المتجر الإلكتروني واعتمادها للنشر أو حظرها</p>
            </div>
          </div>
          
          <div class="dashboard-card">
            <div class="card-header">
              <h4 class="card-title"><i class="far fa-star" style="color: #f59e0b;"></i> سجل مراجعات وتقييمات العملاء الواردة مؤخراً</h4>
            </div>
            <div class="table-container">
              <table class="custom-table">
                <thead>
                  <tr>
                    <th>اسم العميل والتاريخ</th>
                    <th>المنتج المتأثر</th>
                    <th style="text-align: center;">التقييم</th>
                    <th>محتوى المراجعة والتعليق</th>
                    <th>حالة المراجعة</th>
                    <th>إجراءات الإشراف</th>
                  </tr>
                </thead>
                <tbody id="eco-reviews-table-body">
                  <!-- Dynamic reviews loaded dynamically -->
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- SECTION 29: PRODUCT DETAILS (ADMIN VIEW) -->
        <section id="product-details-section" class="view-section">
          <!-- Header with back navigation -->
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 14px; color: var(--text-muted);">المنتجات</span>
              <i class="fas fa-chevron-left" style="font-size: 11px; color: var(--text-muted);"></i>
              <span style="font-weight: 700; color: var(--text-primary);" id="prod-detail-breadcrumb">تفاصيل المنتج</span>
            </div>
            <button class="btn btn-secondary btn-sm" id="btn-prod-detail-back" type="button" onclick="import('./js/app.js').then(m => m.navigateToView('inventory'))">
              <i class="fas fa-arrow-right"></i> عودة للمستودع
            </button>
          </div>

          <div class="woo-editor-container" style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
            <!-- Right Column: Product Image & Quick Info (1/3 width) -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
              <!-- Image card -->
              <div class="dashboard-card" style="padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 16px; border: 1px solid var(--border-color);">
                <div id="admin-prod-detail-img-box" style="width: 100%; height: 220px; border-radius: 8px; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color); overflow: hidden;">
                  <!-- Rendered via JS -->
                </div>
                <div style="text-align: center; width: 100%;">
                  <span id="admin-prod-detail-cat" class="badge badge-success" style="font-size: 11px; margin-bottom: 6px;"></span>
                  <h3 id="admin-prod-detail-name" style="font-size: 16px; font-weight: 700; color: var(--text-primary); margin: 4px 0 8px 0; line-height: 1.4;"></h3>
                  <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);" id="admin-prod-detail-sku"></div>
                </div>
              </div>

              <!-- General Stats card -->
              <div class="dashboard-card" style="padding: 20px; display: flex; flex-direction: column; gap: 12px; border: 1px solid var(--border-color);">
                <h4 style="font-size: 13px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin: 0; color: var(--text-primary);">تفاصيل التسعير والربح</h4>
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                  <span style="color: var(--text-muted);">سعر البيع:</span>
                  <strong style="color: var(--text-primary); font-family: var(--font-english);" id="admin-prod-detail-price"></strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                  <span style="color: var(--text-muted);">التكلفة:</span>
                  <strong style="color: var(--text-primary); font-family: var(--font-english);" id="admin-prod-detail-cost"></strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px; border-top: 1px dashed var(--border-color); padding-top: 8px;">
                  <span style="color: var(--text-muted);">هامش الربح المتوقع:</span>
                  <strong style="color: hsla(var(--success), 1); font-family: var(--font-english);" id="admin-prod-detail-margin"></strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                  <span style="color: var(--text-muted);">قنوات البيع النشطة:</span>
                  <div id="admin-prod-detail-channels" style="display: flex; gap: 3px; flex-wrap: wrap; justify-content: flex-end;"></div>
                </div>
              </div>
            </div>

            <!-- Left Column: KPIs & History (2/3 width) -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
              <!-- KPIs Grid -->
              <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="dashboard-card" style="padding: 16px; border: 1px solid var(--border-color); text-align: center;">
                  <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;"><i class="fas fa-shopping-basket"></i> إجمالي مبيعات المنتج</div>
                  <div style="font-size: 18px; font-weight: 800; color: var(--text-primary); font-family: var(--font-english);" id="admin-prod-kpi-sales-count">0</div>
                </div>
                <div class="dashboard-card" style="padding: 16px; border: 1px solid var(--border-color); text-align: center;">
                  <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;"><i class="fas fa-dollar-sign"></i> الإيرادات المحققة</div>
                  <div style="font-size: 18px; font-weight: 800; color: hsla(var(--success), 1); font-family: var(--font-english);" id="admin-prod-kpi-revenue">0</div>
                </div>
                <div class="dashboard-card" style="padding: 16px; border: 1px solid var(--border-color); text-align: center;">
                  <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 4px;"><i class="fas fa-boxes"></i> المخزون المتوفر</div>
                  <div style="font-size: 18px; font-weight: 800; color: var(--text-primary); font-family: var(--font-english);" id="admin-prod-kpi-stock">0</div>
                </div>
              </div>

              <!-- Product Descriptions -->
              <div class="dashboard-card" style="padding: 20px; border: 1px solid var(--border-color);">
                <h4 style="font-size: 13px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin: 0 0 12px 0; color: var(--text-primary);">نبذة ووصف المنتج</h4>
                <div style="margin-bottom: 12px;">
                  <h5 style="font-size: 11px; color: var(--text-muted); margin: 0 0 4px 0;">الوصف القصير:</h5>
                  <p id="admin-prod-detail-short-desc" style="margin: 0; font-size: 13px; color: var(--text-primary); line-height: 1.5;"></p>
                </div>
                <div>
                  <h5 style="font-size: 11px; color: var(--text-muted); margin: 0 0 4px 0;">الوصف التفصيلي:</h5>
                  <p id="admin-prod-detail-full-desc" style="margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.5; white-space: pre-line;"></p>
                </div>
              </div>

              <!-- Sales History Table -->
              <div class="dashboard-card" style="padding: 20px; border: 1px solid var(--border-color);">
                <h4 style="font-size: 13px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin: 0 0 12px 0; color: var(--text-primary);">حركة مبيعات المنتج الفورية</h4>
                <div class="table-container" style="max-height: 250px; overflow-y: auto;">
                  <table class="custom-table" style="font-size: 12px;">
                    <thead>
                      <tr>
                        <th>رقم الطلب</th>
                        <th>التاريخ</th>
                        <th>العميل</th>
                        <th>الكمية المباعة</th>
                        <th>سعر البيع</th>
                        <th>الإجمالي</th>
                        <th>الحالة</th>
                      </tr>
                    </thead>
                    <tbody id="admin-prod-detail-sales-tbody">
                      <!-- Rendered via JS -->
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Product Reviews List in Admin -->
              <div class="dashboard-card" style="padding: 20px; border: 1px solid var(--border-color);">
                <h4 style="font-size: 13px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin: 0 0 12px 0; color: var(--text-primary);">تقييمات ومراجعات العملاء</h4>
                <div id="admin-prod-detail-reviews-list" style="display: flex; flex-direction: column; gap: 12px;">
                  <!-- Rendered via JS -->
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- SECTION 30: CUSTOMER CRM VIEW -->
        <section id="ecommerce-customers-detail-section" class="view-section">
          <!-- Header with back navigation -->
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <span style="font-size: 14px; color: var(--text-muted);">العملاء</span>
              <i class="fas fa-chevron-left" style="font-size: 11px; color: var(--text-muted);"></i>
              <span style="font-weight: 700; color: var(--text-primary);" id="customer-detail-breadcrumb">ملف العميل CRM</span>
            </div>
            <button class="btn btn-secondary btn-sm" id="btn-customer-detail-back" type="button" onclick="import('./js/app.js').then(m => m.navigateToView('ecommerce-customers'))">
              <i class="fas fa-arrow-right"></i> عودة لدليل العملاء
            </button>
          </div>

          <div class="woo-editor-container" style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; align-items: start;">
            <!-- Right Column: Customer Card & Profile (1/3 width) -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
              <div class="dashboard-card" style="padding: 24px 20px; display: flex; flex-direction: column; align-items: center; gap: 16px; border: 1px solid var(--border-color); text-align: center;">
                <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, hsla(var(--primary), 0.2), hsla(var(--primary), 0.05)); display: flex; align-items: center; justify-content: center; color: hsla(var(--primary), 1); font-size: 32px; border: 2px solid hsla(var(--primary), 0.2);">
                  <i class="fas fa-user"></i>
                </div>
                <div style="width: 100%;">
                  <h3 id="crm-cust-name" style="font-size: 18px; font-weight: 700; color: var(--text-primary); margin: 0 0 4px 0;"></h3>
                  <div style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english); margin-bottom: 12px;" id="crm-cust-email"></div>
                  
                  <span id="crm-cust-tier" class="badge badge-success" style="font-size: 11px; padding: 4px 10px;">عميل فعال</span>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 16px; width: 100%; display: flex; flex-direction: column; gap: 10px; text-align: right; font-size: 13px;">
                  <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);"><i class="fas fa-phone"></i> رقم الجوال:</span>
                    <strong style="color: var(--text-primary); font-family: var(--font-english);" id="crm-cust-phone"></strong>
                  </div>
                  <div style="display: flex; justify-content: space-between;">
                    <span style="color: var(--text-muted);"><i class="fas fa-star" style="color: #f59e0b;"></i> نقاط الولاء:</span>
                    <strong style="color: var(--text-primary); font-family: var(--font-english);" id="crm-cust-points">0 نقطة</strong>
                  </div>
                  <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                    <span style="color: var(--text-muted);"><i class="fas fa-medal"></i> تصنيف العميل:</span>
                    <select id="crm-cust-tier-select" class="form-control" style="width: 110px; height: 28px; padding: 0 8px; font-size: 11px;">
                      <option value="standard">افتراضي</option>
                      <option value="vip">عميل VIP</option>
                      <option value="loyal">عميل وفي</option>
                      <option value="lead">عميل جديد</option>
                    </select>
                  </div>
                </div>
              </div>

              <!-- LTV stats card -->
              <div class="dashboard-card" style="padding: 20px; display: flex; flex-direction: column; gap: 12px; border: 1px solid var(--border-color);">
                <h4 style="font-size: 13px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin: 0; color: var(--text-primary);">مؤشرات القيمة المالية للعميل</h4>
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                  <span style="color: var(--text-muted);">القيمة الإجمالية للمشتريات (LTV):</span>
                  <strong style="color: hsla(var(--success), 1); font-family: var(--font-english);" id="crm-cust-ltv">$0</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                  <span style="color: var(--text-muted);">إجمالي الطلبات الناجحة:</span>
                  <strong style="color: var(--text-primary); font-family: var(--font-english);" id="crm-cust-orders-count">0</strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 13px;">
                  <span style="color: var(--text-muted);">متوسط قيمة الطلب (AOV):</span>
                  <strong style="color: var(--text-primary); font-family: var(--font-english);" id="crm-cust-aov">$0</strong>
                </div>
              </div>
            </div>

            <!-- Left Column: CRM Actions & Order History (2/3 width) -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
              <!-- CRM notes & interactions logs -->
              <div class="dashboard-card" style="padding: 20px; border: 1px solid var(--border-color);">
                <h4 style="font-size: 13px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin: 0 0 12px 0; color: var(--text-primary);"><i class="fas fa-sticky-note" style="color: #eab308; margin-inline-end: 6px;"></i>سجل الملاحظات والتفاعل والتسويق (CRM)</h4>
                
                <!-- Add new CRM note -->
                <form id="crm-add-note-form" style="display: flex; gap: 12px; margin-bottom: 16px;">
                  <input type="text" id="crm-new-note-text" required class="form-control" placeholder="اكتب ملاحظة جديدة للعميل... (مثل: يفضل الشحن بالبريد السريع، طلب كود خصم خاص، إلخ)" style="flex-grow: 1;">
                  <button type="submit" class="btn btn-primary" style="padding: 0 16px; font-size: 12px; display: flex; align-items: center; gap: 6px;">
                    <i class="fas fa-plus"></i> إضافة ملاحظة
                  </button>
                </form>

                <!-- Notes timeline -->
                <div id="crm-notes-timeline" style="max-height: 200px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
                  <!-- Rendered via JS -->
                </div>
              </div>

              <!-- Customer Order History Table -->
              <div class="dashboard-card" style="padding: 20px; border: 1px solid var(--border-color);">
                <h4 style="font-size: 13px; font-weight: 700; border-bottom: 1px solid var(--border-color); padding-bottom: 8px; margin: 0 0 12px 0; color: var(--text-primary);">سجل طلبات العميل وفواتيره</h4>
                <div class="table-container" style="max-height: 300px; overflow-y: auto;">
                  <table class="custom-table" style="font-size: 12px;">
                    <thead>
                      <tr>
                        <th>رقم الطلب</th>
                        <th>التاريخ</th>
                        <th>المنتجات المطلوبة</th>
                        <th>الإجمالي الكلي</th>
                        <th>حالة الطلب</th>
                        <th>إجراءات</th>
                      </tr>
                    </thead>
                    <tbody id="crm-cust-orders-tbody">
                      <!-- Rendered via JS -->
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <!-- MODALS PORTAL -->

  <!-- Modal: Quick Stock Adjustment -->
  <div class="modal-overlay" id="stock-adjust-modal">
    <div class="modal-container" style="max-width: 480px;">
      <div class="modal-header">
        <h3 class="modal-title">تعديل سريع للمخزون</h3>
        <button class="modal-close">&times;</button>
      </div>
      <form id="stock-adjust-form">
        <div class="modal-body">
          <p style="margin-bottom: 16px; font-size: 14px;">
            أنت تقوم بتعديل مخزون المنتج: <strong id="adjust-prod-name" style="color: hsla(var(--primary), 1);"></strong>
          </p>
          <p style="margin-bottom: 20px; font-size: 13px; color: var(--text-muted);">
            المخزون الحالي المتوفر: <strong id="adjust-current-stock" style="font-family: var(--font-english);">0</strong> قطع.
          </p>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">نوع الحركة</label>
              <select class="form-control" id="adjust-type">
                <option value="add">إضافة كميات واردة (+)</option>
                <option value="subtract">سحب / إنقاص كميات (-)</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">الكمية المراد تعديلها</label>
              <input type="number" class="form-control" id="adjust-amount" required min="1" value="1">
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">سبب التعديل / ملاحظة</label>
            <input type="text" class="form-control" id="adjust-reason" placeholder="مثال: جرد دوري للمخزون، تلفيات...">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary modal-close">إلغاء</button>
          <button type="submit" class="btn btn-primary">تأكيد التعديل</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Order Details Invoice View -->
  <div class="modal-overlay" id="order-detail-modal">
    <div class="modal-container">
      <div class="modal-header">
        <h3 class="modal-title">فاتورة تفاصيل الطلب</h3>
        <button class="modal-close">&times;</button>
      </div>
      <div class="modal-body">
        <div class="invoice-grid">
          <div class="invoice-header">
            <div>
              <h2 style="font-size: 24px; font-weight: 800; color: hsla(var(--primary), 1);"><?php echo htmlspecialchars($store_name); ?></h2>
              <p style="font-size: 12px; color: var(--text-muted);" id="invoice-title-label">فاتورة مبيعات معتمدة</p>
            </div>
            <div style="text-align: left;">
              <h4 style="font-family: var(--font-english); font-size: 16px;">الطلب #<span id="invoice-order-id"></span></h4>
              <p style="font-size: 12px; color: var(--text-muted);"><span id="invoice-date"></span></p>
            </div>
          </div>
          
          <div class="invoice-details">
            <div>
              <span id="invoice-type-label" style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">العميل</span>
              <strong id="invoice-cust-name" style="font-size: 15px;"></strong>
            </div>
            <div>
              <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">الحالة الحالية للطلب</span>
              <span class="badge badge-success" id="invoice-status"></span>
            </div>
          </div>
          
          <div style="margin-top: 16px;">
            <table class="custom-table" style="border: 1px solid var(--border-color);">
              <thead>
                <tr>
                  <th>البند والمنتج</th>
                  <th>سعر الوحدة</th>
                  <th style="text-align: center;">الكمية</th>
                  <th>الإجمالي</th>
                </tr>
              </thead>
              <tbody id="invoice-items-body">
                <!-- Injected order items -->
              </tbody>
            </table>
          </div>
          
          <div class="invoice-summary">
            <div class="invoice-summary-row">
              <span>المجموع الفرعي</span>
              <span id="invoice-subtotal" style="font-family: var(--font-english);">$0</span>
            </div>
            <div class="invoice-summary-row">
              <span>ضريبة القيمة المضافة (15%)</span>
              <span id="invoice-tax" style="font-family: var(--font-english);">$0</span>
            </div>
            <div class="invoice-summary-row total">
              <span>الإجمالي النهائي</span>
              <span id="invoice-total" style="font-family: var(--font-english);">$0</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary modal-close">إغلاق</button>
        <button type="button" class="btn btn-primary" id="btn-print-invoice">
          <i class="fas fa-print"></i> طباعة الفاتورة
        </button>
      </div>
    </div>
  </div>

  <!-- Modal: Quotation Details & Print Preview -->
  <div class="modal-overlay" id="quotation-detail-modal">
    <div class="modal-container">
      <div class="modal-header">
        <h3 class="modal-title">عرض سعر معتمد</h3>
        <button class="modal-close">&times;</button>
      </div>
      <div class="modal-body">
        <div class="invoice-grid">
          <div class="invoice-header">
            <div>
              <h2 style="font-size: 24px; font-weight: 800; color: hsla(200, 95%, 45%, 1);"><?php echo htmlspecialchars($store_name); ?></h2>
              <p style="font-size: 12px; color: var(--text-muted);">عرض سعر معتمد رسمي للعميل</p>
            </div>
            <div style="text-align: left;">
              <h4 style="font-family: var(--font-english); font-size: 16px;">العرض #<span id="quo-detail-id"></span></h4>
              <p style="font-size: 12px; color: var(--text-muted);"><span id="quo-detail-date"></span></p>
            </div>
          </div>
          
          <div class="invoice-details" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin: 20px 0; border-bottom: 1px dashed var(--border-color); padding-bottom: 16px;">
            <div>
              <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">العميل الموجه له العرض</span>
              <strong id="quo-detail-cust-name" style="font-size: 15px;"></strong>
            </div>
            <div>
              <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); display: block; margin-bottom: 4px;">صلاحية العرض لغاية</span>
              <strong id="quo-detail-valid-until" style="font-size: 14px; font-family: var(--font-english); color: hsla(347, 84%, 54%, 1);"></strong>
            </div>
          </div>
          
          <div style="margin-top: 16px;">
            <table class="custom-table" style="border: 1px solid var(--border-color);">
              <thead>
                <tr>
                  <th>البند والمنتج</th>
                  <th>السعر المقترح</th>
                  <th style="text-align: center;">الكمية</th>
                  <th>الإجمالي</th>
                </tr>
              </thead>
              <tbody id="quo-detail-items-body">
                <!-- Injected quotation items -->
              </tbody>
            </table>
          </div>
          
          <div class="invoice-summary">
            <div class="invoice-summary-row">
              <span>المجموع الفرعي</span>
              <span id="quo-detail-subtotal" style="font-family: var(--font-english);">$0</span>
            </div>
            <div class="invoice-summary-row">
              <span>الخصم المقترح</span>
              <span id="quo-detail-discount" style="font-family: var(--font-english);">$0</span>
            </div>
            <div class="invoice-summary-row">
              <span>ضريبة القيمة المضافة (15%)</span>
              <span id="quo-detail-tax" style="font-family: var(--font-english);">$0</span>
            </div>
            <div class="invoice-summary-row total" style="border-top: 2px solid hsla(200, 95%, 45%, 1);">
              <span>الإجمالي النهائي المتوقع</span>
              <span id="quo-detail-total" style="font-family: var(--font-english); color: hsla(200, 95%, 45%, 1); font-weight:800;">$0</span>
            </div>
          </div>
          
          <div id="quo-detail-notes-container" style="margin-top: 20px; padding: 12px; background: var(--bg-tertiary); border-radius: var(--border-radius-xs); display: none;">
            <strong style="font-size: 12px; display: block; margin-bottom: 6px;">ملاحظات وشروط العرض:</strong>
            <p id="quo-detail-notes" style="font-size: 12px; color: var(--text-secondary); margin: 0; line-height: 1.5;"></p>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="display:flex; justify-content: space-between; align-items:center; width:100%;">
        <div style="display:flex; gap:8px;">
          <button type="button" class="btn btn-secondary modal-close">إغلاق</button>
          <button type="button" class="btn btn-primary" id="btn-print-quotation" style="background-color: hsla(200, 95%, 45%, 1); border:none;">
            <i class="fas fa-print"></i> طباعة عرض السعر
          </button>
        </div>
        <button type="button" class="btn btn-success" id="btn-convert-quotation-to-invoice" style="background-color: hsla(142, 70%, 45%, 1); border:none; font-family: var(--font-arabic); display: flex; align-items: center; gap: 6px;">
          <i class="fas fa-file-invoice"></i> تحويل إلى فاتورة مبيعات
        </button>
      </div>
    </div>
  </div>

  <!-- Modal: Change Order Status -->
  <div class="modal-overlay" id="order-status-modal">
    <div class="modal-container" style="max-width: 400px;">
      <div class="modal-header">
        <h3 class="modal-title">تحديث حالة الطلب</h3>
        <button class="modal-close">&times;</button>
      </div>
      <form id="status-update-form">
        <div class="modal-body">
          <p style="margin-bottom: 20px;">
            أنت تقوم بتغيير حالة الطلب رقم <strong style="color: hsla(var(--primary), 1);">#<span id="change-status-order-id"></span></strong>.
          </p>
          <div class="form-group">
            <label class="form-label">اختر الحالة الجديدة للطلب</label>
            <select class="form-control" id="order-status-select">
              <option value="Pending">قيد الانتظار</option>
              <option value="Shipped">تم الشحن وإرسال التتبع</option>
              <option value="Delivered">تم التوصيل واستلام النقد</option>
              <option value="Cancelled">ملغي وإرجاع المخزون</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary modal-close">إلغاء</button>
          <button type="submit" class="btn btn-primary">تأكيد الحالة</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Add Supplier -->
  <div class="modal-overlay" id="supplier-modal">
    <div class="modal-container">
      <div class="modal-header">
        <h3 class="modal-title">إضافة مورد جديد للنظام</h3>
        <button class="modal-close">&times;</button>
      </div>
      <form id="supplier-form">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label">اسم جهة التوريد / الشركة</label>
            <input type="text" class="form-control" id="sup-name" required placeholder="مثال: شركة البرمجيات والعتاد المحدودة">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">اسم المسؤول للتواصل</label>
              <input type="text" class="form-control" id="sup-contact" required placeholder="مثال: م. أحمد المطيري">
            </div>
            <div class="form-group">
              <label class="form-label">فئة المنتجات الموردة</label>
              <input type="text" class="form-control" id="sup-products" required placeholder="مثال: شواحن وهواتف ذكية">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">رقم الهاتف</label>
              <input type="text" class="form-control" id="sup-phone" required placeholder="مثال: +966 500 000 000">
            </div>
            <div class="form-group">
              <label class="form-label">البريد الإلكتروني</label>
              <input type="email" class="form-control" id="sup-email" required placeholder="مثال: supplier@domain.com">
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">العنوان الرئيسي</label>
            <input type="text" class="form-control" id="sup-address" required placeholder="مثال: دمشق، الميدان، مستودع رقم 15">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary modal-close">إلغاء</button>
          <button type="submit" class="btn btn-primary">تأكيد المورد</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: Inbound Shipment Restocking -->
  <div class="modal-overlay" id="inbound-shipment-modal">
    <div class="modal-container" style="max-width: 480px;">
      <div class="modal-header">
        <h3 class="modal-title">توريد شحنة مخزون جديدة</h3>
        <button class="modal-close">&times;</button>
      </div>
      <form id="inbound-shipment-form">
        <div class="modal-body">
          <p style="margin-bottom: 20px; font-size: 14px;">
            المورد الحالي للشحنة: <strong id="shipment-supplier-name" style="color: hsla(var(--primary), 1);"></strong>
          </p>
          
          <div class="form-group">
            <label class="form-label">المنتج المراد توريده للمخزن</label>
            <select class="form-control" id="shipment-product-select">
              <!-- Dynamically populated from JS -->
            </select>
          </div>
          
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">كمية التوريد الجديدة</label>
              <input type="number" class="form-control" id="shipment-qty" required min="1" value="10">
            </div>
            <div class="form-group">
              <label class="form-label">سعر تكلفة الشراء المعتمد</label>
              <input type="number" class="form-control" id="shipment-unit-cost" required min="0" step="0.01">
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">حالة سداد الفاتورة للمورد</label>
            <select class="form-control" id="shipment-payment-status">
              <option value="Paid">مسددة نقداً بالكامل (Paid)</option>
              <option value="Pending">آجل / ذمم دائنة (Unpaid)</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary modal-close">إلغاء</button>
          <button type="submit" class="btn btn-primary">تأكيد استلام الشحنة</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Modal: POS Sale Success Receipt -->
  <div class="modal-overlay" id="pos-receipt-modal">
    <div class="modal-container" style="max-width: 380px;">
      <div class="modal-header">
        <h3 class="modal-title">نجحت عملية البيع | فاتورة بيع</h3>
        <button class="modal-close">&times;</button>
      </div>
      <div class="modal-body" style="padding: 16px;">
        <div class="receipt-container">
          <div class="receipt-header">
            <div class="receipt-store-title"><?php echo htmlspecialchars($store_name); ?></div>
            <div style="font-size: 11px; color: var(--text-muted);">نظام نقاط بيع المستودعات المتكامل</div>
            <div style="font-size: 10px; color: var(--text-muted); margin-top: 4px;" id="receipt-date-label"></div>
          </div>
          
          <div style="font-size: 11px; font-weight: 600; text-align: start; width: 100%; margin-bottom: 8px;">
            رقم الفاتورة: <span id="receipt-order-id-label" style="font-family: var(--font-english);"></span>
          </div>
          
          <table class="receipt-table">
            <thead>
              <tr>
                <th>المنتج</th>
                <th style="text-align: center;">الكمية</th>
                <th style="text-align: left;">الإجمالي</th>
              </tr>
            </thead>
            <tbody id="receipt-items-body">
              <!-- Dynamically populated lines -->
            </tbody>
          </table>
          
          <div class="receipt-divider"></div>
          
          <div class="receipt-total-row">
            <span>المجموع فرعي</span>
            <span id="receipt-subtotal-label" style="font-family: var(--font-english);">$0.00</span>
          </div>
          <div class="receipt-total-row">
            <span>الضريبة (15%)</span>
            <span id="receipt-tax-label" style="font-family: var(--font-english);">$0.00</span>
          </div>
          <div class="receipt-divider"></div>
          <div class="receipt-total-row" style="font-size: 15px; color: hsla(var(--primary), 1);">
            <span>الإجمالي النهائي</span>
            <span id="receipt-total-label" style="font-family: var(--font-english);">$0.00</span>
          </div>
          
          <div class="receipt-footer">
            <p>شكراً لتعاملكم معنا!</p>
            <p style="font-size: 9px; margin-top: 4px;">رقم السجل التجاري: 1010XXXXXX</p>
          </div>
        </div>
      </div>
      <div class="modal-footer" style="justify-content: center; gap: 10px;">
        <button type="button" class="btn btn-secondary modal-close" style="flex: 1;">إغلاق</button>
        <button type="button" class="btn btn-primary" id="btn-print-pos-receipt" style="flex: 1;">
          <i class="fas fa-print"></i> طباعة الإيصال
        </button>
      </div>
    </div>
  </div>

  <!-- Core JavaScript Module -->
  <script type="module" src="js/app.js"></script>
</body>
</html>
