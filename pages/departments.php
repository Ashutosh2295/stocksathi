<?php
/**
 * Departments Management Page - Core PHP Version
 * Uses core PHP concepts with direct database queries
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';

// Initialize database connection
$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'] ?? null;
    if ($id) {
        try {
            // Soft delete
            $db->execute("UPDATE departments SET status = 'inactive' WHERE id = ?" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : ""), [$id]);
            Session::setFlash('Department deactivated successfully', 'success');
        } catch (Exception $e) {
            Session::setFlash('Error deactivating department: ' . $e->getMessage(), 'error');
        }
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Get flash message if any
$flash = Session::getFlash();
$message = $flash['message'] ?? '';
$messageType = $flash['type'] ?? '';


// Load all departments with employee counts and manager name
$query = "SELECT d.*, 
          COUNT(e.id) as employee_count,
          u.full_name as head_name
          FROM departments d
          LEFT JOIN employees e ON d.id = e.department_id
          LEFT JOIN users u ON d.manager_id = u.id
          " . ($orgIdPatch ? " WHERE d.organization_id = " . intval($orgIdPatch) : "") . "
          GROUP BY d.id, u.full_name
          ORDER BY d.id DESC";
$departments = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Departments</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Departments</h1>
                        <a href="<?= BASE_PATH ?>/pages/department-form.php" class="btn btn-primary">
                            <span>➕</span> Add Department
                        </a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Departments List</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Department Name</th>
                                    <th>Head</th>
                                    <th>Employees</th>
                                    <th>Location</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($departments)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align: center; padding: 40px;">
                                            No departments found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($departments as $dept): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($dept['name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($dept['head_name'] ?? '-') ?></td>
                                            <td><?= (int)$dept['employee_count'] ?></td>
                                            <td><?= htmlspecialchars($dept['description'] ?? '-') ?></td>
                                            <td>
                                                <?php if (($dept['status'] ?? 'active') === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="department-form.php?id=<?= $dept['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">✏️</a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to deactivate the <?= htmlspecialchars(addslashes($dept['name'])) ?> department?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $dept['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" title="Deactivate">🗑️</button>
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
</body>
</html>
