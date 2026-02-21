<?php
/**
 * Store Form Page - Core PHP Version
 * Add/Edit Stores using core PHP concepts
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
$isEditMode = false;
$store = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $validator = new Validator($_POST);
        $validator->required('name', 'Store name is required');
        $validator->required('location', 'Location is required');
        
        if ($validator->fails()) {
            $message = $validator->getFirstError();
            $messageType = 'error';
        } else {
            $data = Validator::sanitize($_POST);
            
            if (!empty($_POST['id'])) {
                // Update existing store
                $query = "UPDATE stores SET name = ?, location = ?, manager_name = ?, contact = ?, email = ?, status = ? WHERE {$orgFilter} id = ?";
                $affected = $db->execute($query, [
                    $data['name'],
                    $data['location'],
                    $data['manager_name'] ?? null,
                    $data['contact'] ?? null,
                    $data['email'] ?? null,
                    $data['status'] ?? 'active',
                    $_POST['id']
                ]);
                
                if ($affected > 0) {
                    Session::setFlash('Store updated successfully', 'success');
                    header('Location: ' . BASE_PATH . '/pages/stores.php');
                    exit;
                } else {
                    $message = 'Store not found or no changes made';
                    $messageType = 'error';
                }
            } else {
                // Create new store
                $query = "INSERT INTO stores (name, location, manager_name, contact, email, status) VALUES (?, ?, ?, ?, ?, ?)";
                $id = $db->execute($query, [
                    $data['name'],
                    $data['location'],
                    $data['manager_name'] ?? null,
                    $data['contact'] ?? null,
                    $data['email'] ?? null,
                    $data['status'] ?? 'active'
                ]);
                
                Session::setFlash('Store created successfully', 'success');
                header('Location: ' . BASE_PATH . '/pages/stores.php');
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

// Check if editing
$storeId = $_GET['id'] ?? null;
if ($storeId) {
    $isEditMode = true;
    $store = $db->queryOne("SELECT * FROM stores WHERE {$orgFilter} id = ?", [$storeId]);
    
    if (!$store) {
        Session::setFlash('Store not found', 'error');
        header('Location: ' . BASE_PATH . '/pages/stores.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditMode ? 'Edit' : 'Add' ?> Store - Stocksathi</title>
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
                        <a href="<?= BASE_PATH ?>/pages/stores.php" class="breadcrumb-item">Stores</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active"><?= $isEditMode ? 'Edit' : 'Add' ?> Store</span>
                    </nav>
                    <h1 class="content-title"><?= $isEditMode ? 'Edit' : 'Add New' ?> Store</h1>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="storeForm">
                    <?php if ($isEditMode): ?>
                        <input type="hidden" name="id" value="<?= $store['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Store Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label class="form-label required">Store Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Enter store name" 
                                       value="<?= htmlspecialchars($store['name'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label required">Location</label>
                                <input type="text" name="location" class="form-control" placeholder="Enter location" 
                                       value="<?= htmlspecialchars($store['location'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Manager Name</label>
                                <input type="text" name="manager_name" class="form-control" placeholder="Enter manager name" 
                                       value="<?= htmlspecialchars($store['manager_name'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Contact</label>
                                <input type="tel" name="contact" class="form-control" placeholder="+91 98765 43210" 
                                       value="<?= htmlspecialchars($store['contact'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="store@example.com" 
                                       value="<?= htmlspecialchars($store['email'] ?? '') ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" <?= ($store['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= ($store['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-4 justify-end mt-6">
                        <a href="<?= BASE_PATH ?>/pages/stores.php" class="btn btn-ghost">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Store</button>
                    </div>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
