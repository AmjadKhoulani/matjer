<?php
// quotations.php - Price Quotations Management API with tenant isolation
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

// Authenticate session (prevent unauthenticated quotation edits)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
    exit;
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Fetch quotation details for active tenant
        $stmt = $pdo->prepare("SELECT * FROM `ns_quotations` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $quotation = $stmt->fetch();
        
        if (!$quotation) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'عرض السعر غير موجود.']);
            exit;
        }
        
        // Fetch quotation items joining products scoped to tenant
        $stmt = $pdo->prepare("SELECT qi.*, p.`name` as product_name, p.`sku` as product_sku 
            FROM `ns_quotation_items` qi
            JOIN `ns_products` p ON qi.`product_id` = p.`id`
            WHERE qi.`quotation_id` = ? AND p.`tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $quotation['items'] = $stmt->fetchAll();
        
        echo json_encode($quotation, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Fetch all quotations for active tenant
    $stmt = $pdo->prepare("SELECT * FROM `ns_quotations` WHERE `tenant_id` = ? ORDER BY `date` DESC");
    $stmt->execute([$tenant_id]);
    $quotations = $stmt->fetchAll();
    
    // Map items to quotations
    foreach ($quotations as &$q) {
        $stmt = $pdo->prepare("SELECT qi.*, p.`name` as product_name, p.`sku` as product_sku 
            FROM `ns_quotation_items` qi 
            JOIN `ns_products` p ON qi.`product_id` = p.`id`
            WHERE qi.`quotation_id` = ? AND p.`tenant_id` = ?");
        $stmt->execute([$q['id'], $tenant_id]);
        $q['items'] = $stmt->fetchAll();
    }
    
    echo json_encode($quotations, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if ($action === 'convert') {
        // Convert Quotation to Invoice
        $id = isset($input['id']) ? intval($input['id']) : 0;
        
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'عرض السعر المحدد غير صالح.']);
            exit;
        }
        
        // Fetch quotation details for active tenant
        $stmt = $pdo->prepare("SELECT * FROM `ns_quotations` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $q = $stmt->fetch();
        
        if (!$q) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'عرض السعر غير موجود.']);
            exit;
        }
        
        if ($q['status'] === 'Converted') {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'تم تحويل عرض السعر هذا مسبقاً لـ فاتورة مبيعات.']);
            exit;
        }
        
        // Fetch items
        $stmt = $pdo->prepare("SELECT qi.* FROM `ns_quotation_items` qi
            JOIN `ns_products` p ON qi.`product_id` = p.`id`
            WHERE qi.`quotation_id` = ? AND p.`tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $qItems = $stmt->fetchAll();
        
        if (empty($qItems)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'عرض السعر لا يحتوي على أي بنود.']);
            exit;
        }
        
        // 1. Verify stock availability
        foreach ($qItems as $item) {
            $prodStmt = $pdo->prepare("SELECT `stock`, `name` FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
            $prodStmt->execute([$item['product_id'], $tenant_id]);
            $prod = $prodStmt->fetch();
            
            if (!$prod) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'أحد منتجات عرض السعر لم يعد متوفراً في المستودعات.']);
                exit;
            }
            
            if ($prod['stock'] < $item['quantity']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => "المخزون غير كافٍ للمنتج \"{$prod['name']}\" (المطلوب: {$item['quantity']}، المتوفر: {$prod['stock']})."]);
                exit;
            }
        }
        
        // 2. Perform conversion in a transaction
        try {
            $pdo->beginTransaction();
            
            // Create sales invoice with tenant_id
            $stmtInv = $pdo->prepare("INSERT INTO `ns_orders` (
                `tenant_id`, `customer_id`, `customer_name`, `total_usd`, `exchange_rate`, 
                `discount_usd`, `source`, `payment_status`, `payment_method`, `notes`, `status`
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $notes = "فاتورة بيع صادرة بناءً على عرض السعر رقم #QT-{$id}";
            $stmtInv->execute([
                $tenant_id, $q['customer_id'], $q['customer_name'], $q['total_usd'], $q['exchange_rate'],
                $q['discount_usd'], 'invoice', 'Unpaid', 'Cash', $notes, 'Pending'
            ]);
            
            $invoiceId = $pdo->lastInsertId();
            
            // Copy items & Deduct stock
            $stmtItem = $pdo->prepare("INSERT INTO `ns_order_items` (
                `order_id`, `product_id`, `quantity`, `price_usd`
            ) VALUES (?, ?, ?, ?)");
            
            $updateProd = $pdo->prepare("UPDATE `ns_products` SET `stock` = ?, `status` = ? WHERE `id` = ? AND `tenant_id` = ?");
            
            foreach ($qItems as $item) {
                // Insert invoice item
                $stmtItem->execute([$invoiceId, $item['product_id'], $item['quantity'], $item['price_usd']]);
                
                // Deduct stock
                $prodStmt = $pdo->prepare("SELECT `stock`, `min_stock` FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
                $prodStmt->execute([$item['product_id'], $tenant_id]);
                $pData = $prodStmt->fetch();
                
                $newStock = max(0, $pData['stock'] - $item['quantity']);
                $newStatus = 'In Stock';
                if ($newStock === 0) {
                    $newStatus = 'Out of Stock';
                } elseif ($newStock <= $pData['min_stock']) {
                    $newStatus = 'Low Stock';
                }
                
                $updateProd->execute([$newStock, $newStatus, $item['product_id'], $tenant_id]);
            }
            
            // Update quotation status
            $stmtQuoUpdate = $pdo->prepare("UPDATE `ns_quotations` SET `status` = 'Converted' WHERE `id` = ? AND `tenant_id` = ?");
            $stmtQuoUpdate->execute([$id, $tenant_id]);
            
            // Loyalty points
            if ($q['customer_id'] !== null) {
                $pointsEarned = floor($q['total_usd'] / 10);
                if ($pointsEarned > 0) {
                    $stmtCust = $pdo->prepare("UPDATE `ns_customers` SET `loyalty_points` = `loyalty_points` + ? WHERE `id` = ? AND `tenant_id` = ?");
                    $stmtCust->execute([$pointsEarned, $q['customer_id'], $tenant_id]);
                }
            }
            
            $pdo->commit();
            
            log_activity('success', 'تحويل عرض سعر لـ فاتورة', "تم بنجاح تحويل عرض السعر #QT-{$id} لـ فاتورة مبيعات رقم #{$invoiceId} وحسم المخزون.");
            
            echo json_encode([
                'success' => true,
                'message' => 'تم تحويل عرض السعر لفاتورة مبيعات بنجاح.',
                'order_id' => $invoiceId
            ]);
            
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'فشلت عملية تحويل عرض السعر.',
                'details' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    if ($action === 'update_status') {
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $status = isset($input['status']) ? trim($input['status']) : '';
        
        if ($id <= 0 || !in_array($status, ['Draft', 'Sent', 'Accepted', 'Rejected'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'بيانات التحديث غير صالحة.']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE `ns_quotations` SET `status` = ? WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$status, $id, $tenant_id]);
        
        log_activity('info', 'تحديث عرض سعر', "تم تغيير حالة عرض السعر #QT-{$id} إلى ({$status}).");
        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة عرض السعر بنجاح.']);
        exit;
    }
    
    // Create new Quotation
    $customerId = isset($input['customer_id']) && $input['customer_id'] !== '' ? intval($input['customer_id']) : null;
    $customerName = isset($input['customer_name']) ? trim($input['customer_name']) : '';
    $total_usd = isset($input['total_usd']) ? floatval($input['total_usd']) : 0.00;
    $exchange_rate = isset($input['exchange_rate']) ? floatval($input['exchange_rate']) : 1.00;
    $discount_usd = isset($input['discount_usd']) ? floatval($input['discount_usd']) : 0.00;
    $valid_until = isset($input['valid_until']) && !empty($input['valid_until']) ? trim($input['valid_until']) : null;
    $notes = isset($input['notes']) ? trim($input['notes']) : '';
    $items = isset($input['items']) ? $input['items'] : [];
    
    if (empty($customerName) || empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الرجاء إدخال اسم العميل وإضافة عنصر واحد على الأقل.']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert quotation
        $stmt = $pdo->prepare("INSERT INTO `ns_quotations` (
            `tenant_id`, `customer_id`, `customer_name`, `total_usd`, `exchange_rate`, 
            `discount_usd`, `valid_until`, `notes`, `status`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Draft')");
        
        $stmt->execute([
            $tenant_id, $customerId, $customerName, $total_usd, $exchange_rate,
            $discount_usd, $valid_until, $notes
        ]);
        
        $qId = $pdo->lastInsertId();
        
        // Insert items
        $stmtItem = $pdo->prepare("INSERT INTO `ns_quotation_items` (
            `quotation_id`, `product_id`, `quantity`, `price_usd`
        ) VALUES (?, ?, ?, ?)");
        
        foreach ($items as $item) {
            $prodId = intval($item['product_id']);
            $qty = intval($item['quantity']);
            $price = floatval($item['price_usd']);
            
            $stmtItem->execute([$qId, $prodId, $qty, $price]);
        }
        
        $pdo->commit();
        
        log_activity('success', 'إنشاء عرض سعر جديد', "تم إنشاء عرض سعر بقيمة $ {$total_usd} (رمز عرض السعر: #QT-{$qId}) للعميل \"{$customerName}\".");
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء عرض السعر بنجاح.',
            'quotation_id' => $qId
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'فشلت عملية إنشاء عرض السعر.',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}
?>
