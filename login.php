<?php
// login.php - Dynamic branded tenant login page
require_once __DIR__ . '/api/config.php';

// Get tenant details
$tenant_id = get_active_tenant_id();
$tenant = get_active_tenant_details();

$store_name = $tenant ? $tenant['name'] : 'متجر';
$theme_color = $tenant ? $tenant['theme_color'] : '#4f46e5';
$logo_url = ($tenant && !empty($tenant['logo_url'])) ? $tenant['logo_url'] : '';
$tenant_slug = $tenant ? $tenant['slug'] : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول - لوحة تحكم <?php echo htmlspecialchars($store_name); ?></title>
  <?php
  $base_href = '/';
  if (isset($_SERVER['SCRIPT_NAME'])) {
      $base_href = dirname($_SERVER['SCRIPT_NAME']);
      if ($base_href === '\\' || $base_href === '/') {
          $base_href = '/';
      } else {
          $base_href = rtrim($base_href, '/') . '/';
      }
  }
  ?>
  <base href="<?php echo htmlspecialchars($base_href); ?>">
  
  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="css/variables.css?v=3.2">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: var(--font-arabic);
      background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #311042 100%);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      overflow: hidden;
      position: relative;
    }

    /* Ambient background glows */
    .glow-circle {
      position: absolute;
      border-radius: 50%;
      filter: blur(80px);
      z-index: 1;
      opacity: 0.15;
    }
    .glow-1 {
      background: <?php echo $theme_color; ?>;
      width: 400px;
      height: 400px;
      top: -100px;
      right: -100px;
    }
    .glow-2 {
      background: hsla(300, 80%, 50%, 1);
      width: 350px;
      height: 350px;
      bottom: -100px;
      left: -100px;
    }

    .login-container {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 420px;
      padding: 24px;
    }

    .login-card {
      background: rgba(15, 23, 42, 0.65);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: var(--border-radius-md);
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.4);
      padding: 40px 32px;
      color: #f9fafb;
      animation: fadeIn 0.6s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .brand-header {
      text-align: center;
      margin-bottom: 32px;
    }

    .brand-logo {
      width: 64px;
      height: 64px;
      background: linear-gradient(135deg, <?php echo $theme_color; ?> 0%, hsla(300, 80%, 50%, 1) 100%);
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 16px;
      box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
      overflow: hidden;
    }

    .brand-logo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .brand-logo i {
      font-size: 28px;
      color: #ffffff;
    }

    .brand-name {
      font-size: 22px;
      font-weight: 800;
      color: #f9fafb;
      margin: 0;
    }

    .brand-subtitle {
      font-size: 13px;
      color: #9ca3af;
      margin-top: 4px;
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      margin-bottom: 8px;
      color: #d1d5db;
    }

    .input-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }

    .input-wrapper i {
      position: absolute;
      right: 14px;
      color: #6b7280;
      font-size: 16px;
    }

    .form-control {
      width: 100%;
      height: 48px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: var(--border-radius-xs);
      padding: 0 45px 0 16px;
      color: #ffffff;
      font-family: var(--font-arabic);
      font-size: 14px;
      transition: all 0.25s;
      box-sizing: border-box;
    }

    .form-control:focus {
      outline: none;
      border-color: <?php echo $theme_color; ?>;
      background: rgba(255, 255, 255, 0.08);
      box-shadow: 0 0 15px rgba(129, 140, 248, 0.2);
    }

    .btn-login {
      width: 100%;
      height: 48px;
      background: linear-gradient(135deg, <?php echo $theme_color; ?> 0%, <?php echo $theme_color; ?>cc 100%);
      border: none;
      border-radius: var(--border-radius-xs);
      color: #ffffff;
      font-family: var(--font-arabic);
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      box-shadow: 0 4px 12px rgba(129, 140, 248, 0.25);
      transition: all 0.25s;
      margin-top: 24px;
    }

    .btn-login:hover {
      opacity: 0.95;
      transform: translateY(-1px);
    }

    .btn-login:active {
      transform: translateY(1px);
    }

    .btn-login:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
    }

    .alert-danger {
      background: rgba(244, 63, 94, 0.15);
      border: 1px solid rgba(244, 63, 94, 0.3);
      color: #fda4af;
      padding: 12px 16px;
      border-radius: var(--border-radius-xs);
      font-size: 13px;
      margin-bottom: 20px;
      display: none;
      align-items: center;
      gap: 8px;
      animation: shake 0.3s ease-in-out;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .support-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 11px;
      color: #6b7280;
    }
  </style>
</head>
<body>

  <div class="glow-circle glow-1"></div>
  <div class="glow-circle glow-2"></div>

  <div class="login-container">
    <div class="login-card">
      <div class="brand-header">
        <div class="brand-logo" style="background: <?php echo $theme_color; ?>;">
          <?php if (!empty($logo_url)): ?>
            <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="logo">
          <?php else: ?>
            <i class="fas fa-store"></i>
          <?php endif; ?>
        </div>
        <h1 class="brand-name"><?php echo htmlspecialchars($store_name); ?></h1>
        <p class="brand-subtitle">نظام الإدارة المتكامل والـ ERP</p>
      </div>

      <div class="alert-danger" id="login-alert">
        <i class="fas fa-exclamation-triangle"></i>
        <span id="alert-text">اسم المستخدم أو كلمة المرور غير صحيحة.</span>
      </div>

      <form id="login-form">
        <div class="form-group">
          <label class="form-label">اسم المستخدم (البريد الإلكتروني)</label>
          <div class="input-wrapper">
            <i class="fas fa-user"></i>
            <input type="text" class="form-control" id="username" placeholder="أدخل البريد الإلكتروني" required autocomplete="username">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">كلمة المرور</label>
          <div class="input-wrapper">
            <i class="fas fa-lock"></i>
            <input type="password" class="form-control" id="password" placeholder="أدخل كلمة المرور" required autocomplete="current-password">
          </div>
        </div>

        <button type="submit" class="btn-login" id="btn-submit">
          <span>تسجيل الدخول</span>
          <i class="fas fa-arrow-left"></i>
        </button>
      </form>
      
      <div class="support-footer" style="display:flex; justify-content:space-between; align-items:center; margin-top:30px;">
        <span>منصة متجر السحابية</span>
        <a href="index.php" style="color:<?php echo $theme_color; ?>; text-decoration:none;">الرئيسية</a>
      </div>
    </div>
  </div>

  <script>
    // Extract tenant slug from URL
    const urlParams = new URLSearchParams(window.location.search);
    const tenantSlug = urlParams.get('tenant') || '<?php echo htmlspecialchars($tenant_slug); ?>';

    // Automatically check if user is already authenticated
    window.addEventListener('DOMContentLoaded', () => {
      fetch('api/auth.php?action=check')
        .then(res => res.json())
        .then(data => {
          if (data.authenticated) {
            // If the user matches the requested tenant or it is superadmin, redirect to manager
            if (data.user.tenant_id === null) {
              window.location.href = 'dashboard.php'; // Superadmin to saas dashboard
            } else {
              let basePath = window.location.pathname;
              if (basePath.endsWith('/admin') || basePath.endsWith('/admin/')) {
                  basePath = basePath.replace(/\/admin\/?$/, '');
              }
              if (!basePath.endsWith('/')) {
                  basePath += '/';
              }
              window.location.href = basePath + 'admin';
            }
          }
        })
        .catch(err => console.error('Session check failed:', err));
    });

    const form = document.getElementById('login-form');
    const alertBox = document.getElementById('login-alert');
    const alertText = document.getElementById('alert-text');
    const btnSubmit = document.getElementById('btn-submit');

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      
      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value.trim();
      
      alertBox.style.display = 'none';
      btnSubmit.disabled = true;
      btnSubmit.querySelector('span').innerText = 'جاري التحقق...';

      fetch(`api/auth.php?action=login&tenant=${tenantSlug}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ username, password })
      })
      .then(async res => {
        const data = await res.json();
        if (res.ok && data.success) {
          sessionStorage.setItem('ns_user', JSON.stringify(data.user));
          
          if (data.user.tenant_id === null) {
            window.location.href = 'dashboard.php';
          } else {
            // Redirect to merchant manager
            let basePath = window.location.pathname;
            if (basePath.endsWith('/admin') || basePath.endsWith('/admin/')) {
                basePath = basePath.replace(/\/admin\/?$/, '');
            }
            if (!basePath.endsWith('/')) {
                basePath += '/';
            }
            window.location.href = basePath + 'admin';
          }
        } else {
          throw new Error(data.message || 'خطأ غير معروف في السيرفر.');
        }
      })
      .catch(err => {
        alertText.innerText = err.message;
        alertBox.style.display = 'flex';
        btnSubmit.disabled = false;
        btnSubmit.querySelector('span').innerText = 'تسجيل الدخول';
      });
    });
  </script>
</body>
</html>
