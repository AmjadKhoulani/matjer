<?php
// config.php - Main configuration and database connection handler for Multi-Tenant SaaS
session_start();

// Enable CORS for frontend API requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-Tenant");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Database Credentials (Default MAMP Windows credentials)
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
} else {
    define('DB_HOST', 'localhost');
    define('DB_PORT', '3306');
    define('DB_NAME', 'nova_store');
    define('DB_USER', 'root');
    define('DB_PASS', 'root');
}

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If the database connection fails, send a JSON error response
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please ensure MySQL is running in your MAMP stack and the database is installed.',
        'details' => $e->getMessage()
    ]);
    exit;
}

// Helper to determine the active tenant ID
function get_active_tenant_id() {
    global $pdo;
    
    // 1. Check query param or post param or headers (highest priority manual overrides)
    $tenant_slug = '';
    if (isset($_GET['tenant'])) {
        $tenant_slug = trim($_GET['tenant']);
    } elseif (isset($_POST['tenant'])) {
        $tenant_slug = trim($_POST['tenant']);
    } else {
        // Check custom header X-Tenant
        $headers = getallheaders();
        if (isset($headers['X-Tenant'])) {
            $tenant_slug = trim($headers['X-Tenant']);
        } elseif (isset($_SERVER['HTTP_X_TENANT'])) {
            $tenant_slug = trim($_SERVER['HTTP_X_TENANT']);
        }
    }
    
    if (!empty($tenant_slug)) {
        try {
            $stmt = $pdo->prepare("SELECT `id` FROM `ns_tenants` WHERE `slug` = ? AND `status` != 'Suspended'");
            $stmt->execute([$tenant_slug]);
            $id = $stmt->fetchColumn();
            if ($id) {
                $_SESSION['tenant_id'] = $id;
                return $id;
            }
        } catch (Exception $e) {
            // Ignore DB errors during bootstrap
        }
    }
    
    // 2. Resolve by Domain or Subdomain
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = strtolower(trim($_SERVER['HTTP_HOST']));
        $host = explode(':', $host)[0]; // strip port if any
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }
        
        $exclude_domains = ['matjer.net', 'www.matjer.net', 'localhost', '127.0.0.1'];
        if (!in_array($host, $exclude_domains)) {
            try {
                // A. Check if it's a subdomain of matjer.net (e.g. *.matjer.net)
                if (substr($host, -11) === '.matjer.net') {
                    $subdomain = substr($host, 0, -11);
                    if (!empty($subdomain) && $subdomain !== 'www' && $subdomain !== 'admin') {
                        $stmt = $pdo->prepare("SELECT `id` FROM `ns_tenants` WHERE `slug` = ? AND `status` != 'Suspended'");
                        $stmt->execute([$subdomain]);
                        $id = $stmt->fetchColumn();
                        if ($id) {
                            $_SESSION['tenant_id'] = $id;
                            return $id;
                        }
                    }
                }
                
                // B. Check if it's a custom domain mapped to a tenant
                $stmt = $pdo->prepare("SELECT `id` FROM `ns_tenants` WHERE `custom_domain` = ? AND `status` != 'Suspended'");
                $stmt->execute([$host]);
                $id = $stmt->fetchColumn();
                if ($id) {
                    $_SESSION['tenant_id'] = $id;
                    return $id;
                }
            } catch (Exception $e) {
                // Ignore DB errors
            }
        }
    }
    
    // 3. Check session (only if not overridden by explicit query/domain)
    if (isset($_SESSION['tenant_id'])) {
        return $_SESSION['tenant_id'];
    }
    
    // 4. Check if user is logged in, and retrieve their tenant_id
    if (isset($_SESSION['user_id'])) {
        try {
            $stmt = $pdo->prepare("SELECT `tenant_id` FROM `ns_users` WHERE `id` = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $id = $stmt->fetchColumn();
            if ($id) {
                $_SESSION['tenant_id'] = $id;
                return $id;
            }
        } catch (Exception $e) {
            // Ignore
        }
    }
    
    // 5. Fallback: Find the first active tenant in the database (only if we are NOT on root domain)
    if (isset($_SERVER['HTTP_HOST'])) {
        $host = strtolower(trim($_SERVER['HTTP_HOST']));
        $host = explode(':', $host)[0];
        if (strpos($host, 'www.') === 0) $host = substr($host, 4);
        if ($host === 'matjer.net' || $host === 'www.matjer.net') {
            return null; // Root domain does not fall back to a random tenant
        }
    }
    
    try {
        $id = $pdo->query("SELECT `id` FROM `ns_tenants` WHERE `status` != 'Suspended' ORDER BY `id` ASC LIMIT 1")->fetchColumn();
        if ($id) {
            $_SESSION['tenant_id'] = $id;
            return $id;
        }
    } catch (Exception $e) {
        // Ignore
    }
    
    return null;
}

// Helper to get active tenant details
function get_active_tenant_details() {
    global $pdo;
    $tenant_id = get_active_tenant_id();
    if ($tenant_id) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `ns_tenants` WHERE `id` = ?");
            $stmt->execute([$tenant_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
    return null;
}

// Helper to get configuration value from database for active tenant
function get_system_setting($key, $default = '') {
    global $pdo;
    $tenant_id = get_active_tenant_id();
    if (!$tenant_id) return $default;
    try {
        $stmt = $pdo->prepare("SELECT `value` FROM `ns_settings` WHERE `tenant_id` = ? AND `key` = ?");
        $stmt->execute([$tenant_id, $key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Helper to log activities to database for active tenant
function log_activity($type, $title, $desc) {
    global $pdo;
    $tenant_id = get_active_tenant_id();
    if (!$tenant_id) return;
    try {
        $stmt = $pdo->prepare("INSERT INTO `ns_activities` (`tenant_id`, `type`, `title`, `desc`) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tenant_id, $type, $title, $desc]);
    } catch (Exception $e) {
        // Silently ignore log write failures
    }
}
?>
