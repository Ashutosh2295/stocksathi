<?php
/**
 * Fix missing or corrupted tables (e.g. "Table doesn't exist in engine" - MySQL 1932)
 * Run once via: http://localhost/stocksathi/migrations/fix_missing_tables.php
 * Recreates: suppliers, invoices, invoice_items; creates organization_settings if missing.
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$dbname = 'stocksathi';
$username = 'root';
$password = '';

echo "<!DOCTYPE html><html><head><title>Fix Missing Tables - Stocksathi</title>";
echo "<style>body{font-family:system-ui,sans-serif;max-width:700px;margin:40px auto;padding:20px;} .ok{color:green;} .err{color:red;} pre{background:#f5f5f5;padding:10px;overflow:auto;}</style>";
echo "</head><body><h1>Fix missing/corrupt tables</h1>";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='ok'>Database connected.</p>";

    // Disable foreign key checks so we can drop tables that are referenced elsewhere
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    echo "<p class='ok'>Foreign key checks disabled for drops.</p>";

    // Drop in reverse FK order
    foreach (['invoice_items', 'invoices', 'suppliers'] as $t) {
        try {
            $pdo->exec("DROP TABLE IF EXISTS `$t`");
            echo "<p class='ok'>Dropped table (if existed): $t</p>";
        } catch (Exception $e) {
            echo "<p class='err'>Drop $t: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "<p class='ok'>Foreign key checks re-enabled.</p>";

    // Ensure customers exists (required for invoices FK)
    $customersExists = $pdo->query("SHOW TABLES LIKE 'customers'")->fetch();
    if (!$customersExists) {
        $pdo->exec("
            CREATE TABLE `customers` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL,
              `email` varchar(100) DEFAULT NULL,
              `phone` varchar(20) DEFAULT NULL,
              `company` varchar(100) DEFAULT NULL,
              `address` text DEFAULT NULL,
              `city` varchar(100) DEFAULT NULL,
              `state` varchar(100) DEFAULT NULL,
              `pincode` varchar(10) DEFAULT NULL,
              `gst_number` varchar(50) DEFAULT NULL,
              `credit_limit` decimal(10,2) DEFAULT 0.00,
              `outstanding_balance` decimal(10,2) DEFAULT 0.00,
              `status` enum('active','inactive','blocked') DEFAULT 'active',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `phone` (`phone`),
              KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='ok'>Created table: customers</p>";
    }

    // Create suppliers (app uses: name, email, phone, contact_person, address, city, state, status)
    // If "Tablespace exists" error (orphaned .ibd), create as suppliers_new then rename into place
    $suppliersSql = "
        CREATE TABLE `suppliers` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(100) NOT NULL,
          `email` varchar(100) DEFAULT NULL,
          `phone` varchar(20) DEFAULT NULL,
          `contact_person` varchar(100) DEFAULT NULL,
          `company` varchar(100) DEFAULT NULL,
          `address` text DEFAULT NULL,
          `city` varchar(100) DEFAULT NULL,
          `state` varchar(100) DEFAULT NULL,
          `pincode` varchar(10) DEFAULT NULL,
          `gst_number` varchar(50) DEFAULT NULL,
          `payment_terms` varchar(100) DEFAULT NULL,
          `status` enum('active','inactive','blocked') DEFAULT 'active',
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `phone` (`phone`),
          KEY `status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    try {
        $pdo->exec($suppliersSql);
        echo "<p class='ok'>Created table: suppliers</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Tablespace') !== false || strpos($e->getMessage(), '1813') !== false) {
            echo "<p class='ok'>Suppliers: working around orphaned tablespace...</p>";
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("CREATE TABLE `suppliers_new` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL,
              `email` varchar(100) DEFAULT NULL,
              `phone` varchar(20) DEFAULT NULL,
              `contact_person` varchar(100) DEFAULT NULL,
              `company` varchar(100) DEFAULT NULL,
              `address` text DEFAULT NULL,
              `city` varchar(100) DEFAULT NULL,
              `state` varchar(100) DEFAULT NULL,
              `pincode` varchar(10) DEFAULT NULL,
              `gst_number` varchar(50) DEFAULT NULL,
              `payment_terms` varchar(100) DEFAULT NULL,
              `status` enum('active','inactive','blocked') DEFAULT 'active',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `phone` (`phone`),
              KEY `status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            $renamed = false;
            try {
                $pdo->exec("RENAME TABLE `suppliers` TO `suppliers_broken`, `suppliers_new` TO `suppliers`");
                $pdo->exec("DROP TABLE IF EXISTS `suppliers_broken`");
                $renamed = true;
            } catch (Exception $e2) {
                try {
                    $pdo->exec("RENAME TABLE `suppliers_new` TO `suppliers`");
                    $renamed = true;
                } catch (Exception $e3) {
                    echo "<p class='err'>Orphaned tablespace: could not rename to suppliers. In phpMyAdmin or MySQL run: <code>DROP TABLE IF EXISTS suppliers; RENAME TABLE suppliers_new TO suppliers;</code> Then reload this page.</p>";
                }
            }
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            if ($renamed) {
                echo "<p class='ok'>Created table: suppliers (via rename workaround)</p>";
            }
        } else {
            throw $e;
        }
    }

    // Create invoices (payment_status as varchar to allow 'pending')
    $pdo->exec("
        CREATE TABLE `invoices` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `invoice_number` varchar(50) NOT NULL,
          `customer_id` int(11) DEFAULT NULL,
          `invoice_date` date NOT NULL,
          `due_date` date DEFAULT NULL,
          `subtotal` decimal(10,2) DEFAULT 0.00,
          `tax_amount` decimal(10,2) DEFAULT 0.00,
          `discount_amount` decimal(10,2) DEFAULT 0.00,
          `shipping_amount` decimal(10,2) DEFAULT 0.00,
          `total_amount` decimal(10,2) DEFAULT 0.00,
          `paid_amount` decimal(10,2) DEFAULT 0.00,
          `balance_amount` decimal(10,2) DEFAULT 0.00,
          `payment_status` varchar(50) DEFAULT 'unpaid',
          `payment_method` varchar(50) DEFAULT NULL,
          `notes` text DEFAULT NULL,
          `terms_conditions` text DEFAULT NULL,
          `created_by` int(11) DEFAULT NULL,
          `status` varchar(50) DEFAULT 'draft',
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `invoice_number` (`invoice_number`),
          KEY `customer_id` (`customer_id`),
          KEY `invoice_date` (`invoice_date`),
          KEY `payment_status` (`payment_status`),
          CONSTRAINT `invoices_customer_fk` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='ok'>Created table: invoices</p>";

    // Create invoice_items
    $pdo->exec("
        CREATE TABLE `invoice_items` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `invoice_id` int(11) NOT NULL,
          `product_id` int(11) NOT NULL,
          `product_name` varchar(200) NOT NULL,
          `quantity` int(11) NOT NULL,
          `unit_price` decimal(10,2) NOT NULL,
          `tax_rate` decimal(5,2) DEFAULT 0.00,
          `tax_amount` decimal(10,2) DEFAULT 0.00,
          `discount_rate` decimal(5,2) DEFAULT 0.00,
          `discount_amount` decimal(10,2) DEFAULT 0.00,
          `line_total` decimal(10,2) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `invoice_id` (`invoice_id`),
          KEY `product_id` (`product_id`),
          CONSTRAINT `invoice_items_invoice_fk` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
          CONSTRAINT `invoice_items_product_fk` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p class='ok'>Created table: invoice_items</p>";

    // organization_settings (used by Settings page) - create only if missing
    $orgSettingsExists = $pdo->query("SHOW TABLES LIKE 'organization_settings'")->fetch();
    if (!$orgSettingsExists) {
        $pdo->exec("
            CREATE TABLE `organization_settings` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `organization_id` int(11) NOT NULL,
              `setting_key` varchar(100) NOT NULL,
              `setting_value` text DEFAULT NULL,
              `setting_group` varchar(50) DEFAULT 'general',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_org_setting` (`organization_id`, `setting_key`),
              KEY `organization_id` (`organization_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='ok'>Created table: organization_settings</p>";
    }

    // =====================================================
    // HR MODULE TABLES (attendance, leave_requests) + deps
    // =====================================================

    // Ensure departments exists (employees FK)
    $departmentsExists = $pdo->query("SHOW TABLES LIKE 'departments'")->fetch();
    if (!$departmentsExists) {
        $pdo->exec("
            CREATE TABLE `departments` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL,
              `code` varchar(50) DEFAULT NULL,
              `description` text DEFAULT NULL,
              `manager_id` int(11) DEFAULT NULL,
              `status` enum('active','inactive') DEFAULT 'active',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `name` (`name`),
              UNIQUE KEY `code` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='ok'>Created table: departments</p>";
    }

    // Ensure employees exists (required for attendance/leave)
    $employeesExists = $pdo->query("SHOW TABLES LIKE 'employees'")->fetch();
    if (!$employeesExists) {
        $pdo->exec("
            CREATE TABLE `employees` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `employee_code` varchar(50) NOT NULL,
              `user_id` int(11) DEFAULT NULL,
              `first_name` varchar(50) NOT NULL,
              `last_name` varchar(50) NOT NULL,
              `email` varchar(100) NOT NULL,
              `phone` varchar(20) DEFAULT NULL,
              `department_id` int(11) DEFAULT NULL,
              `designation` varchar(100) DEFAULT NULL,
              `date_of_birth` date DEFAULT NULL,
              `date_of_joining` date DEFAULT NULL,
              `gender` enum('male','female','other') DEFAULT NULL,
              `address` text DEFAULT NULL,
              `city` varchar(100) DEFAULT NULL,
              `state` varchar(100) DEFAULT NULL,
              `pincode` varchar(10) DEFAULT NULL,
              `emergency_contact_name` varchar(100) DEFAULT NULL,
              `emergency_contact_phone` varchar(20) DEFAULT NULL,
              `salary` decimal(10,2) DEFAULT 0.00,
              `bank_name` varchar(100) DEFAULT NULL,
              `bank_account` varchar(50) DEFAULT NULL,
              `ifsc_code` varchar(20) DEFAULT NULL,
              `pan_number` varchar(20) DEFAULT NULL,
              `aadhar_number` varchar(20) DEFAULT NULL,
              `status` enum('active','on_leave','resigned','terminated') DEFAULT 'active',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `employee_code` (`employee_code`),
              UNIQUE KEY `email` (`email`),
              KEY `user_id` (`user_id`),
              KEY `department_id` (`department_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='ok'>Created table: employees</p>";
    }

    // Helper: create/repair a table if missing or corrupted (error 1932)
    $ensureHealthy = function(string $table, callable $createSql) use ($pdo) {
        $exists = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetch();
        if (!$exists) {
            $createSql();
            return;
        }
        try {
            $pdo->query("SELECT 1 FROM `$table` LIMIT 1")->fetch();
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (strpos($msg, 'doesn') !== false || strpos($msg, '1932') !== false || strpos($msg, 'doesn\'t exist in engine') !== false) {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                $createSql();
            } else {
                throw $e;
            }
        }
    };

    // attendance
    $ensureHealthy('attendance', function() use ($pdo) {
        $pdo->exec("
            CREATE TABLE `attendance` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) NOT NULL,
              `date` date NOT NULL,
              `check_in` time DEFAULT NULL,
              `check_out` time DEFAULT NULL,
              `total_hours` decimal(5,2) DEFAULT 0.00,
              `overtime_hours` decimal(5,2) DEFAULT 0.00,
              `status` enum('present','absent','half_day','on_leave','holiday') DEFAULT 'present',
              `notes` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `employee_date` (`employee_id`, `date`),
              KEY `date` (`date`),
              KEY `status` (`status`),
              CONSTRAINT `attendance_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='ok'>Ensured table healthy: attendance</p>";
    });

    // leave_requests
    $ensureHealthy('leave_requests', function() use ($pdo) {
        $pdo->exec("
            CREATE TABLE `leave_requests` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `employee_id` int(11) NOT NULL,
              `leave_type` enum('casual','sick','earned','maternity','paternity','unpaid') NOT NULL,
              `from_date` date NOT NULL,
              `to_date` date NOT NULL,
              `total_days` int(11) NOT NULL,
              `reason` text NOT NULL,
              `status` enum('pending','approved','rejected','cancelled') DEFAULT 'pending',
              `approved_by` int(11) DEFAULT NULL,
              `approval_date` date DEFAULT NULL,
              `rejection_reason` text DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `employee_id` (`employee_id`),
              KEY `from_date` (`from_date`),
              KEY `to_date` (`to_date`),
              KEY `status` (`status`),
              CONSTRAINT `leave_requests_employee_fk` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        echo "<p class='ok'>Ensured table healthy: leave_requests</p>";
    });

    $base = (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/migrations/') !== false)
        ? preg_replace('#/migrations/.*#', '', $_SERVER['REQUEST_URI']) : '/stocksathi';
    echo "<p class='ok'><strong>Done.</strong> <a href='" . htmlspecialchars($base) . "/pages/attendance.php'>Attendance</a> | <a href='" . htmlspecialchars($base) . "/pages/leave-management.php'>Leave</a> | <a href='" . htmlspecialchars($base) . "/pages/invoices.php'>Invoices</a> | <a href='" . htmlspecialchars($base) . "/pages/settings.php'>Settings</a></p>";
} catch (Exception $e) {
    echo "<p class='err'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
echo "</body></html>";
