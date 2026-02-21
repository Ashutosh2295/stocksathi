<?php
/**
 * Attendance Page - Core PHP Version
 * Uses core PHP concepts with direct database queries and form submissions
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Session.php';

// Initialize database connection
$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

// Handle check-in/check-out
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = Session::getUserId();
    
    if (!$userId) {
        Session::setFlash('User not logged in', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    
    try {
        if ($action === 'checkin') {
            // Get employee_id from user_id
            $employee = $db->queryOne("SELECT id FROM employees WHERE user_id = ?", [$userId]);
            if (!$employee) {
                Session::setFlash('Employee record not found. Please contact administrator.', 'error');
            } else {
                $employeeId = $employee['id'];
                $today = date('Y-m-d');
                
                // Check if already checked in today
                $existing = $db->queryOne(
                    "SELECT * FROM attendance WHERE employee_id = ? AND date = ?",
                    [$employeeId, $today]
                );
                
                if ($existing) {
                    Session::setFlash('You have already checked in today', 'error');
                } else {
                    $db->execute(
                        "INSERT INTO attendance (employee_id, date, check_in, status) VALUES (?, ?, TIME(NOW()), 'present')",
                        [$employeeId, $today]
                    );
                    Session::setFlash('Checked in successfully', 'success');
                }
            }
        } elseif ($action === 'checkout') {
            // Get employee_id from user_id
            $employee = $db->queryOne("SELECT id FROM employees WHERE user_id = ?", [$userId]);
            if (!$employee) {
                Session::setFlash('Employee record not found. Please contact administrator.', 'error');
            } else {
                $employeeId = $employee['id'];
                $today = date('Y-m-d');
                
                $attendance = $db->queryOne(
                    "SELECT * FROM attendance WHERE employee_id = ? AND date = ? AND check_out IS NULL",
                    [$employeeId, $today]
                );
                
                if (!$attendance) {
                    Session::setFlash('Please check in first', 'error');
                } else {
                    // Calculate hours worked
                    $checkInTime = $attendance['check_in'];
                    $checkOutTime = date('H:i:s');
                    
                    $checkIn = new DateTime($today . ' ' . $checkInTime);
                    $checkOut = new DateTime($today . ' ' . $checkOutTime);
                    $diff = $checkIn->diff($checkOut);
                    $hours = (($diff->days ?? 0) * 24) + $diff->h + ($diff->i / 60) + ($diff->s / 3600);
                    
                    $db->execute(
                        "UPDATE attendance SET check_out = ?, total_hours = ? WHERE {$orgFilter} id = ?",
                        [$checkOutTime, $hours, $attendance['id']]
                    );
                    Session::setFlash('Checked out successfully', 'success');
                }
            }
        }
    } catch (Exception $e) {
        Session::setFlash('Error: ' . $e->getMessage(), 'error');
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Get flash message
$flash = Session::getFlash();
$message = $flash['message'] ?? '';
$messageType = $flash['type'] ?? '';

// Get today's date
$today = date('Y-m-d');

// Load today's attendance
// NOTE: Attendance table uses employee_id, not user_id
$query = "SELECT a.*, 
          CONCAT(e.first_name, ' ', e.last_name) as employee_name,
          e.first_name,
          e.last_name,
          e.employee_code,
          TIMESTAMPDIFF(HOUR, CONCAT(a.date, ' ', a.check_in), CONCAT(a.date, ' ', COALESCE(a.check_out, TIME(NOW())))) as hours_calculated
          FROM attendance a
          LEFT JOIN employees e ON a.employee_id = e.id
          " . ($orgIdPatch ? " WHERE a.organization_id = " . intval($orgIdPatch) . " AND a.date = ?" : " WHERE a.date = ?") . "
          ORDER BY a.check_in DESC";
$todayAttendance = $db->query($query, [$today]);

// Check current user's status - need to find employee_id from user_id
// Check current user's status - need to find employee_id from user_id
$currentUserId = Session::getUserId();
$userAttendance = null;
$employee = null; // Defined here for scope

if ($currentUserId) {
    // First get employee_id from user_id
    $employee = $db->queryOne("SELECT id FROM employees WHERE user_id = ?", [$currentUserId]);
    if ($employee) {
        $userAttendance = $db->queryOne(
            "SELECT * FROM attendance WHERE employee_id = ? AND date = ?",
            [$employee['id'], $today]
        );
    } else {
        // Warning if no employee record found
         $message = "Your user account is not linked to an employee record. Please contact HR or an Administrator.";
         $messageType = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Attendance</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Attendance</h1>
                        <div class="flex gap-3">
                            <?php if ($employee): ?>
                                <?php if ($userAttendance && isset($userAttendance['status']) && $userAttendance['status'] === 'on_leave'): ?>
                                    <span class="badge" style="background-color: #0ea5e9; color: white; padding: 10px 15px; font-size: 14px; border-radius: 6px;">
                                        🏝️ On Leave Today
                                    </span>
                                <?php elseif ($userAttendance && !$userAttendance['check_out']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="checkout">
                                        <button type="submit" class="btn btn-primary">
                                            <span>⏰</span> Check Out
                                        </button>
                                    </form>
                                <?php elseif (!$userAttendance): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="checkin">
                                        <button type="submit" class="btn btn-primary">
                                            <span>✅</span> Check In
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge badge-success" style="padding: 10px 15px; font-size: 14px; border-radius: 6px;">
                                        ✅ Shift Completed
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Today's Attendance (<?= date('F d, Y') ?>)</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Hours</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($todayAttendance)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center" style="padding: 40px;">
                                            No attendance records for today
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($todayAttendance as $record): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($record['employee_name'] ?? ($record['first_name'] . ' ' . $record['last_name']) ?? 'N/A') ?></td>
                                            <td><?= $record['check_in'] ? date('H:i A', strtotime($record['check_in'])) : '-' ?></td>
                                            <td><?= $record['check_out'] ? date('H:i A', strtotime($record['check_out'])) : '-' ?></td>
                                            <td>
                                                <?php if ($record['check_out']): ?>
                                                    <?= number_format((float)($record['total_hours'] ?? 0), 2) ?> hrs
                                                <?php elseif ($record['check_in']): ?>
                                                    <?php
                                                    $checkIn = new DateTime($record['date'] . ' ' . $record['check_in']);
                                                    $now = new DateTime();
                                                    $diff = $now->diff($checkIn);
                                                    echo $diff->h . ':' . str_pad($diff->i, 2, '0', STR_PAD_LEFT) . ' (ongoing)';
                                                    ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (isset($record['status']) && $record['status'] === 'on_leave'): ?>
                                                    <span class="badge badge-info" style="background-color: #0ea5e9; color: white;">On Leave</span>
                                                <?php elseif ($record['check_out']): ?>
                                                    <span class="badge badge-success">Completed</span>
                                                <?php elseif ($record['check_in']): ?>
                                                    <span class="badge badge-warning">In Progress</span>
                                                <?php else: ?>
                                                    <span class="badge badge-gray">Absent</span>
                                                <?php endif; ?>
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
