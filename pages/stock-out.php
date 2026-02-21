<?php
/**
 * Stock Out Management Page - Core PHP Version
 * Uses core PHP concepts with direct database queries and form submissions
 */
require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Validator.php';
require_once __DIR__ . '/../_includes/Session.php';
require_once __DIR__ . '/../_includes/PermissionMiddleware.php';

// Role-based access: Only admin, super_admin, and store_manager can manage stock
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
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = Session::getUserId();
    
    try {
        if ($action === 'create') {
            // Create new stock out entry
            $validator = new Validator($_POST);
            $validator->required('product_id', 'Product is required');
            $validator->required('quantity', 'Quantity is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                // Check if product has enough stock
                $product = $db->queryOne("SELECT stock_quantity FROM products WHERE {$orgFilter} id = ?", [$data['product_id']]);
                if (!$product || $product['stock_quantity'] < (int)$data['quantity']) {
                    $message = 'Insufficient stock available';
                    $messageType = 'error';
                } else {
                    // Start transaction
                    $db->beginTransaction();
                    
                    try {
                        // Insert stock log
                        $query = "INSERT INTO stock_logs (product_id, type, quantity, reference_type, reference_id, warehouse_id, store_id, notes, created_by, organization_id) 
                                 VALUES (?, 'out', ?, 'stock_out', ?, ?, ?, ?, ?, ?)";
                        $logId = $db->execute($query, [
                            $data['product_id'],
                            (int)$data['quantity'],
                            null,
                            $data['warehouse_id'] ?? null,
                            $data['store_id'] ?? null,
                            $data['notes'] ?? null,
                            $userId,
                            $orgIdPatch
                        ]);
                        
                        // Update product stock
                        $db->execute(
                            "UPDATE products SET stock_quantity = stock_quantity - ? WHERE {$orgFilter} id = ?",
                            [(int)$data['quantity'], $data['product_id']]
                        );
                        
                        $db->commit();
                        Session::setFlash('Stock removed successfully', 'success');
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    } catch (Exception $e) {
                        $db->rollBack();
                        throw $e;
                    }
                }
            }
            
        } elseif ($action === 'delete') {
            // Delete stock out entry
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $message = 'Stock entry ID is required';
                $messageType = 'error';
            } else {
                // Get stock log details
                $log = $db->queryOne("SELECT * FROM stock_logs WHERE " . ($orgIdPatch ? "organization_id = " . intval($orgIdPatch) . " AND " : "") . "id = ? AND type = 'out'", [$id]);
                
                if (!$log) {
                    Session::setFlash('Stock entry not found', 'error');
                } else {
                    // Start transaction
                    $db->beginTransaction();
                    
                    try {
                        // Reverse the stock (add it back)
                        $db->execute(
                            "UPDATE products SET stock_quantity = stock_quantity + ? WHERE {$orgFilter} id = ?",
                            [$log['quantity'], $log['product_id']]
                        );
                        
                        $db->execute("DELETE FROM stock_logs WHERE " . ($orgIdPatch ? "organization_id = " . intval($orgIdPatch) . " AND " : "") . "id = ?", [$id]);
                        
                        $db->commit();
                        Session::setFlash('Stock entry deleted successfully', 'success');
                    } catch (Exception $e) {
                        $db->rollBack();
                        throw $e;
                    }
                }
                header('Location: ' . $_SERVER['PHP_SELF']);
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
if ($flash && is_array($flash)) {
    $message = $flash['message'] ?? '';
    $messageType = $flash['type'] ?? 'info';
}

// Load all stock out entries
$stockOuts = [];
try {
    $query = "SELECT sl.*, 
          p.name as product_name,
          p.sku,
          w.name as warehouse_name,
          s.name as store_name,
          u.full_name as created_by_name
          FROM stock_logs sl
          LEFT JOIN products p ON sl.product_id = p.id
          LEFT JOIN warehouses w ON sl.warehouse_id = w.id
          LEFT JOIN stores s ON sl.store_id = s.id
          LEFT JOIN users u ON sl.created_by = u.id
          WHERE sl.type = 'out'" . ($orgIdPatch ? " AND sl.organization_id = " . intval($orgIdPatch) : "") . "
          ORDER BY sl.created_at DESC";
    $stockOuts = $db->query($query);
} catch (Exception $e) {
    // Check if it's a missing table error
    if (strpos($e->getMessage(), 'stock_logs') !== false && strpos($e->getMessage(), 'doesn\'t exist') !== false) {
        $message = 'Database setup required. Please run the migrations first.';
        $messageType = 'error';
    } else {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
    error_log("Stock Out Query Error: " . $e->getMessage());
}

// Load products, warehouses, stores for dropdowns
$products = $db->query("SELECT id, name, sku, stock_quantity FROM products WHERE {$orgFilter} status = 'active' ORDER BY name");
$warehouses = $db->query("SELECT id, name FROM warehouses WHERE {$orgFilter} status = 'active' ORDER BY name");
$stores = $db->query("SELECT id, name FROM stores WHERE {$orgFilter} status = 'active' ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Out - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Stock Out</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Stock Out</h1>
                        <button class="btn btn-primary" onclick="openModal('addModal')">
                            <span>➕</span> Add New
                        </button>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                        <?php if (strpos($message, 'Database setup required') !== false): ?>
                            <a href="<?= BASE_PATH ?>/migrations/run_migrations.php" class="btn btn-primary btn-sm" style="margin-left: 10px;">Run Migrations</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Stock Out List</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Warehouse/Store</th>
                                    <th>Issued By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($stockOuts)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            No stock out records found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($stockOuts as $stockOut): ?>
                                        <tr>
                                            <td><?= date('Y-m-d H:i', strtotime($stockOut['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($stockOut['product_name'] ?? '-') ?> <code><?= htmlspecialchars($stockOut['sku'] ?? '') ?></code></td>
                                            <td><?= (int)$stockOut['quantity'] ?> units</td>
                                            <td><?= htmlspecialchars($stockOut['warehouse_name'] ?? $stockOut['store_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($stockOut['created_by_name'] ?? 'System') ?></td>
                                            <td class="table-actions">
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this stock out entry? This will reverse the stock.');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $stockOut['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" title="Delete">🗑️</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Add Stock Out Modal -->
    <div class="modal-backdrop" id="addModal" style="display:none;">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Stock Out</h3>
                <button class="modal-close" onclick="closeModal('addModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="addForm">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label class="form-label required">Product</label>
                        <select name="product_id" id="productSelect" class="form-control" required>
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>" data-stock="<?= $product['stock_quantity'] ?>">
                                    <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>) - Stock: <?= $product['stock_quantity'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Quantity</label>
                        <input type="number" name="quantity" class="form-control" placeholder="Enter quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Warehouse</label>
                        <select name="warehouse_id" class="form-control">
                            <option value="">Select Warehouse</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?= $warehouse['id'] ?>"><?= htmlspecialchars($warehouse['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Store</label>
                        <select name="store_id" class="form-control">
                            <option value="">Select Store</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?= $store['id'] ?>"><?= htmlspecialchars($store['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" placeholder="Enter notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" form="addForm" class="btn btn-primary">Remove Stock</button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = '';
            document.getElementById('addForm').reset();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('addModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('addModal');
                    }
                });
            }
        });
    </script>
</body>
</html>
