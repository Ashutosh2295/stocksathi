<?php
/**
 * Product Form Page - Core PHP Version
 * Add/Edit Products using core PHP concepts
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Validator.php';
require_once __DIR__ . '/../_includes/Session.php';

// Initialize database connection
$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$message = '';
$messageType = '';
$isEditMode = false;
$product = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $validator = new Validator($_POST);
        $validator->required($_POST['name'] ?? '', 'Product name');
        $validator->required($_POST['sku'] ?? '', 'SKU');
        $validator->required($_POST['selling_price'] ?? '', 'Selling price');
        
        if ($validator->fails()) {
            $message = $validator->getFirstError();
            $messageType = 'error';
        } else {
            $data = Validator::sanitize($_POST);
            
            // Convert numeric fields
            $data['purchase_price'] = isset($data['purchase_price']) ? (float)$data['purchase_price'] : 0;
            $data['selling_price'] = (float)$data['selling_price'];
            $data['stock_quantity'] = isset($data['stock_quantity']) ? (int)$data['stock_quantity'] : 0;
            $data['min_stock_level'] = isset($data['min_stock_level']) ? (int)$data['min_stock_level'] : 0;
            $data['tax_rate'] = isset($data['tax_rate']) ? (float)$data['tax_rate'] : 0;
            $data['category_id'] = !empty($data['category_id']) ? (int)$data['category_id'] : null;
            $data['brand_id'] = !empty($data['brand_id']) ? (int)$data['brand_id'] : null;
            // Empty barcode must be NULL to avoid UNIQUE constraint (multiple products with no barcode)
            $data['barcode'] = isset($data['barcode']) && trim((string)$data['barcode']) !== '' ? trim($data['barcode']) : null;
            
            if (!empty($_POST['id'])) {
                // Update existing product (no expiry_date - column not in schema)
                $query = "UPDATE products SET name = ?, description = ?, sku = ?, barcode = ?, 
                         purchase_price = ?, selling_price = ?, stock_quantity = ?, min_stock_level = ?, 
                         tax_rate = ?, unit = ?, status = ? 
                         WHERE {$orgFilter} id = ?";
                $affected = $db->execute($query, [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['sku'],
                    $data['barcode'],
                    $data['purchase_price'],
                    $data['selling_price'],
                    $data['stock_quantity'],
                    $data['min_stock_level'],
                    $data['tax_rate'],
                    $data['unit'] ?? 'pcs',
                    $data['status'] ?? 'active',
                    $_POST['id']
                ]);
                
                if ($affected > 0) {
                    Session::setFlash('Product updated successfully', 'success');
                    header('Location: ' . BASE_PATH . '/pages/products.php');
                    exit;
                } else {
                    $message = 'Product not found or no changes made';
                    $messageType = 'error';
                }
            } else {
                // Create new product (no expiry_date - column not in schema)
                $query = "INSERT INTO products (name, description, category_id, brand_id, unit, sku, barcode, 
                         purchase_price, selling_price, stock_quantity, min_stock_level, tax_rate, status) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $id = $db->execute($query, [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['category_id'],
                    $data['brand_id'],
                    $data['unit'] ?? 'pcs',
                    $data['sku'],
                    $data['barcode'],
                    $data['purchase_price'],
                    $data['selling_price'],
                    $data['stock_quantity'],
                    $data['min_stock_level'],
                    $data['tax_rate'],
                    $data['status'] ?? 'active'
                ]);
                
                Session::setFlash('Product created successfully', 'success');
                header('Location: ' . BASE_PATH . '/pages/products.php');
                exit;
            }
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Get flash message if any
$flash = Session::getFlash();
if ($flash) {
    $message = $flash['message'];
    $messageType = $flash['type'];
}

// Check if editing
$productId = $_GET['id'] ?? null;
if ($productId) {
    $isEditMode = true;
    $product = $db->queryOne(
        "SELECT p.*, c.name as category_name, b.name as brand_name 
         FROM products p
         LEFT JOIN categories c ON p.category_id = c.id
         LEFT JOIN brands b ON p.brand_id = b.id
         WHERE " . ($orgIdPatch ? " p.organization_id = " . intval($orgIdPatch) . " AND " : "") . " p.id = ?",
        [$productId]
    );
    
    if (!$product) {
        Session::setFlash('Product not found', 'error');
        header('Location: ' . BASE_PATH . '/pages/products.php');
        exit;
    }
}

// Load categories and brands for dropdowns
$categories = $db->query("SELECT id, name FROM categories WHERE {$orgFilter} status = 'active' ORDER BY name");
$brands = $db->query("SELECT id, name FROM brands WHERE {$orgFilter} status = 'active' ORDER BY name");

// Generate SKU for new product if not editing
if (!$isEditMode && empty($_POST)) {
    $sku = 'PRD-' . date('Y') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
} else {
    $sku = $product['sku'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditMode ? 'Edit' : 'Add' ?> Product - Stocksathi</title>
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
                        <a href="<?= BASE_PATH ?>/pages/products.php" class="breadcrumb-item">Products</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active"><?= $isEditMode ? 'Edit' : 'Add' ?> Product</span>
                    </nav>
                    <h1 class="content-title"><?= $isEditMode ? 'Edit' : 'Add New' ?> Product</h1>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="productForm">
                    <?php if ($isEditMode): ?>
                        <input type="hidden" name="id" value="<?= $product['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="grid grid-cols-3 gap-6">
                        <!-- Card 1: Basic Information -->
                        <div class="card mb-6">
                            <div class="card-header">
                                <h3 class="card-title">Basic Information</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label required">Product Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="Enter product name" 
                                           value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" placeholder="Product description" rows="4"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label required">Category</label>
                                        <select name="category_id" class="form-control" <?= $isEditMode ? 'disabled' : 'required' ?>>
                                            <option value="">Select category</option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cat['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($isEditMode): ?>
                                            <input type="hidden" name="category_id" value="<?= $product['category_id'] ?>">
                                            <span class="form-text">Category cannot be changed after creation</span>
                                        <?php endif; ?>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Brand</label>
                                        <select name="brand_id" class="form-control" <?= $isEditMode ? 'disabled' : '' ?>>
                                            <option value="">Select brand</option>
                                            <?php foreach ($brands as $brand): ?>
                                                <option value="<?= $brand['id'] ?>" <?= ($product['brand_id'] ?? '') == $brand['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($brand['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if ($isEditMode): ?>
                                            <input type="hidden" name="brand_id" value="<?= $product['brand_id'] ?>">
                                            <span class="form-text">Brand cannot be changed after creation</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Unit</label>
                                    <select name="unit" class="form-control">
                                        <option value="pcs" <?= ($product['unit'] ?? 'pcs') === 'pcs' ? 'selected' : '' ?>>Piece</option>
                                        <option value="kg" <?= ($product['unit'] ?? '') === 'kg' ? 'selected' : '' ?>>Kg</option>
                                        <option value="ltr" <?= ($product['unit'] ?? '') === 'ltr' ? 'selected' : '' ?>>Liter</option>
                                        <option value="box" <?= ($product['unit'] ?? '') === 'box' ? 'selected' : '' ?>>Box</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2: Pricing & Stock -->
                        <div class="card mb-6">
                            <div class="card-header">
                                <h3 class="card-title">Pricing & Stock</h3>
                            </div>
                            <div class="card-body">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label required">SKU</label>
                                        <input type="text" name="sku" class="form-control" placeholder="Auto-generated" 
                                               value="<?= htmlspecialchars($sku) ?>" <?= $isEditMode ? 'readonly' : '' ?> required>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Barcode</label>
                                        <input type="text" name="barcode" class="form-control" placeholder="Scan or enter" 
                                               value="<?= htmlspecialchars($product['barcode'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Purchase Price (₹)</label>
                                    <input type="number" name="purchase_price" class="form-control" placeholder="0.00" 
                                           step="0.01" min="0" value="<?= $product['purchase_price'] ?? '0' ?>">
                                </div>

                                <div class="form-group">
                                    <label class="form-label required">Selling Price (₹)</label>
                                    <input type="number" name="selling_price" class="form-control" placeholder="0.00" 
                                           step="0.01" min="0" value="<?= $product['selling_price'] ?? '0' ?>" required>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label">Opening Stock</label>
                                        <input type="number" name="stock_quantity" class="form-control" placeholder="0" 
                                               min="0" value="<?= $product['stock_quantity'] ?? '0' ?>">
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Min Stock Alert</label>
                                        <input type="number" name="min_stock_level" class="form-control" placeholder="10" 
                                               min="0" value="<?= $product['min_stock_level'] ?? '0' ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Tax Rate (%)</label>
                                    <input type="number" name="tax_rate" class="form-control" placeholder="0" 
                                           step="0.01" min="0" max="100" value="<?= $product['tax_rate'] ?? '0' ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Card 3: Additional Details -->
                        <div class="card mb-6">
                            <div class="card-header">
                                <h3 class="card-title">Additional Details</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="date" name="expiry_date" class="form-control" 
                                           value="<?= $product['expiry_date'] ?? '' ?>">
                                    <span class="form-text">Leave empty if product doesn't expire</span>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 justify-end mt-6">
                        <a href="<?= BASE_PATH ?>/pages/products.php" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Product</button>
                    </div>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
