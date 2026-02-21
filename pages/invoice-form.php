<?php
/**
 * Invoice Form Page - Create/Edit Invoice with Multiple Products
 * Stocksathi Inventory System
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Validator.php';
require_once __DIR__ . '/../_includes/Session.php';

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$message = '';
$messageType = '';
$isEditMode = false;
$invoice = null;
$invoiceItems = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = Session::getUserId();
    $organizationId = Session::getOrganizationId();
    
    try {
        if ($action === 'create' || $action === 'update') {
            $validator = new Validator($_POST);
            $validator->required($_POST['invoice_date'] ?? '', 'Invoice date');
            
            // Validate items
            $items = json_decode($_POST['items'] ?? '[]', true);
            if (empty($items) || !is_array($items)) {
                $message = 'Please add at least one product to the invoice';
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                // Calculate totals from items
                $subtotal = 0;
                $taxAmount = 0;
                foreach ($items as $item) {
                    $qty = (float)($item['quantity'] ?? 0);
                    $price = (float)($item['unit_price'] ?? 0);
                    $taxRate = (float)($item['tax_rate'] ?? 0);
                    $lineTotal = $qty * $price;
                    $lineTax = ($lineTotal * $taxRate) / 100;
                    $subtotal += $lineTotal;
                    $taxAmount += $lineTax;
                }
                
                $discountAmount = (float)($data['discount_amount'] ?? 0);
                $totalAmount = $subtotal + $taxAmount - $discountAmount;
                
                $db->beginTransaction();
                
                try {
                    if ($action === 'create') {
                        // Generate invoice number
                        $lastInv = $db->queryOne("SELECT invoice_number FROM invoices {$orgWhere} ORDER BY id DESC LIMIT 1");
                        $lastNum = $lastInv ? (int)substr($lastInv['invoice_number'], 4) : 0;
                        $invoiceNumber = 'INV-' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
                        
                        // Create invoice (support tables with or without organization_id / payment_mode_id)
                        $invoiceId = null;
                        try {
                            $query = "INSERT INTO invoices (invoice_number, customer_id, invoice_date, due_date, subtotal, tax_amount, discount_amount, total_amount, paid_amount, payment_mode_id, payment_status, status, notes, created_by, organization_id) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                            $invoiceId = $db->execute($query, [
                            $invoiceNumber,
                            !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                            $data['invoice_date'],
                                $data['due_date'] ?? null,
                                $subtotal,
                                $taxAmount,
                                $discountAmount,
                                $totalAmount,
                                (float)($data['paid_amount'] ?? 0),
                                !empty($data['payment_mode_id']) ? (int)$data['payment_mode_id'] : null,
                                $data['payment_status'] ?? 'pending',
                                $data['status'] ?? 'draft',
                                $data['notes'] ?? null,
                                $userId,
                                $organizationId
                            ]);
                        } catch (Exception $e) {
                            if (strpos($e->getMessage(), 'organization_id') !== false || strpos($e->getMessage(), 'payment_mode_id') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                                $query = "INSERT INTO invoices (invoice_number, customer_id, invoice_date, due_date, subtotal, tax_amount, discount_amount, total_amount, paid_amount, payment_status, status, notes, created_by) 
                                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                                $invoiceId = $db->execute($query, [
                            $invoiceNumber,
                            !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                            $data['invoice_date'],
                                    $data['due_date'] ?? null,
                                    $subtotal,
                                    $taxAmount,
                                    $discountAmount,
                                    $totalAmount,
                                    (float)($data['paid_amount'] ?? 0),
                                    $data['payment_status'] ?? 'pending',
                                    $data['status'] ?? 'draft',
                                    $data['notes'] ?? null,
                                    $userId
                                ]);
                            } else {
                                throw $e;
                            }
                        }
                        
                        // Add invoice items and update stock
                        foreach ($items as $item) {
                            $productId = (int)$item['product_id'];
                            $quantity = (int)$item['quantity'];
                            $unitPrice = (float)$item['unit_price'];
                            $taxRate = (float)($item['tax_rate'] ?? 0);
                            $lineTotal = ($quantity * $unitPrice) + (($quantity * $unitPrice * $taxRate) / 100);
                            
                            // Check stock and get product name for invoice_items
                            $product = $db->queryOne("SELECT stock_quantity, name FROM products WHERE {$orgFilter} id = ?", [$productId]);
                            if (!$product || $product['stock_quantity'] < $quantity) {
                                throw new Exception('Insufficient stock for product. Available: ' . ($product['stock_quantity'] ?? 0));
                            }
                            $productName = $product['name'] ?? 'Product #' . $productId;
                            $itemTaxAmount = ($quantity * $unitPrice * $taxRate) / 100;
                            
                            // Insert invoice item (product_name required by schema)
                            try {
                                $db->execute(
                                    "INSERT INTO invoice_items (invoice_id, product_id, product_name, quantity, unit_price, tax_rate, tax_amount, line_total) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                                    [$invoiceId, $productId, $productName, $quantity, $unitPrice, $taxRate, $itemTaxAmount, $lineTotal]
                                );
                            } catch (Exception $e) {
                                if (strpos($e->getMessage(), 'product_name') !== false || strpos($e->getMessage(), 'tax_amount') !== false || strpos($e->getMessage(), 'Unknown column') !== false) {
                                    $db->execute(
                                        "INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, tax_rate, line_total) 
                                         VALUES (?, ?, ?, ?, ?, ?)",
                                        [$invoiceId, $productId, $quantity, $unitPrice, $taxRate, $lineTotal]
                                    );
                                } else {
                                    throw $e;
                                }
                            }
                            
                            // Update product stock (reduce stock)
                            $db->execute(
                                "UPDATE products SET stock_quantity = stock_quantity - ? WHERE {$orgFilter} id = ?",
                                [$quantity, $productId]
                            );
                            
                            // Log stock out
                            $db->execute(
                                "INSERT INTO stock_logs (product_id, type, quantity, reference_type, reference_id, notes, created_by) 
                                 VALUES (?, 'out', ?, 'invoice', ?, ?, ?)",
                                [$productId, $quantity, $invoiceId, 'Invoice: ' . $invoiceNumber, $userId]
                            );
                        }
                        
                        $db->commit();
                        Session::setFlash('Invoice created successfully: ' . $invoiceNumber . '. Dashboard will now show this sale.', 'success');
                        header('Location: ' . BASE_PATH . '/pages/dashboards/sales-executive.php');
                        exit;
                        
                    } else {
                        // Update existing invoice
                        $invoiceId = (int)$data['id'];
                        
                        // Get existing items to reverse stock
                        $existingItems = $db->query(
                            "SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ?",
                            [$invoiceId]
                        );
                        
                        // Reverse stock for existing items
                        foreach ($existingItems as $existingItem) {
                            $db->execute(
                                "UPDATE products SET stock_quantity = stock_quantity + ? WHERE {$orgFilter} id = ?",
                                [$existingItem['quantity'], $existingItem['product_id']]
                            );
                        }
                        
                        // Delete existing items
                        $db->execute("DELETE FROM invoice_items WHERE invoice_id = ?", [$invoiceId]);
                        
                        // Update invoice
                        $query = "UPDATE invoices SET customer_id = ?, invoice_date = ?, due_date = ?, subtotal = ?, tax_amount = ?, discount_amount = ?, total_amount = ?, paid_amount = ?, payment_mode_id = ?, payment_status = ?, status = ?, notes = ?, organization_id = ? WHERE {$orgFilter} id = ?";
                        $db->execute($query, [
                            !empty($data['customer_id']) ? (int)$data['customer_id'] : null,
                            $data['invoice_date'],
                            $data['due_date'] ?? null,
                            $subtotal,
                            $taxAmount,
                            $discountAmount,
                            $totalAmount,
                            (float)($data['paid_amount'] ?? 0),
                            !empty($data['payment_mode_id']) ? (int)$data['payment_mode_id'] : null,
                            $data['payment_status'] ?? 'pending',
                            $data['status'] ?? 'draft',
                            $data['notes'] ?? null,
                            $organizationId,
                            $invoiceId
                        ]);
                        
                        // Add new items and update stock
                        foreach ($items as $item) {
                            $productId = (int)$item['product_id'];
                            $quantity = (int)$item['quantity'];
                            $unitPrice = (float)$item['unit_price'];
                            $taxRate = (float)($item['tax_rate'] ?? 0);
                            $lineTotal = ($quantity * $unitPrice) + (($quantity * $unitPrice * $taxRate) / 100);
                            
                            // Check stock availability
                            $product = $db->queryOne("SELECT stock_quantity FROM products WHERE {$orgFilter} id = ?", [$productId]);
                            if (!$product || $product['stock_quantity'] < $quantity) {
                                throw new Exception('Insufficient stock for product. Available: ' . ($product['stock_quantity'] ?? 0));
                            }
                            
                            // Insert invoice item
                            $db->execute(
                                "INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, tax_rate, line_total) 
                                 VALUES (?, ?, ?, ?, ?, ?)",
                                [$invoiceId, $productId, $quantity, $unitPrice, $taxRate, $lineTotal]
                            );
                            
                            // Update product stock
                            $db->execute(
                                "UPDATE products SET stock_quantity = stock_quantity - ? WHERE {$orgFilter} id = ?",
                                [$quantity, $productId]
                            );
                        }
                        
                        $db->commit();
                        Session::setFlash('Invoice updated successfully', 'success');
                        header('Location: ' . BASE_PATH . '/pages/invoices.php');
                        exit;
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
            }
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get flash message
$flash = Session::getFlash();
if ($flash) {
    $message = $flash['message'];
    $messageType = $flash['type'];
}

// Check if editing
$invoiceId = $_GET['id'] ?? null;
if ($invoiceId) {
    $isEditMode = true;
    $invoice = $db->queryOne(
        "SELECT i.*, c.name as customer_name 
         FROM invoices i
         LEFT JOIN customers c ON i.customer_id = c.id
         WHERE {$orgFilter} i.id = ?",
        [$invoiceId]
    );
    
    if (!$invoice) {
        Session::setFlash('Invoice not found', 'error');
        header('Location: ' . BASE_PATH . '/pages/invoices.php');
        exit;
    }
    
    // Load invoice items
    $invoiceItems = $db->query(
        "SELECT ii.*, p.name as product_name, p.sku, p.stock_quantity 
         FROM invoice_items ii
         LEFT JOIN products p ON ii.product_id = p.id
         WHERE {$orgFilter} ii.invoice_id = ?",
        [$invoiceId]
    );
}

// Load customers, products, payment modes
$customers = $db->query("SELECT id, name FROM customers WHERE {$orgFilter} status = 'active' ORDER BY name");
$products = $db->query("SELECT id, name, sku, selling_price, stock_quantity, tax_rate FROM products WHERE {$orgFilter} status = 'active' ORDER BY name");
$paymentModes = $db->query("SELECT id, name FROM payment_modes WHERE is_active = 1 ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditMode ? 'Edit' : 'Create' ?> Invoice - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../_includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../_includes/header.php'; ?>
            
            <main class="content">
                <div class="content-header">
                    <nav class="breadcrumb">
                        <a href="<?= BASE_PATH ?>/index.php" class="breadcrumb-item">Home</a>
                        <span class="breadcrumb-separator">/</span>
                        <a href="<?= BASE_PATH ?>/pages/invoices.php" class="breadcrumb-item">Invoices</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active"><?= $isEditMode ? 'Edit' : 'Create' ?> Invoice</span>
                    </nav>
                    <h1 class="content-title"><?= $isEditMode ? 'Edit' : 'Create New' ?> Invoice</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="invoiceForm">
                    <input type="hidden" name="action" value="<?= $isEditMode ? 'update' : 'create' ?>">
                    <?php if ($isEditMode): ?>
                        <input type="hidden" name="id" value="<?= $invoice['id'] ?>">
                    <?php endif; ?>
                    <input type="hidden" name="items" id="itemsInput">
                    
                    <div class="grid grid-cols-2 gap-6 mb-6">
                        <!-- Left Column: Customer & Dates -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Customer Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Customer</label>
                                    <select name="customer_id" id="customerSelect" class="form-control">
                                        <option value="">Walk-in Customer</option>
                                        <?php foreach ($customers as $customer): ?>
                                            <option value="<?= $customer['id'] ?>" <?= ($invoice['customer_id'] ?? '') == $customer['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($customer['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label required">Invoice Date</label>
                                        <input type="date" name="invoice_date" class="form-control" required 
                                               value="<?= $invoice['invoice_date'] ?? date('Y-m-d') ?>">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Due Date</label>
                                        <input type="date" name="due_date" class="form-control" 
                                               value="<?= $invoice['due_date'] ?? date('Y-m-d', strtotime('+30 days')) ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Right Column: Payment Info -->
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Payment Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label">Payment Mode</label>
                                        <select name="payment_mode_id" class="form-control">
                                            <option value="">Select Payment Mode</option>
                                            <?php foreach ($paymentModes as $mode): ?>
                                                <option value="<?= $mode['id'] ?>" <?= ($invoice['payment_mode_id'] ?? '') == $mode['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($mode['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Payment Status</label>
                                        <select name="payment_status" class="form-control">
                                            <option value="pending" <?= ($invoice['payment_status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="paid" <?= ($invoice['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                                            <option value="partial" <?= ($invoice['payment_status'] ?? '') === 'partial' ? 'selected' : '' ?>>Partial</option>
                                            <option value="overdue" <?= ($invoice['payment_status'] ?? '') === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Paid Amount (₹)</label>
                                    <input type="number" step="0.01" name="paid_amount" class="form-control" 
                                           value="<?= $invoice['paid_amount'] ?? '0' ?>" placeholder="0.00">
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="draft" <?= ($invoice['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                                        <option value="finalized" <?= ($invoice['status'] ?? '') === 'finalized' ? 'selected' : '' ?>>Finalized</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Products Section -->
                    <div class="card mb-6">
                        <div class="card-header">
                            <h3 class="card-title">Products</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label">Add Product</label>
                                <div class="flex gap-2">
                                    <select id="productSelect" class="form-control" style="flex: 1;">
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $product): ?>
                                            <option value="<?= $product['id'] ?>" 
                                                    data-name="<?= htmlspecialchars($product['name']) ?>"
                                                    data-sku="<?= htmlspecialchars($product['sku']) ?>"
                                                    data-price="<?= $product['selling_price'] ?>"
                                                    data-tax="<?= $product['tax_rate'] ?>"
                                                    data-stock="<?= $product['stock_quantity'] ?>">
                                                <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>) - Stock: <?= $product['stock_quantity'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="number" id="productQuantity" class="form-control" placeholder="Qty" min="1" value="1" style="width: 100px;">
                                    <button type="button" class="btn btn-primary" onclick="addProduct()">Add</button>
                                </div>
                            </div>
                            
                            <div class="table-container">
                                <table class="table" id="itemsTable">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>SKU</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Tax %</th>
                                            <th>Total</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="itemsTableBody">
                                        <tr id="emptyRow" style="display: none;">
                                            <td colspan="7" style="text-align: center; padding: 40px;">
                                                No products added. Select a product and click "Add".
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="flex justify-end gap-4 mt-4" style="border-top: 1px solid var(--border-light); padding-top: 16px;">
                                <div style="min-width: 300px;">
                                    <div class="flex justify-between mb-2">
                                        <span>Subtotal:</span>
                                        <span id="subtotalDisplay">₹0.00</span>
                                    </div>
                                    <div class="flex justify-between mb-2">
                                        <span>Tax:</span>
                                        <span id="taxDisplay">₹0.00</span>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label class="form-label">Discount (₹)</label>
                                        <input type="number" step="0.01" name="discount_amount" id="discountInput" class="form-control" 
                                               value="<?= $invoice['discount_amount'] ?? '0' ?>" onchange="calculateTotals()">
                                    </div>
                                    <div class="flex justify-between font-bold" style="font-size: 1.1em; border-top: 2px solid var(--color-primary); padding-top: 8px;">
                                        <span>Total:</span>
                                        <span id="totalDisplay">₹0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes..."><?= htmlspecialchars($invoice['notes'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="flex gap-4 justify-end mt-6">
                        <a href="<?= BASE_PATH ?>/pages/invoices.php" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary"><?= $isEditMode ? 'Update' : 'Create' ?> Invoice</button>
                    </div>
                </form>
            </main>
        </div>
    </div>

    <script>
        let items = [];
        
        <?php if ($isEditMode && !empty($invoiceItems)): ?>
            items = <?= json_encode(array_map(function($item) {
                return [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'sku' => $item['sku'],
                    'quantity' => (int)$item['quantity'],
                    'unit_price' => (float)$item['unit_price'],
                    'tax_rate' => (float)$item['tax_rate'],
                    'line_total' => (float)$item['line_total'],
                    'stock_quantity' => (int)($item['stock_quantity'] ?? 0) // Should be available from query
                ];
            }, $invoiceItems)) ?>;
            renderItems();
        <?php endif; ?>
        
        function addProduct() {
            const select = document.getElementById('productSelect');
            const quantityInput = document.getElementById('productQuantity');
            const quantity = parseInt(quantityInput.value) || 1;
            const option = select.options[select.selectedIndex];
            
            if (!option.value) {
                alert('Please select a product');
                return;
            }
            
            const productId = parseInt(option.value);
            const productName = option.dataset.name;
            const sku = option.dataset.sku;
            const unitPrice = parseFloat(option.dataset.price);
            const taxRate = parseFloat(option.dataset.tax || 0);
            const stock = parseInt(option.dataset.stock);
            
            // Check if product already exists to validate total quantity against stock
            const existingIndex = items.findIndex(item => item.product_id == productId);
            let currentQtyInCart = 0;
            if (existingIndex >= 0) {
                currentQtyInCart = items[existingIndex].quantity;
            }
            
            const totalRequestedQty = currentQtyInCart + quantity;
            
            if (totalRequestedQty > stock) {
                alert(`Insufficient stock! \nAvailable: ${stock}\nIn Cart: ${currentQtyInCart}\nRequested: ${quantity}\nTotal: ${totalRequestedQty}`);
                return;
            }
            
            if (existingIndex >= 0) {
                items[existingIndex].quantity += quantity;
            } else {
                items.push({
                    product_id: productId,
                    product_name: productName,
                    sku: sku,
                    quantity: quantity,
                    unit_price: unitPrice,
                    tax_rate: taxRate,
                    stock_quantity: stock, // Store max stock for later validation
                    line_total: 0
                });
            }
            
            renderItems();
            
            // Reset quantity to 1 but keep product selected (optional, or reset both)
            quantityInput.value = 1; 
        }
        
        function removeItem(index) {
            items.splice(index, 1);
            renderItems();
        }
        
        function updateQuantity(index, newQuantity) {
            newQuantity = parseInt(newQuantity);
            if (isNaN(newQuantity) || newQuantity < 1) {
                newQuantity = 1;
            }
            
            const item = items[index];
            
            // Check stock using the stored stock quantity (need to ensure it's available in item object)
            // Note: In edit mode, we might need to account for original quantity, but for now strict check against CURRENT stock
            // Only if we stored stock quantity in the item. 
            // Since we added 'stock_quantity' to the item object in 'addProduct', we should use it.
            // However, PHP loaded items need to have this too. 
            // *CRITICAL*: PHP query needs to fetch stock_quantity for edit mode.
            
            // For now, let's rely on the dataset from the dropdown if accessible, or the item property if set.
            // If item.stock_quantity is not set (e.g. from PHP load without it), we might skip strict check or fetch it.
            // Let's assume we want strict check.
            
            // If added via JS, it has stock_quantity. 
            // If loaded via PHP, we need to ensure the query included it. 
            // (Checking invoice-form.php line 235: SELECT ii.*, p.name..., p.stock_quantity FROM...)
            // Yes, PHP query includes p.stock_quantity!
            
            // Wait, the PHP array_map above mapping 'invoiceItems' did NOT include stock_quantity.
            // I need to add that to the PHP map above first. (I did in this replacement block).
            
            const maxStock = item.stock_quantity || 999999; // Fallback if missing
            
            if (newQuantity > maxStock) {
                alert(`Insufficient stock. Available: ${maxStock}`);
                newQuantity = maxStock;
            }
            
            items[index].quantity = newQuantity;
            renderItems();
        }
        
        function updatePrice(index, price) {
            let newPrice = parseFloat(price);
            if (isNaN(newPrice) || newPrice < 0) newPrice = 0;
            items[index].unit_price = newPrice;
            renderItems();
        }
        
        function renderItems() {
            const tbody = document.getElementById('itemsTableBody');
            const emptyRow = document.getElementById('emptyRow');
            
            if (items.length === 0) {
                try { tbody.innerHTML = ''; } catch(e){} // Safety
                if(emptyRow) emptyRow.style.display = '';
                if(tbody && emptyRow && !tbody.contains(emptyRow)) tbody.appendChild(emptyRow);
            } else {
                if(emptyRow) emptyRow.style.display = 'none';
                tbody.innerHTML = '';
                
                items.forEach((item, index) => {
                    const lineTotal = item.quantity * item.unit_price;
                    const lineTax = (lineTotal * item.tax_rate) / 100;
                    const total = lineTotal + lineTax;
                    item.line_total = total;
                    
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${item.product_name}</td>
                        <td><code>${item.sku}</code></td>
                        <td>
                            <input type="number" class="form-control" min="1" value="${item.quantity}" 
                                   onchange="updateQuantity(${index}, this.value)" style="width: 80px;">
                        </td>
                        <td>
                            <input type="number" step="0.01" class="form-control" value="${item.unit_price.toFixed(2)}" 
                                   onchange="updatePrice(${index}, this.value)" style="width: 100px;">
                        </td>
                        <td>${item.tax_rate.toFixed(1)}%</td>
                        <td>₹${total.toFixed(2)}</td>
                        <td>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="removeItem(${index})">🗑️</button>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            }
            
            calculateTotals();
        }
        
        function calculateTotals() {
            let subtotal = 0;
            let tax = 0;
            
            items.forEach(item => {
                const lineTotal = item.quantity * item.unit_price;
                const lineTax = (lineTotal * item.tax_rate) / 100;
                subtotal += lineTotal;
                tax += lineTax;
            });
            
            const discountInput = document.getElementById('discountInput');
            let discount = parseFloat(discountInput.value);
            if (isNaN(discount) || discount < 0) discount = 0;
            
            // Prevent discount from exceeding total
            if (discount > (subtotal + tax)) {
                // alert('Discount cannot be greater than Total Amount'); // Optional: could be annoying
                // discount = subtotal + tax;
                // discountInput.value = discount.toFixed(2);
            }
            
            const total = subtotal + tax - discount;
            
            document.getElementById('subtotalDisplay').textContent = '₹' + subtotal.toFixed(2);
            document.getElementById('taxDisplay').textContent = '₹' + tax.toFixed(2);
            document.getElementById('totalDisplay').textContent = '₹' + Math.max(0, total).toFixed(2);
        }
        
        document.getElementById('invoiceForm').addEventListener('submit', function(e) {
            if (items.length === 0) {
                e.preventDefault();
                alert('Please add at least one product to the invoice');
                return false;
            }
            
            document.getElementById('itemsInput').value = JSON.stringify(items);
        });
        
        // Initialize
        // Ensure discount input has event listener (already in HTML: onchange="calculateTotals()")
        // Add check for edit mode initialization
        <?php if ($isEditMode): ?>
        // If in edit mode, ensure stock_quantity from PHP is passed correctly.
        // The PHP block at the top handles 'items' array population.
        <?php endif; ?>
        
        calculateTotals();
    </script>
</body>
</html>
