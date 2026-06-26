<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>نوفا ستور | منصة إدارة المتاجر والمستودعات السحابية</title>
  
  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="css/variables.css?v=3.0">
  <link rel="stylesheet" href="css/style.css?v=3.0">
  <link rel="stylesheet" href="css/saas.css?v=3.0">
  <link rel="stylesheet" href="css/components.css?v=3.0">
  
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    /* Registration Modal Styles */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(8px);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      opacity: 0;
      pointer-events: none;
      transition: all 0.3s ease;
    }
    .modal-overlay.active {
      opacity: 1;
      pointer-events: auto;
    }
    .register-modal {
      background: var(--bg-primary);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius-md);
      box-shadow: var(--shadow-lg);
      width: 100%;
      max-width: 500px;
      padding: 32px;
      transform: scale(0.9);
      transition: all 0.3s ease;
    }
    .modal-overlay.active .register-modal {
      transform: scale(1);
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
    }
    .modal-title {
      font-size: 20px;
      font-weight: 800;
      color: var(--text-primary);
    }
    .modal-close {
      background: none;
      border: none;
      color: var(--text-muted);
      cursor: pointer;
      font-size: 20px;
    }
    .modal-close:hover {
      color: var(--text-primary);
    }
  </style>
</head>
<body class="landing-body">

  <!-- Header Navbar -->
  <nav class="landing-nav">
    <a href="index.php" class="logo-container">
      <div class="logo-icon">N</div>
      <span class="logo-text">نوفا ستور</span>
    </a>
    
    <ul class="landing-nav-links">
      <li><a href="features.php" class="landing-nav-link">المميزات</a></li>
      <li><a href="pricing.php" class="landing-nav-link">الأسعار</a></li>
      <li><a href="contact.php" class="landing-nav-link">تواصل معنا</a></li>
    </ul>
    
    <div class="landing-nav-actions">
      <button class="header-action-btn theme-toggle-btn" id="btn-theme-toggle" title="تبديل المظهر">
        <i class="fas fa-moon"></i>
      </button>
      <a href="login.php" class="btn btn-secondary btn-sm">دخول التاجر</a>
      <a href="dashboard.php" class="btn btn-primary btn-sm">لوحة المنصة</a>
    </div>
  </nav>

  <!-- Hero Section -->
  <header class="landing-hero">
    <div class="landing-hero-badge">جديد: منصة SaaS سحابية بميزات إدارة كاملة 🚀</div>
    <h1 class="landing-hero-title">أطلق متجرك السحابي الخاص وتحكم بمستودعاتك بنقرة زر واحدة</h1>
    <p class="landing-hero-desc">المنصة المتكاملة المخصصة للشركات والناشئين. أنشئ متجرك الإلكتروني، جرد مستودعاتك، فعّل نظام كاشير POS المباشر، وتتبع عملائك بكل أمان.</p>
    
    <div class="landing-hero-ctas">
      <button class="btn btn-primary open-register-btn" data-plan="Pro">أنشئ متجرك مجاناً الآن</button>
      <a href="#features" class="btn btn-secondary">استكشف الميزات</a>
    </div>
    
    <!-- Browser Mockup Representation -->
    <div class="landing-mockup-wrapper">
      <div class="landing-mockup-header">
        <div class="landing-mockup-dot red"></div>
        <div class="landing-mockup-dot yellow"></div>
        <div class="landing-mockup-dot green"></div>
        <div style="flex-grow:1; text-align:center; font-size:10px; color:var(--text-muted); font-family:var(--font-english);">dashboard.novastore.saas</div>
      </div>
      <div class="landing-mockup-body" style="background-color: var(--bg-primary); padding: 12px; display:flex; flex-direction:column; gap:12px; height: 380px; justify-content: flex-start; overflow: hidden;">
        <!-- Mockup layout of dashboard -->
        <div style="display:flex; justify-content:space-between; align-items:center; width:100%; border-bottom: 1px solid var(--border-color); padding-bottom:8px;">
          <div style="font-weight:700; font-size:12px;">لوحة تحكم المنصة</div>
          <div style="width:60px; height:18px; border-radius:10px; background-color:hsla(var(--primary), 0.1); display:flex; align-items:center; justify-content:center; font-size:8px; color:hsla(var(--primary),1); font-weight:700;">نشط</div>
        </div>
        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:12px; width:100%;">
          <div style="padding:10px; background-color:var(--bg-secondary); border:1px solid var(--border-color); border-radius:8px; text-align:start;">
            <div style="font-size:8px; color:var(--text-muted);">المبيعات الكلية</div>
            <div style="font-size:12px; font-weight:700; color:var(--text-primary); margin-top:2px;">$18,430</div>
          </div>
          <div style="padding:10px; background-color:var(--bg-secondary); border:1px solid var(--border-color); border-radius:8px; text-align:start;">
            <div style="font-size:8px; color:var(--text-muted);">المنتجات المتاحة</div>
            <div style="font-size:12px; font-weight:700; color:var(--text-primary); margin-top:2px;">86 قطعة</div>
          </div>
          <div style="padding:10px; background-color:var(--bg-secondary); border:1px solid var(--border-color); border-radius:8px; text-align:start;">
            <div style="font-size:8px; color:var(--text-muted);">الطلب نشط</div>
            <div style="font-size:12px; font-weight:700; color:var(--text-primary); margin-top:2px;">5 طلبات</div>
          </div>
          <div style="padding:10px; background-color:var(--bg-secondary); border:1px solid var(--border-color); border-radius:8px; text-align:start;">
            <div style="font-size:8px; color:var(--text-muted);">المستودعات المرتبطة</div>
            <div style="font-size:12px; font-weight:700; color:var(--text-primary); margin-top:2px;">3 مخازن</div>
          </div>
        </div>
        
        <div style="display:flex; gap:12px; width:100%; height:180px;">
          <div style="flex:2; background-color:var(--bg-secondary); border:1px solid var(--border-color); border-radius:8px; padding:12px; display:flex; flex-direction:column; justify-content:space-between;">
            <div style="font-size:9px; font-weight:700; text-align:start;">مبيعات فروع التجزئة هذا الشهر</div>
            <div style="display:flex; align-items:flex-end; gap:8px; height:120px; justify-content:space-between; padding-top:10px;">
              <div style="flex:1; height:30%; background-color:hsla(var(--primary),0.3); border-radius:4px;"></div>
              <div style="flex:1; height:45%; background-color:hsla(var(--primary),0.3); border-radius:4px;"></div>
              <div style="flex:1; height:60%; background-color:hsla(var(--primary),0.3); border-radius:4px;"></div>
              <div style="flex:1; height:40%; background-color:hsla(var(--primary),0.3); border-radius:4px;"></div>
              <div style="flex:1; height:80%; background-color:hsla(var(--primary),1); border-radius:4px;"></div>
              <div style="flex:1; height:70%; background-color:hsla(var(--primary),0.8); border-radius:4px;"></div>
            </div>
          </div>
          <div style="flex:1; background-color:var(--bg-secondary); border:1px solid var(--border-color); border-radius:8px; padding:12px; display:flex; flex-direction:column; gap:10px;">
            <div style="font-size:9px; font-weight:700; text-align:start;">حالة المخازن الفورية</div>
            <div style="display:flex; flex-direction:column; gap:8px; margin-top:4px;">
              <div style="display:flex; justify-content:space-between; align-items:center; font-size:8px;">
                <span>مستودع الرياض الرئيسي</span>
                <span style="color:#10b981; font-weight:700;">85% ممتلئ</span>
              </div>
              <div style="width:100%; height:6px; background-color:var(--bg-tertiary); border-radius:3px; overflow:hidden;">
                <div style="width:85%; height:100%; background-color:#10b981;"></div>
              </div>
              <div style="display:flex; justify-content:space-between; align-items:center; font-size:8px;">
                <span>مستودع جدة الفرعي</span>
                <span style="color:#f59e0b; font-weight:700;">30% ممتلئ</span>
              </div>
              <div style="width:100%; height:6px; background-color:var(--bg-tertiary); border-radius:3px; overflow:hidden;">
                <div style="width:30%; height:100%; background-color:#f59e0b;"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Features Section -->
  <section id="features" class="landing-features">
    <div class="section-header">
      <div class="landing-hero-badge">ميزات المنصة</div>
      <h2 class="section-title">كل ما تحتاجه لإدارة تجارتك بمكان واحد</h2>
      <p class="section-desc">منصة واحدة مصممة خصيصاً لتزيل تعقيدات مزامنة بيانات المتاجر الإلكترونية مع مخازن المستودعات والبيع الفعلي.</p>
    </div>
    
    <div class="features-grid">
      <!-- Feature 1 -->
      <div class="feature-col">
        <div class="feature-icon-box">
          <i class="fas fa-boxes"></i>
        </div>
        <h3 class="feature-col-title">نظام إدارة مستودعات متطور</h3>
        <p class="feature-col-desc">جرد دقيق للمنتجات، تحذيرات فورية عند اقتراب نفاد المخزون، مع إمكانية تعديل الكميات وتدوين أسباب التعديل يدوياً مع سجل كامل للتدقيق.</p>
      </div>
      
      <!-- Feature 2 -->
      <div class="feature-col">
        <div class="feature-icon-box">
          <i class="fas fa-cash-register"></i>
        </div>
        <h3 class="feature-col-title">نظام نقطة البيع POS السحابي</h3>
        <p class="feature-col-desc">قم بالبيع مباشرة لزبائنك وجهاً لوجه من خلال متصفحك أو جهازك اللوحي. ستقوم نقطة البيع بخصم الكميات من المخزون فورياً وتوثيق الفواتير.</p>
      </div>
      
      <!-- Feature 3 -->
      <div class="feature-col">
        <div class="feature-icon-box">
          <i class="fas fa-edit"></i>
        </div>
        <h3 class="feature-col-title">محرر متكامل بنمط WooCommerce</h3>
        <p class="feature-col-desc">أضف منتجاتك بتفاصيل كاملة: الوصف الكامل والوصف المختصر، تحديد الأسعار وسعر التخفيض، الرمز SKU، الوزن، وتصنيف المنتجات في قوائم سريعة.</p>
      </div>
    </div>
  </section>

  <!-- Pricing Section -->
  <section id="pricing" class="landing-pricing">
    <div class="section-header">
      <div class="landing-hero-badge">خطط الأسعار</div>
      <h2 class="section-title">باقات تناسب حجم أعمالك</h2>
      <p class="section-desc">اختر الباقة المناسبة لك وابدأ بإطلاق متجرك ومستودعك السحابي فوراً.</p>
    </div>
    
    <!-- Billing switcher -->
    <div class="pricing-switcher">
      <span class="switch-label active" id="label-monthly">فاتورة شهرية</span>
      <div class="switch-toggle" id="billing-toggle"></div>
      <span class="switch-label" id="label-yearly">فاتورة سنوية (خصم 20%)</span>
    </div>
    
    <div class="pricing-grid">
      <!-- Plan 1 -->
      <div class="pricing-card">
        <div class="plan-name">الباقة المبتدئة</div>
        <div class="plan-price-wrapper">
          <span class="plan-price" id="price-starter" data-monthly="29" data-yearly="23">$29</span>
          <span class="plan-period">/ شهرياً</span>
        </div>
        <p class="plan-desc">مثالية للمتاجر الناشئة التي تريد تتبع بسيط للمخزون.</p>
        <ul class="plan-features">
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>1 مستخدم للوحة التحكم</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>تخزين حتى 200 منتج</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>نظام المستودعات الأساسي</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>1 منفذ نقطة بيع POS</span></li>
        </ul>
        <button class="btn btn-secondary open-register-btn" data-plan="Starter" style="margin-top: auto;">ابدأ الآن</button>
      </div>
      
      <!-- Plan 2 (Popular) -->
      <div class="pricing-card popular">
        <span class="popular-badge">الأكثر طلباً</span>
        <div class="plan-name">الباقة الاحترافية</div>
        <div class="plan-price-wrapper">
          <span class="plan-price" id="price-pro" data-monthly="79" data-yearly="63">$79</span>
          <span class="plan-period">/ شهرياً</span>
        </div>
        <p class="plan-desc">باقة متكاملة للمتاجر المتوسطة والشركات ذات المستودعات النشطة.</p>
        <ul class="plan-features">
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>5 مستخدمين بصلاحيات مختلفة</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>منتجات غير محدودة</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>نظام مستودعات متقدم (تعدد المخازن)</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>3 منافذ لنقاط البيع POS</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>محرر المنتجات الكامل WooCommerce</span></li>
        </ul>
        <button class="btn btn-primary open-register-btn" data-plan="Pro" style="margin-top: auto;">ابدأ الآن</button>
      </div>
      
      <!-- Plan 3 -->
      <div class="pricing-card">
        <div class="plan-name">باقة الشركات</div>
        <div class="plan-price-wrapper">
          <span class="plan-price" id="price-enterprise" data-monthly="199" data-yearly="159">$199</span>
          <span class="plan-period">/ شهرياً</span>
        </div>
        <p class="plan-desc">للشركات والمستودعات الكبيرة التي تبحث عن أقصى أداء مع دعم مخصص.</p>
        <ul class="plan-features">
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>مستخدمين غير محدودين</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>مخازن مستودعات متعددة غير محدودة</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>سجل أنشطة متقدم ونظام مشتريات</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>أكشاك POS غير محدودة</span></li>
          <li class="plan-feature-item"><i class="fas fa-check"></i><span>دعم فني مخصص على مدار الساعة</span></li>
        </ul>
        <button class="btn btn-secondary open-register-btn" data-plan="Enterprise" style="margin-top: auto;">تواصل معنا</button>
      </div>
    </div>
  </section>

  <!-- Footer Section -->
  <footer class="landing-footer" id="contact">
    <a href="index.php" class="logo-container">
      <div class="logo-icon">N</div>
      <span class="logo-text">نوفا ستور</span>
    </a>
    <p style="font-size:14px; color:var(--text-secondary); max-width: 400px; line-height: 1.6;">منصة نوفا ستور السحابية لإدارة متجرك، مستودعاتك، مبيعاتك المباشرة، وحركة المخزون اللحظية بدقة متناهية.</p>
    <div class="footer-copy">جميع الحقوق محفوظة &copy; 2026 منصة نوفا ستور.</div>
  </footer>

  <!-- Registration Modal Overlay -->
  <div class="modal-overlay" id="register-modal-overlay">
    <div class="register-modal">
      <div class="modal-header">
        <h3 class="modal-title" id="register-modal-title">تسجيل متجر سحابي جديد</h3>
        <button class="modal-close" id="btn-close-modal"><i class="fas fa-times"></i></button>
      </div>
      <div class="modal-body">
        <div id="register-error-alert" class="alert-danger" style="display:none; padding:12px; margin-bottom:16px; font-size:13px; border-radius:4px;">
          <i class="fas fa-exclamation-triangle"></i> <span id="register-error-text"></span>
        </div>
        
        <form id="register-form">
          <input type="hidden" id="reg-plan" value="Pro">
          
          <div class="form-group">
            <label class="form-label">اسم المتجر / الشركة</label>
            <input type="text" class="form-control" id="reg-name" placeholder="مثال: أزياء الشام" required>
          </div>
          
          <div class="form-group">
            <label class="form-label">رابط المتجر اللطيف (Slug)</label>
            <div style="display:flex; align-items:center; gap:8px;">
              <input type="text" class="form-control" id="reg-slug" placeholder="مثال: sham-fashion" style="direction:ltr; text-align:left;" required>
              <span style="font-size:12px; font-weight:700; color:var(--text-muted);">.novastore.sa</span>
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">اسم المالك الكامل</label>
            <input type="text" class="form-control" id="reg-owner" placeholder="مثال: محمد الأحمد" required>
          </div>
          
          <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
            <div class="form-group">
              <label class="form-label">البريد الإلكتروني (اسم المستخدم)</label>
              <input type="email" class="form-control" id="reg-email" placeholder="name@domain.com" style="direction:ltr; text-align:left;" required autocomplete="username">
            </div>
            <div class="form-group">
              <label class="form-label">رقم الهاتف</label>
              <input type="text" class="form-control" id="reg-phone" placeholder="09xxxxxxxx" style="direction:ltr; text-align:left;">
            </div>
          </div>
          
          <div class="form-group">
            <label class="form-label">كلمة مرور لوحة التحكم</label>
            <input type="password" class="form-control" id="reg-password" placeholder="أدخل كلمة مرور قوية" required autocomplete="new-password">
          </div>
          
          <button type="submit" class="btn btn-primary" id="btn-submit-register" style="width:100%; height:46px; margin-top:16px;">
            <span>أطلق متجري الآن</span>
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Light/Dark Mode script & Modal Trigger -->
  <script>
    // Theme Switcher
    const themeBtn = document.getElementById('btn-theme-toggle');
    const savedTheme = localStorage.getItem('ns_theme') || 'light';
    document.documentElement.setAttribute('data-theme', savedTheme);
    updateThemeIcon(savedTheme);

    themeBtn.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('ns_theme', newTheme);
      updateThemeIcon(newTheme);
    });

    function updateThemeIcon(theme) {
      const icon = themeBtn.querySelector('i');
      if (icon) {
        if (theme === 'dark') {
          icon.className = 'fas fa-sun';
        } else {
          icon.className = 'fas fa-moon';
        }
      }
    }

    // Modal Control
    const overlay = document.getElementById('register-modal-overlay');
    const closeBtn = document.getElementById('btn-close-modal');
    const planInput = document.getElementById('reg-plan');
    const errorAlert = document.getElementById('register-error-alert');
    const errorText = document.getElementById('register-error-text');

    document.querySelectorAll('.open-register-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const plan = btn.getAttribute('data-plan') || 'Pro';
        planInput.value = plan;
        
        let planLabel = "الباقة الاحترافية";
        if (plan === 'Starter') planLabel = "الباقة المبتدئة";
        if (plan === 'Enterprise') planLabel = "باقة الشركات";
        
        document.getElementById('register-modal-title').innerText = `تسجيل متجر جديد - ${planLabel}`;
        overlay.classList.add('active');
      });
    });

    closeBtn.addEventListener('click', () => {
      overlay.classList.remove('active');
      document.getElementById('register-form').reset();
      errorAlert.style.display = 'none';
    });

    // Handle Form Submit
    const regForm = document.getElementById('register-form');
    const submitBtn = document.getElementById('btn-submit-register');

    regForm.addEventListener('submit', (e) => {
      e.preventDefault();
      errorAlert.style.display = 'none';
      
      const payload = {
        name: document.getElementById('reg-name').value.trim(),
        slug: document.getElementById('reg-slug').value.trim(),
        owner_name: document.getElementById('reg-owner').value.trim(),
        email: document.getElementById('reg-email').value.trim(),
        phone: document.getElementById('reg-phone').value.trim(),
        password: document.getElementById('reg-password').value.trim(),
        plan: planInput.value
      };
      
      submitBtn.disabled = true;
      submitBtn.innerText = 'جاري إطلاق متجرك...';
      
      fetch('api/tenants.php?action=register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(async res => {
        const data = await res.json();
        if (res.ok && data.success) {
          // Success render
          document.querySelector('.register-modal').innerHTML = `
            <div style="text-align:center; padding:20px;">
              <div style="width:64px; height:64px; border-radius:50%; background:#10b981; color:white; display:inline-flex; align-items:center; justify-content:center; font-size:32px; margin-bottom:16px;">
                <i class="fas fa-check"></i>
              </div>
              <h3 style="font-size:22px; font-weight:800; margin-bottom:12px;">تهانينا! تم إطلاق متجرك بنجاح</h3>
              <p style="color:var(--text-secondary); font-size:14px; line-height:1.6; margin-bottom:24px;">
                تم تجهيز متجرك <strong>${payload.name}</strong> بنجاح. يمكنك الآن الانتقال لتسجيل الدخول بلوحة التحكم الخاصة بك.
              </p>
              <a href="login.php?tenant=${payload.slug}" class="btn btn-primary" style="display:inline-block; padding:12px 30px; font-weight:700;">دخول لوحة التحكم</a>
            </div>
          `;
        } else {
          throw new Error(data.message || 'فشلت عملية إنشاء المتجر.');
        }
      })
      .catch(err => {
        errorText.innerText = err.message;
        errorAlert.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerText = 'أطلق متجري الآن';
      });
    });

    // Billing Switcher
    const billingToggle = document.getElementById('billing-toggle');
    const labelMonthly = document.getElementById('label-monthly');
    const labelYearly = document.getElementById('label-yearly');
    
    if (billingToggle) {
      billingToggle.addEventListener('click', () => {
        billingToggle.classList.toggle('active');
        const isYearly = billingToggle.classList.contains('active');
        
        if (isYearly) {
          labelMonthly.classList.remove('active');
          labelYearly.classList.add('active');
          updatePrices(true);
        } else {
          labelMonthly.classList.add('active');
          labelYearly.classList.remove('active');
          updatePrices(false);
        }
      });
    }

    function updatePrices(isYearly) {
      const plans = ['starter', 'pro', 'enterprise'];
      plans.forEach(plan => {
        const priceEl = document.getElementById(`price-${plan}`);
        if (priceEl) {
          const val = isYearly ? priceEl.getAttribute('data-yearly') : priceEl.getAttribute('data-monthly');
          priceEl.innerText = `$${val}`;
        }
      });
    }
  </script>
</body>
</html>
