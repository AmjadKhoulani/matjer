<?php
// warehouses.php - Warehouses CRUD API with tenant isolation
require_once __DIR__ . '/config.php';

header("Content-Type: application/json; charset=utf-8");

$method = $_SERVER['REQUEST_METHOD'];
$tenant_id = get_active_tenant_id();

if (!$tenant_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'المستأجر غير معرّف.']);
    exit;
}

// Authenticate session for modifications
if ($method !== 'GET' && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
    exit;
}

if ($method === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT * FROM `ns_warehouses` WHERE `tenant_id` = ? ORDER BY `id` ASC");
        $stmt->execute([$tenant_id]);
        $warehouses = $stmt->fetchAll();
        echo json_encode($warehouses, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'فشل جلب المستودعات.', 'details' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $name = isset($input['name']) ? trim($input['name']) : '';
    $address = isset($input['address']) ? trim($input['address']) : '';
    $contact = isset($input['contact']) ? trim($input['contact']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $capacity = isset($input['capacity']) ? trim($input['capacity']) : '0%';

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الرجاء إدخال اسم المستودع.']);
        exit;
    }

    try {
        if ($id > 0) {
            // Update
            $stmt = $pdo->prepare("UPDATE `ns_warehouses` SET `name` = ?, `address` = ?, `contact` = ?, `phone` = ?, `capacity` = ? 
                WHERE `id` = ? AND `tenant_id` = ?");
            $stmt->execute([$name, $address, $contact, $phone, $capacity, $id, $tenant_id]);
            log_activity('info', 'تعديل مستودع', "تم تحديث بيانات مستودع \"{$name}\".");
            echo json_encode(['success' => true, 'message' => 'تم تحديث بيانات المستودع بنجاح.']);
        } else {
            // Create
            $stmt = $pdo->prepare("INSERT INTO `ns_warehouses` (`tenant_id`, `name`, `address`, `contact`, `phone`, `capacity`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tenant_id, $name, $address, $contact, $phone, $capacity]);
            $newId = $pdo->lastInsertId();
            log_activity('success', 'إضافة مستودع', "تم إنشاء مستودع جديد باسم \"{$name}\".");
            echo json_encode(['success' => true, 'message' => 'تم إنشاء المستودع بنجاح.', 'id' => $newId]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'فشلت العملية.', 'details' => $e->getMessage()]);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'رقم مستودع غير صالح.']);
        exit;
    }

    try {
        // Find name for activity logging
        $stmt = $pdo->prepare("SELECT `name` FROM `ns_warehouses` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $name = $stmt->fetchColumn();
        
        if (!$name) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'المستودع غير موجود.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM `ns_warehouses` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        
        log_activity('danger', 'حذف مستودع', "تم إزالة مستودع \"{$name}\" نهائياً.");
        echo json_encode(['success' => true, 'message' => 'تم حذف المستودع بنجاح.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'فشل حذف المستودع.', 'details' => $e->getMessage()]);
    }
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'طلب غير صالح.']);
?>
