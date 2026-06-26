<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>باقات الأسعار | متجر Matjer</title>
  
  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="css/variables.css?v=3.0">
  <link rel="stylesheet" href="css/style.css?v=3.0">
  <link rel="stylesheet" href="css/saas.css?v=3.0">
  <link rel="stylesheet" href="css/components.css?v=3.0">
  
  <!-- FontAwesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="landing-body">

  <!-- Header Navbar -->
  <nav class="landing-nav">
    <a href="index.php" class="logo-container">
      <div class="logo-icon">M</div>
      <span class="logo-text">متجر</span>
    </a>
    
    <ul class="landing-nav-links">
      <li><a href="features.php" class="landing-nav-link">المميزات</a></li>
      <li><a href="pricing.php" class="landing-nav-link active" style="color: hsla(var(--primary), 1);">الأسعار</a></li>
      <li><a href="contact.php" class="landing-nav-link">تواصل معنا</a></li>
    </ul>
    
    <div class="landing-nav-actions">
      <button class="header-action-btn theme-toggle-btn" id="btn-theme-toggle" title="تبديل المظهر">
        <i class="fas fa-moon"></i>
      </button>
      <a href="login.php" class="btn btn-primary btn-sm">تسجيل الدخول</a>
    </div>
  </nav>

  <!-- Subpage Hero -->
  <header class="subpage-hero">
    <div class="landing-hero-badge">خطط مرنة 💎</div>
    <h1 class="subpage-hero-title">باقات تناسب جميع أحجام الأعمال</h1>
    <p class="subpage-hero-desc">ابدأ مجاناً لمدة 14 يوماً وجرب كافة مميزات المنصة. لا نطلب بطاقة ائتمانية للتسجيل والتجربة.</p>
  </header>

  <!-- Plans section -->
  <section class="landing-pricing" style="padding-top: 40px;">
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
        <a href="dashboard.php" class="btn btn-secondary" style="margin-top: auto;">ابدأ تجربتك المجانية</a>
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
        <a href="dashboard.php" class="btn btn-primary" style="margin-top: auto;">ابدأ تجربتك المجانية</a>
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
        <a href="contact.php" class="btn btn-secondary" style="margin-top: auto;">تواصل معنا</a>
      </div>
    </div>
  </section>

  <!-- Comparison Matrix Section -->
  <section class="pricing-comparison-section">
    <h2 style="font-size: 26px; font-weight: 800; text-align: center; color: var(--text-primary);">جدول مقارنة تفصيلي للمميزات</h2>
    
    <div class="comparison-table-wrapper">
      <table class="comparison-table">
        <thead>
          <tr>
            <th>المميزات والخصائص</th>
            <th>الباقة المبتدئة</th>
            <th>الباقة الاحترافية</th>
            <th>باقة الشركات</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="comparison-feature-name">حجم المنتجات المتاحة</td>
            <td>حتى 200 منتج</td>
            <td>غير محدود</td>
            <td>غير محدود</td>
          </tr>
          <tr>
            <td class="comparison-feature-name">عدد مستخدمي النظام</td>
            <td>مستخدم واحد</td>
            <td>5 مستخدمين</td>
            <td>غير محدود</td>
          </tr>
          <tr>
            <td class="comparison-feature-name">منافذ البيع الفورية (POS)</td>
            <td>منفذ واحد</td>
            <td>3 منافذ</td>
            <td>غير محدود</td>
          </tr>
          <tr>
            <td class="comparison-feature-name">عدد المستودعات والمخازن</td>
            <td>مستودع واحد فقط</td>
            <td>تعدد المخازن (حتى 5)</td>
            <td>غير محدود جغرافياً</td>
          </tr>
          <tr>
            <td class="comparison-feature-name">محرر المنتجات (WooCommerce Style)</td>
            <td><i class="fas fa-times"></i></td>
            <td><i class="fas fa-check"></i></td>
            <td><i class="fas fa-check"></i></td>
          </tr>
          <tr>
            <td class="comparison-feature-name">إدارة الصلاحيات والأدوار بالفريق</td>
            <td><i class="fas fa-times"></i></td>
            <td><i class="fas fa-check"></i></td>
            <td><i class="fas fa-check"></i></td>
          </tr>
          <tr>
            <td class="comparison-feature-name">دفاتر الحسابات والمالية والفواتير</td>
            <td>بيانات أساسية</td>
            <td>سجل متكامل وقابل للتصدير</td>
            <td>سجل متقدم + نظام مشتريات ومصروفات</td>
          </tr>
          <tr>
            <td class="comparison-feature-name">الدعم الفني المباشر</td>
            <td>عبر البريد الإلكتروني</td>
            <td>هاتف + بريد (خلال ساعات العمل)</td>
            <td>دعم مخصص 24/7 مع مسؤول حسابات</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- FAQ Section -->
  <section class="faq-section">
    <h2 style="font-size: 26px; font-weight: 800; text-align: center; color: var(--text-primary); margin-bottom: 40px;">الأسئلة الشائعة حول الباقات</h2>
    
    <div class="faq-container">
      <div class="faq-item">
        <div class="faq-question">
          <span>هل أحتاج لإضافة بطاقة ائتمانية للاشتراك بالفترة التجريبية؟</span>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="faq-answer">
          لا، لا تطلب منصة متجر أي بيانات دفع لتنشيط الفترة التجريبية. يمكنك تسجيل حسابك والبدء في جرد مستودعاتك واستخدام الكاشير فوراً مجاناً لمدة 14 يوماً.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">
          <span>هل يمكنني ترقية أو تخفيض باقتي في أي وقت؟</span>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="faq-answer">
          نعم بالتأكيد. يمكنك ترقية الباقة أو تعديلها من حسابك وسيقوم النظام باحتساب الفروقات المالية بشكل تناسبي مباشر دون انقطاع الخدمة عن موظفيك ومخازنك.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">
          <span>ماذا يحدث لبياناتي ومخزوني في حال انتهاء الفترة التجريبية؟</span>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="faq-answer">
          تبقى بيانات منتجاتك ومستودعاتك محفوظة بشكل آمن لدينا لمدة 30 يوماً بعد انتهاء الفترة التجريبية، ليتسنى لك الاشتراك وتنشيط النظام دون خسارة ما قمت بجلده.
        </div>
      </div>

      <div class="faq-item">
        <div class="faq-question">
          <span>هل يدعم نظام الفواتير بالمنصة متطلبات هيئة الزكاة والضريبة والجمارك (ZATCA)؟</span>
          <i class="fas fa-chevron-down"></i>
        </div>
        <div class="faq-answer">
          نعم، جميع الفواتير الصادرة من نقاط البيع POS أو فواتير المبيعات الإلكترونية معتمدة ضريبياً بنسبة 15% وتحتوي على الرقم الضريبي ورمز الاستجابة السريعة (QR Code) المتوافق مع الهيئة.
        </div>
      </div>
    </div>
  </section>

  <!-- Footer Section -->
  <footer class="landing-footer">
    <a href="index.php" class="logo-container">
      <div class="logo-icon">M</div>
      <span class="logo-text">متجر</span>
    </a>
    <p style="font-size:14px; color:var(--text-secondary); max-width: 400px; line-height: 1.6;">منصة متجر السحابية لإدارة متجرك، مستودعاتك، مبيعاتك المباشرة، وحركة المخزون اللحظية بدقة متناهية.</p>
    <div class="footer-copy">جميع الحقوق محفوظة &copy; 2026 منصة متجر.</div>
  </footer>

  <!-- Script for Dark/Light Theme & Billing switcher & FAQ accordion -->
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
      if (theme === 'dark') {
        icon.className = 'fas fa-sun';
      } else {
        icon.className = 'fas fa-moon';
      }
    }

    // Billing Switcher Logic
    const toggle = document.getElementById('billing-toggle');
    const labelMonthly = document.getElementById('label-monthly');
    const labelYearly = document.getElementById('label-yearly');
    
    const priceStarter = document.getElementById('price-starter');
    const pricePro = document.getElementById('price-pro');
    const priceEnterprise = document.getElementById('price-enterprise');

    toggle.addEventListener('click', () => {
      const isActive = toggle.classList.toggle('active');
      
      if (isActive) {
        labelMonthly.classList.remove('active');
        labelYearly.classList.add('active');
        
        // Switch to yearly rates
        priceStarter.innerText = `$${priceStarter.getAttribute('data-yearly')}`;
        pricePro.innerText = `$${pricePro.getAttribute('data-yearly')}`;
        priceEnterprise.innerText = `$${priceEnterprise.getAttribute('data-yearly')}`;
      } else {
        labelMonthly.classList.add('active');
        labelYearly.classList.remove('active');
        
        // Switch to monthly rates
        priceStarter.innerText = `$${priceStarter.getAttribute('data-monthly')}`;
        pricePro.innerText = `$${pricePro.getAttribute('data-monthly')}`;
        priceEnterprise.innerText = `$${priceEnterprise.getAttribute('data-monthly')}`;
      }
    });

    // FAQ Accordion Toggle
    const faqQuestions = document.querySelectorAll('.faq-question');
    faqQuestions.forEach(question => {
      question.addEventListener('click', () => {
        const item = question.parentElement;
        const isOpen = item.classList.contains('open');
        
        // Close other items
        document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
        
        if (!isOpen) {
          item.classList.add('open');
        }
      });
    });
  </script>
</body>
</html>
