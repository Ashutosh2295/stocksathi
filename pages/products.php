<?php
/**
 * Products Management Page - Core PHP Version
 * Stocksathi Inventory System
 * Uses core PHP concepts with direct database queries and form submissions
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Session.php';
require_once __DIR__ . '/../_includes/PermissionMiddleware.php';

// Role-based access: Only admin, super_admin, and store_manager can manage products
$userRole = Session::getUserRole();
if (!in_array($userRole, ['super_admin', 'admin', 'store_manager'])) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

// Initialize database connection
$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'] ?? null;
    
    if ($id) {
        try {
            // Check if product is used in invoices
            $invoiceCount = $db->queryOne(
                "SELECT COUNT(*) as count FROM invoice_items WHERE {$orgFilter} product_id = ?",
                [$id]
            );
            
            if ($invoiceCount['count'] > 0) {
                Session::setFlash('Cannot delete product. It is used in ' . $invoiceCount['count'] . ' invoice(s). Please remove it from invoices first or mark it as inactive.', 'error');
            } else {
                // Check if product has stock logs
                $logCount = $db->queryOne(
                    "SELECT COUNT(*) as count FROM stock_logs WHERE {$orgFilter} product_id = ?",
                    [$id]
                );
                
                if ($logCount['count'] > 0) {
                    // Soft delete - mark as inactive instead
                    $affected = $db->execute("UPDATE products SET status = 'inactive' WHERE {$orgFilter} id = ?", [$id]);
                    if ($affected > 0) {
                        Session::setFlash('Product marked as inactive (has stock history). Cannot permanently delete.', 'success');
                    } else {
                        Session::setFlash('Product not found', 'error');
                    }
                } else {
                    // Hard delete if no history
                    $affected = $db->execute("DELETE FROM products WHERE {$orgFilter} id = ?", [$id]);
                    if ($affected > 0) {
                        Session::setFlash('Product deleted successfully', 'success');
                    } else {
                        Session::setFlash('Product not found', 'error');
                    }
                }
            }
        } catch (Exception $e) {
            Session::setFlash('Error deleting product: ' . $e->getMessage(), 'error');
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
        exit;
    }
}

// Get flash message if any
$flash = Session::getFlash();
$message = $flash['message'] ?? '';
$messageType = $flash['type'] ?? '';

// Get query parameters for filtering and pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$categoryFilter = $_GET['category'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build query for products
$query = "SELECT p.*, 
          c.name as category_name, 
          b.name as brand_name
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          LEFT JOIN brands b ON p.brand_id = b.id
          " . ($orgIdPatch ? " WHERE p.organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1");
$params = [];

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($categoryFilter)) {
    $query .= " AND p.category_id = ?";
    $params[] = $categoryFilter;
}

if (!empty($statusFilter)) {
    $query .= " AND p.status = ?";
    $params[] = $statusFilter;
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as count_table";
$totalResult = $db->queryOne($countQuery, $params);
$total = (int)$totalResult['total'];
$totalPages = ceil($total / $limit);

// Add ordering and pagination (LIMIT/OFFSET need to be literal integers, not params)
$query .= " ORDER BY p.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$products = $db->query($query, $params);

// Load categories for filter dropdown
$categories = $db->query("SELECT id, name FROM categories WHERE {$orgFilter} status = 'active' ORDER BY name");

// Helper function to get stock badge
function getStockBadge($stock, $minStock) {
    if ($stock == 0) {
        return '<span class="badge badge-danger">Out of Stock</span>';
    } elseif ($stock <= $minStock) {
        return '<span class="badge badge-warning">' . $stock . ' (Low)</span>';
    } else {
        return '<span class="badge badge-success">' . $stock . '</span>';
    }
}

// Helper function to format number
function formatNumber($num) {
    return number_format((float)$num, 2);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
    <script src="<?= JS_PATH ?>/toast.js"></script>
</head>

<body>
    <div class="app-container">
        <?php include __DIR__ . '/../_includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../_includes/header.php'; ?>
            
            <main class="content">
                <div class="content-header">
                    <nav class="breadcrumb">
                        <span class="breadcrumb-item">Home</span>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active">Products</span>
                    </nav>
                    <h1 class="content-title">Product Management</h1>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="content-actions">
                    <form method="GET" action="" class="search-filter-group" style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; min-width: 200px;">
                        <select name="category" class="form-control" style="min-width: 150px;">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoryFilter == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status" class="form-control" style="min-width: 120px;">
                            <option value="">All Status</option>
                            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if ($search || $categoryFilter || $statusFilter): ?>
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-ghost">Clear</a>
                        <?php endif; ?>
                    </form>
                    <div class="action-buttons">
                        <a href="<?= BASE_PATH ?>/pages/product-form.php" class="btn btn-primary">+ Add Product</a>
                    </div>
                </div>

                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Brand</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px;">
                                            No products found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td><code>#<?= $product['id'] ?></code></td>
                                            <td><?= htmlspecialchars($product['name']) ?></td>
                                            <td><?= htmlspecialchars($product['category_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($product['brand_name'] ?? '-') ?></td>
                                            <td>₹<?= formatNumber($product['selling_price']) ?></td>
                                            <td><?= getStockBadge($product['stock_quantity'], $product['min_stock_level']) ?></td>
                                            <td>
                                                <?php if ($product['status'] === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="product-details.php?id=<?= $product['id'] ?>" class="btn btn-ghost btn-sm" title="View">👁️</a>
                                                <a href="product-form.php?id=<?= $product['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">✏️</a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($product['name'])) ?>?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $product['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" title="Delete">🗑️</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Showing <?= $total > 0 ? (($page - 1) * $limit + 1) : 0 ?>-<?= min($page * $limit, $total) ?> of <?= $total ?> products
                            </div>
                            <div class="pagination-controls">
                                <?php if ($page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-btn">Previous</a>
                                <?php else: ?>
                                    <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">Previous</span>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-btn">Next</a>
                                <?php else: ?>
                                    <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">Next</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>

</html>
