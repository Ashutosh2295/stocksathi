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
