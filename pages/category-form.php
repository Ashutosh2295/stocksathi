<?php
/**
 * Category Form - Add/Edit Categories (server-side, no API)
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
$isEditMode = false;
$category = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $validator = new Validator($_POST);
        $validator->required($_POST['name'] ?? '', 'Category name');
        
        if ($validator->fails()) {
            $message = $validator->getFirstError();
            $messageType = 'error';
        } else {
            $data = Validator::sanitize($_POST);
            $data['parent_id'] = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
            
            if (!empty($_POST['id'])) {
                // Update
                $query = "UPDATE categories SET name = ?, description = ?, parent_id = ?, status = ? WHERE {$orgFilter} id = ?";
                $affected = $db->execute($query, [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['parent_id'],
                    $data['status'] ?? 'active',
                    (int)$_POST['id']
                ]);
                if ($affected > 0) {
                    Session::setFlash('Category updated successfully', 'success');
                    header('Location: ' . BASE_PATH . '/pages/categories.php');
                    exit;
                }
                $message = 'Category not found or no changes made';
                $messageType = 'error';
            } else {
                // Create
                $query = "INSERT INTO categories (name, description, parent_id, status) VALUES (?, ?, ?, ?)";
                $db->execute($query, [
                    $data['name'],
                    $data['description'] ?? null,
                    $data['parent_id'],
                    $data['status'] ?? 'active'
                ]);
                Session::setFlash('Category created successfully', 'success');
                header('Location: ' . BASE_PATH . '/pages/categories.php');
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

// Edit mode: load category
$categoryId = $_GET['id'] ?? null;
if ($categoryId) {
    $isEditMode = true;
    $category = $db->queryOne("SELECT * FROM categories WHERE {$orgFilter} id = ?", [$categoryId]);
    if (!$category) {
        Session::setFlash('Category not found', 'error');
        header('Location: ' . BASE_PATH . '/pages/categories.php');
        exit;
    }
}

// All categories for parent dropdown (exclude self when editing)
$parentOptions = $db->query("SELECT id, name FROM categories WHERE {$orgFilter} status = 'active' ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditMode ? 'Edit' : 'Add' ?> Category - Stocksathi</title>
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
                        <a href="<?= BASE_PATH ?>/pages/categories.php" class="breadcrumb-item">Categories</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active"><?= $isEditMode ? 'Edit' : 'Add' ?> Category</span>
                    </nav>
                    <h1 class="content-title"><?= $isEditMode ? 'Edit' : 'Add New' ?> Category</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="categoryForm">
                    <?php if ($isEditMode): ?>
                        <input type="hidden" name="id" value="<?= (int)$category['id'] ?>">
                    <?php endif; ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Category Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label required">Category Name</label>
                                <input type="text" name="name" class="form-control"
                                    placeholder="Enter category name" value="<?= htmlspecialchars($category['name'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control"
                                    placeholder="Category description" rows="3"><?= htmlspecialchars($category['description'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Parent Category</label>
                                <select name="parent_id" class="form-control">
                                    <option value="">None (Top Level)</option>
                                    <?php foreach ($parentOptions as $cat): ?>
                                        <?php if ($isEditMode && $cat['id'] == $category['id']) continue; ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($category['parent_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" <?= ($category['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($category['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 justify-end mt-6">
                        <a href="<?= BASE_PATH ?>/pages/categories.php" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Category</button>
                    </div>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
