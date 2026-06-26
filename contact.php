<!DOCTYPE html>
<html lang="ar" dir="rtl" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تواصل معنا | متجر Matjer</title>
  
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
      <li><a href="pricing.php" class="landing-nav-link">الأسعار</a></li>
      <li><a href="contact.php" class="landing-nav-link active" style="color: hsla(var(--primary), 1);">تواصل معنا</a></li>
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
    <div class="landing-hero-badge">تواصل مالي وفني 🤝</div>
    <h1 class="subpage-hero-title">يسعدنا تواصلك معنا دائماً</h1>
    <p class="subpage-hero-desc">هل لديك استفسارات حول الباقات المخصصة؟ أو تحتاج إلى استشارة لربط أجهزة جرد المخازن؟ فريق المبيعات والدعم الفني متواجد لمساعدتك.</p>
  </header>

  <!-- Contact Form & Info Grid -->
  <main class="contact-section-grid">
    
    <!-- Right side: Contact Form -->
    <div class="contact-form-card">
      <h2 style="font-size: 20px; font-weight: 700; color: var(--text-primary); text-align: start;">أرسل لنا رسالة مباشرة</h2>
      <p style="font-size: 13px; color: var(--text-secondary); text-align: start; margin-top: -12px; margin-bottom: 8px;">املأ النموذج أدناه وسيقوم ممثل خدمة العملاء بالرد عليك خلال أقل من 12 ساعة.</p>
      
      <!-- Success message banner -->
      <div class="contact-success-alert" id="contact-success-alert">
        <i class="fas fa-check-circle" style="font-size: 18px;"></i>
        <span>شكرًا لتواصلك معنا! تم إرسال رسالتك بنجاح وسنتواصل معك قريبًا.</span>
      </div>

      <form id="public-contact-form" style="display: flex; flex-direction: column; gap: 20px; text-align: start;">
        <div class="form-row">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">الاسم الكريم</label>
            <input type="text" class="form-control" id="contact-name" required placeholder="أدخل اسمك هنا...">
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">البريد الإلكتروني</label>
            <input type="email" class="form-control" id="contact-email" required placeholder="example@domain.com">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">رقم الجوال للتواصل</label>
            <input type="text" class="form-control" id="contact-phone" required placeholder="+966 50 000 0000">
          </div>
          <div class="form-group" style="margin-bottom: 0;">
            <label class="form-label">نوع الاستفسار</label>
            <select class="form-control" id="contact-type">
              <option value="sales">استفسار مبيعات وباقات</option>
              <option value="technical">طلب دعم فني للربط</option>
              <option value="enterprise">طلب كود شركات مخصص</option>
            </select>
          </div>
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">عنوان الاستفسار</label>
          <input type="text" class="form-control" id="contact-subject" required placeholder="كيف يمكننا مساعدتك؟">
        </div>
        <div class="form-group" style="margin-bottom: 0;">
          <label class="form-label">نص الرسالة بالتفصيل</label>
          <textarea class="form-control" id="contact-message" style="height: 120px; resize: vertical;" required placeholder="اكتب تفاصيل طلبك أو استفسارك هنا..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary" style="padding: 12px 24px; align-self: flex-start;">
          <i class="fas fa-paper-plane"></i> إرسال الرسالة الآن
        </button>
      </form>
    </div>

    <!-- Left side: Contact Info and Map -->
    <div class="contact-info-card">
      <div class="contact-info-item">
        <div class="contact-info-icon-box">
          <i class="fas fa-map-marker-alt"></i>
        </div>
        <div class="contact-info-text-box">
          <span class="contact-info-label">المقر الرئيسي للشركة</span>
          <span class="contact-info-value">برج الابتكار الرقمي، الدور الثاني عشر، حي السلي، الرياض، المملكة العربية السعودية</span>
        </div>
      </div>

      <div class="contact-info-item">
        <div class="contact-info-icon-box">
          <i class="fas fa-phone-alt"></i>
        </div>
        <div class="contact-info-text-box">
          <span class="contact-info-label">الهاتف المباشر الموحد</span>
          <span class="contact-info-value" style="font-family: var(--font-english); font-weight: 600;">+966 9200 12345 (الرقم الموحد المجاني)</span>
        </div>
      </div>

      <div class="contact-info-item">
        <div class="contact-info-icon-box">
          <i class="fas fa-envelope"></i>
        </div>
        <div class="contact-info-text-box">
          <span class="contact-info-label">البريد الإلكتروني للخدمات</span>
          <span class="contact-info-value" style="font-family: var(--font-english); font-weight: 600;">sales@matjer.net | support@matjer.net</span>
        </div>
      </div>

      <!-- Mock Google Map visual styling -->
      <div class="map-placeholder">
        <div class="map-grid-pattern"></div>
        <i class="fas fa-map-pin map-marker-pin"></i>
        <div style="z-index: 1; font-weight: 700; background-color: var(--bg-secondary); padding: 8px 16px; border-radius: var(--border-radius-xs); border: 1px solid var(--border-color); font-size: 11px; color: var(--text-primary); margin-top: 60px;">مقر منصة متجر بالرياض</div>
      </div>
    </div>

  </main>

  <!-- Footer Section -->
  <footer class="landing-footer">
    <a href="index.php" class="logo-container">
      <div class="logo-icon">M</div>
      <span class="logo-text">متجر</span>
    </a>
    <p style="font-size:14px; color:var(--text-secondary); max-width: 400px; line-height: 1.6;">منصة متجر السحابية لإدارة متجرك، مستودعاتك، مبيعاتك المباشرة، وحركة المخزون اللحظية بدقة متناهية.</p>
    <div class="footer-copy">جميع الحقوق محفوظة &copy; 2026 منصة متجر.</div>
  </footer>

  <!-- Script for Dark/Light Theme & Contact Form submit logger -->
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

    // Form Submission logger
    const form = document.getElementById('public-contact-form');
    const alertBanner = document.getElementById('contact-success-alert');

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const name = document.getElementById('contact-name').value;
      const email = document.getElementById('contact-email').value;
      const phone = document.getElementById('contact-phone').value;
      const type = document.getElementById('contact-type').value;
      const subject = document.getElementById('contact-subject').value;
      const message = document.getElementById('contact-message').value;

      // Save to localStorage logs
      const contactLogs = JSON.parse(localStorage.getItem('ns_contact_messages')) || [];
      const newLog = {
        id: Date.now().toString(),
        name,
        email,
        phone,
        type,
        subject,
        message,
        date: new Date().toISOString()
      };
      contactLogs.push(newLog);
      localStorage.setItem('ns_contact_messages', JSON.stringify(contactLogs));

      // Show success state
      alertBanner.style.display = 'flex';
      form.reset();
      
      // Clear alert banner after 5 seconds
      setTimeout(() => {
        alertBanner.style.display = 'none';
      }, 5000);
    });
  </script>
</body>
</html>
