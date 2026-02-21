<?php
/**
 * Suppliers Management Page - Core PHP Version
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
            // Create new supplier
            $validator = new Validator($_POST);
            $validator->required($_POST['name'] ?? '', 'Supplier name');
            $validator->required($_POST['email'] ?? '', 'Supplier email');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "INSERT INTO suppliers (name, email, phone, contact_person, address, city, state, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $id = $db->execute($query, [
                    $data['name'],
                    $data['email'],
                    $data['phone'] ?? null,
                    $data['contact_person'] ?? null,
                    $data['address'] ?? null,
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['status'] ?? 'active'
                ]);
                
                Session::setFlash('Supplier created successfully', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'update') {
            // Update existing supplier
            $validator = new Validator($_POST);
            $validator->required($_POST['id'] ?? '', 'Supplier ID');
            $validator->required($_POST['name'] ?? '', 'Supplier name');
            $validator->required($_POST['email'] ?? '', 'Supplier email');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "UPDATE suppliers SET name = ?, email = ?, phone = ?, contact_person = ?, address = ?, city = ?, state = ?, status = ? WHERE {$orgFilter} id = ?";
                $affected = $db->execute($query, [
                    $data['name'],
                    $data['email'],
                    $data['phone'] ?? null,
                    $data['contact_person'] ?? null,
                    $data['address'] ?? null,
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['status'] ?? 'active',
                    $data['id']
                ]);
                
                if ($affected > 0) {
                    Session::setFlash('Supplier updated successfully', 'success');
                } else {
                    Session::setFlash('Supplier not found or no changes made', 'error');
                }
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'delete') {
            // Delete supplier
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $message = 'Supplier ID is required';
                $messageType = 'error';
            } else {
                $affected = $db->execute("DELETE FROM suppliers WHERE {$orgFilter} id = ?", [$id]);
                
                if ($affected > 0) {
                    Session::setFlash('Supplier deleted successfully', 'success');
                } else {
                    Session::setFlash('Supplier not found', 'error');
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

// Get supplier for editing if edit_id is set
$editSupplier = null;
$editId = $_GET['edit_id'] ?? null;
if ($editId) {
    $editSupplier = $db->queryOne("SELECT * FROM suppliers WHERE {$orgFilter} id = ?", [$editId]);
    if (!$editSupplier) {
        Session::setFlash('Supplier not found', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load all suppliers
$suppliers = $db->query("SELECT * FROM suppliers {$orgWhere} ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suppliers - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Suppliers</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Supplier Management</h1>
                        <button class="btn btn-primary" onclick="openModal('supplierModal', true)">
                            <span>➕</span> Add Supplier
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
                    <div class="card-header"><h3 class="card-title">All Suppliers</h3></div>
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
                                <?php if (empty($suppliers)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;padding:40px;">
                                            No suppliers found. Click "Add Supplier" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($suppliers as $supplier): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($supplier['name']) ?></td>
                                            <td><?= htmlspecialchars($supplier['email'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($supplier['phone'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($supplier['city'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($supplier['status'] === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?edit_id=<?= $supplier['id'] ?>" class="btn btn-ghost btn-sm" title="Edit" onclick="event.preventDefault(); editSupplier(<?= htmlspecialchars(json_encode($supplier)) ?>);">
                                                    ✏️
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($supplier['name'])) ?>?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $supplier['id'] ?>">
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

    <!-- Add/Edit Supplier Modal -->
    <div class="modal-backdrop" id="supplierModal" style="display:none;">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="supplierModalTitle">Add Supplier</h3>
                <button class="modal-close" onclick="closeModal('supplierModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="supplierForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="supplierId">
                    <div class="form-group">
                        <label class="form-label required">Name</label>
                        <input type="text" name="name" id="supplierName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" id="supplierEmail" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" id="supplierPhone" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" id="supplierContactPerson" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Address</label>
                        <textarea name="address" id="supplierAddress" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" id="supplierCity" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" name="state" id="supplierState" class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="supplierStatus" class="form-control">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('supplierModal')">Cancel</button>
                <button type="submit" form="supplierForm" class="btn btn-primary">Save Supplier</button>
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
                    document.getElementById('supplierForm').reset();
                    document.getElementById('formAction').value = 'create';
                    document.getElementById('supplierId').value = '';
                    document.getElementById('supplierModalTitle').textContent = 'Add Supplier';
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                document.getElementById('supplierForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('supplierId').value = '';
                document.getElementById('supplierModalTitle').textContent = 'Add Supplier';
            }
        }

        function editSupplier(supplier) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('supplierId').value = supplier.id;
            document.getElementById('supplierName').value = supplier.name || '';
            document.getElementById('supplierEmail').value = supplier.email || '';
            document.getElementById('supplierPhone').value = supplier.phone || '';
            document.getElementById('supplierContactPerson').value = supplier.contact_person || '';
            document.getElementById('supplierAddress').value = supplier.address || '';
            document.getElementById('supplierCity').value = supplier.city || '';
            document.getElementById('supplierState').value = supplier.state || '';
            document.getElementById('supplierStatus').value = supplier.status || 'active';
            document.getElementById('supplierModalTitle').textContent = 'Edit Supplier';
            openModal('supplierModal');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('supplierModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('supplierModal');
                    }
                });
            }
            <?php if ($editSupplier): ?>
                editSupplier(<?= json_encode($editSupplier) ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>
