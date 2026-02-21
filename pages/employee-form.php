<?php
/**
 * Employee Form Page - Add/Edit Employee
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
$employee = null;

// Get employee ID if editing
$employeeId = $_GET['id'] ?? null;
if ($employeeId) {
    $employee = $db->queryOne("SELECT * FROM employees WHERE {$orgFilter} id = ?", [$employeeId]);
    if ($employee) {
        $isEditMode = true;
    } else {
        Session::setFlash('Employee not found', 'error');
        header('Location: employees.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? ($isEditMode ? 'update' : 'create');
    
    try {
        $validator = new Validator($_POST);
        $validator->required('first_name', 'First name is required');
        $validator->required('last_name', 'Last name is required');
        $validator->required('email', 'Email is required');
        
        if ($validator->fails()) {
            $message = $validator->getFirstError();
            $messageType = 'error';
        } else {
            $data = Validator::sanitize($_POST);
            
            // Generate employee code if not provided
            if (empty($data['employee_code'])) {
                $lastEmployee = $db->queryOne("SELECT id FROM employees {$orgWhere} ORDER BY id DESC LIMIT 1");
                $nextId = $lastEmployee ? ((int)$lastEmployee['id'] + 1) : 1;
                $data['employee_code'] = 'EMP-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }
            
            if ($action === 'create') {
                // Check if email already exists
                $existing = $db->queryOne("SELECT id FROM employees WHERE {$orgFilter} email = ?", [$data['email']]);
                if ($existing) {
                    $message = 'Email already exists';
                    $messageType = 'error';
                } else {
                    $query = "INSERT INTO employees (employee_code, user_id, first_name, last_name, email, phone, department_id, designation, date_of_birth, date_of_joining, gender, address, city, state, pincode, status) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $id = $db->execute($query, [
                        $data['employee_code'],
                        !empty($data['user_id']) ? (int)$data['user_id'] : null,
                        $data['first_name'],
                        $data['last_name'],
                        $data['email'],
                        $data['phone'] ?? null,
                        !empty($data['department_id']) ? (int)$data['department_id'] : null,
                        $data['designation'] ?? null,
                        !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
                        !empty($data['date_of_joining']) ? $data['date_of_joining'] : null,
                        $data['gender'] ?? null,
                        $data['address'] ?? null,
                        $data['city'] ?? null,
                        $data['state'] ?? null,
                        $data['pincode'] ?? null,
                        $data['status'] ?? 'active'
                    ]);
                    
                    Session::setFlash('Employee created successfully', 'success');
                    header('Location: employees.php');
                    exit;
                }
            } elseif ($action === 'update') {
                // Check if email exists for another employee
                $existing = $db->queryOne("SELECT id FROM employees WHERE {$orgFilter} email = ? AND id != ?", [$data['email'], $employeeId]);
                if ($existing) {
                    $message = 'Email already exists for another employee';
                    $messageType = 'error';
                } else {
                    $query = "UPDATE employees SET first_name = ?, last_name = ?, email = ?, phone = ?, department_id = ?, designation = ?, date_of_birth = ?, date_of_joining = ?, gender = ?, address = ?, city = ?, state = ?, pincode = ?, status = ? WHERE {$orgFilter} id = ?";
                    $affected = $db->execute($query, [
                        $data['first_name'],
                        $data['last_name'],
                        $data['email'],
                        $data['phone'] ?? null,
                        !empty($data['department_id']) ? (int)$data['department_id'] : null,
                        $data['designation'] ?? null,
                        !empty($data['date_of_birth']) ? $data['date_of_birth'] : null,
                        !empty($data['date_of_joining']) ? $data['date_of_joining'] : null,
                        $data['gender'] ?? null,
                        $data['address'] ?? null,
                        $data['city'] ?? null,
                        $data['state'] ?? null,
                        $data['pincode'] ?? null,
                        $data['status'] ?? 'active',
                        $employeeId
                    ]);
                    
                    if ($affected > 0) {
                        Session::setFlash('Employee updated successfully', 'success');
                        header('Location: employees.php');
                        exit;
                    } else {
                        $message = 'Employee not found or no changes made';
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

// Load departments for dropdown
$departments = $db->query("SELECT id, name FROM departments WHERE {$orgFilter} status = 'active' ORDER BY name");

// Load users for user_id dropdown (optional)
$users = $db->query("SELECT id, full_name, email FROM users WHERE {$orgFilter} status = 'active' ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEditMode ? 'Edit' : 'Add' ?> Employee - Stocksathi</title>
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
                        <a href="employees.php" class="breadcrumb-item">Employees</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active"><?= $isEditMode ? 'Edit' : 'Add' ?> Employee</span>
                    </nav>
                    <h1 class="content-title"><?= $isEditMode ? 'Edit' : 'Add' ?> Employee</h1>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" id="employeeForm">
                            <input type="hidden" name="action" value="<?= $isEditMode ? 'update' : 'create' ?>">
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="form-label required">First Name</label>
                                    <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($employee['first_name'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label required">Last Name</label>
                                    <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($employee['last_name'] ?? '') ?>" required>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="form-label required">Email</label>
                                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($employee['email'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($employee['phone'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="form-label">Employee Code</label>
                                    <input type="text" name="employee_code" class="form-control" value="<?= htmlspecialchars($employee['employee_code'] ?? '') ?>" <?= $isEditMode ? 'readonly' : '' ?>>
                                    <small class="text-muted">Leave blank to auto-generate</small>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Department</label>
                                    <select name="department_id" class="form-control">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= $dept['id'] ?>" <?= ($employee['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dept['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="form-label">Designation</label>
                                    <input type="text" name="designation" class="form-control" value="<?= htmlspecialchars($employee['designation'] ?? '') ?>" placeholder="e.g., Sales Executive">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Gender</label>
                                    <select name="gender" class="form-control">
                                        <option value="">Select Gender</option>
                                        <option value="male" <?= ($employee['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                        <option value="female" <?= ($employee['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                        <option value="other" <?= ($employee['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="form-label">Date of Birth</label>
                                    <input type="date" name="date_of_birth" class="form-control" value="<?= htmlspecialchars($employee['date_of_birth'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Date of Joining</label>
                                    <input type="date" name="date_of_joining" class="form-control" value="<?= htmlspecialchars($employee['date_of_joining'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($employee['address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div class="form-group">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($employee['city'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">State</label>
                                    <input type="text" name="state" class="form-control" value="<?= htmlspecialchars($employee['state'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Pincode</label>
                                    <input type="text" name="pincode" class="form-control" value="<?= htmlspecialchars($employee['pincode'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control">
                                    <option value="active" <?= ($employee['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="on_leave" <?= ($employee['status'] ?? '') === 'on_leave' ? 'selected' : '' ?>>On Leave</option>
                                    <option value="resigned" <?= ($employee['status'] ?? '') === 'resigned' ? 'selected' : '' ?>>Resigned</option>
                                    <option value="terminated" <?= ($employee['status'] ?? '') === 'terminated' ? 'selected' : '' ?>>Terminated</option>
                                </select>
                            </div>
                            
                            <div class="flex gap-3">
                                <button type="submit" class="btn btn-primary"><?= $isEditMode ? 'Update' : 'Create' ?> Employee</button>
                                <a href="employees.php" class="btn btn-ghost">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
