<?php
/**
 * Expenses Management Page - Core PHP Version
 * Uses core PHP concepts with direct database queries and form submissions
 * Updated to match database schema
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Validator.php';
require_once __DIR__ . '/../_includes/Session.php';
require_once __DIR__ . '/../_includes/PermissionMiddleware.php';

// Role-based access: Only admin, super_admin, accountant can manage expenses
$userRole = Session::getUserRole();
if (!in_array($userRole, ['super_admin', 'admin', 'accountant', 'store_manager'])) {
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
            // Create new expense
            $validator = new Validator($_POST);
            $validator->required('category', 'Category is required');
            $validator->required('amount', 'Amount is required');
            $validator->required('expense_date', 'Expense date is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                // Generate expense number
                $expenseNumber = 'EXP-' . date('Y') . '-' . str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
                
                $query = "INSERT INTO expenses (expense_number, category, amount, expense_date, payment_method, vendor, description, receipt, status, created_by) 
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $id = $db->execute($query, [
                    $expenseNumber,
                    $data['category'] ?? 'General',
                    (float)$data['amount'],
                    $data['expense_date'],
                    $data['payment_method'] ?? null,
                    $data['vendor'] ?? null,
                    $data['description'] ?? null,
                    $data['receipt'] ?? null,
                    $data['status'] ?? 'pending',
                    $userId
                ]);
                
                Session::setFlash('Expense created successfully', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'update') {
            // Update existing expense
            $validator = new Validator($_POST);
            $validator->required('id', 'Expense ID is required');
            $validator->required('category', 'Category is required');
            $validator->required('amount', 'Amount is required');
            $validator->required('expense_date', 'Expense date is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "UPDATE expenses SET category = ?, amount = ?, expense_date = ?, 
                         payment_method = ?, vendor = ?, description = ?, receipt = ?, status = ? WHERE {$orgFilter} id = ?";
                $affected = $db->execute($query, [
                    $data['category'] ?? 'General',
                    (float)$data['amount'],
                    $data['expense_date'],
                    $data['payment_method'] ?? null,
                    $data['vendor'] ?? null,
                    $data['description'] ?? null,
                    $data['receipt'] ?? null,
                    $data['status'] ?? 'pending',
                    $data['id']
                ]);
                
                if ($affected > 0) {
                    Session::setFlash('Expense updated successfully', 'success');
                } else {
                    Session::setFlash('Expense not found or no changes made', 'error');
                }
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'delete') {
            // Delete expense
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $message = 'Expense ID is required';
                $messageType = 'error';
            } else {
                $affected = $db->execute("DELETE FROM expenses WHERE {$orgFilter} id = ?", [$id]);
                
                if ($affected > 0) {
                    Session::setFlash('Expense deleted successfully', 'success');
                } else {
                    Session::setFlash('Expense not found', 'error');
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
if ($flash) {
    $message = $flash['message'];
    $messageType = $flash['type'];
}

// Get expense for editing if edit_id is set
$editExpense = null;
$editId = $_GET['edit_id'] ?? null;
if ($editId) {
    $editExpense = $db->queryOne(
        "SELECT * FROM expenses WHERE {$orgFilter} id = ?", 
        [$editId]
    );
    if (!$editExpense) {
        Session::setFlash('Expense not found', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load all expenses with joins
$query = "SELECT e.*, 
          u.full_name as created_by_name,
          u2.full_name as approved_by_name
          FROM expenses e
          LEFT JOIN users u ON e.created_by = u.id
          LEFT JOIN users u2 ON e.approved_by = u2.id
          ORDER BY e.expense_date DESC, e.id DESC";
$expenses = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Expenses</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Expense Management</h1>
                        <button class="btn btn-primary" onclick="openModal('expenseModal', true)">
                            <span>➕</span> Add Expense
                        </button>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header"><h3 class="card-title">All Expenses</h3></div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Expense #</th>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Vendor</th>
                                    <th>Amount</th>
                                    <th>Payment Method</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($expenses)): ?>
                                    <tr>
                                        <td colspan="8" style="text-align:center;padding:40px;">
                                            No expenses found. Click "Add Expense" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($expenses as $expense): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($expense['expense_number'] ?? 'EXP-' . $expense['id']) ?></code></td>
                                            <td><?= date('Y-m-d', strtotime($expense['expense_date'])) ?></td>
                                            <td><?= htmlspecialchars($expense['category'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($expense['vendor'] ?? '-') ?></td>
                                            <td>₹<?= number_format((float)$expense['amount'], 2) ?></td>
                                            <td><?= htmlspecialchars($expense['payment_method'] ?? '-') ?></td>
                                            <td>
                                                <?php
                                                $statusClass = 'warning';
                                                if ($expense['status'] === 'approved') $statusClass = 'success';
                                                elseif ($expense['status'] === 'rejected') $statusClass = 'danger';
                                                ?>
                                                <span class="badge badge-<?= $statusClass ?>"><?= ucfirst($expense['status']) ?></span>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?edit_id=<?= $expense['id'] ?>" class="btn btn-ghost btn-sm" title="Edit" onclick="event.preventDefault(); editExpense(<?= htmlspecialchars(json_encode($editExpense ?? $expense)) ?>);">
                                                    ✏️
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $expense['id'] ?>">
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

    <!-- Add/Edit Expense Modal -->
    <div class="modal-backdrop" id="expenseModal" style="display:none;">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="expenseModalTitle">Add Expense</h3>
                <button class="modal-close" onclick="closeModal('expenseModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="expenseForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="expenseId">
                    <div class="form-group">
                        <label class="form-label required">Category</label>
                        <input type="text" name="category" id="expenseCategory" class="form-control" placeholder="e.g., Office Supplies, Travel, Utilities" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Vendor</label>
                        <input type="text" name="vendor" id="expenseVendor" class="form-control" placeholder="Vendor/Supplier name">
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Amount</label>
                        <input type="number" name="amount" id="expenseAmount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Expense Date</label>
                        <input type="date" name="expense_date" id="expenseDate" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <input type="text" name="payment_method" id="expensePaymentMethod" class="form-control" placeholder="e.g., Cash, Bank Transfer, Credit Card">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="expenseDescription" class="form-control" rows="3" placeholder="Enter description"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Receipt URL</label>
                        <input type="url" name="receipt" id="expenseReceipt" class="form-control" placeholder="https://example.com/receipt.jpg">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="expenseStatus" class="form-control">
                            <option value="pending" selected>Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('expenseModal')">Cancel</button>
                <button type="submit" form="expenseForm" class="btn btn-primary">Save Expense</button>
            </div>
        </div>
    </div>

    <!-- Minimal JavaScript for Modal Only -->
    <script>
        function openModal(modalId, isNew = false) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                if (isNew) {
                    document.getElementById('expenseForm').reset();
                    document.getElementById('formAction').value = 'create';
                    document.getElementById('expenseId').value = '';
                    document.getElementById('expenseDate').value = '<?= date('Y-m-d') ?>';
                    document.getElementById('expenseModalTitle').textContent = 'Add Expense';
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                document.getElementById('expenseForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('expenseId').value = '';
                document.getElementById('expenseModalTitle').textContent = 'Add Expense';
            }
        }

        function editExpense(expense) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('expenseId').value = expense.id;
            document.getElementById('expenseCategory').value = expense.category || '';
            document.getElementById('expenseVendor').value = expense.vendor || '';
            document.getElementById('expenseAmount').value = expense.amount || '';
            document.getElementById('expenseDate').value = expense.expense_date || '<?= date('Y-m-d') ?>';
            document.getElementById('expensePaymentMethod').value = expense.payment_method || '';
            document.getElementById('expenseDescription').value = expense.description || '';
            document.getElementById('expenseReceipt').value = expense.receipt || '';
            document.getElementById('expenseStatus').value = expense.status || 'pending';
            document.getElementById('expenseModalTitle').textContent = 'Edit Expense';
            openModal('expenseModal');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('expenseModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('expenseModal');
                    }
                });
            }
            <?php if ($editExpense): ?>
                editExpense(<?= json_encode($editExpense) ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>
