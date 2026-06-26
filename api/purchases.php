<?php
// purchases.php - Purchase Invoices Management API with tenant isolation
require_once __DIR__ . '/config.php';

header("Content-Type: application/json; charset=utf-8");

$method = $_SERVER['REQUEST_METHOD'];
$tenant_id = get_active_tenant_id();

if (!$tenant_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'المستأجر غير معرّف.']);
    exit;
}

// Authenticate session (prevent unauthenticated warehouse writes)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
    exit;
}

if ($method === 'GET') {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        $stmt = $pdo->prepare("SELECT * FROM `ns_purchase_invoices` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $purchase = $stmt->fetch();
        
        if (!$purchase) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'الفاتورة غير موجودة.']);
            exit;
        }
        
        // Fetch purchase items joining products scoped to tenant
        $stmt = $pdo->prepare("SELECT pi.* FROM `ns_purchase_items` pi
            JOIN `ns_products` p ON pi.`product_id` = p.`id`
            WHERE pi.`purchase_id` = ? AND p.`tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $purchase['items'] = $stmt->fetchAll();
        
        echo json_encode($purchase, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // List all purchases for active tenant
    $stmt = $pdo->prepare("SELECT * FROM `ns_purchase_invoices` WHERE `tenant_id` = ? ORDER BY `date` DESC");
    $stmt->execute([$tenant_id]);
    $purchases = $stmt->fetchAll();
    
    foreach ($purchases as &$p) {
        $stmt = $pdo->prepare("SELECT pi.* FROM `ns_purchase_items` pi
            JOIN `ns_products` p ON pi.`product_id` = p.`id`
            WHERE pi.`purchase_id` = ? AND p.`tenant_id` = ?");
        $stmt->execute([$p['id'], $tenant_id]);
        $p['items'] = $stmt->fetchAll();
    }
    
    echo json_encode($purchases, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    $supplierId = isset($input['supplier_id']) ? intval($input['supplier_id']) : null;
    $supplierName = isset($input['supplier_name']) ? trim($input['supplier_name']) : '';
    $warehouseId = isset($input['warehouse_id']) ? intval($input['warehouse_id']) : null;
    $warehouseName = isset($input['warehouse_name']) ? trim($input['warehouse_name']) : '';
    $date = isset($input['date']) ? trim($input['date']) : '';
    $total_usd = isset($input['total_usd']) ? floatval($input['total_usd']) : 0.00;
    $exchange_rate = isset($input['exchange_rate']) ? floatval($input['exchange_rate']) : 1.00;
    $status = isset($input['status']) ? trim($input['status']) : 'Paid'; // 'Paid', 'Pending'
    $note = isset($input['note']) ? trim($input['note']) : '';
    $items = isset($input['items']) ? $input['items'] : [];
    
    if (empty($supplierName) || empty($warehouseName) || empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الرجاء اختيار المورد والمستودع وإضافة صنف واحد على الأقل.']);
        exit;
    }
    
    // Verify warehouse belongs to active tenant
    if ($warehouseId !== null) {
        $whCheck = $pdo->prepare("SELECT COUNT(*) FROM `ns_warehouses` WHERE `id` = ? AND `tenant_id` = ?");
        $whCheck->execute([$warehouseId, $tenant_id]);
        if ($whCheck->fetchColumn() == 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'المستودع المحدد غير صالح.']);
            exit;
        }
    }
    
    // Verify supplier belongs to active tenant
    if ($supplierId !== null) {
        $supCheck = $pdo->prepare("SELECT COUNT(*) FROM `ns_suppliers` WHERE `id` = ? AND `tenant_id` = ?");
        $supCheck->execute([$supplierId, $tenant_id]);
        if ($supCheck->fetchColumn() == 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'المورد المحدد غير صالح.']);
            exit;
        }
    }

    $invoiceDate = !empty($date) ? date('Y-m-d H:i:s', strtotime($date)) : date('Y-m-d H:i:s');
    
    try {
        $pdo->beginTransaction();
        
        // Insert purchase invoice with tenant_id
        $stmt = $pdo->prepare("INSERT INTO `ns_purchase_invoices` (
            `tenant_id`, `supplier_id`, `supplier_name`, `warehouse_id`, `warehouse_name`, 
            `date`, `total_usd`, `exchange_rate`, `status`, `note`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $tenant_id, $supplierId, $supplierName, $warehouseId, $warehouseName,
            $invoiceDate, $total_usd, $exchange_rate, $status, $note
        ]);
        
        $purchaseId = $pdo->lastInsertId();
        
        // Insert items and adjust product stock & costs
        $stmtItem = $pdo->prepare("INSERT INTO `ns_purchase_items` (
            `purchase_id`, `product_id`, `product_name`, `sku`, `quantity`, `cost_usd`, `discount_usd`, `tax_percent`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        $updateProd = $pdo->prepare("UPDATE `ns_products` SET `cost_usd` = ?, `stock` = ?, `status` = ? WHERE `id` = ? AND `tenant_id` = ?");
        
        foreach ($items as $item) {
            $prodId = intval($item['product_id']);
            $productName = trim($item['product_name']);
            $sku = trim($item['sku']);
            $qty = intval($item['quantity']);
            $cost = floatval($item['cost_usd']);
            $discount = isset($item['discount_usd']) ? floatval($item['discount_usd']) : 0.00;
            $taxPercent = isset($item['tax_percent']) ? floatval($item['tax_percent']) : 15.00;
            
            // Insert line item
            $stmtItem->execute([$purchaseId, $prodId, $productName, $sku, $qty, $cost, $discount, $taxPercent]);
            
            // Fetch product stock and cost
            $prodStmt = $pdo->prepare("SELECT `stock`, `min_stock` FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
            $prodStmt->execute([$prodId, $tenant_id]);
            $pData = $prodStmt->fetch();
            
            if ($pData) {
                $newStock = $pData['stock'] + $qty;
                $newStatus = ($newStock > $pData['min_stock']) ? 'In Stock' : 'Low Stock';
                if ($newStock === 0) $newStatus = 'Out of Stock';
                
                $updateProd->execute([$cost, $newStock, $newStatus, $prodId, $tenant_id]);
                
                log_activity('success', 'تعديل المخزون والتكلفة', "تم تحديث تكلفة \"{$productName}\" لـ $ {$cost} وزيادة المخزون بـ +{$qty} قطعة. (سند شراء رقم PUR-{$purchaseId}). الرصيد الحالي: {$newStock}");
            }
        }
        
        $pdo->commit();
        
        log_activity('success', 'إنشاء سند مشتريات جديد', "تم تسجيل الفاتورة #PUR-{$purchaseId} للمورد \"{$supplierName}\" بقيمة $ {$total_usd}");
        
        echo json_encode([
            'success' => true,
            'message' => "تم إنشاء سند الشراء #PUR-{$purchaseId} بنجاح وتحديث كميات المخازن.",
            'purchase_id' => $purchaseId
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'فشلت عملية إنشاء سند الشراء.',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}
?>
