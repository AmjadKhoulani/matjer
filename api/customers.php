<?php
// customers.php - Customer CRM and interaction notes API with tenant isolation
require_once __DIR__ . '/config.php';

header("Content-Type: application/json; charset=utf-8");

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

$tenant_id = get_active_tenant_id();
if (!$tenant_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'المستأجر غير معرّف.']);
    exit;
}

// Authenticate sessions (prevent unauthorized CRM modifications)
if ($method !== 'GET' && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
    exit;
}

if ($method === 'GET') {
    if ($action === 'notes') {
        $customerId = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : 0;
        if ($customerId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'رقم العميل غير صالح.']);
            exit;
        }
        
        // Verify customer belongs to active tenant
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ns_customers` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$customerId, $tenant_id]);
        if ($stmt->fetchColumn() == 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مسموح بالوصول لهذا العميل.']);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT cn.* FROM `ns_crm_notes` cn
            JOIN `ns_customers` c ON cn.`customer_id` = c.`id`
            WHERE cn.`customer_id` = ? AND c.`tenant_id` = ?
            ORDER BY cn.`date` DESC");
        $stmt->execute([$customerId, $tenant_id]);
        $notes = $stmt->fetchAll();
        
        echo json_encode($notes, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Fetch customer info for active tenant
        $stmt = $pdo->prepare("SELECT * FROM `ns_customers` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $customer = $stmt->fetch();
        
        if (!$customer) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'العميل غير موجود.']);
            exit;
        }
        
        // Calculate stats for this customer
        $statsStmt = $pdo->prepare("SELECT 
            COUNT(*) as total_orders, 
            IFNULL(SUM(`total_usd`), 0) as total_ltv 
            FROM `ns_orders` 
            WHERE `customer_id` = ? AND `tenant_id` = ? AND `status` != 'Cancelled'");
        $statsStmt->execute([$id, $tenant_id]);
        $stats = $statsStmt->fetch();
        
        $customer['total_orders'] = intval($stats['total_orders']);
        $customer['ltv'] = floatval($stats['total_ltv']);
        $customer['aov'] = $customer['total_orders'] > 0 ? floatval($customer['ltv'] / $customer['total_orders']) : 0.00;
        
        echo json_encode($customer, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Fetch all customers for active tenant
    $stmt = $pdo->prepare("SELECT c.*, 
        (SELECT COUNT(*) FROM `ns_orders` o WHERE o.`customer_id` = c.`id` AND o.`tenant_id` = ? AND o.`status` != 'Cancelled') as total_orders,
        (SELECT IFNULL(SUM(o.`total_usd`), 0) FROM `ns_orders` o WHERE o.`customer_id` = c.`id` AND o.`tenant_id` = ? AND o.`status` != 'Cancelled') as ltv
        FROM `ns_customers` c
        WHERE c.`tenant_id` = ?
        ORDER BY c.`created_at` DESC");
    $stmt->execute([$tenant_id, $tenant_id, $tenant_id]);
    $customers = $stmt->fetchAll();
    
    foreach ($customers as &$c) {
        $c['total_orders'] = intval($c['total_orders']);
        $c['ltv'] = floatval($c['ltv']);
        $c['aov'] = $c['total_orders'] > 0 ? floatval($c['ltv'] / $c['total_orders']) : 0.00;
    }
    
    echo json_encode($customers, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if ($action === 'add_note') {
        $customerId = isset($input['customer_id']) ? intval($input['customer_id']) : 0;
        $text = isset($input['text']) ? trim($input['text']) : '';
        
        if ($customerId <= 0 || empty($text)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'بيانات الملاحظة غير صالحة.']);
            exit;
        }
        
        // Verify customer belongs to active tenant
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ns_customers` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$customerId, $tenant_id]);
        if ($stmt->fetchColumn() == 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مسموح بالوصول لهذا العميل.']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO `ns_crm_notes` (`customer_id`, `text`) VALUES (?, ?)");
        $stmt->execute([$customerId, $text]);
        
        echo json_encode(['success' => true, 'message' => 'تمت إضافة الملاحظة بنجاح.', 'id' => $pdo->lastInsertId()]);
        exit;
    }
    
    if ($action === 'delete_note') {
        $noteId = isset($input['id']) ? intval($input['id']) : 0;
        
        if ($noteId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'طلب غير صالح.']);
            exit;
        }
        
        // Verify the note's customer belongs to active tenant
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ns_crm_notes` cn 
            JOIN `ns_customers` c ON cn.`customer_id` = c.`id`
            WHERE cn.`id` = ? AND c.`tenant_id` = ?");
        $stmt->execute([$noteId, $tenant_id]);
        if ($stmt->fetchColumn() == 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مصرح بحذف هذه الملاحظة.']);
            exit;
        }
        
        $stmt = $pdo->prepare("DELETE FROM `ns_crm_notes` WHERE `id` = ?");
        $stmt->execute([$noteId]);
        
        echo json_encode(['success' => true, 'message' => 'تم حذف الملاحظة بنجاح.']);
        exit;
    }
    
    // Create or Update Customer
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $name = isset($input['name']) ? trim($input['name']) : '';
    $phone = isset($input['phone']) ? trim($input['phone']) : null;
    $email = isset($input['email']) ? trim($input['email']) : null;
    $loyalty_points = isset($input['loyalty_points']) ? intval($input['loyalty_points']) : 0;
    $tier = isset($input['tier']) ? trim($input['tier']) : 'standard';
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الرجاء إدخال اسم العميل.']);
        exit;
    }
    
    if ($id > 0) {
        // Edit Customer
        // Verify email uniqueness within the active tenant
        if (!empty($email)) {
            $emailCheck = $pdo->prepare("SELECT COUNT(*) FROM `ns_customers` WHERE `email` = ? AND `tenant_id` = ? AND `id` != ?");
            $emailCheck->execute([$email, $tenant_id, $id]);
            if ($emailCheck->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني هذا مستخدم بالفعل لدى عميل آخر.']);
                exit;
            }
        }
        
        $stmt = $pdo->prepare("UPDATE `ns_customers` SET 
            `name` = ?, `phone` = ?, `email` = ?, `loyalty_points` = ?, `tier` = ?
            WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$name, $phone, $email, $loyalty_points, $tier, $id, $tenant_id]);
        
        log_activity('info', 'تعديل عميل', "تم تحديث بيانات العميل \"{$name}\" بنجاح.");
        echo json_encode(['success' => true, 'message' => 'تم تحديث بيانات العميل بنجاح.']);
    } else {
        // Create Customer
        if (!empty($email)) {
            $emailCheck = $pdo->prepare("SELECT COUNT(*) FROM `ns_customers` WHERE `email` = ? AND `tenant_id` = ?");
            $emailCheck->execute([$email, $tenant_id]);
            if ($emailCheck->fetchColumn() > 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني هذا مستخدم بالفعل.']);
                exit;
            }
        }
        
        $stmt = $pdo->prepare("INSERT INTO `ns_customers` (`tenant_id`, `name`, `phone`, `email`, `loyalty_points`, `tier`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $name, $phone, $email, $loyalty_points, $tier]);
        
        $newId = $pdo->lastInsertId();
        log_activity('success', 'إضافة عميل جديد', "تم تسجيل عميل جديد باسم \"{$name}\" بنظام الـ CRM.");
        echo json_encode(['success' => true, 'message' => 'تم تسجيل العميل بنجاح.', 'id' => $newId]);
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
    
    $stmt = $pdo->prepare("SELECT `name` FROM `ns_customers` WHERE `id` = ? AND `tenant_id` = ?");
    $stmt->execute([$id, $tenant_id]);
    $name = $stmt->fetchColumn();
    
    if (!$name) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'العميل غير موجود.']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM `ns_customers` WHERE `id` = ? AND `tenant_id` = ?");
    $stmt->execute([$id, $tenant_id]);
    
    log_activity('danger', 'حذف عميل', "تم حذف سجل العميل \"{$name}\" من الـ CRM.");
    echo json_encode(['success' => true, 'message' => 'تم حذف العميل بنجاح.']);
    exit;
}
?>
