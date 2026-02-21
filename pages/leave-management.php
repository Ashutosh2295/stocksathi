<?php
require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Session.php';
require_once __DIR__ . '/../_includes/PermissionMiddleware.php';
require_once __DIR__ . '/../_includes/Validator.php';

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

$userId = Session::getUserId();
$userRole = Session::getUserRole();

$canManage = in_array($userRole, ['super_admin', 'admin', 'hr'], true)
    || PermissionMiddleware::hasAnyPermission(['manage_employees', 'manage_leave', 'approve_leave', 'view_leave_requests']);

// Link current user -> employee
$employee = null;
if ($userId) {
    try {
        $employee = $db->queryOne("SELECT id, first_name, last_name, employee_code FROM employees WHERE " . ($orgIdPatch ? "organization_id = " . intval($orgIdPatch) . " AND " : "") . "user_id = ? LIMIT 1", [$userId]);
    } catch (Exception $e) {
        // Missing table or schema issues handled by session_guard missing-table handler
        $employee = null;
    }
}

// Handle actions (Apply / Approve / Reject / Cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'apply_leave') {
            if (!$employee) {
                Session::setFlash('Your user is not linked to an employee record. Please contact HR/Admin.', 'error');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            $validator = new Validator($_POST);
            $validator->required('leave_type', 'Leave type is required');
            $validator->required('from_date', 'From date is required');
            $validator->required('to_date', 'To date is required');
            $validator->required('reason', 'Reason is required');

            if ($validator->fails()) {
                Session::setFlash($validator->getFirstError(), 'error');
                header('Location: ' . $_SERVER['PHP_SELF'] . '?action=apply');
                exit;
            }

            $data = Validator::sanitize($_POST);
            $leaveType = $data['leave_type'];
            $fromDate = $data['from_date'];
            $toDate = $data['to_date'];
            $reason = trim((string)($data['reason'] ?? ''));

            $from = new DateTime($fromDate);
            $to = new DateTime($toDate);
            if ($to < $from) {
                Session::setFlash('To date must be same or after From date', 'error');
                header('Location: ' . $_SERVER['PHP_SELF'] . '?action=apply');
                exit;
            }

            $days = (int)$from->diff($to)->days + 1; // inclusive

            $db->execute(
                "INSERT INTO leave_requests (employee_id, leave_type, from_date, to_date, total_days, reason, status, organization_id) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)",
                [$employee['id'], $leaveType, $fromDate, $toDate, $days, $reason, $orgIdPatch]
            );

            Session::setFlash('Leave request submitted successfully', 'success');
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }

        if ($action === 'approve_leave' || $action === 'reject_leave' || $action === 'cancel_leave') {
            $leaveId = (int)($_POST['leave_id'] ?? 0);
            if ($leaveId <= 0) {
                Session::setFlash('Invalid leave request', 'error');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            $leave = $db->queryOne("SELECT * FROM leave_requests WHERE id = ? LIMIT 1", [$leaveId]);
            if (!$leave) {
                Session::setFlash('Leave request not found', 'error');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            if ($action === 'cancel_leave') {
                // Only the owner can cancel (and only if pending)
                if (!$employee || (int)$leave['employee_id'] !== (int)$employee['id']) {
                    Session::setFlash('You can only cancel your own leave request', 'error');
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }
                if (($leave['status'] ?? '') !== 'pending') {
                    Session::setFlash('Only pending leave requests can be cancelled', 'error');
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                $db->execute("UPDATE leave_requests SET status = 'cancelled', updated_at = NOW() WHERE id = ?", [$leaveId]);
                Session::setFlash('Leave request cancelled', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            // Approve/Reject: HR/Admin only
            if (!$canManage) {
                Session::setFlash('Access denied: you cannot approve/reject leave requests', 'error');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            if (($leave['status'] ?? '') !== 'pending') {
                Session::setFlash('Only pending leave requests can be updated', 'error');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            if ($action === 'approve_leave') {
                $db->execute(
                    "UPDATE leave_requests SET status = 'approved', approved_by = ?, approval_date = CURDATE(), rejection_reason = NULL, updated_at = NOW() WHERE id = ?",
                    [$userId, $leaveId]
                );
                
                // Fetch leave details to insert into attendance
                $leaveDetails = $db->queryOne("SELECT employee_id, from_date, to_date FROM leave_requests WHERE id = ?", [$leaveId]);
                
                if ($leaveDetails) {
                    $currentDate = new DateTime($leaveDetails['from_date']);
                    $endDate = new DateTime($leaveDetails['to_date']);
                    
                    while ($currentDate <= $endDate) {
                        $dateStr = $currentDate->format('Y-m-d');
                        
                        $existing = $db->queryOne(
                            "SELECT id FROM attendance WHERE employee_id = ? AND date = ?",
                            [$leaveDetails['employee_id'], $dateStr]
                        );
                        
                        if ($existing) {
                            $db->execute(
                                "UPDATE attendance SET status = 'on_leave', check_in = NULL, check_out = NULL, total_hours = 0 WHERE id = ?",
                                [$existing['id']]
                            );
                        } else {
                            $db->execute(
                                "INSERT INTO attendance (employee_id, date, status, organization_id) VALUES (?, ?, 'on_leave', ?)",
                                [$leaveDetails['employee_id'], $dateStr, $orgIdPatch]
                            );
                        }
                        
                        $currentDate->modify('+1 day');
                    }
                }

                Session::setFlash('Leave request approved', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }

            if ($action === 'reject_leave') {
                $reason = trim((string)($_POST['rejection_reason'] ?? ''));
                if ($reason === '') $reason = 'Rejected';
                $db->execute(
                    "UPDATE leave_requests SET status = 'rejected', approved_by = ?, approval_date = CURDATE(), rejection_reason = ?, updated_at = NOW() WHERE id = ?",
                    [$userId, $reason, $leaveId]
                );
                Session::setFlash('Leave request rejected', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    } catch (Exception $e) {
        Session::setFlash('Error: ' . $e->getMessage(), 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

$flash = Session::getFlash();
$message = $flash['message'] ?? '';
$messageType = $flash['type'] ?? '';

// Load leave requests
$leaveRequests = [];
try {
    if ($canManage) {
        $leaveRequests = $db->query(
            "SELECT lr.*, CONCAT(e.first_name, ' ', e.last_name) AS employee_name, e.employee_code
             FROM leave_requests lr
             LEFT JOIN employees e ON lr.employee_id = e.id
             " . ($orgIdPatch ? " WHERE lr.organization_id = " . intval($orgIdPatch) : "") . "
             ORDER BY lr.created_at DESC, lr.id DESC
             LIMIT 200"
        );
    } elseif ($employee) {
        $leaveRequests = $db->query(
            "SELECT lr.*, CONCAT(e.first_name, ' ', e.last_name) AS employee_name, e.employee_code
             FROM leave_requests lr
             LEFT JOIN employees e ON lr.employee_id = e.id
             WHERE lr.employee_id = ?" . ($orgIdPatch ? " AND lr.organization_id = " . intval($orgIdPatch) : "") . "
             ORDER BY lr.created_at DESC, lr.id DESC
             LIMIT 200",
            [$employee['id']]
        );
    }
} catch (Exception $e) {
    // handled by session_guard for missing-table issues
    $leaveRequests = [];
}

$pageAction = $_GET['action'] ?? '';

function leaveTypeLabel($type) {
    $map = [
        'casual' => 'Casual Leave',
        'sick' => 'Sick Leave',
        'earned' => 'Earned Leave',
        'maternity' => 'Maternity Leave',
        'paternity' => 'Paternity Leave',
        'unpaid' => 'Unpaid Leave',
    ];
    return $map[$type] ?? ucfirst((string)$type);
}

function statusBadge($status) {
    $status = $status ?: 'pending';
    if ($status === 'approved') return ['success', 'Approved'];
    if ($status === 'rejected') return ['danger', 'Rejected'];
    if ($status === 'cancelled') return ['gray', 'Cancelled'];
    return ['warning', 'Pending'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Leave Management</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Leave Management</h1>
                        <?php if ($employee): ?>
                            <a class="btn btn-primary" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?action=apply">
                                <span>➕</span> Apply Leave
                            </a>
                        <?php else: ?>
                            <button class="btn btn-primary" disabled title="Link your user to an employee first">
                                <span>➕</span> Apply Leave
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <?php if (!$employee): ?>
                    <div class="alert alert-warning" style="margin-bottom: 20px;">
                        Your user account is not linked to an employee record. Please contact HR/Admin to link `employees.user_id` to your user.
                    </div>
                <?php endif; ?>

                <?php if ($pageAction === 'apply'): ?>
                    <div class="card mb-6">
                        <div class="card-header">
                            <div class="flex items-center justify-between">
                                <h3 class="card-title">Apply Leave</h3>
                                <a class="btn btn-ghost" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">← Back</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="apply_leave">

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label required">Leave Type</label>
                                        <select name="leave_type" class="form-control" required>
                                            <option value="">Select...</option>
                                            <option value="casual">Casual Leave</option>
                                            <option value="sick">Sick Leave</option>
                                            <option value="earned">Earned Leave</option>
                                            <option value="maternity">Maternity Leave</option>
                                            <option value="paternity">Paternity Leave</option>
                                            <option value="unpaid">Unpaid Leave</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label class="form-label">Employee</label>
                                        <input class="form-control" value="<?= htmlspecialchars(trim(($employee['first_name'] ?? '') . ' ' . ($employee['last_name'] ?? ''))) ?>" disabled>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div class="form-group">
                                        <label class="form-label required">From Date</label>
                                        <input type="date" name="from_date" class="form-control" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label required">To Date</label>
                                        <input type="date" name="to_date" class="form-control" required>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="form-label required">Reason</label>
                                    <textarea name="reason" class="form-control" rows="4" placeholder="Write the reason..." required></textarea>
                                </div>

                                <div class="flex gap-3">
                                    <button type="submit" class="btn btn-primary">Submit Request</button>
                                    <a class="btn btn-ghost" href="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <div class="flex items-center justify-between">
                            <h3 class="card-title">Leave Requests</h3>
                            <div class="text-sm text-muted">
                                <?= $canManage ? 'Showing: All employees' : 'Showing: My requests' ?>
                            </div>
                        </div>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Employee</th>
                                    <th>Leave Type</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Days</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($leaveRequests)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            No leave requests found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($leaveRequests as $lr): ?>
                                        <?php [$badge, $badgeLabel] = statusBadge($lr['status'] ?? 'pending'); ?>
                                        <tr>
                                            <td>
                                                <div class="font-medium"><?= htmlspecialchars($lr['employee_name'] ?? 'N/A') ?></div>
                                                <div class="text-xs text-muted"><?= htmlspecialchars($lr['employee_code'] ?? '') ?></div>
                                            </td>
                                            <td><?= htmlspecialchars(leaveTypeLabel($lr['leave_type'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars($lr['from_date'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($lr['to_date'] ?? '-') ?></td>
                                            <td><?= (int)($lr['total_days'] ?? 0) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $badge ?>"><?= htmlspecialchars($badgeLabel) ?></span>
                                                <?php if (($lr['status'] ?? '') === 'rejected' && !empty($lr['rejection_reason'])): ?>
                                                    <div class="text-xs text-muted" style="margin-top: 6px;">
                                                        Reason: <?= htmlspecialchars($lr['rejection_reason']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <?php if (($lr['status'] ?? '') === 'pending' && $canManage): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="approve_leave">
                                                        <input type="hidden" name="leave_id" value="<?= (int)$lr['id'] ?>">
                                                        <button type="submit" class="btn btn-success btn-sm" title="Approve">✓</button>
                                                    </form>
                                                    <form method="POST" style="display:inline;" onsubmit="return handleRejectReason(this);">
                                                        <input type="hidden" name="action" value="reject_leave">
                                                        <input type="hidden" name="leave_id" value="<?= (int)$lr['id'] ?>">
                                                        <input type="hidden" name="rejection_reason" value="">
                                                        <button type="submit" class="btn btn-danger btn-sm" title="Reject">✗</button>
                                                    </form>
                                                <?php endif; ?>

                                                <?php if (($lr['status'] ?? '') === 'pending' && $employee && (int)$lr['employee_id'] === (int)$employee['id']): ?>
                                                    <form method="POST" style="display:inline;">
                                                        <input type="hidden" name="action" value="cancel_leave">
                                                        <input type="hidden" name="leave_id" value="<?= (int)$lr['id'] ?>">
                                                        <button type="submit" class="btn btn-ghost btn-sm" title="Cancel">Cancel</button>
                                                    </form>
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
    <script>
        function handleRejectReason(form) {
            const reason = prompt('Rejection reason (optional):', 'Rejected');
            if (reason === null) return false;
            const input = form.querySelector('input[name="rejection_reason"]');
            if (input) input.value = reason;
            return true;
        }
    </script>
</body>
</html>