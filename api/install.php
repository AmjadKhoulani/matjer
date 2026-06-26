<?php
// install.php - Automated Database Installer and Seeder for SaaS
require_once __DIR__ . '/config.php';

try {
    // 1. Connect without dbname to create it if missing (if permissions allow)
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
    // Reuse existing PDO if connected, or establish connection
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 2. Reconnect to the database
    $pdo->exec("USE `" . DB_NAME . "`");
    
    // Disable foreign key checks during drop to prevent failures
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $tables = [
        'ns_activities', 'ns_crm_notes', 'ns_reviews', 'ns_quotation_items', 
        'ns_quotations', 'ns_purchase_items', 'ns_purchase_invoices', 
        'ns_order_items', 'ns_orders', 'ns_warehouses', 'ns_suppliers', 
        'ns_customers', 'ns_products', 'ns_settings', 'ns_currencies', 
        'ns_users', 'ns_tenants'
    ];
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    // 3. Read and execute database.sql
    $sqlFile = __DIR__ . '/database.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("database.sql file not found in " . __DIR__);
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    // Split SQL queries by semicolon
    $queries = explode(';', $sqlContent);
    foreach ($queries as $query) {
        $trimmed = trim($query);
        if (!empty($trimmed)) {
            $pdo->exec($trimmed);
        }
    }
    
    // 4. Seed default Tenant: Nova Store (tenant_id = 1)
    $stmt = $pdo->prepare("INSERT INTO `ns_tenants` (`id`, `name`, `slug`, `owner_name`, `email`, `phone`, `plan`, `status`, `theme_color`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'متجر نوفا الرئيسي', 'nova-store', 'عبد العزيز الحربي', 'admin@novastore.sa', '+963 11 222 3333', 'Pro', 'Active', '#4f46e5']);
    
    // Seed second Tenant: بقالة النخبة الغذائية (tenant_id = 2) for testing SaaS
    $stmt->execute([2, 'بقالة النخبة الغذائية', 'al-nokhbah', 'محمد عبد الله الشمراني', 'owner@nokhbah.com', '+966 50 123 4567', 'Starter', 'Active', '#06b6d4']);
    
    // 5. Seed Users:
    $adminPassword = 'admin123';
    $adminHash = password_hash($adminPassword, PASSWORD_DEFAULT);
    
    // A. SaaS Super Admin (tenant_id = NULL)
    $stmt = $pdo->prepare("INSERT INTO `ns_users` (`tenant_id`, `username`, `password_hash`, `fullname`, `role`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([null, 'superadmin', $adminHash, 'سوبر أدمن المنصة', 'admin', 'active']);
    
    // B. Tenant Admin for Nova Store (tenant_id = 1)
    $stmt->execute([1, 'admin', $adminHash, 'مدير متجر نوفا', 'admin', 'active']);
    
    // C. Tenant Warehouse for Nova Store (tenant_id = 1)
    $stmt->execute([1, 'warehouse', $adminHash, 'أمين مستودع نوفا', 'warehouse', 'active']);
    
    // D. Tenant Sales for Nova Store (tenant_id = 1)
    $stmt->execute([1, 'sales', $adminHash, 'كاشير مبيعات نوفا', 'sales', 'active']);
    
    // E. Tenant Admin for Al-Nokhbah (tenant_id = 2)
    $stmt->execute([2, 'nokhbah_admin', $adminHash, 'مدير النخبة الغذائية', 'admin', 'active']);

    // 6. Seed default warehouses for tenant_id = 1 (Nova Store)
    $stmt = $pdo->prepare("INSERT INTO `ns_warehouses` (`tenant_id`, `name`, `address`, `contact`, `phone`, `capacity`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([1, 'مستودع دمشق الرئيسي', 'السبع بحرات، دمشق، وسط المدينة', 'سلطان الحكيم', '+963 11 222 3333', '90%']);
    $stmt->execute([1, 'مستودع حلب الشمالي', 'الجميلية، حلب، خلف المطار القديم', 'إبراهيم الصباغ', '+963 21 333 4444', '45%']);
    $stmt->execute([1, 'معرض حمص المباشر', 'الدبلان، حمص، مقابل السوق الأثري', 'خالد السباعي', '+963 31 555 6666', '15%']);

    // Seed warehouse for tenant_id = 2 (Al-Nokhbah)
    $stmt->execute([2, 'مستودع الرياض الرئيسي', 'الرياض، العليا', 'فيصل الشمراني', '+966 50 222 3333', '20%']);
    
    // 7. Seed default currencies
    $stmt = $pdo->prepare("INSERT INTO `ns_currencies` (`tenant_id`, `code`, `name`, `symbol`, `rate`, `update_mode`) VALUES (?, ?, ?, ?, ?, ?)");
    // Currencies for Nova Store (tenant_id = 1)
    $stmt->execute([1, 'USD', 'دولار أمريكي', '$', 1.0000, 'manual']);
    $stmt->execute([1, 'SYP', 'ليرة سورية', 'ل.س', 15000.0000, 'auto']);
    
    // Currencies for Al-Nokhbah (tenant_id = 2)
    $stmt->execute([2, 'USD', 'دولار أمريكي', '$', 1.0000, 'manual']);
    $stmt->execute([2, 'SAR', 'ريال سعودي', 'ر.س', 3.7500, 'manual']);
    
    // 8. Seed default settings for Nova Store (tenant_id = 1)
    $settings1 = [
        'ns_settings_currency' => 'SYP (ل.س)',
        'ns_settings_tax_rate' => '15%',
        'ns_settings_store_name' => 'Nova Store',
        'ns_settings_exchange_rate' => '15000',
        'ns_settings_sync_mode' => 'auto',
        'ns_active_theme' => 'jasmine',
        'ns_settings_gateways' => json_encode(['mada' => true, 'visa' => true, 'applepay' => true, 'cod' => false]),
        'ns_settings_carriers' => json_encode(['aramex' => true, 'smsa' => true, 'dhl' => false, 'pickup' => true])
    ];
    
    $stmt = $pdo->prepare("INSERT INTO `ns_settings` (`tenant_id`, `key`, `value`) VALUES (?, ?, ?)");
    foreach ($settings1 as $key => $val) {
        $stmt->execute([1, $key, $val]);
    }
    
    // Seed default settings for Al-Nokhbah (tenant_id = 2)
    $settings2 = [
        'ns_settings_currency' => 'SAR (ر.س)',
        'ns_settings_tax_rate' => '15%',
        'ns_settings_store_name' => 'بقالة النخبة الغذائية',
        'ns_settings_exchange_rate' => '3.75',
        'ns_settings_sync_mode' => 'manual',
        'ns_active_theme' => 'ella',
        'ns_settings_gateways' => json_encode(['mada' => true, 'visa' => false, 'applepay' => true, 'cod' => true]),
        'ns_settings_carriers' => json_encode(['aramex' => false, 'smsa' => true, 'dhl' => false, 'pickup' => true])
    ];
    foreach ($settings2 as $key => $val) {
        $stmt->execute([2, $key, $val]);
    }
    
    // 9. Log activity for installation
    $stmt = $pdo->prepare("INSERT INTO `ns_activities` (`tenant_id`, `type`, `title`, `desc`) VALUES (?, ?, ?, ?)");
    $stmt->execute([1, 'success', 'تهيئة النظام', 'تم تثبيت قاعدة البيانات بنجاح وزرع حسابات وتفضيلات متجر نوفا الرئيسي.']);
    $stmt->execute([2, 'success', 'تهيئة المتجر', 'تم إطلاق متجر النخبة الغذائية وتغذية البيانات الأساسية له.']);
    
    echo json_encode([
        'success' => true,
        'message' => 'SaaS Database initialized and seeded successfully.',
        'database' => DB_NAME,
        'superadmin_user' => 'superadmin',
        'admin_user' => 'admin',
        'admin_pass' => 'admin123',
        'tenants' => [
            ['id' => 1, 'name' => 'متجر نوفا الرئيسي', 'slug' => 'nova-store'],
            ['id' => 2, 'name' => 'بقالة النخبة الغذائية', 'slug' => 'al-nokhbah']
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Installation failed.',
        'details' => $e->getMessage()
    ]);
}
?>
