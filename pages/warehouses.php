<?php
/**
 * Warehouses Management Page - Core PHP Version
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
            // Create new warehouse
            $validator = new Validator($_POST);
            $validator->required('name', 'Warehouse name is required');
            $validator->required('address', 'Address is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "INSERT INTO warehouses (name, code, address, city, state, pincode, phone, manager_id, capacity, status, organization_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $id = $db->execute($query, [
                    $data['name'],
                    $data['code'] ?? null,
                    $data['address'],
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['pincode'] ?? null,
                    $data['phone'] ?? null,
                    !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                    !empty($data['capacity']) ? (int)$data['capacity'] : null,
                    $data['status'] ?? 'active',
                    $orgIdPatch
                ]);
                
                Session::setFlash('Warehouse created successfully', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'update') {
            // Update existing warehouse
            $validator = new Validator($_POST);
            $validator->required('id', 'Warehouse ID is required');
            $validator->required('name', 'Warehouse name is required');
            $validator->required('address', 'Address is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "UPDATE warehouses SET name = ?, code = ?, address = ?, city = ?, state = ?, pincode = ?, phone = ?, manager_id = ?, capacity = ?, status = ? WHERE {$orgFilter} id = ?";
                $affected = $db->execute($query, [
                    $data['name'],
                    $data['code'] ?? null,
                    $data['address'],
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['pincode'] ?? null,
                    $data['phone'] ?? null,
                    !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                    !empty($data['capacity']) ? (int)$data['capacity'] : null,
                    $data['status'] ?? 'active',
                    $data['id']
                ]);
                
                if ($affected > 0) {
                    Session::setFlash('Warehouse updated successfully', 'success');
                } else {
                    Session::setFlash('Warehouse not found or no changes made', 'error');
                }
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'delete') {
            // Delete warehouse
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $message = 'Warehouse ID is required';
                $messageType = 'error';
            } else {
                $affected = $db->execute("DELETE FROM warehouses WHERE {$orgFilter} id = ?", [$id]);
                
                if ($affected > 0) {
                    Session::setFlash('Warehouse deleted successfully', 'success');
                } else {
                    Session::setFlash('Warehouse not found', 'error');
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

// Get warehouse for editing if edit_id is set
$editWarehouse = null;
$editId = $_GET['edit_id'] ?? null;
if ($editId) {
    $editWarehouse = $db->queryOne(
        "SELECT w.*, u.full_name as manager_name 
         FROM warehouses w 
         LEFT JOIN users u ON w.manager_id = u.id 
         WHERE " . ($orgIdPatch ? "w.organization_id = " . intval($orgIdPatch) . " AND " : "") . "w.id = ?", 
        [$editId]
    );
    if (!$editWarehouse) {
        Session::setFlash('Warehouse not found', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load all warehouses with manager name join
// NOTE: Using manager_id (foreign key) not manager_name (column doesn't exist)
$search = $_GET['search'] ?? '';
$warehouses = [];

try {
    // Query uses JOIN to get manager name from users table
    $query = "SELECT w.*, u.full_name as manager_name, u.email as manager_email 
              FROM warehouses w
              LEFT JOIN users u ON w.manager_id = u.id
              " . ($orgIdPatch ? " WHERE w.organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1");
    $params = [];

    if (!empty($search)) {
        // Search uses u.full_name (from joined table), NOT manager_name column
        $query .= " AND (w.name LIKE ? OR w.address LIKE ? OR w.city LIKE ? OR u.full_name LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    $query .= " ORDER BY w.id DESC";
    $warehouses = $db->query($query, $params);
} catch (Exception $e) {
    error_log("Warehouses Query Error: " . $e->getMessage() . " | Query: " . $query);
    $message = 'Error loading warehouses: ' . $e->getMessage();
    $messageType = 'error';
    $warehouses = [];
}

// Load users for manager dropdown (employees/managers only)
try {
    $managers = $db->query("SELECT id, full_name, email FROM users WHERE {$orgFilter} role IN ('admin', 'store_manager') AND status = 'active' ORDER BY full_name");
} catch (Exception $e) {
    $managers = [];
    error_log("Managers Query Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouses - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Warehouses</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Warehouses</h1>
                        <button class="btn btn-primary" onclick="openModal('warehouseModal', true)">
                            <span>➕</span> Add Warehouse
                        </button>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <!-- Search -->
                <div class="card mb-6">
                    <div class="card-body">
                        <form method="GET" action="" style="display: flex; gap: 10px;">
                            <input type="text" name="search" class="form-control" placeholder="Search warehouses..." value="<?= htmlspecialchars($search) ?>" style="flex: 1;">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if ($search): ?>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-ghost">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Warehouses List</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Code</th>
                                    <th>Address</th>
                                    <th>City</th>
                                    <th>Manager</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($warehouses)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center" style="padding: 40px;">
                                            No warehouses found. Click "Add Warehouse" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($warehouses as $warehouse): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($warehouse['name']) ?></td>
                                            <td><?= htmlspecialchars($warehouse['code'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($warehouse['address'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($warehouse['city'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($warehouse['manager_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($warehouse['phone'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($warehouse['status'] === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?edit_id=<?= $warehouse['id'] ?>" class="btn btn-ghost btn-sm" title="Edit" onclick="event.preventDefault(); editWarehouse(<?= htmlspecialchars(json_encode($warehouse)) ?>);">
                                                    ✏️
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($warehouse['name'])) ?>?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $warehouse['id'] ?>">
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

    <!-- Add/Edit Warehouse Modal -->
    <div class="modal-backdrop" id="warehouseModal" style="display:none;">
        <div class="modal" style="max-width:700px;">
            <div class="modal-header">
                <h3 class="modal-title" id="warehouseModalTitle">Add Warehouse</h3>
                <button class="modal-close" onclick="closeModal('warehouseModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="warehouseForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="warehouseId">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label required">Warehouse Name</label>
                            <input type="text" name="name" id="warehouseName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" id="warehouseCode" class="form-control" placeholder="e.g., WH-001">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Address</label>
                        <textarea name="address" id="warehouseAddress" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" id="warehouseCity" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" name="state" id="warehouseState" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="pincode" id="warehousePincode" class="form-control">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" id="warehousePhone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Manager</label>
                            <select name="manager_id" id="warehouseManager" class="form-control">
                                <option value="">Select Manager</option>
                                <?php foreach ($managers as $manager): ?>
                                    <option value="<?= $manager['id'] ?>"><?= htmlspecialchars($manager['full_name']) ?> (<?= htmlspecialchars($manager['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Capacity</label>
                            <input type="text" name="capacity" id="warehouseCapacity" class="form-control" placeholder="e.g., 10,000 sq ft">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" id="warehouseStatus" class="form-control">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('warehouseModal')">Cancel</button>
                <button type="submit" form="warehouseForm" class="btn btn-primary">Save Warehouse</button>
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
                    document.getElementById('warehouseForm').reset();
                    document.getElementById('formAction').value = 'create';
                    document.getElementById('warehouseId').value = '';
                    document.getElementById('warehouseModalTitle').textContent = 'Add Warehouse';
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                document.getElementById('warehouseForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('warehouseId').value = '';
                document.getElementById('warehouseModalTitle').textContent = 'Add Warehouse';
            }
        }

        function editWarehouse(warehouse) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('warehouseId').value = warehouse.id;
            document.getElementById('warehouseName').value = warehouse.name || '';
            document.getElementById('warehouseCode').value = warehouse.code || '';
            document.getElementById('warehouseAddress').value = warehouse.address || '';
            document.getElementById('warehouseCity').value = warehouse.city || '';
            document.getElementById('warehouseState').value = warehouse.state || '';
            document.getElementById('warehousePincode').value = warehouse.pincode || '';
            document.getElementById('warehousePhone').value = warehouse.phone || '';
            document.getElementById('warehouseManager').value = warehouse.manager_id || '';
            document.getElementById('warehouseCapacity').value = warehouse.capacity || '';
            document.getElementById('warehouseStatus').value = warehouse.status || 'active';
            document.getElementById('warehouseModalTitle').textContent = 'Edit Warehouse';
            openModal('warehouseModal');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('warehouseModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('warehouseModal');
                    }
                });
            }
            <?php if ($editWarehouse): ?>
                editWarehouse(<?= json_encode($editWarehouse) ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>