<?php
/**
 * Brands Management Page - Core PHP Version
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
            // Create new brand
            $validator = new Validator($_POST);
            $validator->required('name', 'Brand name is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "INSERT INTO brands (name, description, status) VALUES (?, ?, ?)";
                $id = $db->execute($query, [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['status'] ?? 'active'
                ]);
                
                Session::setFlash('Brand created successfully', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'update') {
            // Update existing brand
            $validator = new Validator($_POST);
            $validator->required('id', 'Brand ID is required');
            $validator->required('name', 'Brand name is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "UPDATE brands SET name = ?, description = ?, status = ? WHERE {$orgFilter} id = ?";
                $affected = $db->execute($query, [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['status'] ?? 'active',
                    $data['id']
                ]);
                
                if ($affected > 0) {
                    Session::setFlash('Brand updated successfully', 'success');
                } else {
                    Session::setFlash('Brand not found or no changes made', 'error');
                }
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'delete') {
            // Delete brand
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $message = 'Brand ID is required';
                $messageType = 'error';
            } else {
                // Check if brand exists
                $brand = $db->queryOne("SELECT * FROM brands WHERE {$orgFilter} id = ?", [$id]);
                
                if (!$brand) {
                    Session::setFlash('Brand not found', 'error');
                } else {
                    // Check if brand has products
                    $productCount = $db->queryOne(
                        "SELECT COUNT(*) as count FROM products WHERE {$orgFilter} brand_id = ?",
                        [$id]
                    );
                    
                    if ($productCount['count'] > 0) {
                        Session::setFlash('Cannot delete brand. It has ' . $productCount['count'] . ' product(s) associated with it.', 'error');
                    } else {
                        $affected = $db->execute("DELETE FROM brands WHERE {$orgFilter} id = ?", [$id]);
                        
                        if ($affected > 0) {
                            Session::setFlash('Brand deleted successfully', 'success');
                        } else {
                            Session::setFlash('Failed to delete brand', 'error');
                        }
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
if ($flash) {
    $message = $flash['message'];
    $messageType = $flash['type'];
}

// Get brand for editing if edit_id is set
$editBrand = null;
$editId = $_GET['edit_id'] ?? null;
if ($editId) {
    $editBrand = $db->queryOne("SELECT * FROM brands WHERE {$orgFilter} id = ?", [$editId]);
    if (!$editBrand) {
        Session::setFlash('Brand not found', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load all brands with product counts
$query = "SELECT b.*, 
          COUNT(p.id) as product_count
          FROM brands b
          LEFT JOIN products p ON b.id = p.brand_id
          " . ($orgIdPatch ? " WHERE b.organization_id = " . intval($orgIdPatch) . " " : "") . "
          GROUP BY b.id
          ORDER BY b.id DESC";
$brands = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brands - Stocksathi</title>
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
                        <a href="<?= BASE_PATH ?>/index.php" class="breadcrumb-item">Home</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active">Brands</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Brand Management</h1>
                        <button class="btn btn-primary" onclick="openModal('brandModal', true)">
                            <span>➕</span> Add Brand
                        </button>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Brands Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Brands</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Brand Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($brands)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center" style="padding: 40px;">
                                            No brands found. Click "Add Brand" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($brands as $brand): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($brand['name']) ?></td>
                                            <td><?= htmlspecialchars($brand['description'] ?? '-') ?></td>
                                            <td><?= (int)$brand['product_count'] ?></td>
                                            <td>
                                                <?php if ($brand['status'] === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?edit_id=<?= $brand['id'] ?>" class="btn btn-ghost btn-sm" title="Edit" onclick="event.preventDefault(); editBrand(<?= htmlspecialchars(json_encode($brand)) ?>);">
                                                    ✏️
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($brand['name'])) ?>?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $brand['id'] ?>">
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

    <!-- Add/Edit Brand Modal -->
    <div class="modal-backdrop" id="brandModal" style="display: none;">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title" id="brandModalTitle">Add Brand</h3>
                <button class="modal-close" onclick="closeModal('brandModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="brandForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="brandId">
                    <div class="form-group">
                        <label class="form-label required">Brand Name</label>
                        <input type="text" name="name" id="brandName" class="form-control" placeholder="Enter brand name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="brandDescription" class="form-control" placeholder="Enter description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="brandStatus" class="form-control">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('brandModal')">Cancel</button>
                <button type="submit" form="brandForm" class="btn btn-primary">Save Brand</button>
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
                    // Reset form for new brand
                    document.getElementById('brandForm').reset();
                    document.getElementById('formAction').value = 'create';
                    document.getElementById('brandId').value = '';
                    document.getElementById('brandModalTitle').textContent = 'Add Brand';
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                // Reset form
                document.getElementById('brandForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('brandId').value = '';
                document.getElementById('brandModalTitle').textContent = 'Add Brand';
            }
        }

        function editBrand(brand) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('brandId').value = brand.id;
            document.getElementById('brandName').value = brand.name || '';
            document.getElementById('brandDescription').value = brand.description || '';
            document.getElementById('brandStatus').value = brand.status || 'active';
            document.getElementById('brandModalTitle').textContent = 'Edit Brand';
            openModal('brandModal');
        }

        // Close modal on backdrop click
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('brandModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('brandModal');
                    }
                });
            }
            
            // Auto-open modal if editing
            <?php if ($editBrand): ?>
                editBrand(<?= json_encode($editBrand) ?>);
            <?php endif; ?>
        });
    </script>
</body>

</html>
