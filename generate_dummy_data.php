<?php
/**
 * Generate Dummy Data for Stocksathi
 * This script populates the database with realistic test data for all modules
 * 
 * Usage: Access via browser or run via CLI: php generate_dummy_data.php
 */

require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/database.php';

$db = Database::getInstance();

// Check if data already exists (only check for products and invoices to avoid blocking if master data exists)
$existingProducts = $db->queryOne("SELECT COUNT(*) as count FROM products")['count'];
$existingInvoices = $db->queryOne("SELECT COUNT(*) as count FROM invoices")['count'];

// Check if running from browser or CLI
$isCLI = php_sapi_name() === 'cli';

// Output HTML header if running from browser
if (!$isCLI) {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Generating Dummy Data</title>';
    echo '<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}</style></head><body>';
    echo '<h1>🚀 Generating Dummy Data for Stocksathi</h1><pre style="background:#fff;padding:20px;border-radius:8px;">';
}

if ($existingProducts > 10 || $existingInvoices > 5) {
    $msg = "⚠️ Database already contains products or invoices.\n\n";
    $msg .= "Note: This script will use existing categories, brands, suppliers, and expense categories.\n";
    $msg .= "It will only create new products, customers, invoices, expenses, and stock movements if they don't exist.\n\n";
    $msg .= "Options:\n";
    $msg .= "1. Continue anyway - script will reuse existing master data (recommended)\n";
    $msg .= "2. Clear database and start fresh\n\n";
    $msg .= "Proceeding anyway... (Press Ctrl+C to cancel)\n\n";
    
    if ($isCLI) {
        echo $msg;
        sleep(2); // Give user time to cancel
    } else {
        echo $msg . '</pre>';
        echo '<div style="margin-top:20px;padding:20px;background:#fef3c7;border-radius:8px;border-left:4px solid #f59e0b;">';
        echo '<h3>⚠️ Notice</h3>';
        echo '<p>Database contains existing data. The script will continue and reuse existing categories, brands, suppliers, etc.</p>';
        echo '<p>If you want to start fresh, clear your database first.</p>';
        echo '</div>';
        echo '<pre style="background:#fff;padding:20px;border-radius:8px;margin-top:20px;">';
    }
}

echo "🚀 Starting dummy data generation...\n\n";

try {
    $db->beginTransaction();

    // === 1. CATEGORIES ===
    echo "📦 Creating categories...\n";
    $categories = ['Electronics', 'Clothing', 'Food & Beverages', 'Home & Kitchen', 'Books', 'Sports', 'Toys', 'Health & Beauty'];
    $categoryIds = [];
    foreach ($categories as $cat) {
        // Check if category already exists
        $existing = $db->queryOne("SELECT id FROM categories WHERE name = ?", [$cat]);
        if ($existing) {
            $categoryId = $existing['id'];
            echo "  ⊙ Category already exists: $cat (using existing)\n";
        } else {
            $categoryId = $db->execute("INSERT INTO categories (name, description, status) VALUES (?, ?, 'active')", [$cat, "Category for $cat items"]);
            echo "  ✓ Created category: $cat\n";
        }
        $categoryIds[] = $categoryId;
    }

    // === 2. BRANDS ===
    echo "\n🏷️ Creating brands...\n";
    $brands = ['Premium', 'Standard', 'Economy', 'Elite', 'Basic', 'Deluxe', 'Professional', 'Classic'];
    $brandIds = [];
    foreach ($brands as $brand) {
        // Check if brand already exists
        $existing = $db->queryOne("SELECT id FROM brands WHERE name = ?", [$brand]);
        if ($existing) {
            $brandId = $existing['id'];
            echo "  ⊙ Brand already exists: $brand (using existing)\n";
        } else {
            $brandId = $db->execute("INSERT INTO brands (name, description, status) VALUES (?, ?, 'active')", [$brand, "Brand: $brand"]);
            echo "  ✓ Created brand: $brand\n";
        }
        $brandIds[] = $brandId;
    }

    // === 3. SUPPLIERS ===
    echo "\n🏪 Creating suppliers...\n";
    $supplierData = [
        ['ABC Suppliers', 'abc@supplier.com', '9876543210', 'Mumbai'],
        ['XYZ Trading', 'xyz@trading.com', '9876543211', 'Delhi'],
        ['Global Imports', 'global@imports.com', '9876543212', 'Bangalore'],
        ['Quality Goods Co.', 'quality@goods.com', '9876543213', 'Pune'],
    ];
    $supplierIds = [];
    foreach ($supplierData as $supp) {
        // Check if supplier already exists
        $existing = $db->queryOne("SELECT id FROM suppliers WHERE name = ? OR email = ?", [$supp[0], $supp[1]]);
        if ($existing) {
            $supplierId = $existing['id'];
            echo "  ⊙ Supplier already exists: {$supp[0]} (using existing)\n";
        } else {
            $supplierId = $db->execute("INSERT INTO suppliers (name, email, phone, address, city, status) VALUES (?, ?, ?, ?, ?, 'active')", 
                [$supp[0], $supp[1], $supp[2], $supp[3] . ', India', $supp[3]]);
            echo "  ✓ Created supplier: {$supp[0]}\n";
        }
        $supplierIds[] = $supplierId;
    }

    // === 4. PRODUCTS ===
    echo "\n📱 Creating products...\n";
    $productNames = [
        'Wireless Mouse', 'USB Keyboard', 'Laptop Stand', 'Phone Case', 'Tablet Cover',
        'T-Shirt (Cotton)', 'Jeans (Blue)', 'Sneakers', 'Cap', 'Backpack',
        'Coffee Maker', 'Water Bottle', 'Lunch Box', 'Dinner Set', 'Cookware Set',
        'Fiction Book', 'Cookbook', 'Children Book', 'Novel', 'Magazine',
        'Football', 'Cricket Bat', 'Tennis Racket', 'Yoga Mat', 'Dumbbells',
        'Action Figure', 'Puzzle Game', 'Board Game', 'Building Blocks', 'Remote Car',
        'Face Cream', 'Shampoo', 'Soap', 'Toothpaste', 'Hand Sanitizer'
    ];
    $productIds = [];
    for ($i = 0; $i < 35; $i++) {
        $catIndex = $i % count($categoryIds);
        $brandIndex = $i % count($brandIds);
        $supplierIndex = $i % count($supplierIds);
        
        $purchasePrice = rand(100, 2000);
        $sellingPrice = $purchasePrice * (1.3 + (rand(0, 30) / 100)); // 30-60% markup
        $stockQty = rand(10, 500);
        $minStock = rand(5, 50);
        
        $sku = 'SKU-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT);
        
        // Get product name with fallback
        $productName = isset($productNames[$i]) ? $productNames[$i] : "Product " . ($i + 1);
        $productDescription = "High quality " . (isset($productNames[$i]) ? $productNames[$i] : 'product');
        
        // Check if product with this SKU already exists
        $existingProduct = $db->queryOne("SELECT id FROM products WHERE sku = ?", [$sku]);
        if ($existingProduct) {
            $productId = $existingProduct['id'];
            // Update stock if needed
            $db->execute("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?", 
                [rand(10, 50), $productId]);
            if (($i + 1) % 10 == 0) {
                echo "  ⊙ Product already exists: $productName (SKU: $sku)\n";
            }
        } else {
            // Products table doesn't have supplier_id column - insert without it
            $productId = $db->execute("INSERT INTO products 
                (name, sku, category_id, brand_id, purchase_price, selling_price, 
                 stock_quantity, min_stock_level, unit, status, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pcs', 'active', ?)", 
                [
                    $productName,
                    $sku,
                    $categoryIds[$catIndex],
                    $brandIds[$brandIndex],
                    $purchasePrice,
                    round($sellingPrice, 2),
                    $stockQty,
                    $minStock,
                    $productDescription
                ]);
            if (($i + 1) % 10 == 0) {
                echo "  ✓ Created " . ($i + 1) . " products...\n";
            }
        }
        $productIds[] = $productId;
        
    }
    echo "  ✓ Processed 35 products total\n";

    // === 5. CUSTOMERS ===
    echo "\n👥 Creating customers...\n";
    $customerNames = [
        'Rajesh Kumar', 'Priya Sharma', 'Amit Patel', 'Sneha Reddy', 'Vikram Singh',
        'Anjali Desai', 'Rohit Gupta', 'Kavita Iyer', 'Suresh Menon', 'Deepa Nair',
        'Manish Joshi', 'Sunita Rao', 'Nikhil Kapoor', 'Meera Chopra', 'Arjun Malhotra'
    ];
    $customerIds = [];
    for ($i = 0; $i < 15; $i++) {
        $email = strtolower(str_replace(' ', '', $customerNames[$i])) . '@email.com';
        $phone = '9' . str_pad($i, 9, rand(0, 9), STR_PAD_LEFT);
        
        // Check if customer already exists
        $existingCustomer = $db->queryOne("SELECT id FROM customers WHERE email = ? OR phone = ?", [$email, $phone]);
        if ($existingCustomer) {
            $customerId = $existingCustomer['id'];
            echo "  ⊙ Customer already exists: {$customerNames[$i]} (using existing)\n";
        } else {
            $customerId = $db->execute("INSERT INTO customers 
                (name, email, phone, address, city, state, pincode, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')", 
                [
                    $customerNames[$i],
                    $email,
                    $phone,
                    "Address " . ($i + 1) . ", Street " . ($i + 1),
                    ['Mumbai', 'Delhi', 'Bangalore', 'Pune', 'Chennai'][$i % 5],
                    'Maharashtra',
                    str_pad(rand(400000, 499999), 6, '0', STR_PAD_LEFT)
                ]);
            echo "  ✓ Created customer: {$customerNames[$i]}\n";
        }
        $customerIds[] = $customerId;
    }

    // === 6. EXPENSE CATEGORIES ===
    // Check if expense_categories table exists
    echo "\n💰 Creating expense categories...\n";
    $expenseCats = ['Rent', 'Utilities', 'Salaries', 'Marketing', 'Office Supplies', 'Maintenance', 'Transport', 'Miscellaneous'];
    $expenseCatIds = [];
    $hasExpenseCategoriesTable = false;
    
    // Check if expense_categories table exists
    try {
        $db->queryOne("SELECT 1 FROM expense_categories LIMIT 1");
        $hasExpenseCategoriesTable = true;
    } catch (Exception $e) {
        $hasExpenseCategoriesTable = false;
    }
    
    if ($hasExpenseCategoriesTable) {
        // Use expense_categories table
        foreach ($expenseCats as $ecat) {
            $existing = $db->queryOne("SELECT id FROM expense_categories WHERE name = ?", [$ecat]);
            if ($existing) {
                $catId = $existing['id'];
                echo "  ⊙ Expense category already exists: $ecat (using existing)\n";
            } else {
                // Try to insert - check if table has status column
                try {
                    $catId = $db->execute("INSERT INTO expense_categories (name, description) VALUES (?, ?)", 
                        [$ecat, "Expense category: $ecat"]);
                    echo "  ✓ Created expense category: $ecat\n";
                } catch (Exception $e) {
                    // Try without description
                    try {
                        $catId = $db->execute("INSERT INTO expense_categories (name) VALUES (?)", [$ecat]);
                        echo "  ✓ Created expense category: $ecat\n";
                    } catch (Exception $e2) {
                        error_log("Could not create expense category: " . $e2->getMessage());
                        continue;
                    }
                }
            }
            $expenseCatIds[] = $catId;
        }
    } else {
        // No expense_categories table - use category names directly
        echo "  ⊙ No expense_categories table - will use category names directly\n";
    }

    // === 7. INVOICES & SALES (Last 3 months + More in current month) ===
    echo "\n📄 Creating invoices and sales...\n";
    $invoiceIds = [];
    $today = date('Y-m-d');
    $currentMonthStart = date('Y-m-01');
    $currentDayOfMonth = (int)date('d');
    
    // Function to create a single invoice
    function createInvoice($db, $invoiceDate, $customerIds, $productIds, $invoiceNum) {
        $customerId = $customerIds[array_rand($customerIds)];
        $numItems = rand(1, 5);
        
        $subtotal = 0;
        $items = [];
        
        // Create invoice items
        for ($item = 0; $item < $numItems; $item++) {
            $productId = $productIds[array_rand($productIds)];
            $product = $db->queryOne("SELECT name, selling_price, stock_quantity FROM products WHERE id = ?", [$productId]);
            
            if (!$product || $product['stock_quantity'] < 1) {
                // Add stock if needed
                $db->execute("UPDATE products SET stock_quantity = stock_quantity + 50 WHERE id = ?", [$productId]);
                $product = $db->queryOne("SELECT name, selling_price, stock_quantity FROM products WHERE id = ?", [$productId]);
            }
            
            $qty = rand(1, min(10, $product['stock_quantity']));
            $unitPrice = $product['selling_price'];
            $lineTotal = $qty * $unitPrice;
            $subtotal += $lineTotal;
            
            $items[] = [
                'product_id' => $productId,
                'product_name' => $product['name'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal
            ];
        }
        
        if (count($items) == 0) return null;
        
        $discount = rand(0, 10) > 7 ? rand(50, 500) : 0; // 30% chance of discount
        $taxRate = 18; // GST
        $taxAmount = (($subtotal - $discount) * $taxRate) / 100;
        $totalAmount = $subtotal - $discount + $taxAmount;
        
        // More variety: 60% paid, 25% partial, 15% unpaid for current month
        // For today's invoices, make them more likely to be paid/partial
        $isTodayInvoice = ($invoiceDate == date('Y-m-d'));
        if ($isTodayInvoice) {
            $paymentStatus = ['paid', 'paid', 'paid', 'partial'][rand(0, 3)]; // 75% paid, 25% partial for today
        } else {
            $paymentStatus = ['paid', 'paid', 'paid', 'paid', 'unpaid', 'partial'][rand(0, 5)]; // 60% paid, 20% unpaid, 20% partial
        }
        $paidAmount = $paymentStatus === 'paid' ? $totalAmount : ($paymentStatus === 'partial' ? $totalAmount * 0.6 : 0);
        
        // Set invoice status based on payment
        $invoiceStatus = ($paymentStatus === 'paid') ? 'paid' : (($paymentStatus === 'partial') ? 'sent' : 'sent');
        
        $balanceAmount = $totalAmount - $paidAmount;
        // Create invoice with proper status
        $invoiceId = $db->execute("INSERT INTO invoices 
            (invoice_number, customer_id, invoice_date, due_date, subtotal, discount_amount, 
             tax_amount, total_amount, paid_amount, balance_amount, payment_status, status, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)", 
            [
                $invoiceNum,
                $customerId,
                $invoiceDate,
                date('Y-m-d', strtotime($invoiceDate . ' +30 days')),
                $subtotal,
                $discount,
                $taxAmount,
                $totalAmount,
                $paidAmount,
                $balanceAmount,
                $paymentStatus,
                $invoiceStatus
            ]);
        
        // Create invoice items and update stock
        foreach ($items as $item) {
            $db->execute("INSERT INTO invoice_items 
                (invoice_id, product_id, product_name, quantity, unit_price, line_total) 
                VALUES (?, ?, ?, ?, ?, ?)", 
                [$invoiceId, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit_price'], $item['line_total']]);
            
            // Update stock
            $db->execute("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?", 
                [$item['quantity'], $item['product_id']]);
        }
        
        return $invoiceId;
    }
    
    // Create 20 invoices in current month (for dashboard visibility)
    echo "  Creating current month invoices...\n";
    
    // Ensure at least 2 invoices are created for TODAY
    echo "    Creating today's invoices (at least 2)...\n";
    for ($i = 0; $i < 2; $i++) {
        $invoiceNumber = 'INV-' . date('Ymd') . '-TODAY-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
        $invoiceId = createInvoice($db, $today, $customerIds, $productIds, $invoiceNumber);
        if ($invoiceId) {
            $invoiceIds[] = $invoiceId;
        }
    }
    
    // Create remaining invoices spread throughout current month
    for ($i = 0; $i < 18; $i++) {
        // Spread throughout current month
        $dayOffset = rand(0, min($currentDayOfMonth - 1, 28));
        $invoiceDate = date('Y-m-d', strtotime($currentMonthStart . " +$dayOffset days"));
        
        if ($invoiceDate > $today) continue;
        
        $invoiceNumber = 'INV-' . date('Ymd', strtotime($invoiceDate)) . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT);
        $invoiceId = createInvoice($db, $invoiceDate, $customerIds, $productIds, $invoiceNumber);
        
        if ($invoiceId) {
            $invoiceIds[] = $invoiceId;
        }
    }
    
    // Create invoices for previous months (last 90 days)
    echo "  Creating previous months invoices...\n";
    $startDate = date('Y-m-d', strtotime('-90 days'));
    $invoiceCounter = count($invoiceIds) + 1;
    
    for ($day = 0; $day < 90; $day += rand(2, 4)) {
        $invoiceDate = date('Y-m-d', strtotime($startDate . " +$day days"));
        if ($invoiceDate >= $currentMonthStart) continue; // Skip current month (already done)
        if ($invoiceDate > $today) break;
        
        // 40% chance to create invoice for older dates
        if (rand(1, 10) > 4) continue;
        
        $invoiceNumber = 'INV-' . date('Ymd', strtotime($invoiceDate)) . '-' . str_pad($invoiceCounter++, 3, '0', STR_PAD_LEFT);
        $invoiceId = createInvoice($db, $invoiceDate, $customerIds, $productIds, $invoiceNumber);
        
        if ($invoiceId) {
            $invoiceIds[] = $invoiceId;
        }
        
        if (count($invoiceIds) % 25 == 0) {
            echo "  ✓ Created " . count($invoiceIds) . " invoices...\n";
        }
    }
    echo "  ✓ Created " . count($invoiceIds) . " invoices total\n";

    // === 8. EXPENSES (Last 2 months + Current month) ===
    echo "\n💸 Creating expenses...\n";
    $expenseStartDate = date('Y-m-d', strtotime('-60 days'));
    $expenseCount = 0;
    
    // Create 10 expenses in current month first
    echo "  Creating current month expenses...\n";
    
    // Ensure at least 1 expense is created for TODAY
    echo "    Creating today's expense...\n";
    $expenseNumber = 'EXP-' . date('Ymd') . '-001';
    // ... expense creation for today will be handled in loop
    
    for ($i = 0; $i < 10; $i++) {
        // First expense should be today, rest spread throughout month
        if ($i == 0) {
            $expenseDate = $today;
        } else {
            $dayOffset = rand(0, min($currentDayOfMonth - 1, 28));
            $expenseDate = date('Y-m-d', strtotime($currentMonthStart . " +$dayOffset days"));
        }
        
        if ($expenseDate > $today) continue;
        
        $categoryName = $expenseCats[array_rand($expenseCats)];
        $title = ['Office Rent', 'Electricity Bill', 'Internet Bill', 'Employee Salary', 'Marketing Campaign', 'Office Supplies', 'Maintenance'][rand(0, 6)];
        $vendors = ['ABC Suppliers', 'XYZ Trading', 'Global Imports', 'Quality Goods Co.', 'Local Vendor'];
        $vendor = $vendors[array_rand($vendors)];
        $amount = rand(500, 10000);
        $expenseNumber = 'EXP-' . date('Ymd', strtotime($expenseDate)) . '-' . str_pad($expenseCount + 1, 3, '0', STR_PAD_LEFT);
        
        try {
            $db->execute("INSERT INTO expenses 
                (expense_number, category, amount, expense_date, description, vendor, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, 'approved', 1)", 
                [
                    $expenseNumber,
                    $categoryName,
                    $amount,
                    $expenseDate,
                    $title . " - " . date('F Y', strtotime($expenseDate)),
                    $vendor
                ]);
            $expenseCount++;
        } catch (Exception $e) {
            $db->execute("INSERT INTO expenses 
                (expense_number, category, amount, expense_date, description, status, created_by) 
                VALUES (?, ?, ?, ?, ?, 'approved', 1)", 
                [$expenseNumber, $categoryName, $amount, $expenseDate, $title . " - " . date('F Y', strtotime($expenseDate))]);
            $expenseCount++;
        }
    }
    
    // Create expenses for previous months
    echo "  Creating previous months expenses...\n";
    for ($day = 0; $day < 60; $day += rand(2, 5)) {
        $expenseDate = date('Y-m-d', strtotime($expenseStartDate . " +$day days"));
        if ($expenseDate >= $currentMonthStart) continue; // Skip current month (already done)
        if ($expenseDate > $today) break;
        
        $categoryName = $expenseCats[array_rand($expenseCats)];
        $title = ['Office Rent', 'Electricity Bill', 'Internet Bill', 'Employee Salary', 'Marketing Campaign', 'Office Supplies', 'Maintenance'][rand(0, 6)];
        $vendors = ['ABC Suppliers', 'XYZ Trading', 'Global Imports', 'Quality Goods Co.', 'Local Vendor'];
        $vendor = $vendors[array_rand($vendors)];
        $amount = rand(500, 10000);
        $expenseNumber = 'EXP-' . date('Ymd', strtotime($expenseDate)) . '-' . str_pad($expenseCount + 1, 3, '0', STR_PAD_LEFT);
        
        try {
            $db->execute("INSERT INTO expenses 
                (expense_number, category, amount, expense_date, description, vendor, status, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, 'approved', 1)", 
                [$expenseNumber, $categoryName, $amount, $expenseDate, $title . " - " . date('F Y', strtotime($expenseDate)), $vendor]);
            $expenseCount++;
        } catch (Exception $e) {
            $db->execute("INSERT INTO expenses 
                (expense_number, category, amount, expense_date, description, status, created_by) 
                VALUES (?, ?, ?, ?, ?, 'approved', 1)", 
                [$expenseNumber, $categoryName, $amount, $expenseDate, $title . " - " . date('F Y', strtotime($expenseDate))]);
            $expenseCount++;
        }
        
        if ($expenseCount % 10 == 0) {
            echo "  ✓ Created $expenseCount expenses...\n";
        }
    }
    echo "  ✓ Created $expenseCount expenses\n";

    // === 9. STOCK IN (Receiving Stock) ===
    echo "\n📥 Creating stock-in records...\n";
    $stockInCount = 0;
    $warehouses = $db->query("SELECT id FROM warehouses LIMIT 1");
    $warehouseId = !empty($warehouses) ? $warehouses[0]['id'] : null;
    for ($i = 0; $i < 20; $i++) {
        $productId = $productIds[array_rand($productIds)];
        $product = $db->queryOne("SELECT purchase_price FROM products WHERE id = ?", [$productId]);
        $qty = rand(10, 100);
        $unitCost = (float) $product['purchase_price'];
        $totalCost = $qty * $unitCost;
        $stockDate = date('Y-m-d', strtotime('-' . rand(0, 60) . ' days'));
        $stockNumber = 'STK-IN-' . date('Ymd', strtotime($stockDate)) . '-' . str_pad($stockInCount + 1, 3, '0', STR_PAD_LEFT);
        
        try {
            $db->execute("INSERT INTO stock_in 
                (reference_no, product_id, warehouse_id, supplier_id, quantity, unit_cost, total_cost, notes, received_by, received_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, 'completed')", 
                [
                    $stockNumber,
                    $productId,
                    $warehouseId,
                    isset($supplierIds[0]) ? $supplierIds[array_rand($supplierIds)] : null,
                    $qty,
                    $unitCost,
                    $totalCost,
                    'Stock received from supplier',
                    $stockDate
                ]);
            $db->execute("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?", [$qty, $productId]);
            $stockInCount++;
        } catch (Exception $e) {
            error_log("Stock-in skip: " . $e->getMessage());
        }
    }
    echo "  ✓ Created $stockInCount stock-in records\n";

    // === 10. ACTIVITY LOGS ===
    echo "\n📝 Creating activity logs...\n";
    $activities = [
        ['action' => 'Product Created', 'module' => 'products'],
        ['action' => 'Invoice Created', 'module' => 'invoices'],
        ['action' => 'Stock Updated', 'module' => 'stock'],
        ['action' => 'Customer Added', 'module' => 'customers'],
        ['action' => 'Expense Recorded', 'module' => 'expenses'],
    ];
    
    for ($i = 0; $i < 50; $i++) {
        $activity = $activities[rand(0, count($activities) - 1)];
        $activityDate = date('Y-m-d H:i:s', strtotime('-' . rand(0, 90) . ' days ' . rand(0, 23) . ' hours'));
        
        $db->execute("INSERT INTO activity_logs (user_id, action, module, created_at) VALUES (?, ?, ?, ?)", 
            [1, $activity['action'], $activity['module'], $activityDate]);
    }
    echo "  ✓ Created 50 activity logs\n";

    $db->commit();
    
    $summary = "\n✅ Dummy data generation completed successfully!\n";
    $summary .= "📊 Summary:\n";
    $summary .= "   - Categories: " . count($categories) . "\n";
    $summary .= "   - Brands: " . count($brands) . "\n";
    $summary .= "   - Suppliers: " . count($supplierData) . "\n";
    $summary .= "   - Products: 35\n";
    $summary .= "   - Customers: " . count($customerNames) . "\n";
    $summary .= "   - Invoices: " . count($invoiceIds) . "\n";
    $summary .= "   - Expenses: $expenseCount\n";
    $summary .= "   - Stock Movements: $stockInCount\n";
    $summary .= "   - Activity Logs: 50\n";
    $summary .= "\n🎉 You can now test all modules with realistic data!\n";
    $summary .= "\n📝 Next Steps:\n";
    $summary .= "   1. Login as admin and view the dashboard\n";
    $summary .= "   2. Check all modules (Products, Invoices, Reports, etc.)\n";
    $summary .= "   3. Verify charts display properly\n";
    $summary .= "   4. Test CRUD operations\n\n";
    
    echo $summary;
    
    if (!$isCLI) {
        echo '</pre>';
        echo '<div style="margin-top:20px;padding:20px;background:#d1fae5;border-radius:8px;">';
        echo '<h2>✅ Data Generation Complete!</h2>';
        echo '<p><a href="index.php" style="display:inline-block;padding:10px 20px;background:#0f766e;color:white;text-decoration:none;border-radius:5px;">Go to Dashboard</a></p>';
        echo '<p><a href="pages/reports.php" style="display:inline-block;padding:10px 20px;background:#0d9488;color:white;text-decoration:none;border-radius:5px;margin-left:10px;">View Reports</a></p>';
        echo '</div></body></html>';
    }
    
} catch (Exception $e) {
    $db->rollback();
    $error = "\n❌ Error: " . $e->getMessage() . "\n";
    $error .= "Stack trace: " . $e->getTraceAsString() . "\n";
    $error .= "\nRolled back all changes.\n";
    
    if ($isCLI) {
        echo $error;
    } else {
        echo $error . '</pre>';
        echo '<div style="margin-top:20px;padding:20px;background:#fee2e2;border-radius:8px;color:#991b1b;">';
        echo '<h2>❌ Error Occurred</h2>';
        echo '<p>Please check the error message above and try again.</p>';
        echo '</div></body></html>';
    }
    exit(1);
}
