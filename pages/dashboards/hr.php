<?php
/**
 * HR Dashboard - Attendance + Leave + Employee quick actions
 */

require_once __DIR__ . '/../../_includes/session_guard.php';
require_once __DIR__ . '/../../_includes/config.php';
require_once __DIR__ . '/../../_includes/database.php';
require_once __DIR__ . '/../../_includes/Session.php';
require_once __DIR__ . '/../../_includes/PermissionMiddleware.php';

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$userId = Session::getUserId();
$userRole = Session::getUserRole();

$canViewHR = in_array($userRole, ['super_admin', 'admin', 'hr'], true)
    || PermissionMiddleware::hasAnyPermission(['view_employees', 'view_attendance', 'view_leave', 'manage_employees', 'manage_leave']);

if (!$canViewHR) {
    http_response_code(403);
    die('Access Denied');
}

// Link current user -> employee (if exists)
$employee = null;
try {
    $employee = $db->queryOne("SELECT id, employee_code, first_name, last_name, email FROM employees WHERE user_id = ? LIMIT 1", [$userId]);
} catch (Exception $e) {
    $employee = null;
}

// Create/link employee profile for current user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'link_my_employee') {
    try {
        // Get user data
        $user = $db->queryOne("SELECT id, full_name, email FROM users WHERE {$orgFilter} id = ? LIMIT 1", [$userId]);
        if (!$user) {
            Session::setFlash('User not found', 'error');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // If an employee exists with this email but not linked, link it.
        $existingByEmail = $db->queryOne("SELECT id, user_id FROM employees WHERE {$orgFilter} email = ? LIMIT 1", [$user['email']]);
        if ($existingByEmail && empty($existingByEmail['user_id'])) {
            $db->execute("UPDATE employees SET user_id = ? WHERE {$orgFilter} id = ?", [$userId, $existingByEmail['id']]);
            Session::setFlash('Employee profile linked to your user successfully', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // If already linked, just reload
        $employee = $db->queryOne("SELECT id FROM employees WHERE user_id = ? LIMIT 1", [$userId]);
        if ($employee) {
            Session::setFlash('Already linked to an employee profile', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        // Create a new employee profile for this user
        $fullName = trim((string)($user['full_name'] ?? ''));
        $parts = preg_split('/\s+/', $fullName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $firstName = $parts[0] ?? 'Employee';
        $lastName = (count($parts) > 1) ? implode(' ', array_slice($parts, 1)) : 'User';

        // Generate employee code
        $last = $db->queryOne("SELECT id FROM employees {$orgWhere} ORDER BY id DESC LIMIT 1");
        $nextId = $last ? ((int)$last['id'] + 1) : 1;
        $employeeCode = 'EMP-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);

        $db->execute(
            "INSERT INTO employees (employee_code, user_id, first_name, last_name, email, status) VALUES (?, ?, ?, ?, ?, 'active')",
            [$employeeCode, $userId, $firstName, $lastName, $user['email']]
        );

        Session::setFlash('Employee profile created & linked successfully', 'success');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        Session::setFlash('Error: ' . $e->getMessage(), 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$flash = Session::getFlash();
$message = $flash['message'] ?? '';
$messageType = $flash['type'] ?? '';

// KPIs
$today = date('Y-m-d');
$kpi = [
    'employees' => 0,
    'present_today' => 0,
    'pending_leaves' => 0,
    'on_leave_today' => 0
];

try {
    $kpi['employees'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM employees {$orgWhere}\")['c'] ?? 0);
} catch (Exception $e) {}

try {
    $kpi['present_today'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM attendance WHERE date = ? AND status = 'present'", [$today])['c'] ?? 0);
} catch (Exception $e) {}

try {
    $kpi['pending_leaves'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM leave_requests WHERE {$orgFilter} status = 'pending'")['c'] ?? 0);
} catch (Exception $e) {}

try {
    $kpi['on_leave_today'] = (int)($db->queryOne("SELECT COUNT(*) AS c FROM leave_requests WHERE {$orgFilter} status = 'approved' AND ? BETWEEN from_date AND to_date", [$today])['c'] ?? 0);
} catch (Exception $e) {}

// Recent leave requests
$recentLeaves = [];
try {
    $recentLeaves = $db->query(
        "SELECT lr.*, CONCAT(e.first_name,' ',e.last_name) AS employee_name
         FROM leave_requests lr
         LEFT JOIN employees e ON e.id = lr.employee_id
         ORDER BY lr.created_at DESC
         LIMIT 8"
    );
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
    <script src="<?= BASE_PATH ?>/js/theme-manager.js"></script>
</head>
<body>
<div class="app-container">
    <?php include __DIR__ . '/../../_includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include __DIR__ . '/../../_includes/header.php'; ?>

        <main class="content">
            <div class="content-header">
                <nav class="breadcrumb">
                    <a href="<?= BASE_PATH ?>/index.php" class="breadcrumb-item">Home</a>
                    <span class="breadcrumb-separator">/</span>
                    <span class="breadcrumb-item active">HR Dashboard</span>
                </nav>
                <div class="flex items-center justify-between">
                    <h1 class="content-title">HR Dashboard</h1>
                    <div class="flex gap-3">
                        <a class="btn btn-outline" href="<?= BASE_PATH ?>/pages/attendance.php">Attendance</a>
                        <a class="btn btn-primary" href="<?= BASE_PATH ?>/pages/leave-management.php">Leave Requests</a>
                    </div>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if (!$employee): ?>
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">Link your user to Employee</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary" style="margin-bottom: 16px;">
                            Your account is not linked to an employee profile. This is required for Attendance/Leave to work.
                        </p>
                        <form method="POST">
                            <input type="hidden" name="action" value="link_my_employee">
                            <button type="submit" class="btn btn-primary">Create/Link my Employee Profile</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-4 gap-6 mb-6">
                <div class="kpi-card">
                    <div class="kpi-icon primary">👥</div>
                    <div class="kpi-label">Employees</div>
                    <div class="kpi-value"><?= number_format($kpi['employees']) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon success">✅</div>
                    <div class="kpi-label">Present Today</div>
                    <div class="kpi-value"><?= number_format($kpi['present_today']) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon warning">⏳</div>
                    <div class="kpi-label">Pending Leaves</div>
                    <div class="kpi-value"><?= number_format($kpi['pending_leaves']) ?></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon info">🌿</div>
                    <div class="kpi-label">On Leave Today</div>
                    <div class="kpi-value"><?= number_format($kpi['on_leave_today']) ?></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="card-title">Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-2 gap-4">
                            <a class="btn btn-primary" href="<?= BASE_PATH ?>/pages/employee-form.php">➕ Add Employee</a>
                            <a class="btn btn-outline" href="<?= BASE_PATH ?>/pages/employees.php">👥 View Employees</a>
                            <a class="btn btn-outline" href="<?= BASE_PATH ?>/pages/departments.php">🏢 Departments</a>
                            <a class="btn btn-outline" href="<?= BASE_PATH ?>/pages/leave-management.php?action=apply">📝 Apply Leave</a>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header flex items-center justify-between">
                        <h3 class="card-title">Recent Leave Requests</h3>
                        <a class="btn btn-ghost btn-sm" href="<?= BASE_PATH ?>/pages/leave-management.php">View all</a>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Type</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($recentLeaves)): ?>
                                <tr><td colspan="5" style="padding: 20px; text-align:center;">No requests</td></tr>
                            <?php else: ?>
                                <?php foreach ($recentLeaves as $lr): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($lr['employee_name'] ?? 'N/A') ?></td>
                                        <td><?= htmlspecialchars($lr['leave_type'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($lr['from_date'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($lr['to_date'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($lr['status'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
</body>
</html>

