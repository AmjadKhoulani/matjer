<?php
// products.php - Products management API (CRUD, categories, stock adjustments) with tenant isolation
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

// Authenticate session (prevent unauthenticated changes)
if ($method !== 'GET' && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'الرجاء تسجيل الدخول أولاً.']);
    exit;
}

if ($method === 'GET') {
    if ($action === 'categories') {
        // Fetch unique categories for active tenant
        $stmt = $pdo->prepare("SELECT DISTINCT `category` FROM `ns_products` WHERE `tenant_id` = ? AND `category` IS NOT NULL AND `category` != ''");
        $stmt->execute([$tenant_id]);
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $result = array_map(function($cat) {
            return ['id' => $cat, 'name' => $cat, 'description' => "منتجات قسم $cat"];
        }, $categories);
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (isset($_GET['id'])) {
        // Get single product for active tenant
        $id = intval($_GET['id']);
        $stmt = $pdo->prepare("SELECT * FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            $product['sales_channels'] = explode(',', $product['sales_channels']);
            echo json_encode($product, JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'المنتج غير موجود.']);
        }
        exit;
    }
    
    // Get all products for active tenant
    $sql = "SELECT * FROM `ns_products` WHERE `tenant_id` = ?";
    $params = [$tenant_id];
    
    if (isset($_GET['channel'])) {
        $sql .= " AND FIND_IN_SET(?, `sales_channels`)";
        $params[] = $_GET['channel'];
    }
    
    if (isset($_GET['publish_status'])) {
        $sql .= " AND `publish_status` = ?";
        $params[] = $_GET['publish_status'];
    }
    
    if (isset($_GET['category']) && $_GET['category'] !== 'all') {
        $sql .= " AND `category` = ?";
        $params[] = $_GET['category'];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Map channels to array
    foreach ($products as &$p) {
        $p['sales_channels'] = explode(',', $p['sales_channels']);
    }
    
    echo json_encode($products, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    if ($action === 'adjust_stock') {
        // Warehouse adjustment
        $id = isset($input['id']) ? intval($input['id']) : 0;
        $amount = isset($input['amount']) ? intval($input['amount']) : 0;
        $reason = isset($input['reason']) ? trim($input['reason']) : '';
        
        if ($id <= 0 || $amount === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'بيانات التعديل غير صالحة.']);
            exit;
        }
        
        // Fetch current product for active tenant
        $stmt = $pdo->prepare("SELECT * FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$id, $tenant_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'المنتج غير موجود.']);
            exit;
        }
        
        $newStock = max(0, $product['stock'] + $amount);
        $newStatus = 'In Stock';
        if ($newStock === 0) {
            $newStatus = 'Out of Stock';
        } elseif ($newStock <= $product['min_stock']) {
            $newStatus = 'Low Stock';
        }
        
        $stmt = $pdo->prepare("UPDATE `ns_products` SET `stock` = ?, `status` = ? WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([$newStock, $newStatus, $id, $tenant_id]);
        
        $logDesc = "تم تعديل مخزون \"{$product['name']}\" بـ " . ($amount > 0 ? "+$amount" : $amount) . " قطعة. (السبب: $reason). الرصيد الحالي: $newStock";
        log_activity($amount > 0 ? 'success' : 'warning', 'تعديل المخزون', $logDesc);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم تعديل كمية المخزون بنجاح.',
            'stock' => $newStock,
            'status' => $newStatus
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Create or Edit Product
    $id = isset($input['id']) ? intval($input['id']) : 0;
    $sku = isset($input['sku']) ? trim($input['sku']) : '';
    $name = isset($input['name']) ? trim($input['name']) : '';
    $category = isset($input['category']) ? trim($input['category']) : '';
    $price_usd = isset($input['price_usd']) ? floatval($input['price_usd']) : 0.00;
    $cost_usd = isset($input['cost_usd']) ? floatval($input['cost_usd']) : 0.00;
    $stock = isset($input['stock']) ? intval($input['stock']) : 0;
    $min_stock = isset($input['min_stock']) ? intval($input['min_stock']) : 5;
    $image_url = isset($input['image_url']) ? trim($input['image_url']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
    $short_description = isset($input['short_description']) ? trim($input['short_description']) : '';
    $publish_status = isset($input['publish_status']) ? trim($input['publish_status']) : 'Publish';
    
    // SEO & Ecommerce fields (optional)
    $seo_title = isset($input['seo_title']) && trim($input['seo_title']) !== '' ? trim($input['seo_title']) : null;
    $seo_description = isset($input['seo_description']) && trim($input['seo_description']) !== '' ? trim($input['seo_description']) : null;
    $seo_keywords = isset($input['seo_keywords']) && trim($input['seo_keywords']) !== '' ? trim($input['seo_keywords']) : null;
    $og_title = isset($input['og_title']) && trim($input['og_title']) !== '' ? trim($input['og_title']) : null;
    $og_description = isset($input['og_description']) && trim($input['og_description']) !== '' ? trim($input['og_description']) : null;
    $og_image = isset($input['og_image']) && trim($input['og_image']) !== '' ? trim($input['og_image']) : null;
    $google_product_category = isset($input['google_product_category']) && trim($input['google_product_category']) !== '' ? trim($input['google_product_category']) : null;
    $gtin = isset($input['gtin']) && trim($input['gtin']) !== '' ? trim($input['gtin']) : null;
    $mpn = isset($input['mpn']) && trim($input['mpn']) !== '' ? trim($input['mpn']) : null;
    
    $channels = isset($input['sales_channels']) ? $input['sales_channels'] : ['ecommerce', 'pos'];
    if (is_array($channels)) {
        $sales_channels = implode(',', $channels);
    } else {
        $sales_channels = 'ecommerce,pos';
    }
    
    if (empty($sku) || empty($name) || empty($category)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'الرجاء إدخال الحقول المطلوبة: الاسم، الفئة، ورمز SKU.']);
        exit;
    }
    
    // Determine status based on stock
    $status = 'In Stock';
    if ($stock === 0) {
        $status = 'Out of Stock';
    } elseif ($stock <= $min_stock) {
        $status = 'Low Stock';
    }
    
    if ($id > 0) {
        // Update product
        // Check if SKU is used by another product for same tenant
        $skuCheck = $pdo->prepare("SELECT COUNT(*) FROM `ns_products` WHERE `sku` = ? AND `tenant_id` = ? AND `id` != ?");
        $skuCheck->execute([$sku, $tenant_id, $id]);
        if ($skuCheck->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'رمز SKU هذا مستخدم بالفعل في منتج آخر.']);
            exit;
        }
        
        $stmt = $pdo->prepare("UPDATE `ns_products` SET 
            `sku` = ?, `name` = ?, `category` = ?, `price_usd` = ?, `cost_usd` = ?, 
            `stock` = ?, `min_stock` = ?, `status` = ?, `image_url` = ?, 
            `description` = ?, `short_description` = ?, `sales_channels` = ?, `publish_status` = ?,
            `seo_title` = ?, `seo_description` = ?, `seo_keywords` = ?, 
            `og_title` = ?, `og_description` = ?, `og_image` = ?, 
            `google_product_category` = ?, `gtin` = ?, `mpn` = ?
            WHERE `id` = ? AND `tenant_id` = ?");
        $stmt->execute([
            $sku, $name, $category, $price_usd, $cost_usd, 
            $stock, $min_stock, $status, $image_url, 
            $description, $short_description, $sales_channels, $publish_status,
            $seo_title, $seo_description, $seo_keywords,
            $og_title, $og_description, $og_image,
            $google_product_category, $gtin, $mpn,
            $id, $tenant_id
        ]);
        
        log_activity('info', 'تعديل منتج', "تم تحديث بيانات المنتج \"{$name}\" (SKU: {$sku}).");
        
        echo json_encode(['success' => true, 'message' => 'تم تحديث بيانات المنتج بنجاح.']);
    } else {
        // Create new product
        // Check if SKU is unique for active tenant
        $skuCheck = $pdo->prepare("SELECT COUNT(*) FROM `ns_products` WHERE `sku` = ? AND `tenant_id` = ?");
        $skuCheck->execute([$sku, $tenant_id]);
        if ($skuCheck->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'رمز SKU هذا مستخدم بالفعل.']);
            exit;
        }
        
        $stmt = $pdo->prepare("INSERT INTO `ns_products` (
            `tenant_id`, `sku`, `name`, `category`, `price_usd`, `cost_usd`, 
            `stock`, `min_stock`, `status`, `image_url`, 
            `description`, `short_description`, `sales_channels`, `publish_status`,
            `seo_title`, `seo_description`, `seo_keywords`, 
            `og_title`, `og_description`, `og_image`, 
            `google_product_category`, `gtin`, `mpn`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $tenant_id, $sku, $name, $category, $price_usd, $cost_usd, 
            $stock, $min_stock, $status, $image_url, 
            $description, $short_description, $sales_channels, $publish_status,
            $seo_title, $seo_description, $seo_keywords,
            $og_title, $og_description, $og_image,
            $google_product_category, $gtin, $mpn
        ]);
        
        $newId = $pdo->lastInsertId();
        log_activity('success', 'إضافة منتج جديد', "تم إنشاء منتج جديد \"{$name}\" (SKU: {$sku}).");
        
        echo json_encode(['success' => true, 'message' => 'تم إنشاء المنتج بنجاح.', 'id' => $newId]);
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
    
    // Fetch product name before delete
    $stmt = $pdo->prepare("SELECT `name` FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
    $stmt->execute([$id, $tenant_id]);
    $name = $stmt->fetchColumn();
    
    if (!$name) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'المنتج غير موجود.']);
        exit;
    }
    
    // Delete product
    $stmt = $pdo->prepare("DELETE FROM `ns_products` WHERE `id` = ? AND `tenant_id` = ?");
    $stmt->execute([$id, $tenant_id]);
    
    log_activity('danger', 'حذف منتج', "تم حذف المنتج \"{$name}\" نهائياً من المستودع.");
    
    echo json_encode(['success' => true, 'message' => 'تم حذف المنتج بنجاح.']);
    exit;
}
?>
