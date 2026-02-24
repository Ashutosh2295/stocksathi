<?php
/**
 * Department Form Page - Add/Edit Department
 * Core PHP Version
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
$department = null;

// Get department ID if editing
$departmentId = $_GET['id'] ?? null;
if ($departmentId) {
    $department = $db->queryOne("SELECT * FROM departments WHERE {$orgFilter} id = ?", [$departmentId]);
    if ($department) {
        $isEditMode = true;
    } else {
        Session::setFlash('Department not found', 'error');
        header('Location: departments.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ($isEditMode ? 'update' : 'create');
    
    try {
        $validator = new Validator($_POST);
        $validator->required('name', 'Department name is required');
        
        if ($validator->fails()) {
            $message = $validator->getFirstError();
            $messageType = 'error';
        } else {
            $data = Validator::sanitize($_POST);
            
            if ($action === 'create') {
                // Check if department name already exists
                $existing = $db->queryOne("SELECT id FROM departments WHERE {$orgFilter} name = ?", [$data['name']]);
                if ($existing) {
                    $message = 'Department name already exists';
                    $messageType = 'error';
                } else {
                    $query = "INSERT INTO departments (name, code, description, manager_id, status, organization_id) VALUES (?, ?, ?, ?, ?, ?)";
                    $id = $db->execute($query, [
                        $data['name'],
                        $data['code'] ?? null,
                        $data['description'] ?? null,
                        !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                        $data['status'] ?? 'active',
                        $orgIdPatch
                    ]);
                    
                    Session::setFlash('Department created successfully', 'success');
                    header('Location: departments.php');
                    exit;
                }
            } elseif ($action === 'update') {
                // Check if department name exists for another department
                $existing = $db->queryOne("SELECT id FROM departments WHERE {$orgFilter} name = ? AND id != ?", [$data['name'], $departmentId]);
                if ($existing) {
                    $message = 'Department name already exists for another department';
                    $messageType = 'error';
                } else {
                    $query = "UPDATE departments SET name = ?, code = ?, description = ?, manager_id = ?, status = ? WHERE {$orgFilter} id = ?";
                    $affected = $db->execute($query, [
                        $data['name'],
                        $data['code'] ?? null,
                        $data['description'] ?? null,
                        !empty($data['manager_id']) ? (int)$data['manager_id'] : null,
                        $data['status'] ?? 'active',
                        $departmentId
                    ]);
                    
                    if ($affected > 0) {
                        Session::setFlash('Department updated successfully', 'success');
                        header('Location: departments.php');
                        exit;
                    } else {
                        $message = 'Department not found or no changes made';
                        $messageType = 'error';
                    }
                }
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

// Load managers for dropdown
$managers = $db->query("SELECT id, full_name, email FROM users WHERE {$orgFilter} role IN ('admin', 'store_manager') AND status = 'active' ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditMode ? 'Edit' : 'Add' ?> Department - Stocksathi</title>
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
                        <a href="departments.php" class="breadcrumb-item">Departments</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active"><?= $isEditMode ? 'Edit' : 'Add' ?> Department</span>
                    </nav>
                    <h1 class="content-title"><?= $isEditMode ? 'Edit' : 'Add' ?> Department</h1>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" id="departmentForm">
                            <input type="hidden" name="action" value="<?= $isEditMode ? 'update' : 'create' ?>">
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="form-label required">Department Name</label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($department['name'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Code</label>
                                    <input type="text" name="code" class="form-control" value="<?= htmlspecialchars($department['code'] ?? '') ?>" placeholder="e.g., DEPT-001">
                                </div>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($department['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="form-label">Manager</label>
                                    <select name="manager_id" class="form-control">
                                        <option value="">Select Manager</option>
                                        <?php foreach ($managers as $manager): ?>
                                            <option value="<?= $manager['id'] ?>" <?= ($department['manager_id'] ?? '') == $manager['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($manager['full_name']) ?> (<?= htmlspecialchars($manager['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-control">
                                        <option value="active" <?= ($department['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                        <option value="inactive" <?= ($department['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="flex gap-3">
                                <button type="submit" class="btn btn-primary"><?= $isEditMode ? 'Update' : 'Create' ?> Department</button>
                                <a href="departments.php" class="btn btn-ghost">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
