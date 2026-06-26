<?php
require_once __DIR__ . '/api/config.php';

$tenant_id = get_active_tenant_id();
$tenant = get_active_tenant_details();

if (!$tenant) {
    echo "<div style='text-align:center; padding:50px; font-family:sans-serif;'><h2>المتجر غير موجود أو تم تجميد اشتراكه مؤقتاً.</h2><p>يرجى مراجعة إدارة المنصة.</p></div>";
    exit;
}

$store_name = $tenant['name'];
$theme_color = $tenant['theme_color'];
$tenant_slug = $tenant['slug'];

// Get active theme from database settings
$active_theme = get_system_setting('ns_active_theme', 'jasmine');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($store_name); ?> - واجهة المتجر</title>
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Google Fonts: Cairo, Amiri, and Jost -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Amiri:ital,wght@0,400;0,700;1,400&family=Cairo:wght@300;400;600;700;800&family=Jost:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
  
  <style>
    /* Premium Damascene Jasmine Theme Variables */
    :root {
      --primary-color: #1b4d3e; /* Forest Green */
      --secondary-color: #c5a880; /* Damascus Gold */
      --bg-cream: #fdfbf7; /* Jasmine Cream */
      --bg-card: #ffffff;
      --text-charcoal: #2c3e50;
      --text-muted: #7f8c8d;
      --border-color: #e8e4db;
      --transition-speed: 0.3s;
      --border-radius: 8px;
    }

    /* Premium Ella Multipurpose Clothing Theme Overrides */
    .theme-ella {
      --primary-color: #000000;
      --secondary-color: #d12442; /* Ella Red accent */
      --bg-cream: #ffffff;
      --bg-card: #ffffff;
      --text-charcoal: #111111;
      --text-muted: #777777;
      --border-color: #e5e5e5;
      --transition-speed: 0.2s;
      --border-radius: 0px;
    }

    .theme-ella,
    .theme-ella *:not(.fa):not(.fab):not(.far):not(.fas):not(i) {
      font-family: 'Jost', 'Cairo', sans-serif !important;
    }

    .theme-ella .product-card,
    .theme-ella .product-thumb,
    .theme-ella .add-to-cart-btn,
    .theme-ella .cart-drawer,
    .theme-ella .cart-item,
    .theme-ella .cart-qty-btn,
    .theme-ella .checkout-btn,
    .theme-ella .checkout-modal,
    .theme-ella .form-control,
    .theme-ella .ella-search-box,
    .theme-ella .ella-search-input,
    .theme-ella .ella-search-btn,
    .theme-ella .ella-hero-btn,
    .theme-ella .ella-newsletter-modal,
    .theme-ella .ella-newsletter-input,
    .theme-ella .ella-newsletter-submit,
    .theme-ella header,
    .theme-ella footer {
      border-radius: 0px !important;
    }

    /* Ella Layout & Components Overrides */
    
    /* Top Bar Brand tabs */
    .ella-top-bar {
      display: none;
      background-color: #000000;
      color: #ffffff;
      font-size: 11px;
      border-bottom: 1px solid #222;
    }
    .theme-ella .ella-top-bar {
      display: block;
    }
    .ella-top-bar-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 40px;
    }
    .ella-top-bar-left {
      display: flex;
      height: 100%;
      align-items: stretch;
    }
    .ella-brand-tab {
      padding: 0 20px;
      display: flex;
      align-items: center;
      color: #888888;
      text-decoration: none;
      font-weight: 700;
      font-size: 13px;
      letter-spacing: 1px;
      transition: all 0.2s ease;
      background: none;
      border: none;
      cursor: pointer;
    }
    .ella-brand-tab:hover {
      color: #ffffff;
    }
    .ella-brand-tab.active {
      background-color: #ffffff;
      color: #000000 !important;
    }
    .ella-top-bar-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .ella-top-bar-right span {
      font-weight: 600;
    }
    .ella-top-bar-icons {
      display: flex;
      gap: 15px;
      font-size: 14px;
    }
    .ella-top-bar-icons a {
      color: #ffffff;
      text-decoration: none;
      transition: opacity 0.2s;
    }
    .ella-top-bar-icons a:hover {
      opacity: 0.8;
    }

    .theme-ella header {
      background-color: #ffffff !important;
      color: #000000 !important;
      border-bottom: 1px solid #e5e5e5 !important;
      box-shadow: none !important;
      padding: 20px 0 10px 0 !important;
    }

    .theme-ella .header-content {
      display: grid !important;
      grid-template-areas: 
        "logo search actions"
        "menu menu menu" !important;
      grid-template-columns: auto 1.5fr auto !important;
      grid-template-rows: auto auto !important;
      gap: 20px 30px !important;
      align-items: center !important;
    }

    @media(max-width: 992px) {
      .theme-ella .header-content {
        display: grid !important;
        grid-template-areas: 
          "logo actions"
          "search search" !important;
        grid-template-columns: 1fr auto !important;
        grid-template-rows: auto auto !important;
        gap: 15px !important;
      }
      .theme-ella .ella-nav-menu {
        display: none !important;
      }
    }

    .theme-ella .store-logo {
      grid-area: logo;
      color: #000000 !important;
      font-weight: 800 !important;
      font-size: 32px !important;
      letter-spacing: 1px !important;
      text-transform: uppercase !important;
      line-height: 1 !important;
      margin: 0 !important;
      font-family: 'Jost', sans-serif !important;
    }

    .ella-nav-menu {
      display: none;
      justify-content: center;
      gap: 20px;
      align-items: center;
    }
    .theme-ella .ella-nav-menu {
      grid-area: menu;
      display: flex !important;
      justify-content: center;
      gap: 35px;
      align-items: center;
      border-top: 1px solid #e5e5e5;
      padding-top: 15px;
      margin-top: 5px;
    }
    .ella-nav-link {
      color: #111111;
      text-decoration: none;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      transition: color 0.2s;
      white-space: nowrap;
    }
    .theme-ella .ella-nav-link {
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      transition: color 0.2s;
      white-space: nowrap;
      position: relative;
      padding: 6px 0;
    }
    .theme-ella .ella-nav-link::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 0;
      height: 2px;
      background-color: #000000;
      transition: width 0.2s ease;
    }
    .theme-ella .ella-nav-link:hover::after {
      width: 100%;
    }
    .theme-ella .ella-nav-link:hover {
      color: #000000;
    }

    .ella-search-col {
      display: none;
      align-items: center;
      justify-content: flex-end;
      gap: 15px;
    }
    .theme-ella .ella-search-col {
      grid-area: search;
      display: flex !important;
      justify-content: center;
      width: 100%;
    }
    .ella-search-box {
      display: flex;
      border: 1px solid #cccccc;
      width: 250px;
    }
    .theme-ella .ella-search-box {
      display: flex !important;
      border: 1.5px solid #000000 !important;
      width: 100% !important;
      max-width: 550px !important;
      background: #f7f7f7 !important;
      transition: border-color 0.2s ease, background-color 0.2s ease !important;
    }
    .theme-ella .ella-search-box:focus-within {
      background: #ffffff !important;
      border-color: #d12442 !important;
    }
    .ella-search-input {
      border: none !important;
      padding: 8px 12px !important;
      font-size: 12px !important;
      width: 100% !important;
      outline: none !important;
      background: transparent !important;
    }
    .theme-ella .ella-search-input {
      border: none !important;
      padding: 10px 15px !important;
      font-size: 13px !important;
      width: 100% !important;
      outline: none !important;
      background: transparent !important;
      color: #111111 !important;
      text-align: right !important;
    }
    .ella-search-btn {
      background-color: #000000 !important;
      color: #ffffff !important;
      border: none !important;
      padding: 0 15px !important;
      cursor: pointer !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-size: 12px !important;
    }
    .theme-ella .ella-search-btn {
      background-color: #000000 !important;
      color: #ffffff !important;
      border: none !important;
      padding: 0 20px !important;
      cursor: pointer !important;
      display: flex !important;
      align-items: center !important;
      justify-content: center !important;
      font-size: 14px !important;
      transition: background-color 0.2s ease !important;
    }
    .ella-search-btn:hover,
    .theme-ella .ella-search-btn:hover {
      background-color: #d12442 !important;
    }
    
    .theme-ella .header-actions-col {
      grid-area: actions;
      display: flex !important;
      align-items: center;
      justify-content: flex-end;
      gap: 15px;
    }

    /* Beige Promo Bar */
    .ella-beige-promo {
      display: none;
      background-color: #f6ebe1;
      border-bottom: 1px solid #ebd9c7;
      padding: 12px 0;
    }
    .theme-ella .ella-beige-promo {
      display: block;
    }
    .ella-beige-promo-content {
      display: grid;
      grid-template-columns: 1fr 1fr;
      text-align: center;
      gap: 20px;
      font-size: 12px;
      color: #000000;
    }
    .ella-beige-promo-col {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .ella-beige-promo-col strong {
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .ella-beige-promo-col span {
      font-size: 11px;
      color: #555555;
    }
    @media(max-width: 768px) {
      .ella-beige-promo-content {
        grid-template-columns: 1fr;
        gap: 10px;
      }
    }

    /* Feature Icon Bar */
    .ella-feature-icon-bar {
      display: none;
      background-color: #f7f7f7;
      border-bottom: 1px solid #e5e5e5;
      padding: 12px 0;
    }
    .theme-ella .ella-feature-icon-bar {
      display: block;
    }
    .ella-feature-icon-content {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      text-align: center;
      gap: 10px;
      font-size: 11px;
      font-weight: 700;
      color: #222222;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .ella-feature-item {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }
    .ella-feature-item i {
      font-size: 13px;
      color: #555555;
    }
    @media(max-width: 768px) {
      .ella-feature-icon-content {
        grid-template-columns: 1fr 1fr;
        gap: 12px;
      }
    }

    /* Three-Column Instiley Hero Grid */
    .ella-hero-grid {
      display: none;
    }
    .theme-ella .ella-hero-grid {
      display: grid;
      grid-template-columns: 1fr 1.2fr 1fr;
      gap: 15px;
      margin-bottom: 40px;
    }
    @media(max-width: 992px) {
      .theme-ella .ella-hero-grid {
        grid-template-columns: 1fr;
      }
      .ella-hero-side-img {
        display: none;
      }
    }
    .ella-hero-side-img {
      height: 500px;
      overflow: hidden;
      position: relative;
    }
    .ella-hero-side-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.5s ease;
    }
    .ella-hero-side-img:hover img {
      transform: scale(1.03);
    }
    .ella-hero-center-card {
      background-color: #5e9b89; /* The exact teal background from the screenshot */
      color: #ffffff;
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      gap: 20px;
      height: 500px;
    }
    .ella-hero-center-tag {
      font-size: 13px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 2px;
      opacity: 0.9;
    }
    .ella-hero-center-title {
      font-family: 'Jost', sans-serif !important;
      font-size: 46px;
      font-weight: 700;
      letter-spacing: 2px;
      text-transform: uppercase;
      margin: 0;
    }
    .ella-hero-center-desc {
      font-size: 13px;
      line-height: 1.6;
      max-width: 320px;
      opacity: 0.85;
    }
    .ella-hero-center-btns {
      display: flex;
      gap: 15px;
      width: 100%;
      max-width: 320px;
    }
    .ella-hero-btn {
      flex: 1;
      background-color: #ffffff;
      color: #000000;
      border: none;
      padding: 12px 0;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      cursor: pointer;
      transition: all 0.2s ease;
      text-align: center;
      text-decoration: none;
    }
    .ella-hero-btn:hover {
      background-color: #000000;
      color: #ffffff;
    }

    /* Category circle carousel styling */
    .ella-category-slider {
      display: none;
      margin-bottom: 50px;
      text-align: center;
    }
    .theme-ella .ella-category-slider {
      display: block;
    }
    .ella-category-slider h3 {
      font-family: 'Jost', 'Cairo', sans-serif !important;
      font-size: 22px;
      font-weight: 700;
      margin-bottom: 25px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
    }
    .ella-categories-grid {
      display: flex;
      justify-content: center;
      gap: 30px;
      flex-wrap: wrap;
    }
    .ella-category-circle-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: #111111;
      cursor: pointer;
    }
    .ella-category-circle-img {
      width: 110px;
      height: 110px;
      border-radius: 50% !important;
      overflow: hidden;
      border: 1px solid #e5e5e5;
      transition: border-color 0.3s ease, transform 0.3s ease;
    }
    .ella-category-circle-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .ella-category-circle-img {
      width: 120px;
      height: 120px;
      border-radius: 50% !important;
      overflow: hidden;
      border: 2px solid #e5e5e5;
      padding: 4px;
      background-color: #ffffff;
      transition: border-color 0.3s ease, transform 0.3s ease;
    }
    .ella-category-circle-img img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50% !important;
    }
    .ella-category-circle-item:hover .ella-category-circle-img {
      border-color: #000000;
      transform: scale(1.05);
    }
    .ella-category-circle-title {
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .ella-category-slider h3 {
      font-family: 'Jost', 'Cairo', sans-serif !important;
      font-size: 24px;
      font-weight: 700;
      margin-bottom: 25px;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      color: #000000;
      position: relative;
      display: inline-block;
    }
    .ella-category-slider h3::after {
      content: '';
      position: absolute;
      bottom: -6px;
      left: 50%;
      transform: translateX(-50%);
      width: 50px;
      height: 2px;
      background-color: #000000;
    }

    /* Ella Slideshow Styling */
    .ella-slideshow {
      display: none;
      position: relative;
      overflow: hidden;
      width: 100%;
      height: 550px;
      margin-bottom: 40px;
    }
    .theme-ella .ella-slideshow {
      display: block;
    }
    .ella-slides-container {
      width: 100%;
      height: 100%;
      position: relative;
    }
    .ella-slide {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.8s ease-in-out, visibility 0.8s ease-in-out;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 40px;
    }
    .ella-slide.active {
      opacity: 1;
      visibility: visible;
      z-index: 2;
    }
    .ella-slide-content {
      max-width: 700px;
      text-align: center;
      color: #ffffff;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px;
      transform: translateY(20px);
      transition: transform 0.8s ease;
    }
    .ella-slide.active .ella-slide-content {
      transform: translateY(0);
    }
    .ella-slide-subtitle {
      font-size: 14px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 2px;
      color: #ffffff;
      opacity: 0.9;
    }
    .ella-slide-title {
      font-family: 'Jost', 'Cairo', sans-serif !important;
      font-size: 48px;
      font-weight: 800;
      text-transform: uppercase;
      line-height: 1.2;
      margin: 0;
      color: #ffffff;
      letter-spacing: 1.5px;
    }
    .ella-slide-desc {
      font-size: 15px;
      line-height: 1.6;
      max-width: 550px;
      opacity: 0.85;
      margin-bottom: 10px;
    }
    .ella-slide-btn {
      background-color: #ffffff;
      color: #000000;
      padding: 14px 35px;
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      border: 1px solid #ffffff;
      text-decoration: none;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    .ella-slide-btn:hover {
      background-color: #000000;
      color: #ffffff;
      border-color: #000000;
    }
    .ella-slideshow-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background-color: rgba(255, 255, 255, 0.7);
      border: 1px solid #e5e5e5;
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: #000000;
      font-size: 16px;
      z-index: 10;
      transition: all 0.2s ease;
    }
    .ella-slideshow-arrow:hover {
      background-color: #000000;
      color: #ffffff;
      border-color: #000000;
    }
    .ella-slideshow-arrow.prev {
      right: 20px;
    }
    .ella-slideshow-arrow.next {
      left: 20px;
    }
    .ella-slideshow-dots {
      position: absolute;
      bottom: 25px;
      left: 50%;
      transform: translateX(-50%);
      display: flex;
      gap: 10px;
      z-index: 10;
    }
    .ella-slideshow-dot {
      width: 10px;
      height: 10px;
      border-radius: 50% !important;
      background-color: rgba(255,255,255,0.4);
      cursor: pointer;
      transition: background-color 0.2s ease;
    }
    .ella-slideshow-dot.active {
      background-color: #ffffff;
    }



    .theme-ella .cart-btn {
      color: #000000 !important;
      border: 1.5px solid #000000 !important;
      padding: 8px 18px !important;
      font-size: 13px !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.5px !important;
      background: #ffffff !important;
    }

    .theme-ella .cart-btn:hover {
      background-color: #000000 !important;
      color: #ffffff !important;
    }

    .theme-ella .cart-badge {
      background-color: #d12442 !important;
      color: #ffffff !important;
      top: -6px !important;
      right: -8px !important;
      font-size: 10px !important;
      width: 20px !important;
      height: 20px !important;
    }

    /* Announcement Bar */
    .ella-announcement-bar {
      display: none;
      background-color: #000000;
      color: #ffffff;
      text-align: center;
      padding: 8px 0;
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 1px;
      text-transform: uppercase;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      direction: rtl;
    }
    .theme-ella .ella-announcement-bar {
      display: block;
    }

    /* Hero Section Overrides */
    .theme-ella .hero-section {
      display: none !important;
    }

    .theme-ella .hero-title {
      font-family: 'Jost', sans-serif !important;
      font-size: 52px !important;
      font-weight: 700 !important;
      color: #ffffff !important;
      text-transform: uppercase !important;
      letter-spacing: 2px !important;
      margin-bottom: 15px !important;
      text-shadow: 1px 1px 4px rgba(0,0,0,0.3);
    }

    .theme-ella .hero-subtitle {
      font-size: 16px !important;
      color: #ffffff !important;
      opacity: 0.95 !important;
      max-width: 650px !important;
      letter-spacing: 0.5px !important;
      text-shadow: 1px 1px 3px rgba(0,0,0,0.3);
    }

    /* Products Grid & Title Overrides */
    .theme-ella .products-title-row {
      border-bottom: 1px solid #e5e5e5 !important;
      padding-bottom: 15px !important;
      margin-bottom: 30px !important;
    }

    .theme-ella .products-title-row h2 {
      font-family: 'Jost', 'Cairo', sans-serif !important;
      color: #000000 !important;
      font-weight: 700 !important;
      font-size: 24px !important;
      text-transform: uppercase !important;
      letter-spacing: 1px !important;
    }

    /* Product Card Overrides */
    .theme-ella .product-card {
      border: 1px solid #e5e5e5 !important;
      box-shadow: none !important;
      transition: border-color 0.3s ease, box-shadow 0.3s ease !important;
      background-color: #ffffff !important;
    }

    .theme-ella .product-card:hover {
      transform: none !important;
      border-color: #000000 !important;
      box-shadow: 0 4px 20px rgba(0,0,0,0.06) !important;
    }

    .theme-ella .product-thumb {
      background-color: #f8f8f8 !important;
      border-bottom: 1px solid #e5e5e5 !important;
      height: 250px !important;
      position: relative;
    }

    .theme-ella .product-thumb img {
      transition: transform 0.5s ease !important;
    }

    .theme-ella .product-card:hover .product-thumb img {
      transform: scale(1.05);
    }

    .theme-ella .product-info {
      padding: 15px !important;
      gap: 8px !important;
    }

    .theme-ella .product-cat {
      font-size: 11px !important;
      color: #777777 !important;
      text-transform: uppercase !important;
      font-weight: 600 !important;
      letter-spacing: 0.5px !important;
    }

    .theme-ella .product-name {
      font-family: 'Jost', 'Cairo', sans-serif !important;
      font-size: 15px !important;
      font-weight: 500 !important;
      color: #000000 !important;
      line-height: 1.4 !important;
      margin: 0 !important;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .theme-ella .product-price {
      font-size: 16px !important;
      font-weight: 700 !important;
      color: #000000 !important;
    }

    /* Product Tags */
    .theme-ella .product-stock-tag {
      font-size: 10px !important;
      font-weight: 700 !important;
      padding: 3px 8px !important;
      text-transform: uppercase !important;
    }

    .theme-ella .stock-in {
      background-color: #ffffff !important;
      color: #000000 !important;
      border: 1px solid #000000 !important;
    }

    .theme-ella .stock-low {
      background-color: #ffffff !important;
      color: #d97706 !important;
      border: 1px solid #d97706 !important;
    }

    .theme-ella .stock-out {
      background-color: #ffffff !important;
      color: #dc2626 !important;
      border: 1px solid #dc2626 !important;
    }

    /* Add To Cart Buttons Overrides */
    .theme-ella .add-to-cart-btn {
      background-color: #000000 !important;
      color: #ffffff !important;
      border: 1.5px solid #000000 !important;
      padding: 12px 10px !important;
      font-size: 13px !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.5px !important;
      transition: all 0.2s ease !important;
    }

    .theme-ella .add-to-cart-btn:hover {
      background-color: #ffffff !important;
      color: #000000 !important;
    }

    .theme-ella .add-to-cart-btn:disabled {
      background-color: #e5e5e5 !important;
      border-color: #e5e5e5 !important;
      color: #999999 !important;
    }

    /* Cart Drawer Overrides */
    .theme-ella .cart-drawer {
      background-color: #ffffff !important;
      max-width: 400px !important;
      border-right: 1px solid #e5e5e5 !important;
    }

    .theme-ella .cart-header {
      background-color: #000000 !important;
      color: #ffffff !important;
      border-bottom: none !important;
      padding: 18px 20px !important;
    }

    .theme-ella .cart-close-btn {
      color: #ffffff !important;
    }

    .theme-ella .cart-item {
      border: 1px solid #e5e5e5 !important;
      padding: 12px !important;
    }

    .theme-ella .cart-item-name {
      color: #000000 !important;
      font-size: 13px !important;
      font-weight: 600 !important;
    }

    .theme-ella .cart-qty-btn {
      background: #ffffff !important;
      border: 1px solid #000000 !important;
      color: #000000 !important;
      width: 24px !important;
      height: 24px !important;
      font-size: 13px !important;
    }

    .theme-ella .cart-qty-btn:hover {
      background: #000000 !important;
      color: #ffffff !important;
    }

    .theme-ella .checkout-btn {
      background-color: #000000 !important;
      color: #ffffff !important;
      border: 1.5px solid #000000 !important;
      padding: 14px !important;
      font-size: 14px !important;
      font-weight: 700 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.5px !important;
    }

    .theme-ella .checkout-btn:hover {
      background-color: #ffffff !important;
      color: #000000 !important;
    }

    /* Modals & Checkout Overrides */
    .theme-ella .checkout-modal {
      border: 1px solid #000000 !important;
      background-color: #ffffff !important;
    }

    .theme-ella .form-label {
      color: #000000 !important;
      text-transform: uppercase !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      letter-spacing: 0.5px !important;
    }

    .theme-ella .form-control {
      border: 1px solid #cccccc !important;
      padding: 12px !important;
    }

    .theme-ella .form-control:focus {
      border-color: #000000 !important;
    }

    /* Footer Overrides */
    .theme-ella footer {
      background-color: #f8f8f8 !important;
      color: #000000 !important;
      border-top: 1px solid #e5e5e5 !important;
      padding: 50px 0 !important;
    }

    .theme-ella footer p {
      color: #666666 !important;
    }

    .theme-ella .footer-links a {
      color: #000000 !important;
      font-weight: 600 !important;
    }

    .theme-ella .footer-links a:hover {
      color: #d12442 !important;
    }

    /* Ella Variant Swatch Layout styles */
    .ella-swatches {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: 5px;
      margin-bottom: 5px;
    }

    .ella-size-swatches, .ella-color-swatches {
      display: flex;
      gap: 6px;
      align-items: center;
      flex-wrap: wrap;
    }

    .ella-size-swatches span, .ella-color-swatches span {
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      color: #777777;
      margin-inline-end: 4px;
    }

    .ella-size-btn {
      min-width: 26px;
      height: 26px;
      border: 1px solid #cccccc;
      background: #ffffff;
      font-size: 10px;
      font-weight: 700;
      color: #111111;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 4px;
      transition: all 0.2s ease;
    }

    .ella-size-btn:hover, .ella-size-btn.selected {
      border-color: #000000;
      background-color: #000000;
      color: #ffffff;
    }

    .ella-color-dot {
      width: 18px;
      height: 18px;
      border-radius: 50% !important;
      border: 1px solid #cccccc;
      cursor: pointer;
      position: relative;
      transition: all 0.2s ease;
    }

    .ella-color-dot.selected {
      border-color: #000000;
      box-shadow: 0 0 0 2px #ffffff, 0 0 0 3px #000000;
    }

    /* Red Ella discount badge inside cards */
    .ella-discount-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background-color: #d12442;
      color: #ffffff;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 8px;
      z-index: 2;
    }

    /* Newsletter Promotion Modal Styling */
    .ella-newsletter-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0,0,0,0.6);
      z-index: 10000;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      visibility: hidden;
      transition: all 0.4s ease;
      padding: 20px;
    }

    .ella-newsletter-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    .ella-newsletter-modal {
      background-color: #ffffff;
      width: 100%;
      max-width: 680px;
      display: grid;
      grid-template-columns: 1.1fr 1fr;
      box-shadow: 0 10px 40px rgba(0,0,0,0.25);
      position: relative;
      animation: ellaSlideUp 0.4s ease forwards;
    }

    @keyframes ellaSlideUp {
      from { transform: translateY(40px); }
      to { transform: translateY(0); }
    }

    .ella-newsletter-image {
      background: url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=600&q=80') no-repeat center center/cover;
      min-height: 380px;
    }

    .ella-newsletter-content {
      padding: 40px 30px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      text-align: center;
      gap: 15px;
    }

    .ella-newsletter-close {
      position: absolute;
      top: 15px;
      left: 15px;
      border: none;
      background: none;
      font-size: 24px;
      cursor: pointer;
      color: #000000;
      font-weight: 300;
      z-index: 10;
    }

    .ella-newsletter-title {
      font-size: 24px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #000000;
    }

    .ella-newsletter-text {
      font-size: 13px;
      color: #666666;
      line-height: 1.5;
    }

    .ella-newsletter-form {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-top: 10px;
    }

    .ella-newsletter-input {
      padding: 12px;
      border: 1px solid #cccccc;
      text-align: center;
      font-size: 13px;
    }

    .ella-newsletter-input:focus {
      border-color: #000000;
      outline: none;
    }

    .ella-newsletter-submit {
      background-color: #000000;
      color: #ffffff;
      border: 1px solid #000000;
      padding: 12px;
      font-weight: 700;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 1px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .ella-newsletter-submit:hover {
      background-color: #ffffff;
      color: #000000;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      font-family: 'Cairo', sans-serif;
    }

    body {
      background-color: var(--bg-cream);
      color: var(--text-charcoal);
      line-height: 1.6;
    }

    h1, h2, h3, h4, .serif-text {
      font-family: 'Amiri', serif;
    }

    /* Container */
    .container {
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 20px;
    }

    /* Header */
    header {
      background-color: var(--primary-color);
      color: #fdfbf7;
      padding: 15px 0;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      border-bottom: 2px solid var(--secondary-color);
    }

    .header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .store-logo {
      font-size: 26px;
      font-weight: 700;
      color: var(--secondary-color);
      text-decoration: none;
      transition: opacity var(--transition-speed);
    }

    .store-logo:hover {
      opacity: 0.9;
    }

    .cart-btn {
      background: none;
      border: none;
      color: #fdfbf7;
      font-size: 20px;
      cursor: pointer;
      position: relative;
      padding: 5px 10px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: color var(--transition-speed);
    }

    .cart-btn:hover {
      color: var(--secondary-color);
    }

    .cart-badge {
      background-color: var(--secondary-color);
      color: var(--primary-color);
      font-size: 11px;
      font-weight: 700;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: absolute;
      top: -2px;
      right: -2px;
    }

    /* Hero Section */
    .hero-section {
      background: linear-gradient(rgba(27, 77, 62, 0.85), rgba(27, 77, 62, 0.95)), 
                  url('https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=1200') no-repeat center center/cover;
      color: #fdfbf7;
      padding: 60px 0;
      text-align: center;
      border-bottom: 3px solid var(--secondary-color);
      margin-bottom: 40px;
    }

    .hero-title {
      font-size: 42px;
      margin-bottom: 12px;
      color: var(--secondary-color);
      font-weight: 700;
    }

    .hero-subtitle {
      font-size: 16px;
      opacity: 0.9;
      max-width: 600px;
      margin: 0 auto;
    }

    /* Product Grid */
    .products-title-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 25px;
      border-bottom: 2px solid var(--border-color);
      padding-bottom: 10px;
    }

    .products-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 30px;
      margin-bottom: 60px;
    }

    .product-card {
      background-color: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      box-shadow: 0 4px 15px rgba(0,0,0,0.02);
      transition: transform var(--transition-speed), box-shadow var(--transition-speed);
      position: relative;
    }

    .product-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 25px rgba(0,0,0,0.06);
      border-color: var(--secondary-color);
    }

    .product-thumb {
      height: 200px;
      background-color: #f4f3ef;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      border-bottom: 1px solid var(--border-color);
      color: var(--primary-color);
      font-size: 48px;
    }

    .product-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .product-info {
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      flex-grow: 1;
    }

    .product-cat {
      font-size: 11px;
      color: var(--secondary-color);
      text-transform: uppercase;
      font-weight: 700;
    }

    .product-name {
      font-size: 18px;
      font-weight: 700;
      color: var(--primary-color);
    }

    .product-price {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-charcoal);
    }

    .product-stock-tag {
      font-size: 11px;
      font-weight: 600;
      align-self: flex-start;
      padding: 2px 8px;
      border-radius: 4px;
    }

    .stock-in {
      background-color: #eef5f2;
      color: #1b4d3e;
    }

    .stock-low {
      background-color: #fff9e6;
      color: #d97706;
    }

    .stock-out {
      background-color: #fef2f2;
      color: #dc2626;
    }

    .add-to-cart-btn {
      background-color: var(--primary-color);
      color: #fdfbf7;
      border: none;
      border-radius: var(--border-radius);
      padding: 10px;
      font-weight: 700;
      cursor: pointer;
      transition: background-color var(--transition-speed);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      margin-top: auto;
    }

    .add-to-cart-btn:hover {
      background-color: #123329;
    }

    .add-to-cart-btn:disabled {
      background-color: var(--border-color);
      color: var(--text-muted);
      cursor: not-allowed;
    }

    /* Cart Drawer */
    .cart-drawer-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0,0,0,0.5);
      z-index: 1000;
      opacity: 0;
      visibility: hidden;
      transition: opacity var(--transition-speed), visibility var(--transition-speed);
    }

    .cart-drawer-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    .cart-drawer {
      position: fixed;
      top: 0;
      left: 0; /* Left side to accommodate RTL layouts naturally */
      width: 100%;
      max-width: 440px;
      height: 100%;
      background-color: var(--bg-cream);
      box-shadow: 5px 0 25px rgba(0,0,0,0.15);
      z-index: 1001;
      transform: translateX(-100%);
      transition: transform var(--transition-speed);
      display: flex;
      flex-direction: column;
    }

    .cart-drawer-overlay.active .cart-drawer {
      transform: translateX(0);
    }

    .cart-header {
      background-color: var(--primary-color);
      color: #fdfbf7;
      padding: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid var(--secondary-color);
    }

    .cart-close-btn {
      background: none;
      border: none;
      color: #fdfbf7;
      font-size: 24px;
      cursor: pointer;
    }

    .cart-items-list {
      padding: 20px;
      overflow-y: auto;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .cart-item {
      display: flex;
      gap: 12px;
      align-items: center;
      background-color: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      padding: 10px;
    }

    .cart-item-thumb {
      width: 50px;
      height: 50px;
      background-color: #f4f3ef;
      border-radius: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      color: var(--primary-color);
    }

    .cart-item-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 4px;
    }

    .cart-item-details {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .cart-item-name {
      font-weight: 700;
      font-size: 13.5px;
      color: var(--primary-color);
    }

    .cart-item-price {
      font-size: 12px;
      color: var(--text-charcoal);
      font-weight: 700;
    }

    .cart-item-actions {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .cart-qty-btn {
      width: 22px;
      height: 22px;
      border-radius: 50%;
      border: 1px solid var(--border-color);
      background: #ffffff;
      font-weight: 700;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
    }

    .cart-qty-btn:hover {
      background-color: var(--bg-cream);
      border-color: var(--secondary-color);
    }

    .cart-summary {
      background-color: var(--bg-card);
      border-top: 1px solid var(--border-color);
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 12px;
      box-shadow: 0 -4px 10px rgba(0,0,0,0.02);
    }

    .summary-row {
      display: flex;
      justify-content: space-between;
      font-size: 14px;
    }

    .summary-total {
      font-size: 18px;
      font-weight: 800;
      color: var(--primary-color);
      border-top: 1px dashed var(--border-color);
      padding-top: 10px;
    }

    .checkout-btn {
      background-color: var(--secondary-color);
      color: var(--primary-color);
      border: none;
      border-radius: var(--border-radius);
      padding: 12px;
      font-weight: 800;
      font-size: 15px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: opacity var(--transition-speed);
      margin-top: 8px;
    }

    .checkout-btn:hover {
      opacity: 0.95;
    }

    /* Checkout Modal */
    .checkout-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0,0,0,0.6);
      z-index: 2000;
      opacity: 0;
      visibility: hidden;
      transition: opacity var(--transition-speed), visibility var(--transition-speed);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .checkout-modal-overlay.active {
      opacity: 1;
      visibility: visible;
    }

    .checkout-modal {
      background-color: var(--bg-cream);
      border: 2px solid var(--secondary-color);
      border-radius: var(--border-radius);
      width: 100%;
      max-width: 520px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      display: flex;
      flex-direction: column;
      overflow: hidden;
      transform: translateY(20px);
      transition: transform var(--transition-speed);
    }

    .checkout-modal-overlay.active .checkout-modal {
      transform: translateY(0);
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-label {
      display: block;
      font-size: 13px;
      font-weight: 700;
      margin-bottom: 6px;
      color: var(--primary-color);
    }

    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      background-color: #ffffff;
      font-size: 14px;
      outline: none;
    }

    .form-control:focus {
      border-color: var(--secondary-color);
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    /* Footer */
    footer {
      background-color: var(--primary-color);
      color: #fdfbf7;
      padding: 40px 0;
      border-top: 3px solid var(--secondary-color);
      text-align: center;
      font-size: 13px;
    }

    .footer-content {
      display: flex;
      flex-direction: column;
      gap: 15px;
      align-items: center;
    }

    .footer-links {
      display: flex;
      gap: 20px;
    }

    .footer-links a {
      color: #fdfbf7;
      text-decoration: none;
      opacity: 0.8;
      transition: opacity var(--transition-speed);
    }

    .footer-links a:hover {
      opacity: 1;
      color: var(--secondary-color);
    }

    /* WoodMart Mega Electronics Theme Variables */
    .theme-woodmart {
      --primary-color: #0b1329; /* Deep Navy */
      --secondary-color: #ffcc00; /* Amber Yellow */
      --bg-cream: #0b1329;
      --bg-card: #ffffff;
      --text-charcoal: #1c2541;
      --text-muted: #5e6e8c;
      --border-color: #e2e8f0;
      --transition-speed: 0.3s;
      --border-radius: 8px;
    }

    .theme-woodmart,
    .theme-woodmart *:not(.fa):not(.fab):not(.far):not(.fas):not(i) {
      font-family: 'Outfit', 'Cairo', sans-serif !important;
    }

    /* Hide standard/Ella elements under WoodMart */
    .theme-woodmart header:not(.woodmart-header),
    .theme-woodmart .hero-section,
    .theme-woodmart .ella-top-bar,
    .theme-woodmart .ella-announcement-bar,
    .theme-woodmart .ella-beige-promo,
    .theme-woodmart .ella-feature-icon-bar,
    .theme-woodmart .ella-slideshow,
    .theme-woodmart .ella-hero-grid,
    .theme-woodmart .ella-category-slider,
    .theme-woodmart .instagram-categories-row {
      display: none !important;
    }

    /* WoodMart Top Bar */
    .woodmart-top-bar {
      display: none;
      background-color: #0b1329;
      color: #a3b1cc;
      border-bottom: 1px solid #1c2541;
      font-size: 12px;
      direction: rtl;
    }
    .theme-woodmart .woodmart-top-bar {
      display: block;
    }
    .woodmart-top-bar-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      height: 40px;
    }
    .woodmart-top-bar-left {
      font-weight: 600;
    }
    .woodmart-top-bar-right {
      display: flex;
      gap: 15px;
    }
    .woodmart-top-bar-right a {
      color: #a3b1cc;
      text-decoration: none;
      transition: color var(--transition-speed);
      display: flex;
      align-items: center;
      gap: 4px;
    }
    .woodmart-top-bar-right a:hover {
      color: #ffcc00;
    }

    /* WoodMart Header */
    .woodmart-header {
      display: none;
      background-color: #0b1329;
      color: #ffffff;
      padding: 15px 0;
      border-bottom: 1px solid #1c2541;
      direction: rtl;
    }
    .theme-woodmart .woodmart-header {
      display: block;
    }
    .woodmart-header-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
    }
    .woodmart-logo {
      font-size: 24px;
      font-weight: 800;
      color: #ffffff;
      text-decoration: none;
      display: flex;
      align-items: center;
    }
    
    /* Search Box */
    .woodmart-search-box {
      flex-grow: 1;
      max-width: 650px;
    }
    .woodmart-search-form {
      display: flex;
      background-color: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      border: 2px solid #ffcc00;
    }
    .woodmart-search-cat-select {
      border: none;
      background-color: #f1f5f9;
      color: #1c2541;
      font-weight: 700;
      font-size: 13px;
      padding: 0 15px;
      outline: none;
      border-inline-end: 1px solid #cbd5e1;
      cursor: pointer;
    }
    .woodmart-search-input {
      flex-grow: 1;
      border: none;
      padding: 10px 15px;
      font-size: 14px;
      color: #1c2541;
      outline: none;
    }
    .woodmart-search-btn {
      background-color: #ffcc00;
      border: none;
      color: #0b1329;
      width: 50px;
      font-size: 16px;
      cursor: pointer;
      transition: background-color var(--transition-speed);
    }
    .woodmart-search-btn:hover {
      background-color: #e6b800;
    }
    
    /* Header Actions */
    .woodmart-header-actions {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .woodmart-action-link {
      display: flex;
      flex-direction: column;
      align-items: center;
      color: #ffffff;
      text-decoration: none;
      font-size: 12px;
      transition: color var(--transition-speed);
      position: relative;
    }
    .woodmart-action-link i {
      font-size: 18px;
      margin-bottom: 4px;
    }
    .woodmart-action-link:hover {
      color: #ffcc00;
    }
    .woodmart-action-link .action-label {
      font-size: 11px;
      opacity: 0.9;
    }
    
    /* Cart Button */
    .woodmart-cart-btn {
      display: flex;
      align-items: center;
      gap: 12px;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      padding: 6px 12px;
      cursor: pointer;
      color: #ffffff;
      transition: all var(--transition-speed);
    }
    .woodmart-cart-btn:hover {
      background: rgba(255, 255, 255, 0.1);
      border-color: #ffcc00;
    }
    .cart-icon-wrapper {
      position: relative;
      font-size: 18px;
    }
    .woodmart-cart-badge {
      position: absolute;
      top: -6px;
      right: -8px;
      background-color: #ffcc00;
      color: #0b1329;
      font-size: 10px;
      font-weight: 800;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .cart-price-info {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      font-size: 11px;
    }
    .cart-price-label {
      opacity: 0.7;
    }
    .cart-price-amount {
      font-weight: 700;
      color: #ffcc00;
    }
    
    /* Navigation Bar */
    .woodmart-nav-bar {
      display: none;
      background-color: #1c2541;
      border-bottom: 2px solid #ffcc00;
      direction: rtl;
    }
    .theme-woodmart .woodmart-nav-bar {
      display: block;
    }
    .woodmart-nav-bar-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .woodmart-nav-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    .woodmart-browse-categories-wrapper {
      position: relative;
      width: 250px;
    }
    .woodmart-browse-btn {
      width: 100%;
      background-color: #ffcc00;
      color: #0b1329;
      border: none;
      padding: 12px 15px;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      border-radius: 0;
    }
    .woodmart-nav-links {
      display: flex;
      gap: 20px;
    }
    .woodmart-nav-link {
      color: #ffffff;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      padding: 12px 5px;
      position: relative;
      transition: color var(--transition-speed);
    }
    .woodmart-nav-link:hover, .woodmart-nav-link.active {
      color: #ffcc00;
    }
    .woodmart-nav-left {
      color: #cbd5e1;
      font-size: 12px;
      padding: 12px 0;
    }

    /* WoodMart Hero Grid split (Categories + Slideshow) */
    .woodmart-hero-grid-container {
      display: none;
      background-color: #0b1329;
      padding: 20px 0;
      direction: rtl;
    }
    .theme-woodmart .woodmart-hero-grid-container {
      display: block;
    }
    .woodmart-hero-split {
      display: flex;
      gap: 20px;
    }
    .woodmart-vertical-menu {
      width: 250px;
      background-color: #ffffff;
      border: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
      border-radius: 8px;
      overflow: hidden;
      flex-shrink: 0;
    }
    .woodmart-menu-item {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      color: #1c2541;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      border-bottom: 1px solid #f1f5f9;
      transition: all var(--transition-speed);
    }
    .woodmart-menu-item:last-child {
      border-bottom: none;
    }
    .woodmart-menu-item:hover, .woodmart-menu-item.active {
      background-color: #f8fafc;
      color: #ffcc00;
      padding-inline-start: 20px;
    }
    .woodmart-menu-item i {
      color: #5e6e8c;
      width: 18px;
    }
    .woodmart-menu-item:hover i {
      color: #ffcc00;
    }

    /* WoodMart Slideshow */
    .woodmart-slideshow {
      flex-grow: 1;
      border-radius: 8px;
      overflow: hidden;
      position: relative;
      height: 380px;
    }
    .woodmart-slides-container {
      width: 100%;
      height: 100%;
      position: relative;
    }
    .woodmart-slide {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-size: cover;
      background-position: center;
      opacity: 0;
      transition: opacity 0.5s ease-in-out;
      z-index: 1;
      display: flex;
      align-items: center;
      padding: 40px;
      color: #ffffff;
    }
    .woodmart-slide.active {
      opacity: 1;
      z-index: 2;
    }
    .woodmart-slide-content {
      max-width: 500px;
      text-align: right;
    }
    .woodmart-slide-tag {
      background-color: #ffcc00;
      color: #0b1329;
      font-size: 11px;
      font-weight: 800;
      padding: 4px 10px;
      border-radius: 4px;
      text-transform: uppercase;
      display: inline-block;
      margin-bottom: 12px;
    }
    .woodmart-slide-title {
      font-size: 38px;
      font-weight: 800;
      margin-bottom: 10px;
      line-height: 1.2;
    }
    .woodmart-slide-desc {
      font-size: 14px;
      opacity: 0.9;
      margin-bottom: 20px;
      line-height: 1.5;
    }
    .woodmart-slide-btn {
      background-color: #ffcc00;
      color: #0b1329;
      border: none;
      padding: 10px 24px;
      font-weight: 700;
      font-size: 13px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color var(--transition-speed);
    }
    .woodmart-slide-btn:hover {
      background-color: #e6b800;
    }
    .woodmart-slideshow-dots {
      position: absolute;
      bottom: 15px;
      left: 15px;
      display: flex;
      gap: 8px;
      z-index: 3;
    }
    .woodmart-dot {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background-color: rgba(255, 255, 255, 0.4);
      cursor: pointer;
      transition: background-color var(--transition-speed);
    }
    .woodmart-dot.active {
      background-color: #ffcc00;
    }

    /* WoodMart Features Row */
    .woodmart-features-row {
      display: none;
      background-color: #ffffff;
      padding: 20px 0;
      border-bottom: 1px solid var(--border-color);
      direction: rtl;
    }
    .theme-woodmart .woodmart-features-row {
      display: block;
    }
    .woodmart-features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 20px;
    }
    .woodmart-feature-box {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .woodmart-feature-box i {
      font-size: 24px;
      color: #0b1329;
      background-color: #ffcc00;
      width: 46px;
      height: 46px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .woodmart-feature-text h4 {
      font-size: 14px;
      font-weight: 700;
      color: #0b1329;
      margin-bottom: 4px;
    }
    .woodmart-feature-text p {
      font-size: 11px;
      color: #5e6e8c;
    }

    /* WoodMart Promo Banners */
    .woodmart-promo-banners {
      display: none;
      padding: 30px 0 10px 0;
      direction: rtl;
      background-color: #f8fafc;
    }
    .theme-woodmart .woodmart-promo-banners {
      display: block;
    }
    .woodmart-promo-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }
    .woodmart-promo-card {
      height: 180px;
      border-radius: 8px;
      overflow: hidden;
      background-size: cover;
      background-position: center;
      display: flex;
      align-items: flex-end;
      padding: 20px;
      color: #ffffff;
      position: relative;
      transition: transform var(--transition-speed);
    }
    .woodmart-promo-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(to top, rgba(11,19,41,0.8) 0%, rgba(11,19,41,0.1) 100%);
      z-index: 1;
    }
    .woodmart-promo-card:hover {
      transform: translateY(-5px);
    }
    .woodmart-promo-text {
      z-index: 2;
    }
    .woodmart-promo-text h3 {
      font-size: 18px;
      font-weight: 800;
      color: #ffcc00;
      margin-bottom: 6px;
    }
    .woodmart-promo-text p {
      font-size: 11px;
      opacity: 0.9;
      margin-bottom: 12px;
    }
    .woodmart-promo-link {
      color: #ffffff;
      text-decoration: none;
      font-size: 12px;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .woodmart-promo-link:hover {
      color: #ffcc00;
    }

    /* WoodMart storefront overrides */
    .theme-woodmart main {
      background-color: #f8fafc !important;
      padding-top: 30px !important;
      padding-bottom: 50px !important;
    }
    .theme-woodmart .products-title-row {
      border-bottom: 2px solid #0b1329 !important;
      padding-bottom: 10px !important;
      margin-bottom: 25px !important;
    }
    .theme-woodmart .products-title-row h2 {
      color: #0b1329 !important;
      font-weight: 800 !important;
    }

    /* Product Card WoodMart Theme styling */
    .theme-woodmart .product-card {
      border-radius: 8px !important;
      overflow: hidden !important;
      border: 1px solid #e2e8f0 !important;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05) !important;
      transition: all var(--transition-speed) !important;
      background-color: #ffffff !important;
    }
    .theme-woodmart .product-card:hover {
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05) !important;
      transform: translateY(-4px) !important;
      border-color: #ffcc00 !important;
    }
    .theme-woodmart .add-to-cart-btn {
      border-radius: 8px !important;
      background-color: #0b1329 !important;
      color: #ffffff !important;
      font-weight: 700 !important;
      font-size: 12px !important;
      padding: 10px 15px !important;
      transition: all var(--transition-speed) !important;
      border: none !important;
    }
    .theme-woodmart .add-to-cart-btn:hover {
      background-color: #ffcc00 !important;
      color: #0b1329 !important;
    }
    .theme-woodmart .add-to-cart-btn:disabled {
      background-color: #cbd5e1 !important;
      color: #64748b !important;
    }
    
    /* WoodMart Review Stars and Stock Bars */
    .woodmart-rating {
      display: flex;
      gap: 2px;
      color: #ffcc00;
      font-size: 11px;
      margin-top: 5px;
      margin-bottom: 5px;
      direction: rtl;
    }
    .woodmart-stock-bar-wrapper {
      margin-top: 8px;
      margin-bottom: 12px;
    }
    .woodmart-stock-bar-info {
      display: flex;
      justify-content: space-between;
      font-size: 10px;
      font-weight: 700;
      color: #5e6e8c;
      margin-bottom: 4px;
    }
    .woodmart-stock-bar-bg {
      height: 6px;
      background-color: #e2e8f0;
      border-radius: 3px;
      overflow: hidden;
    }
    .woodmart-stock-bar-fill {
      height: 100%;
      background-color: #10b981;
      border-radius: 3px;
    }
    .woodmart-stock-bar-fill.low {
      background-color: #ffcc00;
    }
    .woodmart-stock-bar-fill.out {
      background-color: #ef4444;
    }

    /* Cart drawer woodmart overrides */
    .theme-woodmart .cart-drawer {
      border-radius: 8px 0 0 8px !important;
      border-left: 2px solid #ffcc00 !important;
    }
    .theme-woodmart .cart-header {
      background-color: #0b1329 !important;
      color: #ffffff !important;
    }
    .theme-woodmart .checkout-btn {
      background-color: #ffcc00 !important;
      color: #0b1329 !important;
      font-weight: 800 !important;
      border-radius: 8px !important;
    }
    .theme-woodmart .checkout-btn:hover {
      background-color: #e6b800 !important;
    }
    
    /* Checkout modal woodmart overrides */
    .theme-woodmart .checkout-modal {
      border-radius: 8px !important;
      border: 2px solid #ffcc00 !important;
      overflow: hidden !important;
    }
    .theme-woodmart .form-control {
      border-radius: 8px !important;
      border: 1px solid #cbd5e1 !important;
    }
    .theme-woodmart .form-control:focus {
      border-color: #ffcc00 !important;
      box-shadow: 0 0 0 3px rgba(255, 204, 0, 0.2) !important;
    }

    /* Footer woodmart overrides */
    .theme-woodmart footer {
      background-color: #0b1329 !important;
      color: #ffffff !important;
      border-top: 2px solid #ffcc00 !important;
      padding: 40px 0 !important;
    }
    .theme-woodmart footer p {
      color: #a3b1cc !important;
    }
    .theme-woodmart .footer-links a {
      color: #ffffff !important;
    }
    .theme-woodmart .footer-links a:hover {
      color: #ffcc00 !important;
    }
  </style>
  <style>
    :root {
      --primary-color: <?php echo $theme_color; ?> !important;
    }
    .theme-ella {
      --secondary-color: <?php echo $theme_color; ?> !important;
    }
    .brand-logo-text {
      color: <?php echo $theme_color; ?> !important;
    }
  </style>
</head>
<body>
  <!-- WoodMart Top Bar (Visible only under theme-woodmart) -->
  <div class="woodmart-top-bar">
    <div class="container woodmart-top-bar-content">
      <div class="woodmart-top-bar-left">
        <span>أهلاً بكم في ميجا إلكترونيكس - متجر الأجهزة الذكية الأول في سوريا</span>
      </div>
      <div class="woodmart-top-bar-right">
        <a href="#"><i class="fas fa-truck"></i> تتبع الطلب</a>
        <a href="#"><i class="fas fa-info-circle"></i> المساعدة</a>
        <a href="#"><i class="fas fa-map-marker-alt"></i> معارضنا (دمشق، حلب، حمص)</a>
      </div>
    </div>
  </div>

  <!-- WoodMart Header (Visible only under theme-woodmart) -->
  <header class="woodmart-header">
    <div class="container woodmart-header-content">
      <!-- 1. Logo -->
      <a href="#" class="woodmart-logo">
        <i class="fas fa-bolt" style="color: #ffcc00; margin-inline-end: 8px;"></i>
        <span>ميجا إلكترونيكس</span>
      </a>
      
      <!-- 2. Wide Search Bar with mock category dropdown -->
      <div class="woodmart-search-box">
        <form action="#" class="woodmart-search-form" onsubmit="event.preventDefault();">
          <select class="woodmart-search-cat-select" id="woodmart-search-category">
            <option value="all">كل الفئات</option>
            <option value="إلكترونيات">إلكترونيات</option>
            <option value="كتب وأجهزة قراءة">أجهزة قراءة</option>
            <option value="ملابس وأحذية">ملابس رياضية</option>
          </select>
          <input type="text" class="woodmart-search-input" id="woodmart-global-search" placeholder="ابحث عن المنتجات الإلكترونية، الأجهزة المنزلية، ماركات...">
          <button type="button" class="woodmart-search-btn"><i class="fas fa-search"></i></button>
        </form>
      </div>
      
      <!-- 3. Actions Icons (Compare, Wishlist, Cart) -->
      <div class="woodmart-header-actions">
        <a href="#" class="woodmart-action-link" title="مقارنة المنتجات">
          <i class="fas fa-random"></i>
          <span class="action-label">مقارنة</span>
        </a>
        <a href="#" class="woodmart-action-link" title="قائمة المفضلة">
          <i class="far fa-heart"></i>
          <span class="action-label">المفضلة</span>
        </a>
        <button class="woodmart-cart-btn" id="woodmart-btn-toggle-cart">
          <div class="cart-icon-wrapper">
            <i class="fas fa-shopping-bag"></i>
            <span class="woodmart-cart-badge" id="woodmart-cart-count">0</span>
          </div>
          <div class="cart-price-info">
            <span class="cart-price-label">سلة المشتريات</span>
            <span class="cart-price-amount" id="woodmart-cart-total">0 ل.س</span>
          </div>
        </button>
      </div>
    </div>
  </header>

  <!-- WoodMart Navigation Bar (Visible only under theme-woodmart) -->
  <div class="woodmart-nav-bar">
    <div class="container woodmart-nav-bar-content">
      <div class="woodmart-nav-right">
        <div class="woodmart-browse-categories-wrapper">
          <button type="button" class="woodmart-browse-btn" id="woodmart-btn-browse">
            <i class="fas fa-bars"></i>
            <span>تصفح الفئات</span>
            <i class="fas fa-chevron-down" style="font-size: 10px; margin-inline-start: auto;"></i>
          </button>
        </div>
        <nav class="woodmart-nav-links">
          <a href="#" class="woodmart-nav-link active">الرئيسية</a>
          <a href="#" class="woodmart-nav-link">وصلنا حديثاً</a>
          <a href="#" class="woodmart-nav-link">العروض الأقوى</a>
          <a href="#" class="woodmart-nav-link">ماركات عالمية</a>
          <a href="#" class="woodmart-nav-link">المدونة</a>
          <a href="#" class="woodmart-nav-link">اتصل بنا</a>
        </nav>
      </div>
      <div class="woodmart-nav-left">
        <span class="woodmart-promo-nav-text"><i class="fas fa-fire" style="color: #ffcc00; margin-inline-end: 4px;"></i> خصومات تصل إلى 40% على شاشات الألعاب</span>
      </div>
    </div>
  </div>

  <!-- WoodMart Hero Grid split (Vertical Categories + Slideshow) -->
  <div class="woodmart-hero-grid-container">
    <div class="container woodmart-hero-split">
      <!-- Right sidebar: Vertical Category Menu (permanently visible on home) -->
      <div class="woodmart-vertical-menu">
        <a href="#" class="woodmart-menu-item" data-category="إلكترونيات"><i class="fas fa-laptop" style="margin-inline-end: 10px;"></i> لابتوبات وأجهزة كمبيوتر</a>
        <a href="#" class="woodmart-menu-item" data-category="إلكترونيات"><i class="fas fa-mobile-alt" style="margin-inline-end: 10px;"></i> هواتف ذكية وأجهزة لوحية</a>
        <a href="#" class="woodmart-menu-item" data-category="إلكترونيات"><i class="fas fa-gamepad" style="margin-inline-end: 10px;"></i> ألعاب ومعدات جيمينج</a>
        <a href="#" class="woodmart-menu-item" data-category="إلكترونيات"><i class="fas fa-headphones" style="margin-inline-end: 10px;"></i> صوتيات وسماعات رأس</a>
        <a href="#" class="woodmart-menu-item" data-category="إلكترونيات"><i class="fas fa-camera" style="margin-inline-end: 10px;"></i> كاميرات ومعدات تصوير</a>
        <a href="#" class="woodmart-menu-item" data-category="كتب وأجهزة قراءة"><i class="fas fa-book" style="margin-inline-end: 10px;"></i> أجهزة القراءة والكتب الإلكترونية</a>
        <a href="#" class="woodmart-menu-item" data-category="ملابس وأحذية"><i class="fas fa-tshirt" style="margin-inline-end: 10px;"></i> إكسسوارات وملابس رياضية</a>
        <a href="#" class="woodmart-menu-item active" data-category="all"><i class="fas fa-th-large" style="margin-inline-end: 10px;"></i> كافة التصنيفات الأخرى</a>
      </div>
      
      <!-- Left body: Main Slideshow -->
      <div class="woodmart-slideshow" id="woodmart-slideshow-container">
        <div class="woodmart-slides-container" id="woodmart-slides-wrapper">
          <!-- Slide 1: Asus gaming laptop -->
          <div class="woodmart-slide active" style="background-image: linear-gradient(rgba(11, 19, 41, 0.4), rgba(11, 19, 41, 0.7)), url('https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=1200&q=80');">
            <div class="woodmart-slide-content">
              <span class="woodmart-slide-tag">الوحش الجديد للألعاب</span>
              <h2 class="woodmart-slide-title">Asus ROG Strix G16</h2>
              <p class="woodmart-slide-desc">كرت شاشة RTX 4070 ومعالج Core i9. استعد لتجربة لعب خرافية بأعلى جودة مع شحن مجاني وضمان 3 سنوات.</p>
              <button class="woodmart-slide-btn" data-product-id="7">اطلب الآن - $1,850</button>
            </div>
          </div>
          <!-- Slide 2: Samsung S24 Ultra -->
          <div class="woodmart-slide" style="background-image: linear-gradient(rgba(11, 19, 41, 0.4), rgba(11, 19, 41, 0.7)), url('https://images.unsplash.com/photo-1610945265064-0e34e5519bbf?w=1200&q=80');">
            <div class="woodmart-slide-content">
              <span class="woodmart-slide-tag">ثورة الذكاء الاصطناعي</span>
              <h2 class="woodmart-slide-title">Galaxy S24 Ultra</h2>
              <p class="woodmart-slide-desc">كاميرا بدقة 200 ميجابكسل وميزات غوغل الذكية المدمجة. رائد الهواتف متوفر الآن بجميع الألوان.</p>
              <button class="woodmart-slide-btn" data-product-id="8">تسوق الآن - $1,350</button>
            </div>
          </div>
          <!-- Slide 3: PlayStation 5 Slim -->
          <div class="woodmart-slide" style="background-image: linear-gradient(rgba(11, 19, 41, 0.4), rgba(11, 19, 41, 0.7)), url('https://images.unsplash.com/photo-1606813907291-d86efa9b94db?w=1200&q=80');">
            <div class="woodmart-slide-content">
              <span class="woodmart-slide-tag">متعة الترفيه المنزلي</span>
              <h2 class="woodmart-slide-title">PlayStation 5 Slim</h2>
              <p class="woodmart-slide-desc">ألعاب بدقة 4K ومعدل إطارات يصل لـ 120 إطار بالثانية. احصل عليه الآن بسعر خاص ولفترة محدودة.</p>
              <button class="woodmart-slide-btn" data-product-id="11">اكتشف العرض - $499</button>
            </div>
          </div>
        </div>
        <!-- Dots -->
        <div class="woodmart-slideshow-dots" id="woodmart-dots-container">
          <span class="woodmart-dot active" data-index="0"></span>
          <span class="woodmart-dot" data-index="1"></span>
          <span class="woodmart-dot" data-index="2"></span>
        </div>
      </div>
    </div>
  </div>

  <!-- WoodMart Features Row (Visible only under theme-woodmart) -->
  <div class="woodmart-features-row">
    <div class="container woodmart-features-grid">
      <div class="woodmart-feature-box">
        <i class="fas fa-shipping-fast"></i>
        <div class="woodmart-feature-text">
          <h4>شحن سريع ومجاني</h4>
          <p>لكافة المحافظات السورية للطلبات المؤكدة</p>
        </div>
      </div>
      <div class="woodmart-feature-box">
        <i class="fas fa-shield-alt"></i>
        <div class="woodmart-feature-text">
          <h4>ضمان الوكيل الرسمي</h4>
          <p>أجهزة مكفولة وحقيقية 100% مع صيانة</p>
        </div>
      </div>
      <div class="woodmart-feature-box">
        <i class="fas fa-headset"></i>
        <div class="woodmart-feature-text">
          <h4>دعم فني متواصل</h4>
          <p>اتصل بنا على مدار الساعة 24/7 لأي استفسار</p>
        </div>
      </div>
      <div class="woodmart-feature-box">
        <i class="fas fa-sync-alt"></i>
        <div class="woodmart-feature-text">
          <h4>إرجاع سهل وآمن</h4>
          <p>استبدل أو استرجع مشترياتك خلال 14 يوماً</p>
        </div>
      </div>
    </div>
  </div>

  <!-- WoodMart Promo Banners (Visible only under theme-woodmart) -->
  <div class="woodmart-promo-banners">
    <div class="container woodmart-promo-grid">
      <div class="woodmart-promo-card" style="background-image: url('https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=600');">
        <div class="woodmart-promo-text">
          <h3>معدات ألعاب واحتراف</h3>
          <p>سماعات محيطية، كيبورد ميكانيكي وماوسات احترافية</p>
          <a href="#" class="woodmart-promo-link" data-search="العاب">اكتشف العروض <i class="fas fa-arrow-left"></i></a>
        </div>
      </div>
      <div class="woodmart-promo-card" style="background-image: url('https://images.unsplash.com/photo-1545454675-3531b543be5d?w=600');">
        <div class="woodmart-promo-text">
          <h3>أنظمة صوتيات ذكية</h3>
          <p>أقوى مكبرات الصوت JBL وسماعات سوني الفاخرة</p>
          <a href="#" class="woodmart-promo-link" data-search="سماعات">تسوق الآن <i class="fas fa-arrow-left"></i></a>
        </div>
      </div>
      <div class="woodmart-promo-card" style="background-image: url('https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600');">
        <div class="woodmart-promo-text">
          <h3>هواتف ذكية رائدة</h3>
          <p>جالكسي S24، آيفون 15 برو ماكس وغيرها</p>
          <a href="#" class="woodmart-promo-link" data-search="هاتف">تصفح الهواتف <i class="fas fa-arrow-left"></i></a>
        </div>
      </div>
    </div>
  </div>

  <!-- Ella Top Bar (Visible only under theme-ella) -->
  <div class="ella-top-bar">
    <div class="container ella-top-bar-content">
      <div class="ella-top-bar-left">
        <button type="button" class="ella-brand-tab active">إيلا</button>
        <button type="button" class="ella-brand-tab">رجالي</button>
        <button type="button" class="ella-brand-tab">بيل دول</button>
      </div>
      <div class="ella-top-bar-right">
        <span>خصم إضافي 10% على أول طلب لك بالمتجر</span>
        <div class="ella-top-bar-icons">
          <a href="#" title="حسابي"><i class="far fa-user"></i></a>
          <a href="#" title="الموقع الجغرافي"><i class="fas fa-globe"></i></a>
          <a href="#" title="قائمة المفضلة"><i class="far fa-heart"></i></a>
        </div>
      </div>
    </div>
  </div>

  <!-- Announcement Bar (Ella Theme only) -->
  <div class="ella-announcement-bar" id="ella-announcement-bar-container">
    <div class="container" style="display:flex; justify-content:center; align-items:center; gap:20px; flex-wrap:wrap;">
      <span>أهلاً بكم في متجر ELLA للألبسة والموضة</span>
      <span>•</span>
      <span>توصيل سريع لكافة المحافظات السورية</span>
      <span>•</span>
      <span>شحن مجاني للطلبات فوق 100,000 ل.س</span>
    </div>
  </div>

  <!-- Header -->
  <header>
    <div class="container header-content">
      <!-- 1. Logo -->
      <a href="#" class="store-logo" id="store-logo-label">متجر الياسمين الدمشقي</a>
      
      <!-- 2. Search Column (Dynamic in Ella) -->
      <div class="ella-search-col">
        <div class="ella-search-box">
          <input type="text" class="ella-search-input" placeholder="ابحث عن الملابس، الأحذية، الإكسسوارات..." id="ella-global-search">
          <button type="button" class="ella-search-btn"><i class="fas fa-search"></i></button>
        </div>
      </div>
      
      <!-- 3. Navigation Links (Dynamic in Ella) -->
      <nav class="ella-nav-menu">
        <a href="#" class="ella-nav-link">الرئيسية</a>
        <a href="#" class="ella-nav-link">وصلنا حديثاً</a>
        <a href="#" class="ella-nav-link">ملابس نسائية</a>
        <a href="#" class="ella-nav-link">ملابس رجالية</a>
        <a href="#" class="ella-nav-link">حقائب وإكسسوارات</a>
        <a href="#" class="ella-nav-link">عروض خاصة</a>
      </nav>

      <!-- 4. Header Actions (Cart) -->
      <div class="header-actions-col">
        <button class="cart-btn" id="btn-toggle-cart">
          <i class="fas fa-shopping-bag"></i>
          <span>السلة</span>
          <span class="cart-badge" id="cart-count-badge">0</span>
        </button>
      </div>
    </div>
  </header>

  <!-- Ella Double Promo Sub-headers -->
  <div class="ella-beige-promo">
    <div class="container ella-beige-promo-content">
      <div class="ella-beige-promo-col">
        <strong>شحن مجاني للطلبات فوق 100,000 ل.س*</strong>
        <span>بالإضافة إلى توصيل سريع خلال يومين لكافة المحافظات السورية.</span>
      </div>
      <div class="ella-beige-promo-col">
        <strong>قيمة مذهلة وأسعار حقيقية كل يوم</strong>
        <span>كل ما تحبه من تشكيلات الموضة بأسعار تناسب ميزانيتك تماماً.</span>
      </div>
    </div>
  </div>

  <div class="ella-feature-icon-bar">
    <div class="container ella-feature-icon-content">
      <div class="ella-feature-item">
        <i class="fas fa-gift"></i>
        <span>تغليف هدايا مجاني</span>
      </div>
      <div class="ella-feature-item">
        <i class="fas fa-undo"></i>
        <span>إرجاع سهل وسريع</span>
      </div>
      <div class="ella-feature-item">
        <i class="fas fa-percentage"></i>
        <span>خصومات للشباب والطلاب</span>
      </div>
      <div class="ella-feature-item">
        <i class="fas fa-shield-alt"></i>
        <span>تسوق آمن ومحمي 100%</span>
      </div>
    </div>
  </div>

  <!-- Ella Slideshow (Visible only under theme-ella) -->
  <div class="ella-slideshow" id="ella-slideshow-container">
    <div class="ella-slides-container" id="ella-slides-container">
      
      <!-- Slide 1: Women's Collection -->
      <div class="ella-slide active" style="background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)), url('https://images.unsplash.com/photo-1483985988355-763728e1935b?w=1600&q=80');">
        <div class="ella-slide-content">
          <span class="ella-slide-subtitle">تشكيلة ربيع وصيف 2026</span>
          <h2 class="ella-slide-title">أناقة عصرية بلا حدود</h2>
          <p class="ella-slide-desc">اكتشفي أحدث صيحات الموضة النسائية والتصاميم الراقية التي تمنحك إطلالة فريدة وجذابة.</p>
          <a href="#" class="ella-slide-btn" data-collection="فساتين">تسوقي التشكيلة النسائية</a>
        </div>
      </div>
      
      <!-- Slide 2: Men's Collection -->
      <div class="ella-slide" style="background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)), url('https://images.unsplash.com/photo-1488161628813-04466f872be2?w=1600&q=80');">
        <div class="ella-slide-content">
          <span class="ella-slide-subtitle">الملابس الرجالية الفاخرة</span>
          <h2 class="ella-slide-title">تصاميم كلاسيكية وعصرية</h2>
          <p class="ella-slide-desc">مجموعة مميزة من السترات والقمصان المريحة للرجال المصممة بأعلى معايير الجودة.</p>
          <a href="#" class="ella-slide-btn" data-collection="ملابس خارجية">تسوق التشكيلة الرجالية</a>
        </div>
      </div>
      
      <!-- Slide 3: Accessories -->
      <div class="ella-slide" style="background-image: linear-gradient(rgba(0,0,0,0.35), rgba(0,0,0,0.35)), url('https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=1600&q=80');">
        <div class="ella-slide-content">
          <span class="ella-slide-subtitle">إكسسوارات وحقائب أنيقة</span>
          <h2 class="ella-slide-title">اللمسة النهائية المثالية</h2>
          <p class="ella-slide-desc">تألقي مع تشكيلتنا الواسعة من الحقائب والأحذية والنظارات الشمسية الفاخرة.</p>
          <a href="#" class="ella-slide-btn" data-collection="إكسسوارات">اكتشفي الإكسسوارات</a>
        </div>
      </div>
      
    </div>
    
    <!-- Navigation Arrows -->
    <button type="button" class="ella-slideshow-arrow prev" id="ella-slideshow-prev"><i class="fas fa-chevron-right"></i></button>
    <button type="button" class="ella-slideshow-arrow next" id="ella-slideshow-next"><i class="fas fa-chevron-left"></i></button>
    
    <!-- Dots Indicators -->
    <div class="ella-slideshow-dots" id="ella-slideshow-dots">
      <span class="ella-slideshow-dot active" data-index="0"></span>
      <span class="ella-slideshow-dot" data-index="1"></span>
      <span class="ella-slideshow-dot" data-index="2"></span>
    </div>
  </div>

  <!-- Ella Three-Column Instiley Hero Grid -->
  <div class="container ella-hero-grid" style="margin-top: 30px;">
    <div class="ella-hero-side-img">
      <img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?w=800&q=80" alt="أزياء نسائية يسار">
    </div>
    <div class="ella-hero-center-card">
      <span class="ella-hero-center-tag">حدد أسلوبك الفريد</span>
      <h2 class="ella-hero-center-title">إن ستايلي</h2>
      <p class="ella-hero-center-desc">اكتشف مجموعتنا الشتوية والصيفية الفاخرة لعام 2026 المصممة بعناية لتناسب إطلالتك وجاذبيتك.</p>
      <div class="ella-hero-center-btns">
        <button type="button" class="ella-hero-btn">تسوق النسائي</button>
        <button type="button" class="ella-hero-btn">تسوق الرجالي</button>
      </div>
    </div>
    <div class="ella-hero-side-img">
      <img src="https://images.unsplash.com/photo-1549298916-b41d501d3772?w=800&q=80" alt="أزياء نسائية يمين">
    </div>
  </div>

  <!-- Hero Section (Jasmine Theme only) -->
  <section class="hero-section">
    <div class="container">
      <h1 class="hero-title" id="hero-store-title">متجر الياسمين الدمشقي</h1>
      <p class="hero-subtitle">أهلاً بكم في متجرنا لمنتجات السوق السورية التراثية والحديثة. جودة فاخرة وتوصيل سريع لكافة المحافظات السورية.</p>
    </div>
  </section>  <!-- Product Catalog -->
  <main class="container">
    <div class="products-title-row">
      <h2 style="font-size: 22px; color: var(--primary-color);" id="storefront-catalog-title">منتجاتنا المعروضة</h2>
      <div style="font-size: 13px; color: var(--text-muted);" id="storefront-catalog-subtitle">الشحن لكافة المحافظات السورية متوفر</div>
    </div>
    
    <div class="products-grid" id="storefront-products-grid">
      <!-- Dynamically populated products -->
    </div>
  </main>

  <!-- Shopping Cart Drawer -->
  <div class="cart-drawer-overlay" id="cart-drawer-overlay">
    <div class="cart-drawer">
      <div class="cart-header">
        <h3 style="font-size:18px;"><i class="fas fa-shopping-basket"></i> سلة المشتريات</h3>
        <button class="cart-close-btn" id="btn-close-cart">&times;</button>
      </div>
      
      <div class="cart-items-list" id="cart-items-container">
        <!-- Cart lines dynamically rendered -->
      </div>
      
      <div class="cart-summary">
        <div class="summary-row">
          <span>المجموع فرعي</span>
          <span id="summary-subtotal" style="font-weight:700;">0 ل.س</span>
        </div>
        <div class="summary-row" id="summary-discount-row" style="display:none; color: #10b981;">
          <span>خصم الكوبون</span>
          <span id="summary-discount-val" style="font-weight:700;">0 ل.س</span>
        </div>
        <div class="summary-row">
          <span>ضريبة القيمة المضافة (15%)</span>
          <span id="summary-tax" style="font-weight:700;">0 ل.س</span>
        </div>
        <div class="summary-row summary-total">
          <span>الإجمالي النهائي</span>
          <span id="summary-total-final">0 ل.س</span>
        </div>
        <button class="checkout-btn" id="btn-go-checkout">
          <i class="fas fa-credit-card"></i> الانتقال لإتمام الطلب
        </button>
      </div>
    </div>
  </div>

  <!-- Checkout Modal Dialog -->
  <div class="checkout-modal-overlay" id="checkout-modal-overlay">
    <div class="checkout-modal">
      <div class="cart-header">
        <h3 style="font-size:18px;"><i class="fas fa-map-marked-alt"></i> تفاصيل الشحن وعنوان التسليم</h3>
        <button class="cart-close-btn" id="btn-close-checkout">&times;</button>
      </div>
      <form id="storefront-checkout-form" style="padding: 20px;">
        <div class="form-group">
          <label class="form-label">الاسم الكامل للمستلم</label>
          <input type="text" class="form-control" id="cust-fullname" required placeholder="مثال: يوسف الشاملي">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">المحافظة / المدينة</label>
            <select class="form-control" id="cust-city" required>
              <option value="دمشق">دمشق (العاصمة)</option>
              <option value="ريف دمشق">ريف دمشق</option>
              <option value="حلب">حلب الشهباء</option>
              <option value="حمص">حمص العدية</option>
              <option value="حماة">حماة أبي الفداء</option>
              <option value="اللاذقية">اللاذقية (عروس الساحل)</option>
              <option value="طرطوس">طرطوس</option>
              <option value="السويداء">السويداء</option>
              <option value="درعا">درعا</option>
              <option value="دير الزور">دير الزور</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">رقم الهاتف الجوال</label>
            <input type="text" class="form-control" id="cust-phone" required placeholder="مثال: 0933XXXXXX">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">العنوان التفصيلي بالتفصيل</label>
          <input type="text" class="form-control" id="cust-address" required placeholder="مثال: الميدان، طلعة الجزماتية، مقابل صيدلية الوفاء">
        </div>
        <div class="form-group">
          <label class="form-label">طريقة الدفع المتاحة في سوريا</label>
          <select class="form-control" id="cust-payment" required>
            <option value="COD">الدفع نقداً عند استلام البضائع (COD)</option>
            <option value="E-Pay">الدفع الإلكتروني (بوابات الدفع المحلية)</option>
          </select>
        </div>

        <!-- Promo Coupon Application Box -->
        <div class="form-group" style="margin-top: 15px; border-top: 1px dashed var(--border-color); padding-top: 15px;">
          <label class="form-label">هل لديك كوبون خصم؟</label>
          <div style="display: flex; gap: 8px;">
            <input type="text" class="form-control" id="promo-code-input" placeholder="أدخل رمز الكوبون..." style="text-transform: uppercase;">
            <button type="button" class="checkout-btn" id="btn-apply-promo" style="padding: 0 16px; white-space: nowrap; font-size: 13px; margin: 0; width: auto;">تطبيق</button>
          </div>
          <span id="promo-feedback" style="display: none; font-size: 11px; margin-top: 4px; font-weight: 600;"></span>
        </div>
        
        <div style="margin-top:20px; border-top:1px dashed var(--border-color); padding-top:15px; display:flex; flex-direction:column; gap:10px;">
          <div id="checkout-discount-row" style="display:none; justify-content:space-between; color: #10b981; font-weight: 700; font-size: 14px;">
            <span>خصم الكوبون المطبق:</span>
            <span id="checkout-discount-val">0 ل.س</span>
          </div>
          <div style="display:flex; justify-content:space-between; font-weight:700;">
            <span>المبلغ المطلوب تسديده لشركة الشحن:</span>
            <span id="checkout-total-label" style="color:var(--primary-color);">0 ل.س</span>
          </div>
          <button type="submit" class="checkout-btn" style="width:100%; margin-top:5px;">
            <i class="fas fa-check-circle"></i> تأكيد الطلب وإرسال الشحنة للمستودعات
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Product Details Modal Dialog -->
  <div class="checkout-modal-overlay" id="product-detail-modal-overlay" style="z-index: 100000; display: flex; align-items: center; justify-content: center; visibility: hidden; opacity: 0; transition: opacity 0.3s, visibility 0.3s;">
    <div class="checkout-modal" style="max-width: 900px; width: 95%; max-height: 90vh; padding: 0; overflow: hidden; border-radius: 12px; transform: translateY(20px); transition: transform 0.3s;">
      <div class="cart-header" style="padding: 16px 20px; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
        <h3 style="font-size:18px; margin:0;" id="detail-modal-title"><i class="fas fa-info-circle"></i> تفاصيل المنتج</h3>
        <button class="cart-close-btn" id="btn-close-product-detail" style="font-size: 28px; cursor: pointer; background: none; border: none; line-height: 1;">&times;</button>
      </div>
      
      <div class="product-detail-content" style="max-height: calc(90vh - 60px); overflow-y: auto; padding: 24px; direction: rtl; text-align: right;">
        <div class="product-detail-grid" style="display: grid; grid-template-columns: 1fr 1.2fr; gap: 32px;">
          <!-- Right Column: Product Image -->
          <div class="product-detail-media" style="display: flex; flex-direction: column; gap: 16px;">
            <div id="detail-product-image-container" style="width: 100%; height: 380px; border-radius: 8px; overflow: hidden; background: var(--bg-tertiary); display: flex; align-items: center; justify-content: center; border: 1px solid var(--border-color);">
              <!-- Rendered via JS -->
            </div>
          </div>
          
          <!-- Left Column: Product Details Info -->
          <div class="product-detail-info" style="display: flex; flex-direction: column; gap: 16px; justify-content: space-between;">
            <div>
              <span id="detail-product-cat" style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; font-weight: 600;"></span>
              <h2 id="detail-product-name" style="font-size: 22px; font-weight: 700; margin: 4px 0 8px 0; line-height: 1.3; color: var(--secondary-color);"></h2>
              <div id="detail-product-rating" style="display: flex; align-items: center; gap: 8px; font-size: 13px; margin-bottom: 8px;">
                <!-- Stars rendered via JS -->
              </div>
            </div>
            
            <div style="font-size: 22px; font-weight: 800; color: var(--primary-color); display: flex; align-items: baseline; gap: 8px; margin-bottom: 8px;">
              <span id="detail-product-price-syp">0 ل.س</span>
              <span id="detail-product-price-usd" style="font-size: 14px; color: var(--text-muted); font-weight: 500;">($0)</span>
            </div>
            
            <div style="border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 12px 0; margin-bottom: 8px;">
              <h4 style="margin: 0 0 6px 0; font-size: 13px; color: var(--secondary-color);">نبذة سريعة:</h4>
              <p id="detail-product-short-desc" style="margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.5;"></p>
            </div>
            
            <!-- Variants Selection -->
            <div id="detail-variants-container" style="margin-bottom: 8px;">
              <!-- Rendered via JS -->
            </div>
            
            <!-- Stock & Add to Cart -->
            <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 8px;">
              <div style="display: flex; align-items: center; justify-content: space-between;">
                <span id="detail-product-stock-tag" class="product-stock-tag stock-in" style="font-size: 12px; padding: 4px 8px;"></span>
                <span style="font-size: 12px; color: var(--text-muted); font-family: var(--font-english);" id="detail-product-sku"></span>
              </div>
              <div style="display: flex; gap: 12px; align-items: center;">
                <div style="display: flex; border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden; height: 42px; background: var(--bg-tertiary);">
                  <button type="button" id="btn-detail-qty-minus" style="border: none; background: none; width: 36px; cursor: pointer; font-weight: bold; font-size: 16px;">-</button>
                  <input type="text" id="detail-qty-input" value="1" readonly style="width: 45px; text-align: center; border: none; font-weight: 700; background: none; font-family: var(--font-english); font-size: 14px; color: var(--text-primary);">
                  <button type="button" id="btn-detail-qty-plus" style="border: none; background: none; width: 36px; cursor: pointer; font-weight: bold; font-size: 16px;">+</button>
                </div>
                <button type="button" class="checkout-btn" id="btn-detail-add-to-cart" style="flex-grow: 1; height: 42px; margin: 0; display: flex; align-items: center; justify-content: center; gap: 8px;">
                  <i class="fas fa-shopping-cart"></i>
                  <span>أضف إلى السلة</span>
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Tabs Section: Description & Reviews -->
        <div style="margin-top: 32px; border-top: 1px solid var(--border-color); padding-top: 24px;">
          <div style="display: flex; gap: 16px; border-bottom: 2px solid var(--border-color); margin-bottom: 20px;">
            <button type="button" class="detail-tab-btn active-tab" id="btn-tab-desc" style="background: none; border: none; padding: 10px 16px; font-weight: 700; font-size: 14px; cursor: pointer; border-bottom: 2px solid var(--primary-color); color: var(--primary-color); margin-bottom: -2px; transition: all 0.2s;">الوصف الكامل</button>
            <button type="button" class="detail-tab-btn" id="btn-tab-reviews" style="background: none; border: none; padding: 10px 16px; font-weight: 700; font-size: 14px; cursor: pointer; color: var(--text-muted); margin-bottom: -2px; transition: all 0.2s;">التقييمات والآراء (<span id="detail-reviews-count">0</span>)</button>
          </div>
          
          <div id="tab-desc" class="detail-tab-content" style="display: block;">
            <p id="detail-product-full-desc" style="font-size: 14px; color: var(--text-muted); line-height: 1.6; margin: 0; white-space: pre-line;"></p>
          </div>
          
          <div id="tab-reviews" class="detail-tab-content" style="display: none;">
            <!-- Reviews List -->
            <div id="detail-reviews-list" style="display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px;">
              <!-- Rendered via JS -->
            </div>
            
            <!-- Write Review Form -->
            <div style="background: var(--bg-secondary); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color);">
              <h4 style="margin: 0 0 16px 0; font-size: 14px; color: var(--secondary-color);"><i class="fas fa-pen"></i> كتابة مراجعة وتقييم للمنتج</h4>
              <form id="new-review-form">
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 12px;">
                  <div class="form-group" style="margin-bottom: 0; display:flex; flex-direction:column; gap:4px;">
                    <label class="form-label" style="font-size: 12px; margin:0;">اسمك الكريم</label>
                    <input type="text" class="form-control" id="review-author" required placeholder="مثال: محمد الأحمد" style="height:38px; padding: 0 12px;">
                  </div>
                  <div class="form-group" style="margin-bottom: 0; display:flex; flex-direction:column; gap:4px;">
                    <label class="form-label" style="font-size: 12px; margin:0;">التقييم بالنجوم</label>
                    <div style="display: flex; gap: 6px; font-size: 20px; color: #cbd5e1; margin-top: 4px;" id="star-rating-selector">
                      <i class="far fa-star rating-star" data-value="1" style="cursor: pointer; color: #f59e0b;"></i>
                      <i class="far fa-star rating-star" data-value="2" style="cursor: pointer; color: #f59e0b;"></i>
                      <i class="far fa-star rating-star" data-value="3" style="cursor: pointer; color: #f59e0b;"></i>
                      <i class="far fa-star rating-star" data-value="4" style="cursor: pointer; color: #f59e0b;"></i>
                      <i class="far fa-star rating-star" data-value="5" style="cursor: pointer; color: #f59e0b;"></i>
                    </div>
                    <input type="hidden" id="review-rating-value" value="5">
                  </div>
                </div>
                <div class="form-group" style="margin-bottom: 16px; display:flex; flex-direction:column; gap:4px;">
                  <label class="form-label" style="font-size: 12px; margin:0;">تعليقك وتقييمك للمنتج</label>
                  <textarea class="form-control" id="review-text" required rows="3" placeholder="اكتب رأيك الصادق في جودة المنتج والأداء هنا..." style="resize: vertical; min-height: 70px; padding: 8px 12px;"></textarea>
                </div>
                <button type="submit" class="checkout-btn" style="margin: 0; width: auto; padding: 0 24px; height: 38px; font-size: 13px;">إرسال التقييم للمراجعة</button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Ella Circular Categories Carousel -->
  <div class="container ella-category-slider">
    <h3>تسوق حسب التشكيلات</h3>
    <div class="ella-categories-grid">
      <a href="#" class="ella-category-circle-item" data-collection="فساتين">
        <div class="ella-category-circle-img">
          <img src="https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=300&q=80" alt="فساتين نسائية">
        </div>
        <span class="ella-category-circle-title">فساتين</span>
      </a>
      <a href="#" class="ella-category-circle-item" data-collection="ملابس خارجية">
        <div class="ella-category-circle-img">
          <img src="https://images.unsplash.com/photo-1544022613-e87ca75a784a?w=300&q=80" alt="معاطف وجواكيت">
        </div>
        <span class="ella-category-circle-title">ملابس خارجية</span>
      </a>
      <a href="#" class="ella-category-circle-item" data-collection="أحذية">
        <div class="ella-category-circle-img">
          <img src="https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=300&q=80" alt="أحذية أنيقة">
        </div>
        <span class="ella-category-circle-title">أحذية</span>
      </a>
      <a href="#" class="ella-category-circle-item" data-collection="حقائب">
        <div class="ella-category-circle-img">
          <img src="https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=300&q=80" alt="حقائب يد">
        </div>
        <span class="ella-category-circle-title">حقائب</span>
      </a>
      <a href="#" class="ella-category-circle-item" data-collection="إكسسوارات">
        <div class="ella-category-circle-img">
          <img src="https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?w=300&q=80" alt="إكسسوارات الموضة">
        </div>
        <span class="ella-category-circle-title">إكسسوارات</span>
      </a>
    </div>
  </div>

  <!-- Ella Premium Newsletter Popup Modal -->
  <div class="ella-newsletter-overlay" id="ella-newsletter-popup">
    <div class="ella-newsletter-modal">
      <button class="ella-newsletter-close" id="btn-close-newsletter">&times;</button>
      <div class="ella-newsletter-image"></div>
      <div class="ella-newsletter-content">
        <h3 class="ella-newsletter-title">انضم إلينا الآن</h3>
        <p class="ella-newsletter-text">اشترك في النشرة البريدية للمتجر واحصل على خصم فوري بقيمة <strong>10%</strong> على طلبك الأول!</p>
        <form class="ella-newsletter-form" id="ella-newsletter-subscribe-form">
          <input type="email" class="ella-newsletter-input" placeholder="عنوان بريدك الإلكتروني" required>
          <button type="submit" class="ella-newsletter-submit">اشترك الآن واحصل على الخصم</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer>
    <div class="container footer-content">
      <div style="font-weight:700; color:var(--secondary-color); font-size:16px;" id="footer-logo">متجر الياسمين الدمشقي</div>
      <div class="footer-links">
        <a href="store-manager.html"><i class="fas fa-lock"></i> لوحة الإدارة والتحكم ERP</a>
        <a href="#">سياسة الخصوصية</a>
        <a href="#">شروط التوصيل</a>
      </div>
      <p style="margin-top:10px; opacity:0.6;">&copy; 2026 متجر الياسمين الدمشقي. مدعوم بنظام متجر المتكامل للمستودعات السورية.</p>
    </div>
  </footer>

  <!-- Script Logic Integration -->
  <script type="module">
    import { store } from './js/store.js';
    // Injected SaaS tenant context
    window.ActiveTenant = <?php echo json_encode($tenant); ?>;
    window.ActiveTheme = '<?php echo $active_theme; ?>';


    let cart = []; // Array of { productId, qty, price, size, color }
    let activeSelections = {}; // Maps product ID to selected variant: { size: '...', color: '...' }
    let activeBrandFilter = 'إيلا'; 
    let activeCollectionFilter = 'الكل';
    let currentSlideIndex = 0;
    let slideshowInterval;
    let activeTheme = 'jasmine'; // global theme variable
    let appliedCoupon = null;

    document.addEventListener('DOMContentLoaded', async () => {
      // Support URL preview parameter
      const urlParams = new URLSearchParams(window.location.search);
      const previewTheme = urlParams.get('preview');
      activeTheme = previewTheme || window.ActiveTheme || 'jasmine';

      // Load all database tables into cache
      await store.loadAllData();

      loadStoreConfigurations();
      renderCatalog();
      setupEvents();
      initNewsletterPromo();
    });

    function loadStoreConfigurations() {
      const storeName = window.ActiveTenant ? window.ActiveTenant.name : 'متجر الياسمين الدمشقي';

      if (activeTheme === 'woodmart') {
        document.body.classList.add('theme-woodmart');
        document.body.classList.remove('theme-ella');
        document.title = storeName + ' - ميجا إلكترونيكس (WoodMart)';
        
        const footerLogo = document.getElementById('footer-logo');
        if (footerLogo) footerLogo.innerText = 'ميجا إلكترونيكس';
        
        // Update catalog headers dynamically for WoodMart
        const catTitle = document.getElementById('storefront-catalog-title');
        const catSub = document.getElementById('storefront-catalog-subtitle');
        if (catTitle) catTitle.innerText = 'أقوى العروض والمنتجات التقنية';
        if (catSub) catSub.innerText = 'منتجات أصلية 100% مع ضمان رسمي وتوصيل سريع لكافة المحافظات';

        const annBar = document.getElementById('ella-announcement-bar-container');
        if (annBar) annBar.style.display = 'none';
      } else if (activeTheme === 'ella') {
        document.body.classList.add('theme-ella');
        document.body.classList.remove('theme-woodmart');
        document.getElementById('store-logo-label').innerText = 'إيلا للأزياء';
        document.getElementById('footer-logo').innerText = 'إيلا للأزياء';
        document.title = storeName + ' - ثيم إيلا للألبسة';
        
        // Update catalog headers dynamically for Ella
        const catTitle = document.getElementById('storefront-catalog-title');
        const catSub = document.getElementById('storefront-catalog-subtitle');
        if (catTitle) catTitle.innerText = 'أحدث الأزياء والتصاميم';
        if (catSub) catSub.innerText = 'تصفح تشكيلتنا الحصرية المختارة بعناية';
        
        // Seed clothing products if they don't exist yet
        let products = store.getProducts();
        const hasElla = products.some(p => p.id.startsWith('ella-'));
        if (!hasElla) {
          const ellaProducts = [
            { id: 'ella-p1', sku: 'ELLA-DRS-01', name: 'فستان مخمل أحمر كلاسيكي بكسرات', category: 'ملابس نسائية', price: 95, cost: 65, stock: 12, minStock: 2, status: 'In Stock', imageUrl: 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=600&q=80' },
            { id: 'ella-p2', sku: 'ELLA-COT-02', name: 'معطف طويل صوف بيج خريفي فاخر', category: 'ملابس نسائية', price: 140, cost: 95, stock: 8, minStock: 2, status: 'In Stock', imageUrl: 'https://images.unsplash.com/photo-1539571696357-5a69c17a67c6?w=600&q=80' },
            { id: 'ella-p3', sku: 'ELLA-SUIT-03', name: 'طقم بليزر رسمي بني قطعتين للسيدات', category: 'ملابس نسائية', price: 180, cost: 120, stock: 5, minStock: 1, status: 'In Stock', imageUrl: 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?w=600&q=80' },
            { id: 'ella-p4', sku: 'ELLA-HD-04', name: 'كنزة قطنية هودي شبابي رمادي كاجوال', category: 'ملابس رجالية', price: 48, cost: 30, stock: 20, minStock: 3, status: 'In Stock', imageUrl: 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?w=600&q=80' },
            { id: 'ella-p5', sku: 'ELLA-JCK-05', name: 'جاكيت جينز أزرق كلاسيكي ممزق', category: 'ملابس رجالية', price: 65, cost: 42, stock: 15, minStock: 3, status: 'In Stock', imageUrl: 'https://images.unsplash.com/photo-1576995853123-5a10305d93c0?w=600&q=80' },
            { id: 'ella-p6', sku: 'ELLA-SH-06', name: 'حذاء كعب عالي كلاسيكي جلدي ناعم', category: 'أحذية نسائية', price: 75, cost: 50, stock: 10, minStock: 2, status: 'In Stock', imageUrl: 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=600&q=80' },
            { id: 'ella-p7', sku: 'ELLA-TSH-07', name: 'تي شيرت قطني أبيض مطبوع عصري', category: 'ملابس رجالية', price: 25, cost: 15, stock: 35, minStock: 5, status: 'In Stock', imageUrl: 'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?w=600&q=80' },
            { id: 'ella-p8', sku: 'ELLA-SKR-08', name: 'تنورة بليسيه طويلة خضراء ساتان', category: 'ملابس نسائية', price: 55, cost: 35, stock: 14, minStock: 2, status: 'In Stock', imageUrl: 'https://images.unsplash.com/photo-1583496661160-fb5886a0aaaa?w=600&q=80' },
            { id: 'ella-p9', sku: 'ELLA-BAG-09', name: 'حقيبة يد جلدية حمراء أنيقة بمسند ذهبي', category: 'حقائب وإكسسوارات', price: 110, cost: 75, stock: 7, minStock: 2, status: 'In Stock', imageUrl: 'https://images.unsplash.com/photo-1584917865442-de89df76afd3?w=600&q=80' },
            { id: 'ella-p10', sku: 'ELLA-GLS-10', name: 'نظارات شمسية إطار أسود معدني فاخر', category: 'حقائب وإكسسوارات', price: 35, cost: 22, stock: 25, minStock: 4, status: 'In Stock', imageUrl: 'https://images.unsplash.com/photo-1511556532299-8f662fc26c06?w=600&q=80' }
          ];
          products = [...ellaProducts, ...products];
          store.saveProducts(products);
        }

        // Update announcement bar visibility
        const annBar = document.getElementById('ella-announcement-bar-container');
        if (annBar) annBar.style.display = 'block';
      } else {
        document.body.classList.remove('theme-ella');
        document.body.classList.remove('theme-woodmart');
        document.getElementById('store-logo-label').innerText = storeName;
        document.getElementById('footer-logo').innerText = storeName;
        document.title = storeName + ' - ثيم الياسمين';
        
        // Update catalog headers dynamically for Jasmine
        const catTitle = document.getElementById('storefront-catalog-title');
        const catSub = document.getElementById('storefront-catalog-subtitle');
        if (catTitle) catTitle.innerText = 'منتجاتنا المعروضة';
        if (catSub) catSub.innerText = 'الشحن لكافة المحافظات السورية متوفر';

        const annBar = document.getElementById('ella-announcement-bar-container');
        if (annBar) annBar.style.display = 'none';
      }
    }

    function renderCatalog() {
      let products = store.getProducts();

      // Filter products by eCommerce sales channel
      products = products.filter(p => !p.salesChannels || p.salesChannels.includes('ecommerce'));

      // Filter catalog products depending on active theme
      if (activeTheme === 'woodmart') {
        products = products.filter(p => !p.id.startsWith('ella-'));
        
        // Filter by category
        if (activeCollectionFilter !== 'الكل' && activeCollectionFilter !== 'all') {
          products = products.filter(p => p.category === activeCollectionFilter);
        } else {
          // If no specific category is selected, let's only show electronics and books/reading devices by default
          products = products.filter(p => p.category === 'إلكترونيات' || p.category === 'كتب وأجهزة قراءة');
        }
      } else if (activeTheme === 'ella') {
        products = products.filter(p => p.id.startsWith('ella-'));
        
        // Brand filter
        if (activeBrandFilter === 'إيلا') {
          products = products.filter(p => p.category === 'ملابس نسائية' || p.category === 'أحذية نسائية');
        } else if (activeBrandFilter === 'رجالي') {
          products = products.filter(p => p.category === 'ملابس رجالية');
        } else if (activeBrandFilter === 'بيل دول') {
          products = products.filter(p => p.category === 'حقائب وإكسسوارات');
        }
        
        // Collection circle filter
        if (activeCollectionFilter !== 'الكل') {
          if (activeCollectionFilter === 'فساتين') {
            products = products.filter(p => p.name.includes('فستان') || p.name.includes('تنورة'));
          } else if (activeCollectionFilter === 'ملابس خارجية') {
            products = products.filter(p => p.name.includes('معطف') || p.name.includes('بليزر') || p.name.includes('جاكيت') || p.name.includes('هودي'));
          } else if (activeCollectionFilter === 'أحذية') {
            products = products.filter(p => p.category.includes('أحذية') || p.name.includes('حذاء'));
          } else if (activeCollectionFilter === 'حقائب') {
            products = products.filter(p => p.category.includes('حقائب') || p.name.includes('حقيبة'));
          } else if (activeCollectionFilter === 'إكسسوارات') {
            products = products.filter(p => p.category.includes('إكسسوارات') || p.name.includes('نظارات'));
          }
        }
      } else {
        products = products.filter(p => !p.id.startsWith('ella-'));
      }

      // Search bar filter
      const searchInput = activeTheme === 'woodmart'
        ? document.getElementById('woodmart-global-search')
        : document.getElementById('ella-global-search');
      const searchQuery = searchInput ? searchInput.value.trim().toLowerCase() : '';
      if (searchQuery) {
        products = products.filter(p => 
          p.name.toLowerCase().includes(searchQuery) || 
          p.category.toLowerCase().includes(searchQuery)
        );
      }
      const grid = document.getElementById('storefront-products-grid');
      if (!grid) return;

      // Initialize default selections for all products if not already set
      products.forEach(p => {
        if (!activeSelections[p.id]) {
          let sizes = ['S', 'M', 'L', 'XL'];
          let colors = [{ name: 'أسود', hex: '#000000' }, { name: 'أبيض', hex: '#ffffff' }, { name: 'أحمر', hex: '#ef4444' }, { name: 'أزرق', hex: '#3b82f6' }];
          
          if (p.category.includes('إلكترونيات') || p.category.includes('هواتف')) {
            sizes = ['128GB', '256GB', '512GB'];
            colors = [{ name: 'رمادي', hex: '#2b2b2b' }, { name: 'فضي', hex: '#e3e3e3' }, { name: 'ذهبي', hex: '#c5a880' }];
          } else if (!p.category.includes('ملابس') && !p.category.includes('أحذية')) {
            sizes = ['عادي', 'كبير'];
            colors = [{ name: 'أبيض', hex: '#ffffff' }, { name: 'أسود', hex: '#000000' }];
          }
          
          activeSelections[p.id] = {
            size: sizes[0],
            color: colors[0].name
          };
        }
      });

      // SYP Factor simulation for realistic pricing in Syrian Pounds
      const sypFactor = 15000; 

      grid.innerHTML = products.map(p => {
        const isOutOfStock = p.stock === 0;
        const isLowStock = p.stock > 0 && p.stock <= p.minStock;
        
        let stockClass = 'stock-in';
        let stockText = 'متوفر في المستودع';
        if (isOutOfStock) {
          stockClass = 'stock-out';
          stockText = 'نفد المخزون';
        } else if (isLowStock) {
          stockClass = 'stock-low';
          stockText = `كمية محدودة (${p.stock} قطع)`;
        }

        // Category icon logic
        let iconClass = 'fa-box';
        if (p.category.includes('إلكترونيات') || p.category.includes('هواتف')) iconClass = 'fa-laptop';
        else if (p.category.includes('ملابس') || p.category.includes('أحذية')) iconClass = 'fa-tshirt';

        const thumbnail = p.imageUrl 
          ? `<img src="${p.imageUrl}" alt="${p.name}">` 
          : `<div style="height:100%; display:flex; align-items:center; justify-content:center;"><i class="fas ${iconClass}"></i></div>`;

        // Format price realistically in SYP (multiplying product default price by factor)
        const priceSYP = p.price * sypFactor;

        // Ella/WoodMart theme swatches & discount badge & reviews & stock bar
        let swatchesHTML = '';
        let discountBadgeHTML = '';
        let starsHTML = '';
        let stockBarHTML = '';
        
        if (activeTheme === 'ella') {
          let sizes = ['S', 'M', 'L', 'XL'];
          let colors = [{ name: 'أسود', hex: '#000000' }, { name: 'أبيض', hex: '#ffffff' }, { name: 'أحمر', hex: '#ef4444' }, { name: 'أزرق', hex: '#3b82f6' }];
          
          if (p.category.includes('إلكترونيات') || p.category.includes('هواتف')) {
            sizes = ['128GB', '256GB', '512GB'];
            colors = [{ name: 'رمادي', hex: '#2b2b2b' }, { name: 'فضي', hex: '#e3e3e3' }, { name: 'ذهبي', hex: '#c5a880' }];
          } else if (!p.category.includes('ملابس') && !p.category.includes('أحذية')) {
            sizes = ['عادي', 'كبير'];
            colors = [{ name: 'أبيض', hex: '#ffffff' }, { name: 'أسود', hex: '#000000' }];
          }
          
          discountBadgeHTML = `<div class="ella-discount-badge">خصم 20%</div>`;
          swatchesHTML = `
            <div class="ella-swatches">
              <div class="ella-size-swatches">
                <span>المقاس:</span>
                ${sizes.map(s => `
                  <button type="button" class="ella-size-btn ${activeSelections[p.id].size === s ? 'selected' : ''}" data-id="${p.id}" data-size="${s}">${s}</button>
                `).join('')}
              </div>
              <div class="ella-color-swatches">
                <span>اللون:</span>
                ${colors.map(c => `
                  <div class="ella-color-dot ${activeSelections[p.id].color === c.name ? 'selected' : ''}" data-id="${p.id}" data-color="${c.name}" style="background-color: ${c.hex};" title="${c.name}"></div>
                `).join('')}
              </div>
            </div>
          `;
        } else if (activeTheme === 'woodmart') {
          // Stars Rating
          const rating = (parseInt(p.id) % 3 === 0) ? '4.5' : (parseInt(p.id) % 2 === 0) ? '5.0' : '4.8';
          const fullStars = Math.floor(parseFloat(rating));
          const hasHalf = parseFloat(rating) % 1 !== 0;
          let starsIcon = '';
          for (let i = 0; i < 5; i++) {
            if (i < fullStars) starsIcon += '<i class="fas fa-star"></i>';
            else if (i === fullStars && hasHalf) starsIcon += '<i class="fas fa-star-half-alt"></i>';
            else starsIcon += '<i class="far fa-star"></i>';
          }
          starsHTML = `<div class="woodmart-rating">${starsIcon} <span>(${rating})</span></div>`;
          
          // Stock Level Bar
          const sold = ((parseInt(p.id) * 7) % 30) + 10;
          const totalStock = sold + p.stock;
          const percent = totalStock > 0 ? Math.round((sold / totalStock) * 100) : 0;
          stockBarHTML = `
            <div class="woodmart-stock-bar-wrapper">
              <div class="woodmart-stock-bar-info">
                <span>المباع: ${sold} قطعة</span>
                <span>المتبقي: ${p.stock} قطعة</span>
              </div>
              <div class="woodmart-stock-bar-bg">
                <div class="woodmart-stock-bar-fill ${p.stock <= p.minStock ? 'low' : ''} ${p.stock === 0 ? 'out' : ''}" style="width: ${percent}%"></div>
              </div>
            </div>
          `;
          
          discountBadgeHTML = `<div class="ella-discount-badge" style="background-color: #ffcc00; color: #0b1329; font-weight: 800; border-radius: 4px;">شحن مجاني</div>`;
        }

        return `
          <div class="product-card">
            <div class="product-thumb js-view-product-detail" data-id="${p.id}" style="cursor: pointer;">
              ${discountBadgeHTML}
              ${thumbnail}
            </div>
            <div class="product-info">
              <span class="product-cat">${p.category}</span>
              <h3 class="product-name js-view-product-detail" data-id="${p.id}" style="cursor: pointer;">${p.name}</h3>
              ${starsHTML}
              ${swatchesHTML}
              ${stockBarHTML}
              <div style="display:flex; justify-content:space-between; align-items:center; margin-top:auto; margin-bottom:10px;">
                <span class="product-price">${priceSYP.toLocaleString()} ل.س</span>
                <span class="product-stock-tag ${stockClass}">${stockText}</span>
              </div>
              <button class="add-to-cart-btn" data-id="${p.id}" ${isOutOfStock ? 'disabled' : ''}>
                <i class="fas fa-shopping-cart"></i>
                <span>${isOutOfStock ? 'غير متوفر' : 'أضف إلى السلة'}</span>
              </button>
            </div>
          </div>
        `;
      }).join('');

      // Add to cart buttons listeners
      grid.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          const id = e.currentTarget.getAttribute('data-id');
          addToCart(id);
        });
      });

      // Swatches selection listeners (Ella theme)
      if (activeTheme === 'ella') {
        grid.querySelectorAll('.ella-size-btn').forEach(btn => {
          btn.addEventListener('click', (e) => {
            e.stopPropagation();
            const pId = btn.getAttribute('data-id');
            const size = btn.getAttribute('data-size');
            activeSelections[pId].size = size;
            
            // Toggle active visual states
            btn.parentElement.querySelectorAll('.ella-size-btn').forEach(b => b.classList.remove('selected'));
            btn.classList.add('selected');
          });
        });
        
        grid.querySelectorAll('.ella-color-dot').forEach(dot => {
          dot.addEventListener('click', (e) => {
            e.stopPropagation();
            const pId = dot.getAttribute('data-id');
            const color = dot.getAttribute('data-color');
            activeSelections[pId].color = color;
            
            // Toggle active visual states
            dot.parentElement.querySelectorAll('.ella-color-dot').forEach(d => d.classList.remove('selected'));
            dot.classList.add('selected');
          });
        });
      }

      // View product details click listeners
      grid.querySelectorAll('.js-view-product-detail').forEach(el => {
        el.addEventListener('click', (e) => {
          e.stopPropagation();
          const id = el.getAttribute('data-id');
          openProductDetailModal(id);
        });
      });
    }

    function addToCart(id, qty = 1) {
      const products = store.getProducts();
      const product = products.find(p => p.id === id);
      if (!product || product.stock === 0) return;

      let selectedSize = '';
      let selectedColor = '';
      if ((activeTheme === 'ella' || activeTheme === 'woodmart') && activeSelections[id]) {
        selectedSize = activeSelections[id].size;
        selectedColor = activeSelections[id].color;
      }

      // Check if product with this variant combination is already in the cart
      const existing = cart.find(item => 
        item.productId === id && 
        item.size === selectedSize && 
        item.color === selectedColor
      );

      // Check total quantity of this product in cart against stock
      const totalQtyOfProductInCart = cart
        .filter(item => item.productId === id)
        .reduce((sum, item) => sum + item.qty, 0);

      if (existing) {
        if (totalQtyOfProductInCart + qty > product.stock) {
          alert('عذراً، تجاوزت الحد الأقصى للمخزون المتوفر في المستودع!');
          return;
        }
        existing.qty += qty;
      } else {
        if (totalQtyOfProductInCart + qty > product.stock) {
          alert('عذراً، تجاوزت الحد الأقصى للمخزون المتوفر في المستودع!');
          return;
        }
        cart.push({
          productId: id,
          qty: qty,
          price: product.price,
          size: selectedSize,
          color: selectedColor
        });
      }

      updateCartUI();
      openCartDrawer();
    }

    function decreaseQtyByKey(key) {
      const parts = key.split('_');
      const pId = parts[0];
      const size = parts[1] || '';
      const color = parts[2] || '';

      const existing = cart.find(item => 
        item.productId === pId && 
        (item.size || '') === size && 
        (item.color || '') === color
      );

      if (existing) {
        existing.qty--;
        if (existing.qty === 0) {
          cart = cart.filter(item => item !== existing);
        }
      }
      updateCartUI();
    }

    function increaseQtyByKey(key) {
      const parts = key.split('_');
      const pId = parts[0];
      const size = parts[1] || '';
      const color = parts[2] || '';

      const products = store.getProducts();
      const product = products.find(p => p.id === pId);
      if (!product) return;

      const existing = cart.find(item => 
        item.productId === pId && 
        (item.size || '') === size && 
        (item.color || '') === color
      );

      if (existing) {
        const totalQtyOfProductInCart = cart
          .filter(item => item.productId === pId)
          .reduce((sum, item) => sum + item.qty, 0);
        if (totalQtyOfProductInCart + 1 > product.stock) {
          alert('عذراً، تجاوزت الحد الأقصى للمخزون المتوفر في المستودع!');
          return;
        }
        existing.qty++;
      }
      updateCartUI();
    }

    function updateCartUI() {
      const products = store.getProducts();
      const container = document.getElementById('cart-items-container');
      const sypFactor = 15000;

      // Update badge
      const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
      document.getElementById('cart-count-badge').innerText = totalItems;
      
      const woodmartBadge = document.getElementById('woodmart-cart-count');
      if (woodmartBadge) woodmartBadge.innerText = totalItems;

      if (cart.length === 0) {
        container.innerHTML = `
          <div style="text-align: center; padding: 40px 0; color: var(--text-muted); display:flex; flex-direction:column; gap:10px; margin:auto;">
            <i class="fas fa-shopping-basket" style="font-size:40px; opacity:0.3;"></i>
            <p>سلة المشتريات فارغة حالياً</p>
          </div>
        `;
        document.getElementById('summary-subtotal').innerText = '0 ل.س';
        document.getElementById('summary-tax').innerText = '0 ل.س';
        document.getElementById('summary-total-final').innerText = '0 ل.س';
        const woodmartTotal = document.getElementById('woodmart-cart-total');
        if (woodmartTotal) woodmartTotal.innerText = '0 ل.س';
        return;
      }

      container.innerHTML = cart.map(item => {
        const p = products.find(prod => prod.id === item.productId);
        if (!p) return '';
        const priceSYP = item.price * sypFactor;
        const totalLine = priceSYP * item.qty;

        const variantInfo = (item.size || item.color) 
          ? `<span style="font-size:11px; color:var(--text-muted); display:block; margin-top:2px;">المواصفات: ${item.size} / ${item.color}</span>`
          : '';

        const variantKey = `${item.productId}_${item.size || ''}_${item.color || ''}`;

        return `
          <div class="cart-item">
            <div class="cart-item-thumb">
              ${p.imageUrl ? `<img src="${p.imageUrl}" alt="${p.name}">` : `<i class="fas fa-box"></i>`}
            </div>
            <div class="cart-item-details">
              <span class="cart-item-name">${p.name}</span>
              ${variantInfo}
              <span class="cart-item-price">${totalLine.toLocaleString()} ل.س</span>
            </div>
            <div class="cart-item-actions">
              <button class="cart-qty-btn btn-minus" data-key="${variantKey}">-</button>
              <span style="font-family:Cairo; font-weight:700; font-size:13px;">${item.qty}</span>
              <button class="cart-qty-btn btn-plus" data-key="${variantKey}">+</button>
            </div>
          </div>
        `;
      }).join('');

      // Cart buttons event handlers
      container.querySelectorAll('.btn-minus').forEach(btn => {
        btn.addEventListener('click', (e) => {
          decreaseQtyByKey(e.currentTarget.getAttribute('data-key'));
        });
      });
      container.querySelectorAll('.btn-plus').forEach(btn => {
        btn.addEventListener('click', (e) => {
          increaseQtyByKey(e.currentTarget.getAttribute('data-key'));
        });
      });

      // Recalculate totals
      const subtotal = cart.reduce((sum, item) => sum + (item.qty * item.price * sypFactor), 0);
      const subtotalUSD = cart.reduce((sum, item) => sum + (item.qty * item.price), 0);

      let discountSYP = 0;
      let discountUSD = 0;

      if (appliedCoupon) {
        if (subtotalUSD >= appliedCoupon.minPurchase) {
          if (appliedCoupon.type === 'percentage') {
            discountUSD = subtotalUSD * (appliedCoupon.value / 100);
          } else { // fixed
            discountUSD = appliedCoupon.value;
          }
          discountUSD = Math.min(discountUSD, subtotalUSD);
          discountSYP = Math.round(discountUSD * sypFactor);
        } else {
          // Reset coupon if it doesn't meet minimum requirements anymore (e.g. user removed items from cart)
          appliedCoupon = null;
          const feedback = document.getElementById('promo-feedback');
          if (feedback) {
            feedback.style.display = 'block';
            feedback.style.color = '#ef4444';
            feedback.innerText = 'تم إلغاء الكوبون لعدم استيفاء الحد الأدنى للطلب بعد تعديل السلة.';
          }
          const promoInput = document.getElementById('promo-code-input');
          if (promoInput) promoInput.value = '';
        }
      }

      const discountedSubtotal = Math.max(0, subtotal - discountSYP);
      const tax = Math.round(discountedSubtotal * 0.15);
      const finalTotal = discountedSubtotal + tax;

      document.getElementById('summary-subtotal').innerText = subtotal.toLocaleString() + ' ل.س';
      
      // Update coupon discount displays if they exist
      const discountRow = document.getElementById('summary-discount-row');
      const discountVal = document.getElementById('summary-discount-val');
      if (discountRow && discountVal) {
        if (discountSYP > 0) {
          discountRow.style.display = 'flex';
          discountVal.innerText = '-' + discountSYP.toLocaleString() + ' ل.س';
        } else {
          discountRow.style.display = 'none';
        }
      }
      
      // Update checkout modal discount display if it exists
      const checkoutDiscountRow = document.getElementById('checkout-discount-row');
      const checkoutDiscountVal = document.getElementById('checkout-discount-val');
      if (checkoutDiscountRow && checkoutDiscountVal) {
        if (discountSYP > 0) {
          checkoutDiscountRow.style.display = 'flex';
          checkoutDiscountVal.innerText = '-' + discountSYP.toLocaleString() + ' ل.س';
        } else {
          checkoutDiscountRow.style.display = 'none';
        }
      }

      document.getElementById('summary-tax').innerText = tax.toLocaleString() + ' ل.س';
      document.getElementById('summary-total-final').innerText = finalTotal.toLocaleString() + ' ل.س';
      document.getElementById('checkout-total-label').innerText = finalTotal.toLocaleString() + ' ل.س';
      
      const woodmartTotal = document.getElementById('woodmart-cart-total');
      if (woodmartTotal) woodmartTotal.innerText = finalTotal.toLocaleString() + ' ل.س';
    }

    function initNewsletterPromo() {
      if (activeTheme !== 'ella') return;

      const wasShown = localStorage.getItem('ns_ella_newsletter_shown');
      if (wasShown === 'true') return;

      setTimeout(() => {
        const popup = document.getElementById('ella-newsletter-popup');
        if (popup) popup.classList.add('active');
      }, 2000);
    }

    function openCartDrawer() {
      document.getElementById('cart-drawer-overlay').classList.add('active');
    }

    function closeCartDrawer() {
      document.getElementById('cart-drawer-overlay').classList.remove('active');
    }

    function openCheckoutModal() {
      if (cart.length === 0) {
        alert('السلة فارغة!');
        return;
      }
      closeCartDrawer();
      document.getElementById('checkout-modal-overlay').classList.add('active');
    }

    function closeCheckoutModal() {
      document.getElementById('checkout-modal-overlay').classList.remove('active');
      appliedCoupon = null;
      const codeInput = document.getElementById('promo-code-input');
      if (codeInput) codeInput.value = '';
      const feedback = document.getElementById('promo-feedback');
      if (feedback) {
        feedback.style.display = 'none';
        feedback.innerText = '';
      }
      updateCartUI();
    }

    let activeProductForDetail = null;
    let modalSelectedSize = '';
    let modalSelectedColor = '';
    let modalSelectedQty = 1;

    function openProductDetailModal(productId) {
      const products = store.getProducts();
      const product = products.find(p => p.id === productId);
      if (!product) return;

      activeProductForDetail = product;
      modalSelectedQty = 1;
      
      // Populate text/image fields
      document.getElementById('detail-product-cat').innerText = product.category;
      document.getElementById('detail-product-name').innerText = product.name;
      document.getElementById('detail-product-sku').innerText = `SKU: ${product.sku}`;
      
      const sypFactor = 15000;
      const priceSYP = product.price * sypFactor;
      document.getElementById('detail-product-price-syp').innerText = `${priceSYP.toLocaleString()} ل.س`;
      document.getElementById('detail-product-price-usd').innerText = `($${product.price})`;
      
      document.getElementById('detail-product-short-desc').innerText = product.shortDescription || product.description || 'لا يوجد وصف قصير لهذا المنتج.';
      document.getElementById('detail-product-full-desc').innerText = product.description || 'لا يوجد وصف تفصيلي لهذا المنتج.';

      // Render media
      const imgBox = document.getElementById('detail-product-image-container');
      let iconClass = 'fa-box';
      if (product.category.includes('إلكترونيات') || product.category.includes('هواتف')) iconClass = 'fa-laptop';
      else if (product.category.includes('ملابس') || product.category.includes('أحذية')) iconClass = 'fa-tshirt';
      imgBox.innerHTML = product.imageUrl 
        ? `<img src="${product.imageUrl}" alt="${product.name}" style="max-width: 100%; max-height: 100%; object-fit: contain;">` 
        : `<div style="font-size: 64px; color: var(--text-muted);"><i class="fas ${iconClass}"></i></div>`;

      // Render ratings stars in main detail area
      const reviews = store.getReviews() || [];
      const prodReviews = reviews.filter(r => r.productSku === product.sku && r.status === 'Approved');
      let rating = 0;
      if (prodReviews.length > 0) {
        const sum = prodReviews.reduce((s, r) => s + r.rating, 0);
        rating = (sum / prodReviews.length).toFixed(1);
      } else {
        rating = (parseInt(product.id) % 3 === 0) ? 4.5 : (parseInt(product.id) % 2 === 0) ? 5.0 : 4.8;
      }
      const fullStars = Math.floor(rating);
      const hasHalf = rating % 1 !== 0;
      let starsHTML = '';
      for (let i = 0; i < 5; i++) {
        if (i < fullStars) starsHTML += '<i class="fas fa-star" style="color: #f59e0b; margin-left: 2px;"></i>';
        else if (i === fullStars && hasHalf) starsHTML += '<i class="fas fa-star-half-alt" style="color: #f59e0b; margin-left: 2px;"></i>';
        else starsHTML += '<i class="far fa-star" style="color: #cbd5e1; margin-left: 2px;"></i>';
      }
      document.getElementById('detail-product-rating').innerHTML = `${starsHTML} <span style="color: var(--text-muted); font-size:12px; font-weight:600;">(${rating})</span>`;

      // Render stock tag
      const tagEl = document.getElementById('detail-product-stock-tag');
      const isOutOfStock = product.stock === 0;
      const isLowStock = product.stock > 0 && product.stock <= product.minStock;
      
      const btnAddToCart = document.getElementById('btn-detail-add-to-cart');
      const btnQtyPlus = document.getElementById('btn-detail-qty-plus');
      const btnQtyMinus = document.getElementById('btn-detail-qty-minus');
      const qtyInput = document.getElementById('detail-qty-input');

      if (isOutOfStock) {
        tagEl.className = 'product-stock-tag stock-out';
        tagEl.innerText = 'نفد المخزون';
        btnAddToCart.disabled = true;
        btnAddToCart.querySelector('span').innerText = 'غير متوفر';
        qtyInput.value = 0;
        btnQtyPlus.disabled = true;
        btnQtyMinus.disabled = true;
      } else {
        btnAddToCart.disabled = false;
        btnAddToCart.querySelector('span').innerText = 'أضف إلى السلة';
        qtyInput.value = 1;
        btnQtyPlus.disabled = false;
        btnQtyMinus.disabled = false;

        if (isLowStock) {
          tagEl.className = 'product-stock-tag stock-low';
          tagEl.innerText = `كمية محدودة (${product.stock} قطع)`;
        } else {
          tagEl.className = 'product-stock-tag stock-in';
          tagEl.innerText = 'متوفر في المستودع';
        }
      }

      // Render variants (size/color selectors)
      let sizes = ['S', 'M', 'L', 'XL'];
      let colors = [{ name: 'أسود', hex: '#000000' }, { name: 'أبيض', hex: '#ffffff' }, { name: 'أحمر', hex: '#ef4444' }, { name: 'أزرق', hex: '#3b82f6' }];
      
      if (product.category.includes('إلكترونيات') || product.category.includes('هواتف')) {
        sizes = ['128GB', '256GB', '512GB'];
        colors = [{ name: 'رمادي', hex: '#2b2b2b' }, { name: 'فضي', hex: '#e3e3e3' }, { name: 'ذهبي', hex: '#c5a880' }];
      } else if (!product.category.includes('ملابس') && !product.category.includes('أحذية')) {
        sizes = ['عادي', 'كبير'];
        colors = [{ name: 'أبيض', hex: '#ffffff' }, { name: 'أسود', hex: '#000000' }];
      }

      modalSelectedSize = activeSelections[product.id]?.size || sizes[0];
      modalSelectedColor = activeSelections[product.id]?.color || colors[0].name;

      const varsContainer = document.getElementById('detail-variants-container');
      varsContainer.innerHTML = `
        <div class="ella-swatches" style="padding: 0; margin-bottom: 0;">
          <div class="ella-size-swatches" style="margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <span style="font-size:12px; font-weight:700; color:var(--text-primary); min-width: 60px;">المقاس:</span>
            <div style="display:flex; gap:6px;">
              ${sizes.map(s => `
                <button type="button" class="ella-size-btn js-modal-size-btn ${modalSelectedSize === s ? 'selected' : ''}" data-size="${s}">${s}</button>
              `).join('')}
            </div>
          </div>
          <div class="ella-color-swatches" style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size:12px; font-weight:700; color:var(--text-primary); min-width: 60px;">اللون:</span>
            <div style="display:flex; gap:6px; align-items: center;">
              ${colors.map(c => `
                <div class="ella-color-dot js-modal-color-dot ${modalSelectedColor === c.name ? 'selected' : ''}" data-color="${c.name}" style="background-color: ${c.hex};" title="${c.name}"></div>
              `).join('')}
            </div>
          </div>
        </div>
      `;

      // Bind variant listeners
      varsContainer.querySelectorAll('.js-modal-size-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          modalSelectedSize = btn.getAttribute('data-size');
          varsContainer.querySelectorAll('.js-modal-size-btn').forEach(b => b.classList.remove('selected'));
          btn.classList.add('selected');
        });
      });
      varsContainer.querySelectorAll('.js-modal-color-dot').forEach(dot => {
        dot.addEventListener('click', () => {
          modalSelectedColor = dot.getAttribute('data-color');
          varsContainer.querySelectorAll('.js-modal-color-dot').forEach(d => d.classList.remove('selected'));
          dot.classList.add('selected');
        });
      });

      // Render reviews list
      renderProductReviews(product);

      // Default review form state
      resetReviewForm();

      // Show default tab (Description)
      switchDetailTab('desc');

      // Show modal
      const overlay = document.getElementById('product-detail-modal-overlay');
      overlay.style.visibility = 'visible';
      overlay.style.opacity = '1';
      overlay.querySelector('.checkout-modal').style.transform = 'translateY(0)';
    }

    function closeProductDetailModal() {
      const overlay = document.getElementById('product-detail-modal-overlay');
      if (overlay) {
        overlay.style.visibility = 'hidden';
        overlay.style.opacity = '0';
        overlay.querySelector('.checkout-modal').style.transform = 'translateY(20px)';
      }
      activeProductForDetail = null;
    }

    function switchDetailTab(tabName) {
      const btnDesc = document.getElementById('btn-tab-desc');
      const btnReviews = document.getElementById('btn-tab-reviews');
      const tabDesc = document.getElementById('tab-desc');
      const tabReviews = document.getElementById('tab-reviews');

      if (tabName === 'desc') {
        btnDesc.style.borderBottom = '2px solid var(--primary-color)';
        btnDesc.style.color = 'var(--primary-color)';
        btnReviews.style.borderBottom = 'none';
        btnReviews.style.color = 'var(--text-muted)';
        
        tabDesc.style.display = 'block';
        tabReviews.style.display = 'none';
      } else {
        btnReviews.style.borderBottom = '2px solid var(--primary-color)';
        btnReviews.style.color = 'var(--primary-color)';
        btnDesc.style.borderBottom = 'none';
        btnDesc.style.color = 'var(--text-muted)';
        
        tabReviews.style.display = 'block';
        tabDesc.style.display = 'none';
      }
    }

    function renderProductReviews(product) {
      const reviews = store.getReviews() || [];
      const prodReviews = reviews.filter(r => r.productSku === product.sku && r.status === 'Approved');

      document.getElementById('detail-reviews-count').innerText = prodReviews.length;

      const listContainer = document.getElementById('detail-reviews-list');
      if (prodReviews.length === 0) {
        listContainer.innerHTML = `
          <div style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 13px;">
            <i class="far fa-comments" style="font-size: 28px; margin-bottom: 8px; display: block; color: var(--text-muted);"></i>
            لا توجد مراجعات معتمدة لهذا المنتج بعد. كن أول من يقيم هذا المنتج!
          </div>
        `;
        return;
      }

      listContainer.innerHTML = prodReviews.map(r => {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
          stars += `<i class="${i <= r.rating ? 'fas' : 'far'} fa-star" style="color: #f59e0b; font-size: 12px; margin-left: 2px;"></i>`;
        }
        const formattedDate = new Date(r.date).toLocaleDateString('ar-SY', {
          year: 'numeric',
          month: 'long',
          day: 'numeric'
        });
        return `
          <div style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
              <strong style="font-size: 13px; color: var(--secondary-color);">${r.customerName}</strong>
              <span style="font-size: 11px; color: var(--text-muted); font-family: var(--font-english);">${formattedDate}</span>
            </div>
            <div style="display: flex; gap: 4px; margin-bottom: 6px;">
              ${stars}
            </div>
            <p style="margin: 0; font-size: 13px; color: var(--text-muted); line-height: 1.5;">${r.text}</p>
          </div>
        `;
      }).join('');
    }

    function resetReviewForm() {
      document.getElementById('new-review-form').reset();
      document.getElementById('review-rating-value').value = 5;
      const stars = document.querySelectorAll('#star-rating-selector .rating-star');
      stars.forEach(s => {
        s.className = 'fas fa-star rating-star';
        s.style.color = '#f59e0b';
      });
    }

    function setupEvents() {
      document.getElementById('btn-toggle-cart').addEventListener('click', openCartDrawer);
      document.getElementById('btn-close-cart').addEventListener('click', closeCartDrawer);
      document.getElementById('cart-drawer-overlay').addEventListener('click', (e) => {
        if (e.target.id === 'cart-drawer-overlay') closeCartDrawer();
      });

      document.getElementById('btn-go-checkout').addEventListener('click', openCheckoutModal);
      document.getElementById('btn-close-checkout').addEventListener('click', closeCheckoutModal);
      document.getElementById('checkout-modal-overlay').addEventListener('click', (e) => {
        if (e.target.id === 'checkout-modal-overlay') closeCheckoutModal();
      });

      // Checkout form submit
      document.getElementById('storefront-checkout-form').addEventListener('submit', handleCheckoutSubmit);

      // Promo Code Apply Button
      const btnApplyPromo = document.getElementById('btn-apply-promo');
      if (btnApplyPromo) {
        btnApplyPromo.addEventListener('click', handleApplyPromoCode);
      }

      // Ella newsletter event handlers
      const btnCloseNews = document.getElementById('btn-close-newsletter');
      if (btnCloseNews) {
        btnCloseNews.addEventListener('click', () => {
          const popup = document.getElementById('ella-newsletter-popup');
          if (popup) popup.classList.remove('active');
          localStorage.setItem('ns_ella_newsletter_shown', 'true');
        });
      }
      
      const formNews = document.getElementById('ella-newsletter-subscribe-form');
      if (formNews) {
        formNews.addEventListener('submit', (e) => {
          e.preventDefault();
          const email = formNews.querySelector('input[type="email"]').value;
          alert(`شكراً لك! تم الاشتراك بالنشرة البريدية ببريدك: ${email}.\nكود الخصم الخاص بك هو: ELLA10`);
          const popup = document.getElementById('ella-newsletter-popup');
          if (popup) popup.classList.remove('active');
          localStorage.setItem('ns_ella_newsletter_shown', 'true');
        });
      }

      // Brand tabs click
      const brandTabs = document.querySelectorAll('.ella-brand-tab');
      brandTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
          brandTabs.forEach(t => t.classList.remove('active'));
          e.currentTarget.classList.add('active');
          activeBrandFilter = e.currentTarget.innerText.trim();
          // Reset collection filter on brand change to prevent empty states
          activeCollectionFilter = 'الكل';
          const collectionItems = document.querySelectorAll('.ella-category-circle-item');
          collectionItems.forEach(i => i.querySelector('.ella-category-circle-img').style.borderColor = '');
          renderCatalog();
        });
      });

      // Search field input
      const searchInput = document.getElementById('ella-global-search');
      if (searchInput) {
        searchInput.addEventListener('input', () => {
          renderCatalog();
        });
      }

      // Collection items click
      const collectionItems = document.querySelectorAll('.ella-category-circle-item');
      collectionItems.forEach(item => {
        item.addEventListener('click', (e) => {
          e.preventDefault();
          const selected = item.getAttribute('data-collection');
          if (activeCollectionFilter === selected) {
            activeCollectionFilter = 'الكل';
            item.querySelector('.ella-category-circle-img').style.borderColor = '';
          } else {
            activeCollectionFilter = selected;
            collectionItems.forEach(i => i.querySelector('.ella-category-circle-img').style.borderColor = '');
            item.querySelector('.ella-category-circle-img').style.borderColor = '#000000';
          }
          renderCatalog();
        });
      });

      // Slide buttons click
      const slideBtns = document.querySelectorAll('.ella-slide-btn');
      slideBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const col = btn.getAttribute('data-collection');
          activeCollectionFilter = col;
          
          // Reset brand filter to accommodate collection
          brandTabs.forEach(t => t.classList.remove('active'));
          if (col === 'فساتين') {
            brandTabs[0].classList.add('active'); // إيلا
            activeBrandFilter = 'إيلا';
          } else if (col === 'ملابس خارجية') {
            brandTabs[1].classList.add('active'); // رجالي
            activeBrandFilter = 'رجالي';
          } else if (col === 'إكسسوارات') {
            brandTabs[2].classList.add('active'); // بيل دول
            activeBrandFilter = 'بيل دول';
          }
          
          // Highlight bubble in collections grid
          collectionItems.forEach(item => {
            const circleImg = item.querySelector('.ella-category-circle-img');
            if (item.getAttribute('data-collection') === col) {
              circleImg.style.borderColor = '#000000';
            } else {
              circleImg.style.borderColor = '';
            }
          });
          
          renderCatalog();
          
          // Scroll to catalog
          const catalogSection = document.getElementById('storefront-catalog-title');
          if (catalogSection) {
            catalogSection.scrollIntoView({ behavior: 'smooth' });
          }
        });
      });

      // Setup Slideshow
      setupSlideshowEvents();

      // Quantity minus/plus in detail modal
      const qtyInput = document.getElementById('detail-qty-input');
      if (qtyInput) {
        document.getElementById('btn-detail-qty-minus').addEventListener('click', () => {
          if (!activeProductForDetail) return;
          modalSelectedQty = Math.max(1, modalSelectedQty - 1);
          qtyInput.value = modalSelectedQty;
        });
        document.getElementById('btn-detail-qty-plus').addEventListener('click', () => {
          if (!activeProductForDetail) return;
          modalSelectedQty = Math.min(activeProductForDetail.stock, modalSelectedQty + 1);
          qtyInput.value = modalSelectedQty;
        });
      }

      // Add to cart from detail modal
      const btnDetailAddToCart = document.getElementById('btn-detail-add-to-cart');
      if (btnDetailAddToCart) {
        btnDetailAddToCart.addEventListener('click', () => {
          if (!activeProductForDetail) return;
          // Update selection in global activeSelections for this product
          activeSelections[activeProductForDetail.id] = {
            size: modalSelectedSize,
            color: modalSelectedColor
          };
          // Add to cart
          addToCart(activeProductForDetail.id, modalSelectedQty);
          closeProductDetailModal();
        });
      }

      // Tabs click
      const btnTabDesc = document.getElementById('btn-tab-desc');
      const btnTabReviews = document.getElementById('btn-tab-reviews');
      if (btnTabDesc && btnTabReviews) {
        btnTabDesc.addEventListener('click', () => switchDetailTab('desc'));
        btnTabReviews.addEventListener('click', () => switchDetailTab('reviews'));
      }

      // Close modal events
      const btnCloseProductDetail = document.getElementById('btn-close-product-detail');
      if (btnCloseProductDetail) {
        btnCloseProductDetail.addEventListener('click', closeProductDetailModal);
      }
      const productDetailModalOverlay = document.getElementById('product-detail-modal-overlay');
      if (productDetailModalOverlay) {
        productDetailModalOverlay.addEventListener('click', (e) => {
          if (e.target.id === 'product-detail-modal-overlay') closeProductDetailModal();
        });
      }

      // Review Rating Stars Selector
      const ratingStars = document.querySelectorAll('#star-rating-selector .rating-star');
      const ratingValueInput = document.getElementById('review-rating-value');
      if (ratingStars.length && ratingValueInput) {
        ratingStars.forEach(star => {
          star.addEventListener('click', () => {
            const val = parseInt(star.getAttribute('data-value'));
            ratingValueInput.value = val;
            ratingStars.forEach(s => {
              const sVal = parseInt(s.getAttribute('data-value'));
              if (sVal <= val) {
                s.className = 'fas fa-star rating-star';
                s.style.color = '#f59e0b';
              } else {
                s.className = 'far fa-star rating-star';
                s.style.color = '#cbd5e1';
              }
            });
          });
        });
      }

      // Review Form Submit
      const newReviewForm = document.getElementById('new-review-form');
      if (newReviewForm) {
        newReviewForm.addEventListener('submit', (e) => {
          e.preventDefault();
          const author = document.getElementById('review-author').value.trim();
          const text = document.getElementById('review-text').value.trim();
          const rating = parseInt(document.getElementById('review-rating-value').value) || 5;
          
          if (!activeProductForDetail) return;
          
          fetch('api/reviews.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              customer_name: author,
              product_id: activeProductForDetail.id,
              rating: rating,
              text: text
            })
          })
          .then(res => res.json())
          .then(data => {
            alert(data.message || 'تم إرسال التقييم بنجاح وسيكون ظاهراً في الصفحة فور موافقة الإدارة عليه.');
            resetReviewForm();
          })
          .catch(err => {
            console.error('Failed to submit review:', err);
            alert('فشل إرسال التقييم، الرجاء المحاولة لاحقاً.');
          });
        });
      }
      
      // Setup Woodmart specific events
      setupWoodmartEvents();
    }

    // Slideshow helper functions
    function showSlide(index) {
      const slides = document.querySelectorAll('.ella-slide');
      const dots = document.querySelectorAll('.ella-slideshow-dot');
      if (slides.length === 0) return;
      
      if (index >= slides.length) currentSlideIndex = 0;
      else if (index < 0) currentSlideIndex = slides.length - 1;
      else currentSlideIndex = index;
      
      slides.forEach((slide, i) => {
        if (i === currentSlideIndex) {
          slide.classList.add('active');
        } else {
          slide.classList.remove('active');
        }
      });
      
      dots.forEach((dot, i) => {
        if (i === currentSlideIndex) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });
    }

    function startSlideshowTimer() {
      stopSlideshowTimer();
      slideshowInterval = setInterval(() => {
        showSlide(currentSlideIndex + 1);
      }, 5000);
    }

    function stopSlideshowTimer() {
      if (slideshowInterval) clearInterval(slideshowInterval);
    }

    function setupSlideshowEvents() {
      const btnPrev = document.getElementById('ella-slideshow-prev');
      const btnNext = document.getElementById('ella-slideshow-next');
      const dots = document.querySelectorAll('.ella-slideshow-dot');
      
      if (btnPrev) {
        btnPrev.addEventListener('click', () => {
          showSlide(currentSlideIndex - 1);
          startSlideshowTimer();
        });
      }
      
      if (btnNext) {
        btnNext.addEventListener('click', () => {
          showSlide(currentSlideIndex + 1);
          startSlideshowTimer();
        });
      }
      
      dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
          showSlide(i);
          startSlideshowTimer();
        });
      });
      
      const slideshowContainer = document.getElementById('ella-slideshow-container');
      if (slideshowContainer) {
        slideshowContainer.addEventListener('mouseenter', stopSlideshowTimer);
        slideshowContainer.addEventListener('mouseleave', startSlideshowTimer);
      }
      
      if (activeTheme === 'ella') {
        startSlideshowTimer();
      }
    }

    // WoodMart Slideshow & UI events
    let woodmartSlideIndex = 0;
    let woodmartSlideshowInterval;

    function showWoodmartSlide(index) {
      const slides = document.querySelectorAll('.woodmart-slide');
      const dots = document.querySelectorAll('.woodmart-dot');
      if (slides.length === 0) return;
      
      if (index >= slides.length) woodmartSlideIndex = 0;
      else if (index < 0) woodmartSlideIndex = slides.length - 1;
      else woodmartSlideIndex = index;
      
      slides.forEach((slide, i) => {
        if (i === woodmartSlideIndex) {
          slide.classList.add('active');
        } else {
          slide.classList.remove('active');
        }
      });
      
      dots.forEach((dot, i) => {
        if (i === woodmartSlideIndex) {
          dot.classList.add('active');
        } else {
          dot.classList.remove('active');
        }
      });
    }

    function startWoodmartSlideshowTimer() {
      stopWoodmartSlideshowTimer();
      woodmartSlideshowInterval = setInterval(() => {
        showWoodmartSlide(woodmartSlideIndex + 1);
      }, 5000);
    }

    function stopWoodmartSlideshowTimer() {
      if (woodmartSlideshowInterval) clearInterval(woodmartSlideshowInterval);
    }

    function setupWoodmartEvents() {
      // WoodMart search global
      const woodSearch = document.getElementById('woodmart-global-search');
      if (woodSearch) {
        woodSearch.addEventListener('input', () => {
          renderCatalog();
        });
      }
      
      // Category select filter
      const catSelect = document.getElementById('woodmart-search-category');
      if (catSelect) {
        catSelect.addEventListener('change', (e) => {
          const val = e.target.value;
          activeCollectionFilter = val;
          renderCatalog();
        });
      }
      
      // Toggle cart
      const cartToggle = document.getElementById('woodmart-btn-toggle-cart');
      if (cartToggle) {
        cartToggle.addEventListener('click', openCartDrawer);
      }
      
      // Toggle vertical menu browse categories
      const browseBtn = document.getElementById('woodmart-btn-browse');
      if (browseBtn) {
        browseBtn.addEventListener('click', () => {
          const menu = document.querySelector('.woodmart-vertical-menu');
          if (menu) {
            menu.style.display = (menu.style.display === 'none' || menu.style.display === '') ? 'flex' : 'none';
          }
        });
      }

      // Sidebar category clicks
      const woodMenu = document.querySelectorAll('.woodmart-menu-item');
      woodMenu.forEach(item => {
        item.addEventListener('click', (e) => {
          e.preventDefault();
          woodMenu.forEach(i => i.classList.remove('active'));
          item.classList.add('active');
          const cat = item.getAttribute('data-category');
          activeCollectionFilter = cat === 'all' ? 'الكل' : cat;
          renderCatalog();
          
          const catalogTitle = document.getElementById('storefront-catalog-title');
          if (catalogTitle) catalogTitle.scrollIntoView({ behavior: 'smooth' });
        });
      });

      // Banners links click
      const promoLinks = document.querySelectorAll('.woodmart-promo-link');
      promoLinks.forEach(link => {
        link.addEventListener('click', (e) => {
          e.preventDefault();
          const term = link.getAttribute('data-search');
          if (woodSearch) {
            woodSearch.value = term;
            woodSearch.dispatchEvent(new Event('input'));
          }
          const catalogTitle = document.getElementById('storefront-catalog-title');
          if (catalogTitle) catalogTitle.scrollIntoView({ behavior: 'smooth' });
        });
      });

      // Slides dots
      const woodDots = document.querySelectorAll('.woodmart-dot');
      woodDots.forEach(dot => {
        dot.addEventListener('click', () => {
          const idx = parseInt(dot.getAttribute('data-index'));
          showWoodmartSlide(idx);
          startWoodmartSlideshowTimer();
        });
      });

      // Slides buy buttons
      const buyBtns = document.querySelectorAll('.woodmart-slide-btn');
      buyBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          const id = btn.getAttribute('data-product-id');
          addToCart(id);
        });
      });

      // slideshow hover pausing
      const slideshowContainer = document.getElementById('woodmart-slideshow-container');
      if (slideshowContainer) {
        slideshowContainer.addEventListener('mouseenter', stopWoodmartSlideshowTimer);
        slideshowContainer.addEventListener('mouseleave', startWoodmartSlideshowTimer);
      }

      if (activeTheme === 'woodmart') {
        startWoodmartSlideshowTimer();
      }
    }

    function handleApplyPromoCode() {
      const codeInput = document.getElementById('promo-code-input');
      const feedback = document.getElementById('promo-feedback');
      if (!codeInput || !feedback) return;

      const code = codeInput.value.trim().toUpperCase();
      if (!code) {
        feedback.style.display = 'block';
        feedback.style.color = '#ef4444';
        feedback.innerText = 'الرجاء إدخال رمز الكوبون أولاً!';
        return;
      }

      const coupons = JSON.parse(localStorage.getItem('ns_coupons')) || [];
      const coupon = coupons.find(c => c.code === code);
      const sypFactor = 15000;

      if (!coupon) {
        feedback.style.display = 'block';
        feedback.style.color = '#ef4444';
        feedback.innerText = 'هذا الكوبون غير صحيح أو منتهي!';
        appliedCoupon = null;
        updateCartUI();
        return;
      }

      if (coupon.status !== 'Active') {
        feedback.style.display = 'block';
        feedback.style.color = '#ef4444';
        feedback.innerText = 'هذا الكوبون غير فعال حالياً!';
        appliedCoupon = null;
        updateCartUI();
        return;
      }

      // Check expiry
      const currentDate = new Date();
      const expiryDate = new Date(coupon.expiry);
      if (expiryDate < currentDate.setHours(0,0,0,0)) {
        feedback.style.display = 'block';
        feedback.style.color = '#ef4444';
        feedback.innerText = 'عذراً، انتهت صلاحية هذا الكوبون!';
        appliedCoupon = null;
        updateCartUI();
        return;
      }

      // Check minimum purchase amount
      const subtotalUSD = cart.reduce((sum, item) => sum + (item.qty * item.price), 0);
      if (subtotalUSD < coupon.minPurchase) {
        const minSYP = Math.round(coupon.minPurchase * sypFactor);
        const currentSYP = Math.round(subtotalUSD * sypFactor);
        feedback.style.display = 'block';
        feedback.style.color = '#ef4444';
        feedback.innerText = `الحد الأدنى لتفعيل هذا الكوبون هو ${minSYP.toLocaleString()} ل.س (قيمة طلبك الحالي: ${currentSYP.toLocaleString()} ل.س).`;
        appliedCoupon = null;
        updateCartUI();
        return;
      }

      // Successful application
      appliedCoupon = coupon;
      feedback.style.display = 'block';
      feedback.style.color = '#10b981';
      
      let discountText = '';
      if (coupon.type === 'percentage') {
        discountText = `${coupon.value}%`;
      } else {
        discountText = `${Math.round(coupon.value * sypFactor).toLocaleString()} ل.س`;
      }
      feedback.innerText = `تم تطبيق الكوبون "${code}" بنجاح! خصم بقيمة ${discountText}.`;
      
      // Update displays
      updateCartUI();
    }

    function handleCheckoutSubmit(e) {
      e.preventDefault();

      const customerName = document.getElementById('cust-fullname').value.trim();
      const city = document.getElementById('cust-city').value.trim();
      const phone = document.getElementById('cust-phone').value.trim();
      const address = document.getElementById('cust-address').value.trim();
      const paymentMethod = document.getElementById('cust-payment').value;

      const sypFactor = 15000;

      // Total calculations in USD to match admin DB structure
      const subtotalUSD = cart.reduce((sum, item) => sum + (item.qty * item.price), 0);
      const totalSYP = cart.reduce((sum, item) => sum + (item.qty * item.price * sypFactor), 0);

      let discountUSD = 0;
      let discountSYP = 0;

      if (appliedCoupon) {
        if (appliedCoupon.type === 'percentage') {
          discountUSD = subtotalUSD * (appliedCoupon.value / 100);
        } else {
          discountUSD = appliedCoupon.value;
        }
        discountUSD = Math.min(discountUSD, subtotalUSD);
        discountSYP = Math.round(discountUSD * sypFactor);
      }

      const discountedSubtotalUSD = Math.max(0, subtotalUSD - discountUSD);
      const discountedSubtotalSYP = Math.max(0, totalSYP - discountSYP);
      const finalSYP = discountedSubtotalSYP + Math.round(discountedSubtotalSYP * 0.15);

      const payload = {
        customer_name: `${customerName} (${city})`,
        total_usd: discountedSubtotalUSD,
        exchange_rate: sypFactor,
        coupon_code: appliedCoupon ? appliedCoupon.code : null,
        discount_usd: discountUSD,
        source: 'ecommerce',
        payment_status: 'Unpaid',
        payment_method: paymentMethod,
        notes: `الهاتف: ${phone} - العنوان: ${address}`,
        items: cart.map(item => ({
          product_id: item.productId,
          quantity: item.qty,
          price_usd: item.price,
          size: item.size || '',
          color: item.color || ''
        }))
      };

      fetch('api/orders.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert(`نجح إرسال طلبك! رقم الطلب الخاص بك هو #${data.order_id}.\nمبلغ الفاتورة: ${finalSYP.toLocaleString()} ل.س.\nسيقوم كابتن التوصيل بالتواصل معك قريباً لشحن طلبك في ${city}.`);
          
          // Reset cart and UI
          cart = [];
          appliedCoupon = null;
          updateCartUI();
          closeCheckoutModal();
          
          // Reload database cache and redraw catalog
          store.loadAllData().then(() => {
            renderCatalog();
          });
        } else {
          alert(`فشل إرسال الطلب: ${data.message}`);
        }
      })
      .catch(err => {
        console.error('Failed to submit order:', err);
        alert('فشل الاتصال بالسيرفر لإرسال الطلب، الرجاء المحاولة لاحقاً.');
      });
    }
  </script>
</body>
</html>
