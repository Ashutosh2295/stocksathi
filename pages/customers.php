<?php
/**
 * Customers Management Page - Core PHP Version
 * Stocksathi Inventory System
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
    
    try {
        if ($action === 'create') {
            // Create new customer
            $validator = new Validator($_POST);
            $validator->required('name', 'Customer name is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "INSERT INTO customers (name, email, phone, address, city, state, status, organization_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $id = $db->execute($query, [
                    $data['name'],
                    $data['email'] ?? null,
                    $data['phone'] ?? null,
                    $data['address'] ?? null,
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['status'] ?? 'active',
                    $orgIdPatch
                ]);
                
                Session::setFlash('Customer created successfully', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'update') {
            // Update existing customer
            $validator = new Validator($_POST);
            $validator->required('id', 'Customer ID is required');
            $validator->required('name', 'Customer name is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "UPDATE customers SET name = ?, email = ?, phone = ?, address = ?, city = ?, state = ?, status = ? WHERE {$orgFilter} id = ?";
                $affected = $db->execute($query, [
                    $data['name'],
                    $data['email'] ?? null,
                    $data['phone'] ?? null,
                    $data['address'] ?? null,
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['status'] ?? 'active',
                    $data['id']
                ]);
                
                if ($affected > 0) {
                    Session::setFlash('Customer updated successfully', 'success');
                } else {
                    Session::setFlash('Customer not found or no changes made', 'error');
                }
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'delete') {
            // Delete customer
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $message = 'Customer ID is required';
                $messageType = 'error';
            } else {
                $affected = $db->execute("DELETE FROM customers WHERE {$orgFilter} id = ?", [$id]);
                
                if ($affected > 0) {
                    Session::setFlash('Customer deleted successfully', 'success');
                } else {
                    Session::setFlash('Customer not found', 'error');
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

// Get customer for editing if edit_id is set
$editCustomer = null;
$editId = $_GET['edit_id'] ?? null;
if ($editId) {
    $editCustomer = $db->queryOne("SELECT * FROM customers WHERE {$orgFilter} id = ?", [$editId]);
    if (!$editCustomer) {
        Session::setFlash('Customer not found', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load all customers
$customers = $db->query("SELECT * FROM customers {$orgWhere} ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Customers</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Customer Management</h1>
                        <button class="btn btn-primary" onclick="openModal('customerModal', true)">
                            <span>➕</span> Add Customer
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
                    <div class="card-header"><h3 class="card-title">All Customers</h3></div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>City</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;padding:40px;">
                                            No customers found. Click "Add Customer" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($customer['name']) ?></td>
                                            <td><?= htmlspecialchars($customer['email'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($customer['phone'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($customer['city'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($customer['status'] === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?edit_id=<?= $customer['id'] ?>" class="btn btn-ghost btn-sm" title="Edit" onclick="event.preventDefault(); editCustomer(<?= htmlspecialchars(json_encode($customer)) ?>);">
                                                    ✏️
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($customer['name'])) ?>?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $customer['id'] ?>">
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

    <!-- Add/Edit Customer Modal -->
    <div class="modal-backdrop" id="customerModal" style="display:none;">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="customerModalTitle">Add Customer</h3>
                <button class="modal-close" onclick="closeModal('customerModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="customerForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="customerId">
                    <div class="form-group">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" id="customerName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="customerEmail" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" id="customerPhone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="customerAddress" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" id="customerCity" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" name="state" id="customerState" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="customerStatus" class="form-control">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('customerModal')">Cancel</button>
                <button type="submit" form="customerForm" class="btn btn-primary">Save Customer</button>
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
                    document.getElementById('customerForm').reset();
                    document.getElementById('formAction').value = 'create';
                    document.getElementById('customerId').value = '';
                    document.getElementById('customerModalTitle').textContent = 'Add Customer';
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                document.getElementById('customerForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('customerId').value = '';
                document.getElementById('customerModalTitle').textContent = 'Add Customer';
            }
        }

        function editCustomer(customer) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('customerId').value = customer.id;
            document.getElementById('customerName').value = customer.name || '';
            document.getElementById('customerEmail').value = customer.email || '';
            document.getElementById('customerPhone').value = customer.phone || '';
            document.getElementById('customerAddress').value = customer.address || '';
            document.getElementById('customerCity').value = customer.city || '';
            document.getElementById('customerState').value = customer.state || '';
            document.getElementById('customerStatus').value = customer.status || 'active';
            document.getElementById('customerModalTitle').textContent = 'Edit Customer';
            openModal('customerModal');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('customerModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('customerModal');
                    }
                });
            }
            <?php if ($editCustomer): ?>
                editCustomer(<?= json_encode($editCustomer) ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>
