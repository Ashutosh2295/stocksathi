<?php
/**
 * Quotations Management Page - Core PHP Version
 * Sales & Billing Module
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
$userId = Session::getUserId();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create' || $action === 'update') {
            // Validate quotation data
            $validator = new Validator($_POST);
            $validator->required('customer_id', 'Customer is required');
            $validator->required('quotation_date', 'Quotation date is required');
            $validator->required('valid_until', 'Valid until date is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                // Calculate totals
                $subtotal = (float)($data['subtotal'] ?? 0);
                $taxAmount = (float)($data['tax_amount'] ?? 0);
                $discountAmount = (float)($data['discount_amount'] ?? 0);
                $totalAmount = $subtotal + $taxAmount - $discountAmount;
                
                $db->beginTransaction();
                
                try {
                    if ($action === 'create') {
                        // Generate quotation number
                        $lastQuot = $db->queryOne("SELECT quotation_number FROM quotations " . ($orgIdPatch ? "WHERE organization_id = " . intval($orgIdPatch) : "") . " ORDER BY id DESC LIMIT 1");
                        $lastNum = $lastQuot ? (int)substr($lastQuot['quotation_number'], 3) : 0;
                        $quotNumber = 'QT-' . str_pad($lastNum + 1, 4, '0', STR_PAD_LEFT);
                        
                        // Insert quotation
                        $query = "INSERT INTO quotations (quotation_number, customer_id, quotation_date, valid_until, subtotal, tax_amount, discount_amount, total_amount, status, notes, created_by, organization_id) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $quotId = $db->execute($query, [
                            $quotNumber,
                            $data['customer_id'],
                            $data['quotation_date'],
                            $data['valid_until'],
                            $subtotal,
                            $taxAmount,
                            $discountAmount,
                            $totalAmount,
                            $data['status'] ?? 'pending',
                            $data['notes'] ?? null,
                            $userId,
                            $orgIdPatch
                        ]);
                        
                        $db->commit();
                        Session::setFlash('Quotation created successfully', 'success');
                    } else {
                        // Update quotation
                        $query = "UPDATE quotations SET customer_id = ?, quotation_date = ?, valid_until = ?, subtotal = ?, tax_amount = ?, discount_amount = ?, total_amount = ?, status = ?, notes = ? WHERE {$orgFilter} id = ?";
                        $db->execute($query, [
                            $data['customer_id'],
                            $data['quotation_date'],
                            $data['valid_until'],
                            $subtotal,
                            $taxAmount,
                            $discountAmount,
                            $totalAmount,
                            $data['status'] ?? 'pending',
                            $data['notes'] ?? null,
                            $data['id']
                        ]);
                        
                        $db->commit();
                        Session::setFlash('Quotation updated successfully', 'success');
                    }
                    
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
                }
            }
            
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $message = 'Quotation ID is required';
                $messageType = 'error';
            } else {
                $db->beginTransaction();
                try {
                    // Delete quotation items first
                    $db->execute("DELETE FROM quotation_items WHERE quotation_id = ?", [$id]);
                    // Delete quotation
                    $affected = $db->execute("DELETE FROM quotations WHERE {$orgFilter} id = ?", [$id]);
                    
                    $db->commit();
                    
                    if ($affected > 0) {
                        Session::setFlash('Quotation deleted successfully', 'success');
                    } else {
                        Session::setFlash('Quotation not found', 'error');
                    }
                } catch (Exception $e) {
                    $db->rollBack();
                    throw $e;
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

// Get flash message
$flash = Session::getFlash();
if ($flash) {
    $message = $flash['message'];
    $messageType = $flash['type'];
}

// Get quotation for editing
$editQuotation = null;
$editId = $_GET['edit_id'] ?? null;
if ($editId) {
    $editQuotation = $db->queryOne("SELECT * FROM quotations WHERE {$orgFilter} id = ?", [$editId]);
    if (!$editQuotation) {
        Session::setFlash('Quotation not found', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load all quotations
try {
    $query = "SELECT q.*, c.name as customer_name 
              FROM quotations q 
              LEFT JOIN customers c ON q.customer_id = c.id 
              " . ($orgIdPatch ? " WHERE q.organization_id = " . intval($orgIdPatch) : "") . "
              ORDER BY q.created_at DESC";
    $quotations = $db->query($query);
} catch (Exception $e) {
    $quotations = [];
    error_log("Quotations Query Error: " . $e->getMessage());
}

// Load customers for dropdown
try {
    $customers = $db->query("SELECT id, name FROM customers WHERE {$orgFilter} status = 'active' ORDER BY name");
} catch (Exception $e) {
    $customers = [];
    error_log("Customers Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotations - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Quotations</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Quotations</h1>
                        <button class="btn btn-primary" onclick="openModal('quotationModal', true)">
                            <span>➕</span> Create Quotation
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
                    <div class="card-header">
                        <h3 class="card-title">Quotations List</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Quotation #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Valid Until</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($quotations)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center" style="padding: 40px;">
                                            No quotations found. Click "Create Quotation" to add one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($quotations as $quot): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($quot['quotation_number']) ?></code></td>
                                            <td><?= htmlspecialchars($quot['customer_name'] ?? 'N/A') ?></td>
                                            <td><?= date('Y-m-d', strtotime($quot['quotation_date'])) ?></td>
                                            <td><?= date('Y-m-d', strtotime($quot['valid_until'])) ?></td>
                                            <td>₹<?= number_format($quot['total_amount'], 2) ?></td>
                                            <td>
                                                <?php if ($quot['status'] === 'approved'): ?>
                                                    <span class="badge badge-success">Approved</span>
                                                <?php elseif ($quot['status'] === 'rejected'): ?>
                                                    <span class="badge badge-danger">Rejected</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?edit_id=<?= $quot['id'] ?>" class="btn btn-ghost btn-sm" title="Edit" onclick="event.preventDefault(); editQuotation(<?= htmlspecialchars(json_encode($quot)) ?>);">
                                                    ✏️
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this quotation?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $quot['id'] ?>">
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

    <!-- Add/Edit Quotation Modal -->
    <div class="modal-backdrop" id="quotationModal" style="display:none;">
        <div class="modal" style="max-width:700px;">
            <div class="modal-header">
                <h3 class="modal-title" id="quotationModalTitle">Create Quotation</h3>
                <button class="modal-close" onclick="closeModal('quotationModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="quotationForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="quotationId">
                    <div class="form-group">
                        <label class="form-label required">Customer</label>
                        <select name="customer_id" id="customerId" class="form-control" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label required">Quotation Date</label>
                            <input type="date" name="quotation_date" id="quotationDate" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Valid Until</label>
                            <input type="date" name="valid_until" id="validUntil" class="form-control" required value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="form-group">
                            <label class="form-label">Subtotal</label>
                            <input type="number" step="0.01" name="subtotal" id="subtotal" class="form-control" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tax Amount</label>
                            <input type="number" step="0.01" name="tax_amount" id="taxAmount" class="form-control" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Discount</label>
                            <input type="number" step="0.01" name="discount_amount" id="discountAmount" class="form-control" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('quotationModal')">Cancel</button>
                <button type="submit" form="quotationForm" class="btn btn-primary">Save Quotation</button>
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
                    document.getElementById('quotationForm').reset();
                    document.getElementById('formAction').value = 'create';
                    document.getElementById('quotationId').value = '';
                    document.getElementById('quotationModalTitle').textContent = 'Create Quotation';
                    document.getElementById('quotationDate').value = '<?= date('Y-m-d') ?>';
                    document.getElementById('validUntil').value = '<?= date('Y-m-d', strtotime('+30 days')) ?>';
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

        function editQuotation(quotation) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('quotationId').value = quotation.id;
            document.getElementById('customerId').value = quotation.customer_id || '';
            document.getElementById('quotationDate').value = quotation.quotation_date || '';
            document.getElementById('validUntil').value = quotation.valid_until || '';
            document.getElementById('subtotal').value = quotation.subtotal || '';
            document.getElementById('taxAmount').value = quotation.tax_amount || '';
            document.getElementById('discountAmount').value = quotation.discount_amount || '';
            document.getElementById('status').value = quotation.status || 'pending';
            document.getElementById('notes').value = quotation.notes || '';
            document.getElementById('quotationModalTitle').textContent = 'Edit Quotation';
            openModal('quotationModal');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('quotationModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('quotationModal');
                    }
                });
            }
            <?php if ($editQuotation): ?>
                editQuotation(<?= json_encode($editQuotation) ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>