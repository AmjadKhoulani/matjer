-- Nova Store ERP & eCommerce Multi-Tenant SaaS Database Schema

-- 1. Tenants Table (إدارة المتاجر المشتركة في المنصة)
CREATE TABLE IF NOT EXISTS `ns_tenants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(50) UNIQUE NOT NULL,
  `owner_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `plan` ENUM('Starter', 'Pro', 'Enterprise') NOT NULL DEFAULT 'Pro',
  `status` ENUM('Active', 'Trial', 'Suspended') NOT NULL DEFAULT 'Active',
  `logo_url` TEXT DEFAULT NULL,
  `theme_color` VARCHAR(20) DEFAULT '#4f46e5',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users & RBAC (المستخدمين والموظفين)
CREATE TABLE IF NOT EXISTS `ns_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT DEFAULT NULL, -- NULL means SaaS Super Admin
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `fullname` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'warehouse', 'sales') NOT NULL DEFAULT 'sales',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Currencies (العملات لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_currencies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `name` VARCHAR(50) NOT NULL,
  `symbol` VARCHAR(10) NOT NULL,
  `rate` DECIMAL(15, 4) NOT NULL, -- Rate relative to USD (USD = 1.00)
  `update_mode` ENUM('manual', 'auto') NOT NULL DEFAULT 'auto',
  `last_updated` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_tenant_currency` (`tenant_id`, `code`),
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Settings Table (إعدادات المتاجر الفردية)
CREATE TABLE IF NOT EXISTS `ns_settings` (
  `tenant_id` INT NOT NULL,
  `key` VARCHAR(50) NOT NULL,
  `value` TEXT NOT NULL,
  PRIMARY KEY (`tenant_id`, `key`),
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Products Table (المنتجات لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `sku` VARCHAR(50) NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `price_usd` DECIMAL(10, 2) NOT NULL,
  `cost_usd` DECIMAL(10, 2) NOT NULL,
  `stock` INT NOT NULL DEFAULT 0,
  `min_stock` INT NOT NULL DEFAULT 5,
  `status` VARCHAR(50) NOT NULL DEFAULT 'In Stock',
  `image_url` TEXT DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `short_description` TEXT DEFAULT NULL,
  `sales_channels` VARCHAR(255) NOT NULL DEFAULT 'ecommerce,pos',
  `publish_status` ENUM('Publish', 'Draft') NOT NULL DEFAULT 'Publish',
  `seo_title` VARCHAR(150) DEFAULT NULL,
  `seo_description` TEXT DEFAULT NULL,
  `seo_keywords` VARCHAR(255) DEFAULT NULL,
  `og_title` VARCHAR(150) DEFAULT NULL,
  `og_description` TEXT DEFAULT NULL,
  `og_image` TEXT DEFAULT NULL,
  `google_product_category` VARCHAR(150) DEFAULT NULL,
  `gtin` VARCHAR(50) DEFAULT NULL,
  `mpn` VARCHAR(50) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_tenant_sku` (`tenant_id`, `sku`),
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Customers Table (العملاء للـ CRM لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `loyalty_points` INT NOT NULL DEFAULT 0,
  `tier` ENUM('standard', 'vip', 'loyal', 'lead') NOT NULL DEFAULT 'standard',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_tenant_cust_email` (`tenant_id`, `email`),
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Suppliers Table (الموردين لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_suppliers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `contact_name` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `products` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Warehouses Table (المستودعات الجغرافية لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_warehouses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `contact` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `capacity` VARCHAR(20) DEFAULT '0%',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Orders Table (فواتير المبيعات لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `customer_id` INT DEFAULT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('Pending', 'Shipped', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `total_usd` DECIMAL(10, 2) NOT NULL,
  `exchange_rate` DECIMAL(15, 4) NOT NULL, -- exchange rate at order time
  `coupon_code` VARCHAR(20) DEFAULT NULL,
  `discount_usd` DECIMAL(10, 2) DEFAULT 0.00,
  `source` ENUM('ecommerce', 'pos', 'invoice') NOT NULL DEFAULT 'ecommerce',
  `payment_status` ENUM('Paid', 'Unpaid', 'Partially Paid') NOT NULL DEFAULT 'Paid',
  `payment_method` VARCHAR(50) DEFAULT 'Cash',
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `ns_customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Order Items Table (تفاصيل أصناف فواتير المبيعات)
CREATE TABLE IF NOT EXISTS `ns_order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price_usd` DECIMAL(10, 2) NOT NULL,
  `size` VARCHAR(20) DEFAULT NULL,
  `color` VARCHAR(30) DEFAULT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `ns_orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `ns_products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Purchase Invoices (فواتير المشتريات والتوريد لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_purchase_invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `supplier_id` INT DEFAULT NULL,
  `supplier_name` VARCHAR(150) NOT NULL,
  `warehouse_id` INT DEFAULT NULL,
  `warehouse_name` VARCHAR(150) NOT NULL,
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `total_usd` DECIMAL(10, 2) NOT NULL,
  `exchange_rate` DECIMAL(15, 4) NOT NULL,
  `status` ENUM('Paid', 'Pending') NOT NULL DEFAULT 'Paid',
  `note` TEXT DEFAULT NULL,
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`supplier_id`) REFERENCES `ns_suppliers`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`warehouse_id`) REFERENCES `ns_warehouses`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Purchase Invoice Items (تفاصيل أصناف فواتير المشتريات)
CREATE TABLE IF NOT EXISTS `ns_purchase_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `purchase_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `product_name` VARCHAR(150) NOT NULL,
  `sku` VARCHAR(50) NOT NULL,
  `quantity` INT NOT NULL,
  `cost_usd` DECIMAL(10, 2) NOT NULL,
  `discount_usd` DECIMAL(10, 2) DEFAULT 0.00,
  `tax_percent` DECIMAL(5, 2) DEFAULT 15.00,
  FOREIGN KEY (`purchase_id`) REFERENCES `ns_purchase_invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `ns_products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Quotations (عروض الأسعار لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_quotations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `customer_id` INT DEFAULT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `valid_until` DATE DEFAULT NULL,
  `status` ENUM('Draft', 'Sent', 'Accepted', 'Rejected', 'Converted') NOT NULL DEFAULT 'Draft',
  `total_usd` DECIMAL(10, 2) NOT NULL,
  `exchange_rate` DECIMAL(15, 4) NOT NULL,
  `discount_usd` DECIMAL(10, 2) DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `ns_customers`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Quotation Items (تفاصيل أصناف عروض الأسعار)
CREATE TABLE IF NOT EXISTS `ns_quotation_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `quotation_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price_usd` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`quotation_id`) REFERENCES `ns_quotations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `ns_products`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Product Reviews (مراجعات المنتجات لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `customer_name` VARCHAR(150) NOT NULL,
  `product_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` >= 1 AND `rating` <= 5),
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `text` TEXT DEFAULT NULL,
  `status` ENUM('Pending', 'Approved', 'Spam') NOT NULL DEFAULT 'Pending',
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `ns_products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. CRM Notes (ملاحظات العملاء لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_crm_notes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `customer_id` INT NOT NULL,
  `date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `text` TEXT NOT NULL,
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`customer_id`) REFERENCES `ns_customers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Activities Logs (سجل العمليات والأنشطة لكل متجر)
CREATE TABLE IF NOT EXISTS `ns_activities` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tenant_id` INT NOT NULL,
  `type` VARCHAR(30) NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `desc` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`tenant_id`) REFERENCES `ns_tenants`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
