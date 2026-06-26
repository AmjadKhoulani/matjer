<?php
// auth.php - Authentication API (sessions, login, status check, logout)
require_once __DIR__ . '/config.php';

header("Content-Type: application/json; charset=utf-8");

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'login') {
    // Read JSON inputs
    $input = json_decode(file_get_contents("php://input"), true);
    $username = isset($input['username']) ? trim($input['username']) : '';
    $password = isset($input['password']) ? trim($input['password']) : '';

    if (empty($username) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الرجاء إدخال اسم المستخدم وكلمة المرور.']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM `ns_users` WHERE `username` = ? AND `status` = 'active'");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Enforce store status check if user is not superadmin
        if ($user['tenant_id'] !== null) {
            $tStmt = $pdo->prepare("SELECT `status`, `name` FROM `ns_tenants` WHERE `id` = ?");
            $tStmt->execute([$user['tenant_id']]);
            $tenant = $tStmt->fetch();
            
            if ($tenant && $tenant['status'] === 'Suspended') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'عذراً، هذا الحساب معلق حالياً. يرجى التواصل مع إدارة المنصة.']);
                exit;
            }
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];

        log_activity('info', 'تسجيل دخول', "قام الموظف {$user['fullname']} ({$user['role']}) بتسجيل الدخول للنظام.");

        echo json_encode([
            'success' => true,
            'message' => 'تم تسجيل الدخول بنجاح.',
            'user' => [
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'role' => $user['role'],
                'tenant_id' => $user['tenant_id']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة.']);
    }
    exit;
}

if ($action === 'logout') {
    if (isset($_SESSION['fullname'])) {
        log_activity('info', 'تسجيل خروج', "قام الموظف {$_SESSION['fullname']} بتسجيل الخروج من النظام.");
    }
    
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'تم تسجيل الخروج بنجاح.']);
    exit;
}

if ($action === 'check') {
    if (isset($_SESSION['user_id'])) {
        echo json_encode([
            'authenticated' => true,
            'user' => [
                'username' => $_SESSION['username'],
                'fullname' => $_SESSION['fullname'],
                'role' => $_SESSION['role'],
                'tenant_id' => $_SESSION['tenant_id']
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'authenticated' => false,
            'message' => 'غير مصرح بالوصول.'
        ]);
    }
    exit;
}

// Default response for unhandled action
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'طلب غير صالح.']);
?>
