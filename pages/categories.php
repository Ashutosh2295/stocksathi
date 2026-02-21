<?php
/**
 * Categories Management Page - Core PHP Version
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
            // Create new category
            $validator = new Validator($_POST);
            $validator->required($_POST['name'] ?? '', 'Category name');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "INSERT INTO categories (name, description, status) VALUES (?, ?, ?)";
                $id = $db->execute($query, [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['status'] ?? 'active'
                ]);
                
                Session::setFlash('Category created successfully', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'update') {
            // Update existing category
            $validator = new Validator($_POST);
            $validator->required($_POST['id'] ?? '', 'Category ID');
            $validator->required($_POST['name'] ?? '', 'Category name');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                $query = "UPDATE categories SET name = ?, description = ?, status = ? WHERE {$orgFilter} id = ?";
                $affected = $db->execute($query, [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['status'] ?? 'active',
                    $data['id']
                ]);
                
                if ($affected > 0) {
                    Session::setFlash('Category updated successfully', 'success');
                } else {
                    Session::setFlash('Category not found or no changes made', 'error');
                }
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'delete') {
            // Delete category
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                $message = 'Category ID is required';
                $messageType = 'error';
            } else {
                // Check if category exists
                $category = $db->queryOne("SELECT * FROM categories WHERE {$orgFilter} id = ?", [$id]);
                
                if (!$category) {
                    Session::setFlash('Category not found', 'error');
                } else {
                    // Check if category has products
                    $productCount = $db->queryOne(
                        "SELECT COUNT(*) as count FROM products WHERE {$orgFilter} category_id = ?",
                        [$id]
                    );
                    
                    if ($productCount['count'] > 0) {
                        Session::setFlash('Cannot delete category. It has ' . $productCount['count'] . ' product(s) associated with it.', 'error');
                    } else {
                        $affected = $db->execute("DELETE FROM categories WHERE {$orgFilter} id = ?", [$id]);
                        
                        if ($affected > 0) {
                            Session::setFlash('Category deleted successfully', 'success');
                        } else {
                            Session::setFlash('Failed to delete category', 'error');
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

// Get category for editing if edit_id is set
$editCategory = null;
$editId = $_GET['edit_id'] ?? null;
if ($editId) {
    $editCategory = $db->queryOne("SELECT * FROM categories WHERE {$orgFilter} id = ?", [$editId]);
    if (!$editCategory) {
        Session::setFlash('Category not found', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Load all categories with product counts
$query = "SELECT c.*, 
          COUNT(p.id) as product_count
          FROM categories c
          LEFT JOIN products p ON c.id = p.category_id
          " . ($orgIdPatch ? " WHERE c.organization_id = " . intval($orgIdPatch) . " " : "") . "
          GROUP BY c.id
          ORDER BY c.id DESC";
$categories = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Categories</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Category Management</h1>
                        <button class="btn btn-primary" onclick="openModal('categoryModal', true)">
                            <span>➕</span> Add Category
                        </button>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Categories Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">All Categories</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center" style="padding: 40px;">
                                            No categories found. Click "Add Category" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($category['name']) ?></td>
                                            <td><?= htmlspecialchars($category['description'] ?? '-') ?></td>
                                            <td><?= (int)$category['product_count'] ?></td>
                                            <td>
                                                <?php if ($category['status'] === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?edit_id=<?= $category['id'] ?>" class="btn btn-ghost btn-sm" title="Edit" onclick="event.preventDefault(); editCategory(<?= htmlspecialchars(json_encode($category)) ?>);">
                                                    ✏️
                                                </a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($category['name'])) ?>?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $category['id'] ?>">
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

    <!-- Add/Edit Category Modal -->
    <div class="modal-backdrop" id="categoryModal" style="display: none;">
        <div class="modal" style="max-width: 500px;">
            <div class="modal-header">
                <h3 class="modal-title" id="categoryModalTitle">Add Category</h3>
                <button class="modal-close" onclick="closeModal('categoryModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="categoryForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="categoryId">
                    <div class="form-group">
                        <label class="form-label required">Category Name</label>
                        <input type="text" name="name" id="categoryName" class="form-control" placeholder="Enter category name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="categoryDescription" class="form-control" placeholder="Enter description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="categoryStatus" class="form-control">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('categoryModal')">Cancel</button>
                <button type="submit" form="categoryForm" class="btn btn-primary">Save Category</button>
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
                    // Reset form for new category
                    document.getElementById('categoryForm').reset();
                    document.getElementById('formAction').value = 'create';
                    document.getElementById('categoryId').value = '';
                    document.getElementById('categoryModalTitle').textContent = 'Add Category';
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                // Reset form
                document.getElementById('categoryForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('categoryId').value = '';
                document.getElementById('categoryModalTitle').textContent = 'Add Category';
            }
        }

        function editCategory(category) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name || '';
            document.getElementById('categoryDescription').value = category.description || '';
            document.getElementById('categoryStatus').value = category.status || 'active';
            document.getElementById('categoryModalTitle').textContent = 'Edit Category';
            openModal('categoryModal');
        }

        // Close modal on backdrop click
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('categoryModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('categoryModal');
                    }
                });
            }
            
            // Auto-open modal if editing
            <?php if ($editCategory): ?>
                editCategory(<?= json_encode($editCategory) ?>);
            <?php endif; ?>
        });
    </script>
</body>

</html>
