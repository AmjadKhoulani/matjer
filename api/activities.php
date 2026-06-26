<?php
// activities.php - Audit Logs API with tenant isolation
require_once __DIR__ . '/config.php';

header("Content-Type: application/json; charset=utf-8");

$method = $_SERVER['REQUEST_METHOD'];
$tenant_id = get_active_tenant_id();

if (!$tenant_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'المستأجر غير معرّف.']);
    exit;
}

// Authenticate session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
    exit;
}

if ($method === 'GET') {
    // Return last 30 activities for active tenant
    $stmt = $pdo->prepare("SELECT * FROM `ns_activities` WHERE `tenant_id` = ? ORDER BY `created_at` DESC LIMIT 30");
    $stmt->execute([$tenant_id]);
    $activities = $stmt->fetchAll();
    
    // Format creation times to reader-friendly duration
    foreach ($activities as &$act) {
        $timestamp = strtotime($act['created_at']);
        $diff = time() - $timestamp;
        
        if ($diff < 60) {
            $act['time'] = 'الآن';
        } elseif ($diff < 3600) {
            $act['time'] = 'منذ ' . floor($diff / 60) . ' دقيقة';
        } elseif ($diff < 86400) {
            $act['time'] = 'منذ ' . floor($diff / 3600) . ' ساعة';
        } else {
            $act['time'] = date('Y-m-d H:i', $timestamp);
        }
    }
    
    echo json_encode($activities, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    $type = isset($input['type']) ? trim($input['type']) : 'info';
    $title = isset($input['title']) ? trim($input['title']) : '';
    $desc = isset($input['desc']) ? trim($input['desc']) : '';
    
    if (empty($title) || empty($desc)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات السجل غير كاملة.']);
        exit;
    }
    
    log_activity($type, $title, $desc);
    echo json_encode(['success' => true]);
    exit;
}

if ($method === 'DELETE') {
    // Clear all activities for active tenant
    $stmt = $pdo->prepare("DELETE FROM `ns_activities` WHERE `tenant_id` = ?");
    $stmt->execute([$tenant_id]);
    
    // Log the clear action
    log_activity('warning', 'مسح السجل الكلي', 'تم إفراغ سجل الأنشطة والعمليات بالكامل');
    
    echo json_encode(['success' => true, 'message' => 'تم مسح السجل بنجاح.']);
    exit;
}
?>
