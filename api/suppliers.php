<?php
// suppliers.php - Suppliers management API with tenant isolation
require_once __DIR__ . '/config.php';

header("Content-Type: application/json; charset=utf-8");

$method = $_SERVER['REQUEST_METHOD'];
$tenant_id = get_active_tenant_id();

if (!$tenant_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'المستأجر غير معرّف.']);
    exit;
}

// Authenticate session (prevent unauthenticated supplier modifications)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
    exit;
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM `ns_suppliers` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $supplier = $stmt->fetch();
        if ($supplier) {
            echo json_encode($supplier, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'المورد غير موجود.']);
        }
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM `ns_suppliers` WHERE `tenant_id` = ? ORDER BY `name` ASC");
    $stmt->execute([$tenant_id]);
    $suppliers = $stmt->fetchAll();
    echo json_encode($suppliers, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $name = isset($input['name']) ? trim($input['name']) : '';
    $contact_name = isset($input['contact_name']) ? trim($input['contact_name']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : '';
    $email = isset($input['email']) ? trim($input['email']) : '';
    $address = isset($input['address']) ? trim($input['address']) : '';
    $products = isset($input['products']) ? trim($input['products']) : '';
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الرجاء إدخال اسم المورد.']);
        exit;
    }
    
    if ($id > 0) {
        // Verify owner
        $check = $pdo->prepare("SELECT COUNT(*) FROM `ns_suppliers` WHERE `id` = ? AND `tenant_id` = ?");
        $check->execute([$id, $tenant_id]);
        if ($check->fetchColumn() == 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مصرح بالتعديل.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE `ns_suppliers` SET 
            `name` = ?, `contact_name` = ?, `phone` = ?, `email` = ?, `address` = ?, `products` = ?
            WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$name, $contact_name, $phone, $email, $address, $products, $id, $tenant_id]);
        
        log_activity('info', 'تعديل مورد', "تم تحديث بيانات المورد \"{$name}\".");
        echo json_encode(['success' => true, 'message' => 'تم تحديث المورد بنجاح.']);
    } else {
        $stmt = $pdo->prepare("INSERT INTO `ns_suppliers` (`tenant_id`, `name`, `contact_name`, `phone`, `email`, `address`, `products`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $name, $contact_name, $phone, $email, $address, $products]);
        
        $newId = $pdo->lastInsertId();
        log_activity('success', 'إضافة مورد جديد', "تم تسجيل مورد جديد باسم \"{$name}\".");
        echo json_encode(['success' => true, 'message' => 'تم تسجيل المورد بنجاح.', 'id' => $newId]);
    }
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'طلب غير صالح.']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT `name` FROM `ns_suppliers` WHERE `id` = ? AND `tenant_id` = ?");
    $stmt->execute([$id, $tenant_id]);
    $name = $stmt->fetchColumn();
    
    if (!$name) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'المورد غير موجود.']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM `ns_suppliers` WHERE `id` = ? AND `tenant_id` = ?");
    $stmt->execute([$id, $tenant_id]);
    
    log_activity('danger', 'حذف مورد', "تم حذف المورد \"{$name}\" من الدليل.");
    echo json_encode(['success' => true, 'message' => 'تم حذف المورد بنجاح.']);
    exit;
}
?>
