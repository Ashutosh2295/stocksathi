<?php
/**
 * Stock Adjustments Management Page - Core PHP Version
 * Uses core PHP concepts with direct database queries and form submissions
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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = Session::getUserId();
    
    try {
        if ($action === 'create') {
            // Create new stock adjustment
            $validator = new Validator($_POST);
            $validator->required('product_id', 'Product is required');
            $validator->required('adjustment_type', 'Adjustment type is required');
            $validator->required('quantity', 'Quantity is required');
            $validator->required('reason', 'Reason is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                $adjustmentType = $data['adjustment_type']; // 'add' or 'subtract'
                $quantity = (int)$data['quantity'];
                
                // Start transaction
                $db->beginTransaction();
                
                try {
                    // Insert stock log (use 'adjustment' as type, store adjustment_type in notes or separate field)
                    $query = "INSERT INTO stock_logs (product_id, type, quantity, reference_type, reference_id, notes, created_by, organization_id) 
                             VALUES (?, 'adjustment', ?, 'stock_adjustment', ?, ?, ?, ?)";
                    $logId = $db->execute($query, [
                        $data['product_id'],
                        $adjustmentType === 'add' ? $quantity : -$quantity,
                        null,
                        ($data['reason'] ?? '') . ' [Type: ' . $adjustmentType . ']',
                        $userId,
                        $orgIdPatch
                    ]);
                    
                    // Update product stock based on adjustment type
                    if ($adjustmentType === 'add') {
                        $db->execute(
                            "UPDATE products SET stock_quantity = stock_quantity + ? WHERE {$orgFilter} id = ?",
                            [$quantity, $data['product_id']]
                        );
                    } else {
                        // Check stock before subtracting
                        $product = $db->queryOne("SELECT stock_quantity FROM products WHERE {$orgFilter} id = ?", [$data['product_id']]);
                        if ($product['stock_quantity'] < $quantity) {
                            throw new Exception('Insufficient stock. Available: ' . $product['stock_quantity']);
                        }
                        $db->execute(
                            "UPDATE products SET stock_quantity = stock_quantity - ? WHERE {$orgFilter} id = ?",
                            [$quantity, $data['product_id']]
                        );
                    }
                    
                    $db->commit();
                    Session::setFlash('Stock adjusted successfully', 'success');
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
            }
            
        } elseif ($action === 'delete') {
            // Delete stock adjustment entry
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $message = 'Adjustment ID is required';
                $messageType = 'error';
            } else {
                // Get stock log details
                $log = $db->queryOne("SELECT * FROM stock_logs WHERE " . ($orgIdPatch ? "organization_id = " . intval($orgIdPatch) . " AND " : "") . "id = ? AND type = 'adjustment'", [$id]);
                
                if (!$log) {
                    Session::setFlash('Adjustment entry not found', 'error');
                } else {
                    // Start transaction
                    $db->beginTransaction();
                    
                    try {
                        // Reverse the adjustment (quantity is signed: positive for add, negative for subtract)
                        $quantity = abs($log['quantity']);
                        if ($log['quantity'] > 0) {
                            // Was an addition, so subtract
                            $db->execute(
                                "UPDATE products SET stock_quantity = stock_quantity - ? WHERE {$orgFilter} id = ?",
                                [$quantity, $log['product_id']]
                            );
                        } else {
                            // Was a subtraction, so add back
                            $db->execute(
                                "UPDATE products SET stock_quantity = stock_quantity + ? WHERE {$orgFilter} id = ?",
                                [$quantity, $log['product_id']]
                            );
                        }
                        
                        $db->execute("DELETE FROM stock_logs WHERE " . ($orgIdPatch ? "organization_id = " . intval($orgIdPatch) . " AND " : "") . "id = ?", [$id]);
                        
                        $db->commit();
                        Session::setFlash('Adjustment deleted successfully', 'success');
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

// Load all stock adjustments
$adjustments = [];
try {
    $query = "SELECT sl.*, 
          p.name as product_name,
          p.sku,
          u.full_name as created_by_name
          FROM stock_logs sl
          LEFT JOIN products p ON sl.product_id = p.id
          LEFT JOIN users u ON sl.created_by = u.id
          WHERE sl.type = 'adjustment'" . ($orgIdPatch ? " AND sl.organization_id = " . intval($orgIdPatch) : "") . "
          ORDER BY sl.created_at DESC";
    $adjustments = $db->query($query);
} catch (Exception $e) {
    // Check if it's a missing table error
    if (strpos($e->getMessage(), 'stock_logs') !== false && strpos($e->getMessage(), 'doesn\'t exist') !== false) {
        $message = 'Database setup required. Please run the migrations first.';
        $messageType = 'error';
    } else {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
    error_log("Stock Adjustments Query Error: " . $e->getMessage());
}

// Load products for dropdown
$products = [];
try {
    $products = $db->query("SELECT id, name, sku, stock_quantity FROM products WHERE {$orgFilter} status = 'active' ORDER BY name");
} catch (Exception $e) {
    error_log("Products Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Adjustments - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Stock Adjustments</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Stock Adjustments</h1>
                        <button class="btn btn-primary" onclick="openModal('addModal')">
                            <span>➕</span> Add Adjustment
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
                        <h3 class="card-title">Adjustments List</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Product</th>
                                    <th>Adjustment</th>
                                    <th>Reason</th>
                                    <th>Adjusted By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($adjustments)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            No adjustments found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($adjustments as $adj): ?>
                                        <tr>
                                            <td><?= date('Y-m-d H:i', strtotime($adj['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($adj['product_name'] ?? '-') ?> <code><?= htmlspecialchars($adj['sku'] ?? '') ?></code></td>
                                            <td>
                                                <?php 
                                                $qty = (int)$adj['quantity'];
                                                // Quantity is stored as signed: positive for add, negative for subtract
                                                if ($qty > 0): 
                                                ?>
                                                    <span class="badge badge-success">+<?= abs($qty) ?> units</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">-<?= abs($qty) ?> units</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars(preg_replace('/\s*\[Type:.*?\]/', '', $adj['notes'] ?? '') ?: '-') ?></td>
                                            <td><?= htmlspecialchars($adj['created_by_name'] ?? 'System') ?></td>
                                            <td class="table-actions">
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this adjustment? This will reverse the stock change.');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $adj['id'] ?>">
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
    
    <!-- Add Adjustment Modal -->
    <div class="modal-backdrop" id="addModal" style="display:none;">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Add Stock Adjustment</h3>
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
                                    <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>) - Current Stock: <?= $product['stock_quantity'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Adjustment Type</label>
                        <select name="adjustment_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="add">Add Stock (+)</option>
                            <option value="subtract">Subtract Stock (-)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Quantity</label>
                        <input type="number" name="quantity" class="form-control" placeholder="Enter quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Reason</label>
                        <select name="reason" class="form-control" required>
                            <option value="">Select Reason</option>
                            <option value="Damaged goods">Damaged goods</option>
                            <option value="Expired items">Expired items</option>
                            <option value="Stock count correction">Stock count correction</option>
                            <option value="Return from customer">Return from customer</option>
                            <option value="Lost/Stolen">Lost/Stolen</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('addModal')">Cancel</button>
                <button type="submit" form="addForm" class="btn btn-primary">Save Adjustment</button>
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