<?php
// settings.php - Settings management API for tenant
require_once __DIR__ . '/config.php';

header("Content-Type: application/json; charset=utf-8");

$method = $_SERVER['REQUEST_METHOD'];

$tenant_id = get_active_tenant_id();
if (!$tenant_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'المستأجر غير معرّف.']);
    exit;
}

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT `key`, `value` FROM `ns_settings` WHERE `tenant_id` = ?");
        $stmt->execute([$tenant_id]);
        $rows = $stmt->fetchAll();
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }
        
        // Also add theme primary color from tenant table
        $tenantDetails = get_active_tenant_details();
        if ($tenantDetails) {
            $settings['ns_tenant_name'] = $tenantDetails['name'];
            $settings['ns_tenant_slug'] = $tenantDetails['slug'];
            $settings['ns_tenant_logo'] = $tenantDetails['logo_url'];
            $settings['ns_tenant_color'] = $tenantDetails['theme_color'];
            $settings['ns_tenant_plan'] = $tenantDetails['plan'];
            $settings['ns_tenant_status'] = $tenantDetails['status'];
        }

        echo json_encode(['success' => true, 'settings' => $settings], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'فشل جلب الإعدادات.', 'details' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    // Authenticate session (prevent unauthenticated modifications)
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
        exit;
    }

    $input = json_decode(file_get_contents("php://input"), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات الإدخال غير صالحة.']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("INSERT INTO `ns_settings` (`tenant_id`, `key`, `value`) VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            
        foreach ($input as $key => $value) {
            // Ignore internal settings names that don't belong to ns_settings table
            if (strpos($key, 'ns_settings_') === 0 || strpos($key, 'ns_active_') === 0) {
                // If it's a JSON array/object, stringify it
                $valStr = is_array($value) ? json_encode($value) : (string)$value;
                $stmt->execute([$tenant_id, $key, $valStr]);
            }
        }
        
        // Also allow updating theme color or logo directly in ns_tenants
        if (isset($input['ns_tenant_color']) || isset($input['ns_tenant_logo']) || isset($input['ns_tenant_name'])) {
            $tenantDetails = get_active_tenant_details();
            if ($tenantDetails) {
                $color = isset($input['ns_tenant_color']) ? trim($input['ns_tenant_color']) : $tenantDetails['theme_color'];
                $logo = isset($input['ns_tenant_logo']) ? trim($input['ns_tenant_logo']) : $tenantDetails['logo_url'];
                $name = isset($input['ns_tenant_name']) ? trim($input['ns_tenant_name']) : $tenantDetails['name'];
                
                $tStmt = $pdo->prepare("UPDATE `ns_tenants` SET `theme_color` = ?, `logo_url` = ?, `name` = ? WHERE `id` = ?");
                $tStmt->execute([$color, $logo, $name, $tenant_id]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'تم حفظ الإعدادات بنجاح.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'فشل حفظ الإعدادات.', 'details' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'طلب غير صالح.']);
?>
