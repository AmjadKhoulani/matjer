<?php
require_once __DIR__ . '/api/config.php';

// Enforce authentication
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Enforce SaaS Super Admin check (tenant_id must be NULL)
if ($_SESSION['tenant_id'] !== null) {
    $tenant = get_active_tenant_details();
    $slug = $tenant ? $tenant['slug'] : 'nova-store';
    header("Location: store-manager.php?tenant=" . urlencode($slug));
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة تحكم المنصة | منصة متجر SaaS</title>
  
  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="css/variables.css?v=3.0">
  <link rel="stylesheet" href="css/style.css?v=3.0">
  <link rel="stylesheet" href="css/components.css?v=3.0">
  
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Chart.js for SaaS Metrics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <div class="app-container">
    
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <a href="index.php" class="logo-container">
          <div class="logo-icon">M</div>
          <span class="logo-text">متجر</span>
        </a>
      </div>
      
      <!-- Dynamic menu depending on perspective (Super Admin or Merchant) -->
      <ul class="sidebar-menu" id="saas-sidebar-menu">
        <!-- Managed dynamically in JS -->
      </ul>
      
      <div class="sidebar-footer" style="display: flex; align-items: center; justify-content: space-between; gap: 8px;">
        <div class="user-profile-summary" style="flex-grow: 1;">
          <div class="user-avatar" id="sidebar-user-avatar">SA</div>
          <div class="user-info">
            <span class="user-name" id="sidebar-user-name">سوبر أدمن</span>
            <span class="user-role" id="sidebar-user-role">الوصول الكامل</span>
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
            <h1 class="page-title" id="active-page-title">نظرة عامة على المنصة</h1>
            <p class="page-subtitle" id="active-page-subtitle">إحصائيات إيرادات المنصة والمتاجر النشطة</p>
          </div>
        </div>
        
        <div class="header-right">
          <!-- Perspective Selector (Crucial for user test request!) -->
          <div style="display: flex; align-items: center; gap: 8px; background-color: var(--bg-tertiary); padding: 6px 12px; border-radius: var(--border-radius-full); border: 1px solid var(--border-color);">
            <span style="font-size: 11px; font-weight: 700; color: var(--text-secondary);">منظور العرض:</span>
            <select id="perspective-selector" class="filter-select" style="padding: 4px 8px; font-size: 12px; border: none; background: transparent; font-weight: 700; color: hsla(var(--primary), 1); cursor: pointer; outline: none;">
              <option value="super-admin">سوبر أدمن (لوحة المنصة)</option>
              <option value="merchant">حساب التاجر (متجرك الرئيسي)</option>
            </select>
          </div>

          <!-- Theme Switcher Button -->
          <button class="header-action-btn theme-toggle-btn" id="btn-theme-toggle" title="تبديل المظهر">
            <i class="fas fa-sun"></i>
          </button>
        </div>
      </header>
      
      <!-- Main Content Dynamic Container -->
      <main class="main-content">
        
        <!-- ================= PERSPECTIVE 1: SUPER ADMIN ================= -->
        <div id="view-super-admin" class="saas-view-wrapper">
          
          <!-- Section: Dashboard Overview (SaaS Stats) -->
          <div id="super-admin-overview" class="saas-section active">
            <div class="stats-grid">
              <div class="stat-card">
                <div class="stat-info">
                  <span class="stat-title">الإيرادات المتكررة شهرياً (MRR)</span>
                  <span class="stat-value" id="kpi-saas-mrr">$8,450</span>
                  <span class="stat-trend trend-up">
                    <i class="fas fa-arrow-up"></i> 14.2%
                  </span>
                </div>
                <div class="stat-icon success">
                  <i class="fas fa-chart-line"></i>
                </div>
              </div>
              
              <div class="stat-card">
                <div class="stat-info">
                  <span class="stat-title">المتاجر المشتركة</span>
                  <span class="stat-value" id="kpi-saas-stores">0</span>
                  <span class="stat-trend trend-up">
                    <i class="fas fa-plus"></i> +3 هذا الأسبوع
                  </span>
                </div>
                <div class="stat-icon primary">
                  <i class="fas fa-store"></i>
                </div>
              </div>
              
              <div class="stat-card">
                <div class="stat-info">
                  <span class="stat-title">متوسط قيمة الاشتراك</span>
                  <span class="stat-value">$78</span>
                  <span class="stat-trend trend-up">
                    مستقر
                  </span>
                </div>
                <div class="stat-icon info">
                  <i class="fas fa-ticket-alt"></i>
                </div>
              </div>
              
              <div class="stat-card">
                <div class="stat-info">
                  <span class="stat-title">تذاكر الدعم المفتوحة</span>
                  <span class="stat-value" style="color: hsla(var(--danger), 1);">2</span>
                  <span class="stat-trend trend-down">
                    تتطلب رد
                  </span>
                </div>
                <div class="stat-icon danger">
                  <i class="fas fa-headset"></i>
                </div>
              </div>
            </div>

            <!-- SaaS Analytics Charts -->
            <div class="dashboard-grid">
              <div class="dashboard-card">
                <div class="card-header">
                  <h3 class="card-title">
                    <i class="fas fa-dollar-sign" style="color: hsla(var(--primary), 1);"></i>
                    نمو إيرادات منصة الـ SaaS شهرياً
                  </h3>
                </div>
                <div class="card-body" style="position: relative; height: 300px;">
                  <canvas id="saasRevenueChart"></canvas>
                </div>
              </div>
              
              <div class="dashboard-card">
                <div class="card-header">
                  <h3 class="card-title">
                    <i class="fas fa-store-alt" style="color: #06b6d4;"></i>
                    نسبة توزيع الباقات
                  </h3>
                </div>
                <div class="card-body" style="position: relative; height: 300px;">
                  <canvas id="saasPlansChart"></canvas>
                </div>
              </div>
            </div>
          </div>

          <!-- Section: Manage Merchant Stores -->
          <div id="super-admin-stores" class="saas-section">
            <div class="toolbar">
              <h3 style="font-weight: 700; color: var(--text-primary);">إدارة المتاجر المشتركة في المنصة</h3>
              <div class="filters-group">
                <input type="text" class="form-control" id="stores-search-input" style="padding: 6px 12px; font-size: 13px;" placeholder="بحث عن متجر...">
              </div>
            </div>
            
            <div class="dashboard-card">
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>اسم المتجر / المالك</th>
                      <th>الباقة الحالية</th>
                      <th>تاريخ الاشتراك</th>
                      <th>قيمة الفاتورة</th>
                      <th>الحالة</th>
                      <th>إجراءات الإشراف</th>
                    </tr>
                  </thead>
                  <tbody id="saas-stores-table-body">
                    <!-- Dynamic stores data -->
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Section: SaaS Admin Settings -->
          <div id="super-admin-settings" class="saas-section">
            <div class="dashboard-card" style="max-width: 600px; margin: 0 auto;">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-sliders-h"></i> إعدادات منصة متجر SaaS</h3>
              </div>
              <div class="card-body">
                <form id="saas-general-settings-form">
                  <div class="form-group">
                    <label class="form-label">اسم المنصة</label>
                    <input type="text" class="form-control" value="منصة متجر للحلول السحابية" required>
                  </div>
                  <div class="form-row">
                    <div class="form-group">
                      <label class="form-label">البريد الإلكتروني للإدارة</label>
                      <input type="email" class="form-control" value="admin@matjer.net" required>
                    </div>
                    <div class="form-group">
                      <label class="form-label">نسبة ضريبة المبيعات الافتراضية (%)</label>
                      <input type="number" class="form-control" value="15" required>
                    </div>
                  </div>
                  <div class="form-group">
                    <label class="form-label">بوابة الدفع الافتراضية للمنصة</label>
                    <select class="form-control">
                      <option value="stripe">سترايب (Stripe)</option>
                      <option value="paytabs">بي تابز (PayTabs)</option>
                      <option value="moyasar">ميسر (Moyasar)</option>
                    </select>
                  </div>
                  <button type="submit" class="btn btn-primary" style="width: 100%;">حفظ إعدادات المنصة</button>
                </form>
              </div>
            </div>
          </div>
          
        </div>

        <!-- ================= PERSPECTIVE 2: MERCHANT ACCOUNT ================= -->
        <div id="view-merchant" class="saas-view-wrapper" style="display: none;">
          
          <!-- Section: Merchant Account Overview -->
          <div id="merchant-overview" class="saas-section">
            <!-- Alert low-billing status -->
            <div class="alert-banner" style="background-color: hsla(var(--success), 0.1); border-color: hsla(var(--success), 0.3);">
              <div class="alert-message">
                <i class="fas fa-check-circle" style="color: hsla(var(--success), 1);"></i>
                <span>حساب متجرك نشط بالكامل. الباقة الحالية: <strong>الباقة الاحترافية (Pro Tier)</strong>. التجديد القادم بعد 24 يوم.</span>
              </div>
            </div>
            
            <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr);">
              <div class="stat-card">
                <div class="stat-info">
                  <span class="stat-title">باقة الاشتراك</span>
                  <span class="stat-value" style="color: hsla(var(--primary), 1); font-size: 24px;">الاحترافية (Pro)</span>
                  <span class="stat-trend trend-up">نشط</span>
                </div>
                <div class="stat-icon primary">
                  <i class="fas fa-crown"></i>
                </div>
              </div>
              
              <div class="stat-card">
                <div class="stat-info">
                  <span class="stat-title">الرسوم الشهرية للباقة</span>
                  <span class="stat-value">$79</span>
                  <span class="stat-trend trend-up">دفع تلقائي نشط</span>
                </div>
                <div class="stat-icon success">
                  <i class="fas fa-credit-card"></i>
                </div>
              </div>
              
              <div class="stat-card">
                <div class="stat-info">
                  <span class="stat-title">فترة صلاحية الاشتراك</span>
                  <span class="stat-value" style="font-size: 24px;">24 يوم متبقية</span>
                  <span class="stat-trend trend-up">ينتهي في 18 يوليو 2026</span>
                </div>
                <div class="stat-icon info">
                  <i class="fas fa-hourglass-half"></i>
                </div>
              </div>
            </div>

            <!-- Launch Merchant Store Control Grid -->
            <div class="dashboard-card" style="margin-bottom: 32px;">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-store"></i> متجرك النشط ونظام مستودعاتك</h3>
              </div>
              <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="display: flex; gap: 16px; align-items: center;">
                  <div style="width: 64px; height: 64px; border-radius: var(--border-radius-md); background: linear-gradient(135deg, hsla(var(--primary), 1), hsla(var(--info), 1)); display: flex; align-items: center; justify-content: center; color: white; font-size: 28px; font-weight: 800;">
                    M
                  </div>
                  <div>
                    <h4 style="font-size: 18px; font-weight: 700;">متجرك الرئيسي</h4>
                    <p style="font-size: 13px; color: var(--text-muted);">رابط متجرك: <a href="store-manager.php" target="_blank" style="color: hsla(var(--primary), 1); text-decoration: underline;">store-manager.php</a></p>
                  </div>
                </div>
                
                <a href="store-manager.php" class="btn btn-primary" style="padding: 12px 28px; font-size: 15px; border-radius: var(--border-radius-md);">
                  <i class="fas fa-external-link-alt"></i>
                  دخول لوحة إدارة المتجر والمستودعات
                </a>
              </div>
            </div>
          </div>

          <!-- Section: Merchant Billing -->
          <div id="merchant-billing" class="saas-section">
            <div class="dashboard-card" style="margin-bottom: 32px;">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-credit-card"></i> طريقة الدفع المسجلة</h3>
              </div>
              <div class="card-body" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div style="display: flex; align-items: center; gap: 16px;">
                  <div style="font-size: 32px; color: #1a1f71;"><i class="fab fa-cc-visa"></i></div>
                  <div>
                    <span style="font-weight: 700; display: block; font-family: var(--font-english);">Visa ending in 4242</span>
                    <span style="font-size: 12px; color: var(--text-muted);">تاريخ الانتهاء: 12/2028</span>
                  </div>
                </div>
                <button class="btn btn-secondary btn-sm">تحديث بطاقة الدفع</button>
              </div>
            </div>
            
            <div class="dashboard-card">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice"></i> الفواتير السابقة</h3>
              </div>
              <div class="table-container">
                <table class="custom-table">
                  <thead>
                    <tr>
                      <th>رقم الفاتورة</th>
                      <th>تاريخ الفاتورة</th>
                      <th>باقة الفاتورة</th>
                      <th>القيمة الإجمالية</th>
                      <th>الحالة</th>
                      <th>تحميل</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td style="font-family: var(--font-english); font-weight: 700;">#INV-2026-003</td>
                      <td>18 يونيو 2026</td>
                      <td>الباقة الاحترافية (Pro)</td>
                      <td style="font-family: var(--font-english); font-weight: 700;">$79.00</td>
                      <td><span class="badge badge-success">تم الدفع</span></td>
                      <td><button class="btn btn-secondary btn-sm btn-icon"><i class="fas fa-download"></i></button></td>
                    </tr>
                    <tr>
                      <td style="font-family: var(--font-english); font-weight: 700;">#INV-2026-002</td>
                      <td>18 مايو 2026</td>
                      <td>الباقة الاحترافية (Pro)</td>
                      <td style="font-family: var(--font-english); font-weight: 700;">$79.00</td>
                      <td><span class="badge badge-success">تم الدفع</span></td>
                      <td><button class="btn btn-secondary btn-sm btn-icon"><i class="fas fa-download"></i></button></td>
                    </tr>
                    <tr>
                      <td style="font-family: var(--font-english); font-weight: 700;">#INV-2026-001</td>
                      <td>18 أبريل 2026</td>
                      <td>الباقة الاحترافية (Pro)</td>
                      <td style="font-family: var(--font-english); font-weight: 700;">$79.00</td>
                      <td><span class="badge badge-success">تم الدفع</span></td>
                      <td><button class="btn btn-secondary btn-sm btn-icon"><i class="fas fa-download"></i></button></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          
        </div>
        
      </main>
    </div>
  </div>

  <!-- Core JavaScript Module -->
  <script type="module" src="js/saas-dashboard.js"></script>
</body>
</html>
