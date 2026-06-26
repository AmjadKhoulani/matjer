<?php
// reviews.php - Storefront customer reviews management API with tenant isolation
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

// Allow GET and review creation POST without login, but moderation changes require login
if ($method === 'POST' && ($action === 'approve' || $action === 'spam' || $action === 'update_status')) {
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
        exit;
    }
}
if ($method === 'DELETE' && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
    exit;
}

if ($method === 'GET') {
    $sql = "SELECT r.*, p.`name` as product_name, p.`sku` as product_sku 
            FROM `ns_reviews` r
            JOIN `ns_products` p ON r.`product_id` = p.`id` 
            WHERE r.`tenant_id` = ? AND p.`tenant_id` = ?";
    $params = [$tenant_id, $tenant_id];
    
    if (isset($_GET['product_id'])) {
        $sql .= " AND r.`product_id` = ?";
        $params[] = intval($_GET['product_id']);
    }
    
    if (isset($_GET['status'])) {
        $sql .= " AND r.`status` = ?";
        $params[] = $_GET['status'];
    }
    
    $sql .= " ORDER BY r.`date` DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();
    
    echo json_encode($reviews, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if ($action === 'update_status') {
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $status = isset($input['status']) ? trim($input['status']) : 'Pending';
        
        if ($id <= 0 || !in_array($status, ['Approved', 'Spam', 'Pending'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'بيانات التحديث غير صالحة.']);
            exit;
        }
        
        // Verify ownership
        $check = $pdo->prepare("SELECT COUNT(*) FROM `ns_reviews` WHERE `id` = ? AND `tenant_id` = ?");
        $check->execute([$id, $tenant_id]);
        if ($check->fetchColumn() == 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مصرح بالتعديل.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE `ns_reviews` SET `status` = ? WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$status, $id, $tenant_id]);
        
        $statusMap = [
            'Approved' => 'مقبول ونشط',
            'Spam' => 'مزعج/سبام',
            'Pending' => 'معلق'
        ];
        
        log_activity('info', 'تعديل حالة مراجعة', "تم تعديل حالة المراجعة رقم #{$id} إلى ({$statusMap[$status]}).");
        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة المراجعة بنجاح.']);
        exit;
    }
    
    // Create new Review
    $customerName = isset($input['customer_name']) ? trim($input['customer_name']) : '';
    $productId = isset($input['product_id']) ? intval($input['product_id']) : 0;
    $rating = isset($input['rating']) ? intval($input['rating']) : 5;
    $text = isset($input['text']) ? trim($input['text']) : '';
    
    if (empty($customerName) || $productId <= 0 || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الرجاء ملء حقل الاسم والتقييم واختيار المنتج.']);
        exit;
    }
    
    // Check if product belongs to tenant
    $check = $pdo->prepare("SELECT COUNT(*) FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
    $check->execute([$productId, $tenant_id]);
    if ($check->fetchColumn() == 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'المنتج غير صالح.']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO `ns_reviews` (`tenant_id`, `customer_name`, `product_id`, `rating`, `text`, `status`) VALUES (?, ?, ?, ?, ?, 'Pending')");
    $stmt->execute([$tenant_id, $customerName, $productId, $rating, $text]);
    
    echo json_encode([
        'success' => true,
        'message' => 'نشكرك على تقييمك! سيظهر تقييمك في المتجر فور مراجعته واعتماده من قبل الإدارة.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'طلب غير صالح.']);
        exit;
    }
    
    // Verify ownership
    $check = $pdo->prepare("SELECT COUNT(*) FROM `ns_reviews` WHERE `id` = ? AND `tenant_id` = ?");
    $check->execute([$id, $tenant_id]);
    if ($check->fetchColumn() == 0) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح بحذف هذه المراجعة.']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM `ns_reviews` WHERE `id` = ? AND `tenant_id` = ?");
    $stmt->execute([$id, $tenant_id]);
    
    log_activity('warning', 'حذف مراجعة', "تم حذف تقييم العميل (المراجعة رقم #{$id}) نهائياً.");
    echo json_encode(['success' => true, 'message' => 'تم حذف التقييم بنجاح.']);
    exit;
}
?>
