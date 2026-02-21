<?php
/**
 * Employees Management Page - Core PHP Version
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

// Load all employees with department info
$query = "SELECT e.*, 
          d.name as department_name
          FROM employees e
          LEFT JOIN departments d ON e.department_id = d.id
          " . ($orgIdPatch ? " WHERE e.organization_id = " . intval($orgIdPatch) : "") . "
          ORDER BY e.id DESC";
$employees = $db->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Employees</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Employees</h1>
                        <a href="<?= BASE_PATH ?>/pages/employee-form.php" class="btn btn-primary">
                            <span>➕</span> Add Employee
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Employees List</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>Name</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            No employees found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($employee['employee_code'] ?? 'EMP-' . str_pad($employee['id'], 3, '0', STR_PAD_LEFT)) ?></code></td>
                                            <td><?= htmlspecialchars(trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''))) ?></td>
                                            <td><?= htmlspecialchars($employee['department_name'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($employee['designation'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($employee['email'] ?? '-') ?></td>
                                            <td>
                                                <?php if (($employee['status'] ?? 'active') === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="employee-form.php?id=<?= $employee['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">✏️</a>
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
