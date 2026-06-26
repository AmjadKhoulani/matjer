<?php
// tenants.php - Tenant registration and management API (SaaS Platform)
require_once __DIR__ . '/config.php';

header("Content-Type: application/json; charset=utf-8");

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

// 1. GET requests
if ($method === 'GET') {
    if ($action === 'list') {
        // Enforce Super Admin security check
        if (!isset($_SESSION['user_id']) || $_SESSION['tenant_id'] !== null) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول. هذا الإجراء مخصص لمدير المنصة فقط.']);
            exit;
        }

        try {
            // Retrieve all tenants and their owners
            $stmt = $pdo->query("SELECT * FROM `ns_tenants` ORDER BY `id` DESC");
            $tenants = $stmt->fetchAll();
            echo json_encode($tenants, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء جلب المتاجر.', 'details' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'details') {
        $details = get_active_tenant_details();
        if ($details) {
            echo json_encode(['success' => true, 'tenant' => $details], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'المتجر غير موجود.']);
        }
        exit;
    }
}

// 2. POST requests
if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);

    if ($action === 'register') {
        $name = isset($input['name']) ? trim($input['name']) : '';
        $slug = isset($input['slug']) ? trim($input['slug']) : '';
        $owner_name = isset($input['owner_name']) ? trim($input['owner_name']) : '';
        $email = isset($input['email']) ? trim($input['email']) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';
        $phone = isset($input['phone']) ? trim($input['phone']) : '';
        $plan = isset($input['plan']) ? trim($input['plan']) : 'Pro';

        // Validation
        if (empty($name) || empty($slug) || empty($owner_name) || empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'الرجاء ملء كافة الحقول المطلوبة للتسجيل.']);
            exit;
        }

        // Clean slug
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9\-]/', '', $slug));
        if (empty($slug)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'اسم النطاق (Slug) غير صالح.']);
            exit;
        }

        // Check if slug is unique
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ns_tenants` WHERE `slug` = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'اسم النطاق هذا محجوز مسبقاً، الرجاء اختيار اسم آخر.']);
            exit;
        }

        // Check if username / email is unique in users
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ns_users` WHERE `username` = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'البريد الإلكتروني هذا مسجل بالفعل كمستخدم في النظام.']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Insert tenant
            $stmt = $pdo->prepare("INSERT INTO `ns_tenants` (`name`, `slug`, `owner_name`, `email`, `phone`, `plan`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $owner_name, $email, $phone, $plan, 'Active']);
            $tenant_id = $pdo->lastInsertId();

            // Create admin user for the tenant
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO `ns_users` (`tenant_id`, `username`, `password_hash`, `fullname`, `role`, `status`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tenant_id, $email, $password_hash, $owner_name, 'admin', 'active']);

            // Seed default settings for the tenant
            $defaultSettings = [
                'ns_settings_currency' => 'SYP (ل.س)',
                'ns_settings_tax_rate' => '15%',
                'ns_settings_store_name' => $name,
                'ns_settings_exchange_rate' => '15000',
                'ns_settings_sync_mode' => 'auto',
                'ns_active_theme' => 'jasmine',
                'ns_settings_gateways' => json_encode(['mada' => true, 'visa' => true, 'applepay' => true, 'cod' => false]),
                'ns_settings_carriers' => json_encode(['aramex' => true, 'smsa' => true, 'dhl' => false, 'pickup' => true])
            ];

            $stmt = $pdo->prepare("INSERT INTO `ns_settings` (`tenant_id`, `key`, `value`) VALUES (?, ?, ?)");
            foreach ($defaultSettings as $key => $val) {
                $stmt->execute([$tenant_id, $key, $val]);
            }

            // Seed default warehouse for the tenant
            $stmt = $pdo->prepare("INSERT INTO `ns_warehouses` (`tenant_id`, `name`, `address`, `contact`, `phone`, `capacity`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tenant_id, 'المستودع الرئيسي', 'وسط المدينة', $owner_name, $phone, '10%']);

            // Seed default currencies for the tenant
            $stmt = $pdo->prepare("INSERT INTO `ns_currencies` (`tenant_id`, `code`, `name`, `symbol`, `rate`, `update_mode`) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tenant_id, 'USD', 'دولار أمريكي', '$', 1.0000, 'manual']);
            $stmt->execute([$tenant_id, 'SYP', 'ليرة سورية', 'ل.س', 15000.0000, 'auto']);

            // Log activity for the new tenant
            $stmt = $pdo->prepare("INSERT INTO `ns_activities` (`tenant_id`, `type`, `title`, `desc`) VALUES (?, ?, ?, ?)");
            $stmt->execute([$tenant_id, 'success', 'إنشاء المتجر', 'تم تسجيل المتجر بنجاح وتوليد الإعدادات التلقائية والمستودع الرئيسي.']);

            $pdo->commit();

            echo json_encode([
                'success' => true,
                'message' => 'تم إنشاء متجرك السحابي الجديد بنجاح! يمكنك الآن تسجيل الدخول بلوحة التحكم.',
                'tenant' => [
                    'id' => $tenant_id,
                    'name' => $name,
                    'slug' => $slug,
                    'email' => $email
                ]
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'فشلت عملية التسجيل بسبب خطأ داخلي.', 'details' => $e->getMessage()]);
        }
        exit;
    }

    if ($action === 'toggle_status') {
        // Enforce Super Admin check
        if (!isset($_SESSION['user_id']) || $_SESSION['tenant_id'] !== null) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مصرح بالوصول.']);
            exit;
        }

        $id = isset($input['id']) ? intval($input['id']) : 0;
        $status = isset($input['status']) ? trim($input['status']) : '';

        if ($id <= 0 || !in_array($status, ['Active', 'Trial', 'Suspended'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'بيانات التحديث غير صالحة.']);
            exit;
        }

        try {
            $stmt = $pdo->prepare("UPDATE `ns_tenants` SET `status` = ? WHERE `id` = ?");
            $stmt->execute([$status, $id]);
            echo json_encode(['success' => true, 'message' => 'تم تحديث حالة المتجر بنجاح.']);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'فشل التحديث.', 'details' => $e->getMessage()]);
        }
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'طلب غير صالح.']);
?>
