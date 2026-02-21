<?php
/**
 * Product Details Page - Core PHP Version
 * View Product using core PHP concepts with direct database queries
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';

// Initialize database connection
$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

// Get product ID from URL
$productId = $_GET['id'] ?? null;

if (!$productId) {
    header('Location: ' . BASE_PATH . '/pages/products.php');
    exit;
}

// Load product data with joins
$product = $db->queryOne(
    "SELECT p.*, 
     c.name as category_name, 
     b.name as brand_name
     FROM products p
     LEFT JOIN categories c ON p.category_id = c.id
     LEFT JOIN brands b ON p.brand_id = b.id
     WHERE " . ($orgIdPatch ? " p.organization_id = " . intval($orgIdPatch) . " AND " : "") . " p.id = ?",
    [$productId]
);

if (!$product) {
    header('Location: ' . BASE_PATH . '/pages/products.php');
    exit;
}

// Helper functions
function formatNumber($num) {
    return number_format((float)$num, 2);
}

function formatDate($dateString) {
    if (!$dateString) return 'No expiry';
    return date('F d, Y', strtotime($dateString));
}

function formatDateTime($dateString) {
    if (!$dateString) return '-';
    return date('M d, Y h:i A', strtotime($dateString));
}

function formatUnit($unit) {
    $units = [
        'pcs' => 'Piece',
        'kg' => 'Kilogram',
        'ltr' => 'Liter',
        'box' => 'Box'
    ];
    return $units[$unit] ?? $unit ?? 'Piece';
}

function getStockBadge($stock, $minStock) {
    $qty = (int)$stock;
    $min = (int)$minStock;
    
    if ($qty === 0) {
        return '<span class="badge badge-danger">Out of Stock (0)</span>';
    } elseif ($qty <= $min) {
        return '<span class="badge badge-warning">' . $qty . ' units (Low Stock)</span>';
    } else {
        return '<span class="badge badge-success">' . $qty . ' units</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - Product Details - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/modal.css">
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
                        <span class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></span>
                    </nav>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h1 class="content-title"><?= htmlspecialchars($product['name']) ?></h1>
                        <div class="action-buttons">
                            <a href="<?= BASE_PATH ?>/pages/products.php" class="btn btn-ghost">← Back</a>
                            <a href="<?= BASE_PATH ?>/pages/product-form.php?id=<?= $product['id'] ?>" class="btn btn-primary">✏️ Edit</a>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-6">
                    <!-- Card 1: Basic Information -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Basic Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="detail-row">
                                <strong>Product Name:</strong>
                                <p><?= htmlspecialchars($product['name']) ?></p>
                            </div>
                            <div class="detail-row">
                                <strong>Description:</strong>
                                <p style="white-space: pre-wrap;"><?= htmlspecialchars($product['description'] ?: 'No description available') ?></p>
                            </div>
                            <div class="detail-row">
                                <strong>Category:</strong>
                                <p><?= htmlspecialchars($product['category_name'] ?: 'Not specified') ?></p>
                            </div>
                            <div class="detail-row">
                                <strong>Brand:</strong>
                                <p><?= htmlspecialchars($product['brand_name'] ?: 'Not specified') ?></p>
                            </div>
                            <div class="detail-row">
                                <strong>Unit:</strong>
                                <p><?= formatUnit($product['unit']) ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: Pricing & Stock -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Pricing & Stock</h3>
                        </div>
                        <div class="card-body">
                            <div class="detail-row">
                                <strong>SKU:</strong>
                                <p><code><?= htmlspecialchars($product['sku'] ?: '-') ?></code></p>
                            </div>
                            <div class="detail-row">
                                <strong>Barcode:</strong>
                                <p><?= htmlspecialchars($product['barcode'] ?: 'Not set') ?></p>
                            </div>
                            <div class="detail-row">
                                <strong>Purchase Price:</strong>
                                <p><?= $product['purchase_price'] ? '₹' . formatNumber($product['purchase_price']) : 'Not set' ?></p>
                            </div>
                            <div class="detail-row">
                                <strong>Selling Price:</strong>
                                <p style="font-size: 1.25rem; font-weight: 600; color: var(--color-primary);">
                                    ₹<?= formatNumber($product['selling_price'] ?: 0) ?>
                                </p>
                            </div>
                            <div class="detail-row">
                                <strong>Current Stock:</strong>
                                <p><?= getStockBadge($product['stock_quantity'], $product['min_stock_level']) ?></p>
                            </div>
                            <div class="detail-row">
                                <strong>Min Stock Alert:</strong>
                                <p><?= $product['min_stock_level'] ?: '0' ?></p>
                            </div>
                            <div class="detail-row">
                                <strong>Tax Rate:</strong>
                                <p><?= $product['tax_rate'] ? $product['tax_rate'] . '%' : '0%' ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Additional Details -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Additional Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="detail-row">
                                <strong>Expiry Date:</strong>
                                <p><?= formatDate($product['expiry_date']) ?></p>
                            </div>
                            <div class="detail-row">
                                <strong>Status:</strong>
                                <p>
                                    <?php if ($product['status'] === 'active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactive</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="detail-row">
                                <strong>Created At:</strong>
                                <p><?= formatDateTime($product['created_at']) ?></p>
                            </div>
                            <div class="detail-row">
                                <strong>Last Updated:</strong>
                                <p><?= formatDateTime($product['updated_at']) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <style>
        .detail-row {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .detail-row strong {
            display: block;
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .detail-row p {
            margin: 0;
            color: var(--text-primary);
            font-size: 1rem;
        }
    </style>
</body>
</html>
