<?php
// currency_sync.php - Dynamic exchange rate sync from sp-today.com with fallback (tenant isolated)
require_once __DIR__ . '/config.php';

header("Content-Type: application/json; charset=utf-8");

$action = isset($_GET['action']) ? $_GET['action'] : '';
$tenant_id = get_active_tenant_id();

if (!$tenant_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'المستأجر غير معرّف.']);
    exit;
}

// Function to fetch current SYP rate from sp-today.com
function scrape_syp_rate() {
    $url = "https://sp-today.com/ar/";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || empty($html)) {
        return false;
    }
    
    preg_match_all('/([0-9]{4,5})/', $html, $matches);
    
    if (!empty($matches[1])) {
        foreach ($matches[1] as $num) {
            $val = intval($num);
            if ($val >= 10000 && $val <= 25000) {
                return $val;
            }
        }
    }
    
    return false;
}

if ($action === 'sync') {
    $mode = get_system_setting('ns_settings_sync_mode', 'auto');
    
    $rate = scrape_syp_rate();
    $source = "sp-today.com";
    $success = true;
    
    if ($rate === false) {
        // Fallback: Read last stored rate
        $stmt = $pdo->prepare("SELECT `rate` FROM `ns_currencies` WHERE `code` = 'SYP' AND `tenant_id` = ?");
        $stmt->execute([$tenant_id]);
        $rate = $stmt->fetchColumn();
        $source = "Database Fallback (فشل الاتصال بالموقع)";
        $success = false;
        
        log_activity('warning', 'فشل تحديث العملة تلقائياً', 'تعذر الاتصال بـ sp-today.com للحصول على سعر الصرف. تم استخدام آخر سعر صرف محفوظ.');
    } else {
        if ($mode === 'auto') {
            // Update currency rate in table
            $stmt = $pdo->prepare("UPDATE `ns_currencies` SET `rate` = ?, `last_updated` = CURRENT_TIMESTAMP WHERE `code` = 'SYP' AND `tenant_id` = ?");
            $stmt->execute([$rate, $tenant_id]);
            
            // Update general setting
            $stmt = $pdo->prepare("INSERT INTO `ns_settings` (`tenant_id`, `key`, `value`) VALUES (?, 'ns_settings_exchange_rate', ?) 
                ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute([$tenant_id, $rate]);
            
            log_activity('success', 'تحديث تلقائي للعملة', "تم تحديث سعر صرف الليرة السورية تلقائياً إلى {$rate} ل.س للدولار عبر sp-today.com.");
        }
    }
    
    echo json_encode([
        'success' => $success,
        'rate' => intval($rate),
        'source' => $source,
        'mode' => $mode
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'update_mode') {
    // Authenticate session
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);
    $mode = isset($input['mode']) ? $input['mode'] : 'auto'; // 'auto' or 'manual'
    $rate = isset($input['rate']) ? floatval($input['rate']) : 0;
    
    if ($mode === 'manual' && $rate <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الرجاء إدخل سعر صرف صالح أكبر من الصفر.']);
        exit;
    }
    
    // Update settings
    $stmt = $pdo->prepare("INSERT INTO `ns_settings` (`tenant_id`, `key`, `value`) VALUES (?, 'ns_settings_sync_mode', ?) 
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    $stmt->execute([$tenant_id, $mode]);
    
    if ($mode === 'manual') {
        // Update currency rate manually
        $stmt = $pdo->prepare("UPDATE `ns_currencies` SET `rate` = ?, `update_mode` = 'manual', `last_updated` = CURRENT_TIMESTAMP WHERE `code` = 'SYP' AND `tenant_id` = ?");
        $stmt->execute([$rate, $tenant_id]);
        
        $stmt = $pdo->prepare("INSERT INTO `ns_settings` (`tenant_id`, `key`, `value`) VALUES (?, 'ns_settings_exchange_rate', ?) 
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        $stmt->execute([$tenant_id, $rate]);
        
        log_activity('info', 'تعديل عملة يدوي', "تم تحويل تحديث سعر الصرف إلى يدوي وتحديد السعر بقيمة {$rate} ل.س.");
    } else {
        $stmt = $pdo->prepare("UPDATE `ns_currencies` SET `update_mode` = 'auto' WHERE `code` = 'SYP' AND `tenant_id` = ?");
        $stmt->execute([$tenant_id]);
        
        log_activity('info', 'تغيير نمط العملة', "تم إعادة تفعيل وضع تحديث سعر الصرف التلقائي.");
    }
    
    echo json_encode(['success' => true, 'message' => 'تم حفظ إعدادات سعر الصرف بنجاح.']);
    exit;
}

// Default response: get current rates for active tenant
$stmt = $pdo->prepare("SELECT * FROM `ns_currencies` WHERE `tenant_id` = ? ORDER BY `code` ASC");
$stmt->execute([$tenant_id]);
$currencies = $stmt->fetchAll();

echo json_encode([
    'success' => true,
    'currencies' => $currencies,
    'sync_mode' => get_system_setting('ns_settings_sync_mode', 'auto')
], JSON_UNESCAPED_UNICODE);
?>
