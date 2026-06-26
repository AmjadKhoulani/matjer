<?php
// orders.php - Orders & Sales Invoices Management API with tenant isolation
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

// Allow GET for public store storefront.html checkout without session checks
if ($method !== 'GET' && !isset($_SESSION['user_id'])) {
    // Only allow order creation if it comes from storefront (source = 'ecommerce')
    $input = json_decode(file_get_contents("php://input"), true);
    $source = isset($input['source']) ? $input['source'] : '';
    if ($source !== 'ecommerce') {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
        exit;
    }
}

if ($method === 'GET') {
    if ($action === 'stats') {
        try {
            // Revenue: Delivered or Shipped orders for active tenant
            $stmt = $pdo->prepare("SELECT IFNULL(SUM(`total_usd`), 0) FROM `ns_orders` WHERE `tenant_id` = ? AND `status` IN ('Delivered', 'Shipped')");
            $stmt->execute([$tenant_id]);
            $revenue = floatval($stmt->fetchColumn());
            
            // Sales count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ns_orders` WHERE `tenant_id` = ? AND `status` IN ('Delivered', 'Shipped')");
            $stmt->execute([$tenant_id]);
            $salesCount = intval($stmt->fetchColumn());
            
            // Stock value (sum of cost * stock for all products)
            $stmt = $pdo->prepare("SELECT IFNULL(SUM(`cost_usd` * `stock`), 0) FROM `ns_products` WHERE `tenant_id` = ?");
            $stmt->execute([$tenant_id]);
            $stockValue = floatval($stmt->fetchColumn());
            
            // Low stock count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ns_products` WHERE `tenant_id` = ? AND `stock` <= `min_stock` AND `stock` > 0");
            $stmt->execute([$tenant_id]);
            $lowStockCount = intval($stmt->fetchColumn());
            
            // Out of stock count
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM `ns_products` WHERE `tenant_id` = ? AND `stock` = 0");
            $stmt->execute([$tenant_id]);
            $outOfStockCount = intval($stmt->fetchColumn());
            
            echo json_encode([
                'success' => true,
                'revenue' => $revenue,
                'sales_count' => $salesCount,
                'stock_value' => $stockValue,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'فشلت إحصائيات لوحة التحكم.', 'details' => $e->getMessage()]);
        }
        exit;
    }
    
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        
        // Fetch order details for active tenant
        $stmt = $pdo->prepare("SELECT * FROM `ns_orders` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'الفاتورة غير موجودة.']);
            exit;
        }
        
        // Fetch order items
        $stmt = $pdo->prepare("SELECT oi.*, p.`name` as product_name, p.`sku` as product_sku 
            FROM `ns_order_items` oi
            JOIN `ns_products` p ON oi.`product_id` = p.`id`
            WHERE oi.`order_id` = ? AND p.`tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $order['items'] = $stmt->fetchAll();
        
        echo json_encode($order, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Fetch list of orders for active tenant
    $sql = "SELECT * FROM `ns_orders` WHERE `tenant_id` = ?";
    $params = [$tenant_id];
    
    if (isset($_GET['source'])) {
        $sql .= " AND `source` = ?";
        $params[] = $_GET['source'];
    }
    
    $sql .= " ORDER BY `date` DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // Map items to orders
    foreach ($orders as &$o) {
        $stmt = $pdo->prepare("SELECT oi.*, p.`name` as product_name, p.`sku` as product_sku 
            FROM `ns_order_items` oi 
            JOIN `ns_products` p ON oi.`product_id` = p.`id`
            WHERE oi.`order_id` = ? AND p.`tenant_id` = ?");
        $stmt->execute([$o['id'], $tenant_id]);
        $o['items'] = $stmt->fetchAll();
    }
    
    echo json_encode($orders, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if ($action === 'update_status') {
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $status = isset($input['status']) ? trim($input['status']) : '';
        
        if ($id <= 0 || !in_array($status, ['Pending', 'Shipped', 'Delivered', 'Cancelled'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'بيانات التحديث غير صالحة.']);
            exit;
        }
        
        // Fetch current status for active tenant
        $stmt = $pdo->prepare("SELECT `status`, `customer_name` FROM `ns_orders` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $order = $stmt->fetch();
        
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'الفاتورة غير موجودة.']);
            exit;
        }
        
        $oldStatus = $order['status'];
        
        // Update order status
        $stmt = $pdo->prepare("UPDATE `ns_orders` SET `status` = ? WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$status, $id, $tenant_id]);
        
        $statusMap = [
            'Pending' => 'قيد الانتظار',
            'Shipped' => 'تم الشحن',
            'Delivered' => 'تم التوصيل',
            'Cancelled' => 'ملغي'
        ];
        
        // If cancelled, return stock levels back
        if ($status === 'Cancelled' && $oldStatus !== 'Cancelled') {
            // Fetch items
            $stmt = $pdo->prepare("SELECT oi.`product_id`, oi.`quantity` FROM `ns_order_items` oi 
                JOIN `ns_products` p ON oi.`product_id` = p.`id`
                WHERE oi.`order_id` = ? AND p.`tenant_id` = ?");
            $stmt->execute([$id, $tenant_id]);
            $items = $stmt->fetchAll();
            
            foreach ($items as $item) {
                // Read current stock
                $prodStmt = $pdo->prepare("SELECT `stock`, `min_stock` FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
                $prodStmt->execute([$item['product_id'], $tenant_id]);
                $prod = $prodStmt->fetch();
                
                if ($prod) {
                    $newStock = $prod['stock'] + $item['quantity'];
                    $newStatus = ($newStock > $prod['min_stock']) ? 'In Stock' : 'Low Stock';
                    
                    $updateProd = $pdo->prepare("UPDATE `ns_products` SET `stock` = ?, `status` = ? WHERE `id` = ? AND `tenant_id` = ?");
                    $updateProd->execute([$newStock, $newStatus, $item['product_id'], $tenant_id]);
                }
            }
            log_activity('danger', 'إلغاء فاتورة مبيعات', "تم إلغاء الفاتورة رقم #{$id} وإعادة البضائع للمستودع.");
        }
        
        log_activity('info', 'تحديث الفاتورة', "تم تغيير حالة الفاتورة #{$id} إلى ({$statusMap[$status]}).");
        
        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة الفاتورة بنجاح.']);
        exit;
    }
    
    if ($action === 'update_payment') {
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $payment_status = isset($input['payment_status']) ? trim($input['payment_status']) : '';
        
        if ($id <= 0 || !in_array($payment_status, ['Paid', 'Unpaid', 'Partially Paid'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'بيانات الدفع غير صالحة.']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE `ns_orders` SET `payment_status` = ? WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$payment_status, $id, $tenant_id]);
        
        $payMap = [
            'Paid' => 'مدفوعة',
            'Unpaid' => 'غير مدفوعة',
            'Partially Paid' => 'مدفوعة جزئياً'
        ];
        
        log_activity('info', 'سداد الفاتورة', "تم تحديث حالة السداد للفاتورة #{$id} إلى ({$payMap[$payment_status]}).");
        
        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة السداد بنجاح.']);
        exit;
    }
    
    // Create new Order / Sales Invoice
    $customerId = isset($input['customer_id']) && $input['customer_id'] !== '' ? intval($input['customer_id']) : null;
    $customerName = isset($input['customer_name']) ? trim($input['customer_name']) : '';
    $total_usd = isset($input['total_usd']) ? floatval($input['total_usd']) : 0.00;
    $exchange_rate = isset($input['exchange_rate']) ? floatval($input['exchange_rate']) : 1.00;
    $coupon_code = isset($input['coupon_code']) && !empty($input['coupon_code']) ? trim($input['coupon_code']) : null;
    $discount_usd = isset($input['discount_usd']) ? floatval($input['discount_usd']) : 0.00;
    $source = isset($input['source']) ? trim($input['source']) : 'ecommerce'; // 'ecommerce', 'pos', 'invoice'
    $payment_status = isset($input['payment_status']) ? trim($input['payment_status']) : 'Paid';
    $payment_method = isset($input['payment_method']) ? trim($input['payment_method']) : 'Cash';
    $notes = isset($input['notes']) ? trim($input['notes']) : '';
    $items = isset($input['items']) ? $input['items'] : [];
    
    if (empty($customerName) || empty($items)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الرجاء إدخال اسم العميل وإضافة عنصر واحد على الأقل.']);
        exit;
    }
    
    // 1. Validate stock levels first
    foreach ($items as $item) {
        $prodId = intval($item['product_id']);
        $qty = intval($item['quantity']);
        
        $stmt = $pdo->prepare("SELECT `stock`, `name` FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$prodId, $tenant_id]);
        $prod = $stmt->fetch();
        
        if (!$prod) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "المنتج غير موجود في المستودع."]);
            exit;
        }
        
        if ($prod['stock'] < $qty) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "كمية المخزون غير كافية للمنتج \"{$prod['name']}\". المتبقي: {$prod['stock']} قطع."]);
            exit;
        }
    }
    
    // 2. Perform Stock Deductions & DB insertion inside a transaction
    try {
        $pdo->beginTransaction();
        
        // Insert order record
        $stmt = $pdo->prepare("INSERT INTO `ns_orders` (
            `tenant_id`, `customer_id`, `customer_name`, `total_usd`, `exchange_rate`, 
            `coupon_code`, `discount_usd`, `source`, `payment_status`, `payment_method`, `notes`, `status`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // For POS and ecommerce direct orders, mark status as 'Delivered' immediately, otherwise 'Pending'
        $status = ($source === 'pos' || $source === 'ecommerce') ? 'Delivered' : 'Pending';
        
        $stmt->execute([
            $tenant_id, $customerId, $customerName, $total_usd, $exchange_rate,
            $coupon_code, $discount_usd, $source, $payment_status, $payment_method, $notes, $status
        ]);
        
        $orderId = $pdo->lastInsertId();
        
        // Insert items and deduct stock
        $stmtItem = $pdo->prepare("INSERT INTO `ns_order_items` (
            `order_id`, `product_id`, `quantity`, `price_usd`, `size`, `color`
        ) VALUES (?, ?, ?, ?, ?, ?)");
        
        $updateProd = $pdo->prepare("UPDATE `ns_products` SET `stock` = ?, `status` = ? WHERE `id` = ? AND `tenant_id` = ?");
        
        foreach ($items as $item) {
            $prodId = intval($item['product_id']);
            $qty = intval($item['quantity']);
            $price = floatval($item['price_usd']);
            $size = isset($item['size']) ? trim($item['size']) : null;
            $color = isset($item['color']) ? trim($item['color']) : null;
            
            // Insert line item
            $stmtItem->execute([$orderId, $prodId, $qty, $price, $size, $color]);
            
            // Deduct stock
            $prodStmt = $pdo->prepare("SELECT `stock`, `min_stock` FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
            $prodStmt->execute([$prodId, $tenant_id]);
            $pData = $prodStmt->fetch();
            
            $newStock = max(0, $pData['stock'] - $qty);
            $newStatus = 'In Stock';
            if ($newStock === 0) {
                $newStatus = 'Out of Stock';
            } elseif ($newStock <= $pData['min_stock']) {
                $newStatus = 'Low Stock';
            }
            
            $updateProd->execute([$newStock, $newStatus, $prodId, $tenant_id]);
        }
        
        // 3. Add Loyalty Points if customer is linked
        if ($customerId !== null) {
            $pointsEarned = floor($total_usd / 10);
            if ($pointsEarned > 0) {
                $stmtCust = $pdo->prepare("UPDATE `ns_customers` SET `loyalty_points` = `loyalty_points` + ? WHERE `id` = ? AND `tenant_id` = ?");
                $stmtCust->execute([$pointsEarned, $customerId, $tenant_id]);
            }
        }
        
        $pdo->commit();
        
        $sourceMap = [
            'ecommerce' => 'طلب متجر إلكتروني',
            'pos' => 'مبيعات نقطة البيع POS',
            'invoice' => 'فاتورة مبيعات مباشرة'
        ];
        
        log_activity('success', $sourceMap[$source] ?? 'عملية بيع جديدة', "تم إتمام عملية بيع بنجاح بقيمة $ {$total_usd} (الفاتورة #{$orderId}) للعميل \"{$customerName}\".");
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إصدار الفاتورة بنجاح وتحديث كميات المخزن.',
            'order_id' => $orderId
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'فشلت عملية إنشاء الفاتورة.',
            'details' => $e->getMessage()
        ]);
    }
    exit;
}
?>
