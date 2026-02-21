<?php
/**
 * Sales Returns Management Page - Core PHP Version
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
$userId = Session::getUserId();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create') {
            $validator = new Validator($_POST);
            $validator->required('invoice_id', 'Invoice is required');
            $validator->required('return_date', 'Return date is required');
            $validator->required('total_amount', 'Total amount is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                // Get invoice items to restore stock
                $invoiceItems = $db->query(
                    "SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ?",
                    [$data['invoice_id']]
                );
                
                if (empty($invoiceItems)) {
                    $message = 'Invoice has no items to return';
                    $messageType = 'error';
                } else {
                    $db->beginTransaction();
                    
                    try {
                        // Generate return number
                        $lastReturn = $db->queryOne("SELECT return_number FROM sales_returns {$orgWhere} ORDER BY id DESC LIMIT 1");
                        $lastNum = $lastReturn ? (int)substr($lastReturn['return_number'], 4) : 0;
                        $returnNumber = 'RET-' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
                        
                        // Get invoice details
                        $invoice = $db->queryOne("SELECT customer_id, invoice_number FROM invoices WHERE {$orgFilter} id = ?", [$data['invoice_id']]);
                        
                        // Create sales return
                        $query = "INSERT INTO sales_returns (return_number, invoice_id, customer_id, return_date, total_amount, refund_amount, refund_status, reason, created_by) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $returnId = $db->execute($query, [
                            $returnNumber,
                            $data['invoice_id'],
                            $invoice['customer_id'] ?? null,
                            $data['return_date'],
                            (float)$data['total_amount'],
                            (float)($data['refund_amount'] ?? 0),
                            $data['refund_status'] ?? 'pending',
                            $data['reason'] ?? null,
                            $userId
                        ]);
                        
                        // Restore stock for each returned item
                        foreach ($invoiceItems as $item) {
                            $db->execute(
                                "UPDATE products SET stock_quantity = stock_quantity + ? WHERE {$orgFilter} id = ?",
                                [$item['quantity'], $item['product_id']]
                            );
                            
                            // Log stock in
                            $db->execute(
                                "INSERT INTO stock_logs (product_id, type, quantity, reference_type, reference_id, notes, created_by) 
                                 VALUES (?, 'in', ?, 'sales_return', ?, ?, ?)",
                                [$item['product_id'], $item['quantity'], $returnId, 'Sales Return: ' . $returnNumber, $userId]
                            );
                        }
                        
                        $db->commit();
                        Session::setFlash('Sales return created successfully: ' . $returnNumber . '. Stock has been restored.', 'success');
                        header('Location: ' . $_SERVER['PHP_SELF']);
                        exit;
                    } catch (Exception $e) {
                        $db->rollBack();
                        throw $e;
                    }
                }
            }
            
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $db->execute("DELETE FROM sales_returns WHERE {$orgFilter} id = ?", [$id]);
                Session::setFlash('Sales return deleted successfully', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$flash = Session::getFlash();
if ($flash) {
    $message = $flash['message'];
    $messageType = $flash['type'];
}

// Load all sales returns
try {
    $query = "SELECT sr.*, 
              i.invoice_number,
              c.name as customer_name,
              u.full_name as created_by_name
              FROM sales_returns sr
              LEFT JOIN invoices i ON sr.invoice_id = i.id
              LEFT JOIN customers c ON sr.customer_id = c.id
              LEFT JOIN users u ON sr.created_by = u.id
              ORDER BY sr.created_at DESC";
    $returns = $db->query($query);
} catch (Exception $e) {
    $returns = [];
    error_log("Sales Returns Query Error: " . $e->getMessage());
}

// Load invoices for dropdown
try {
    $invoices = $db->query("SELECT i.id, i.invoice_number, c.name as customer_name FROM invoices i LEFT JOIN customers c ON i.customer_id = c.id ORDER BY i.id DESC LIMIT 100");
} catch (Exception $e) {
    $invoices = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Returns - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Sales Returns</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Sales Returns</h1>
                        <button class="btn btn-primary" onclick="openModal('returnModal', true)">
                            <span>➕</span> Process Return
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sales Returns List</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Return #</th>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Return Date</th>
                                    <th>Amount</th>
                                    <th>Refund Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($returns)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center" style="padding: 40px;">
                                            No sales returns found. Click "Process Return" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($returns as $return): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($return['return_number']) ?></code></td>
                                            <td><code><?= htmlspecialchars($return['invoice_number'] ?? '-') ?></code></td>
                                            <td><?= htmlspecialchars($return['customer_name'] ?? '-') ?></td>
                                            <td><?= date('Y-m-d', strtotime($return['return_date'])) ?></td>
                                            <td>₹<?= number_format($return['total_amount'], 2) ?></td>
                                            <td>₹<?= number_format($return['refund_amount'], 2) ?></td>
                                            <td>
                                                <?php if ($return['refund_status'] === 'completed'): ?>
                                                    <span class="badge badge-success">Completed</span>
                                                <?php elseif ($return['refund_status'] === 'processing'): ?>
                                                    <span class="badge badge-info">Processing</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this return?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $return['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm">🗑️</button>
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

    <!-- Process Return Modal -->
    <div class="modal-backdrop" id="returnModal" style="display:none;">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header">
                <h3 class="modal-title">Process Sales Return</h3>
                <button class="modal-close" onclick="closeModal('returnModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="returnForm">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label class="form-label required">Invoice</label>
                        <select name="invoice_id" class="form-control" required>
                            <option value="">Select Invoice</option>
                            <?php foreach ($invoices as $inv): ?>
                                <option value="<?= $inv['id'] ?>">
                                    <?= htmlspecialchars($inv['invoice_number']) ?> - <?= htmlspecialchars($inv['customer_name'] ?? 'No Customer') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Return Date</label>
                        <input type="date" name="return_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label required">Total Amount</label>
                            <input type="number" step="0.01" name="total_amount" class="form-control" required placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Refund Amount</label>
                            <input type="number" step="0.01" name="refund_amount" class="form-control" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Refund Status</label>
                        <select name="refund_status" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reason for Return</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Enter reason for return..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('returnModal')">Cancel</button>
                <button type="submit" form="returnForm" class="btn btn-primary">Process Return</button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId, isNew = false) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                if (isNew) {
                    document.getElementById('returnForm').reset();
                    document.querySelector('[name="return_date"]').value = '<?= date('Y-m-d') ?>';
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('returnModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('returnModal');
                    }
                });
            }
        });
    </script>
</body>
</html>