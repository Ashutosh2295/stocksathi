<?php
/**
 * Stores Management Page - Core PHP Version
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
            // Create new store
            $validator = new Validator($_POST);
            $validator->required('name', 'Store name is required');
            $validator->required('address', 'Address is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "INSERT INTO stores (name, code, address, city, state, pincode, phone, manager_id, status, organization_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $id = $db->execute($query, [
                    $data['name'],
                    $data['code'] ?? null,
                    $data['address'],
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['pincode'] ?? null,
                    $data['phone'] ?? null,
                    !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                    $data['status'] ?? 'active',
                    $orgIdPatch
                ]);
                
                Session::setFlash('Store created successfully', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'update') {
            // Update existing store
            $validator = new Validator($_POST);
            $validator->required('id', 'Store ID is required');
            $validator->required('name', 'Store name is required');
            $validator->required('address', 'Address is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "UPDATE stores SET name = ?, code = ?, address = ?, city = ?, state = ?, pincode = ?, phone = ?, manager_id = ?, status = ? WHERE {$orgFilter} id = ?";
                $affected = $db->execute($query, [
                    $data['name'],
                    $data['code'] ?? null,
                    $data['address'],
                    $data['city'] ?? null,
                    $data['state'] ?? null,
                    $data['pincode'] ?? null,
                    $data['phone'] ?? null,
                    !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                    $data['status'] ?? 'active',
                    $data['id']
                ]);
                
                if ($affected > 0) {
                    Session::setFlash('Store updated successfully', 'success');
                } else {
                    Session::setFlash('Store not found or no changes made', 'error');
                }
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'delete') {
            // Delete store
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $message = 'Store ID is required';
                $messageType = 'error';
            } else {
                $affected = $db->execute("DELETE FROM stores WHERE {$orgFilter} id = ?", [$id]);
                
                if ($affected > 0) {
                    Session::setFlash('Store deleted successfully', 'success');
                } else {
                    Session::setFlash('Store not found', 'error');
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

// Get store for editing if edit_id is set
$editStore = null;
$editId = $_GET['edit_id'] ?? null;
if ($editId) {
    $editStore = $db->queryOne(
        "SELECT s.*, u.full_name as manager_name 
         FROM stores s 
         LEFT JOIN users u ON s.manager_id = u.id 
         WHERE " . ($orgIdPatch ? "s.organization_id = " . intval($orgIdPatch) . " AND " : "") . "s.id = ?", 
        [$editId]
    );
    if (!$editStore) {
        Session::setFlash('Store not found', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load all stores with manager name join
$search = $_GET['search'] ?? '';
$query = "SELECT s.*, u.full_name as manager_name, u.email as manager_email 
          FROM stores s
          LEFT JOIN users u ON s.manager_id = u.id
          " . ($orgIdPatch ? " WHERE s.organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1");
$params = [];

if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.address LIKE ? OR s.city LIKE ? OR u.full_name LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= " ORDER BY s.id DESC";
$stores = $db->query($query, $params);

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
    <title>Stores - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Stores</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Stores</h1>
                        <button class="btn btn-primary" onclick="openModal('storeModal', true)">
                            <span>➕</span> Add Store
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
                            <input type="text" name="search" class="form-control" placeholder="Search stores..." value="<?= htmlspecialchars($search) ?>" style="flex: 1;">
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if ($search): ?>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-ghost">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Stores List</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Store Name</th>
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
                                <?php if (empty($stores)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center" style="padding: 40px;">
                                            No stores found. Click "Add Store" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($stores as $store): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($store['name']) ?></td>
                                            <td><?= htmlspecialchars($store['code'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($store['address'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($store['city'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($store['manager_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($store['phone'] ?? '-') ?></td>
                                            <td>
                                                <?php if ($store['status'] === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?edit_id=<?= $store['id'] ?>" class="btn btn-ghost btn-sm" title="Edit" onclick="event.preventDefault(); editStore(<?= htmlspecialchars(json_encode($store)) ?>);">
                                                    ✏️
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($store['name'])) ?>?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $store['id'] ?>">
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

    <!-- Add/Edit Store Modal -->
    <div class="modal-backdrop" id="storeModal" style="display:none;">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="storeModalTitle">Add Store</h3>
                <button class="modal-close" onclick="closeModal('storeModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="storeForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="storeId">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label required">Store Name</label>
                            <input type="text" name="name" id="storeName" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Code</label>
                            <input type="text" name="code" id="storeCode" class="form-control" placeholder="e.g., ST-001">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Address</label>
                        <textarea name="address" id="storeAddress" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="form-group">
                            <label class="form-label">City</label>
                            <input type="text" name="city" id="storeCity" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">State</label>
                            <input type="text" name="state" id="storeState" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Pincode</label>
                            <input type="text" name="pincode" id="storePincode" class="form-control">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" id="storePhone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Manager</label>
                            <select name="manager_id" id="storeManager" class="form-control">
                                <option value="">Select Manager</option>
                                <?php foreach ($managers as $manager): ?>
                                    <option value="<?= $manager['id'] ?>"><?= htmlspecialchars($manager['full_name']) ?> (<?= htmlspecialchars($manager['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="storeStatus" class="form-control">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('storeModal')">Cancel</button>
                <button type="submit" form="storeForm" class="btn btn-primary">Save Store</button>
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
                    document.getElementById('storeForm').reset();
                    document.getElementById('formAction').value = 'create';
                    document.getElementById('storeId').value = '';
                    document.getElementById('storeModalTitle').textContent = 'Add Store';
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                document.getElementById('storeForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('storeId').value = '';
                document.getElementById('storeModalTitle').textContent = 'Add Store';
            }
        }

        function editStore(store) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('storeId').value = store.id;
            document.getElementById('storeName').value = store.name || '';
            document.getElementById('storeCode').value = store.code || '';
            document.getElementById('storeAddress').value = store.address || '';
            document.getElementById('storeCity').value = store.city || '';
            document.getElementById('storeState').value = store.state || '';
            document.getElementById('storePincode').value = store.pincode || '';
            document.getElementById('storePhone').value = store.phone || '';
            document.getElementById('storeManager').value = store.manager_id || '';
            document.getElementById('storeStatus').value = store.status || 'active';
            document.getElementById('storeModalTitle').textContent = 'Edit Store';
            openModal('storeModal');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('storeModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('storeModal');
                    }
                });
            }
            <?php if ($editStore): ?>
                editStore(<?= json_encode($editStore) ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>
