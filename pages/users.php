<?php
/**
 * Users Management Page - Core PHP Version
 * Stocksathi Inventory System
 * Uses core PHP concepts with direct database queries and form submissions
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Validator.php';
require_once __DIR__ . '/../_includes/Session.php';
require_once __DIR__ . '/../_includes/PermissionMiddleware.php';

// Check if user has permission to manage users (only admin and super_admin)
$userRole = Session::getUserRole();
if (!in_array($userRole, ['super_admin', 'admin'])) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

// Initialize database connection
$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        /**
         * When creating/updating an HR user, auto link/create employee record.
         * This makes Attendance/Leave work immediately (employees.user_id is required).
         */
        $ensureEmployeeLinkedForHrUser = function(int $userId, string $fullName, string $email) use ($db) {
            // Ensure employees table exists
            $employeesExists = $db->queryOne("SHOW TABLES LIKE 'employees'");
            if (!$employeesExists) {
                return; // HR tables can be created via migrations/fix_missing_tables.php
            }

            // Already linked?
            $linked = $db->queryOne("SELECT id FROM employees WHERE user_id = ? LIMIT 1", [$userId]);
            if ($linked) return;

            // If an employee exists with same email, link it
            $byEmail = $db->queryOne("SELECT id, user_id FROM employees WHERE {$orgFilter} email = ? LIMIT 1", [$email]);
            if ($byEmail && empty($byEmail['user_id'])) {
                $db->execute("UPDATE employees SET user_id = ? WHERE {$orgFilter} id = ?", [$userId, $byEmail['id']]);
                return;
            }

            // Create a new employee profile
            $parts = preg_split('/\s+/', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            $firstName = $parts[0] ?? 'Employee';
            $lastName = (count($parts) > 1) ? implode(' ', array_slice($parts, 1)) : 'HR';

            $lastEmployee = $db->queryOne("SELECT id FROM employees {$orgWhere} ORDER BY id DESC LIMIT 1");
            $nextId = $lastEmployee ? ((int)$lastEmployee['id'] + 1) : 1;
            $employeeCode = 'EMP-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);

            // If email already exists (unique), don't fail the whole user create
            $emailExists = $db->queryOne("SELECT id FROM employees WHERE {$orgFilter} email = ? LIMIT 1", [$email]);
            if ($emailExists) return;

            $db->execute(
                "INSERT INTO employees (employee_code, user_id, first_name, last_name, email, status) VALUES (?, ?, ?, ?, ?, 'active')",
                [$employeeCode, $userId, $firstName, $lastName, $email]
            );
        };

        if ($action === 'create') {
            // Create new user
            $validator = new Validator($_POST);
            $validator->required('full_name', 'Full name is required');
            $validator->required('email', 'Email is required');
            $validator->required('password', 'Password is required');
            $validator->required('role', 'Role is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                // Check if email already exists
                $existing = $db->queryOne("SELECT id FROM users WHERE {$orgFilter} email = ?", [$data['email']]);
                if ($existing) {
                    $message = 'Email already exists';
                    $messageType = 'error';
                } else {
                    // Hash password
                    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                    // username is NOT NULL UNIQUE - use email so it's unique and login-by-email works
                    $username = trim((string)($data['email'] ?? ''));
                    if ($username === '') {
                        $message = 'Email is required for username';
                        $messageType = 'error';
                    } else {
                    $dailyTarget = 0;
                    if (in_array($data['role'] ?? '', ['sales_executive', 'store_manager']) && isset($data['daily_sales_target']) && $data['daily_sales_target'] !== '') {
                        $dailyTarget = max(0, (float)$data['daily_sales_target']);
                    }
                    $dailyTarget = $dailyTarget > 0 ? $dailyTarget : (in_array($data['role'] ?? '', ['sales_executive', 'store_manager']) ? 10000 : 0);
                    $query = "INSERT INTO users (username, full_name, email, password, role, status, daily_sales_target) VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $id = $db->execute($query, [
                        $username,
                        $data['full_name'],
                        $data['email'],
                        $hashedPassword,
                        $data['role'],
                        $data['status'] ?? 'active',
                        $dailyTarget
                    ]);

                    // Auto-link/create employee record for HR users
                    if (($data['role'] ?? '') === 'hr') {
                        try {
                            $ensureEmployeeLinkedForHrUser((int)$id, (string)$data['full_name'], (string)$data['email']);
                        } catch (Exception $e) {
                            // Don't block user creation; just log
                            error_log("HR employee auto-link failed: " . $e->getMessage());
                        }
                        Session::setFlash('HR user created. Employee profile linked/created automatically.', 'success');
                    } else {
                        Session::setFlash('User created successfully', 'success');
                    }
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                    }
                }
            }
            
        } elseif ($action === 'update') {
            // Update existing user
            $validator = new Validator($_POST);
            $validator->required('id', 'User ID is required');
            $validator->required('full_name', 'Full name is required');
            $validator->required('email', 'Email is required');
            $validator->required('role', 'Role is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                // Check if email already exists for another user
                $existing = $db->queryOne("SELECT id FROM users WHERE {$orgFilter} email = ? AND id != ?", [$data['email'], $data['id']]);
                if ($existing) {
                    $message = 'Email already exists for another user';
                    $messageType = 'error';
                } else {
                    // Update user (username kept in sync with email for login)
                    $username = trim((string)($data['email'] ?? ''));
                    if ($username === '') {
                        $message = 'Email is required';
                        $messageType = 'error';
                    } else {
                    $dailyTarget = 0;
                    if (in_array($data['role'] ?? '', ['sales_executive', 'store_manager']) && isset($data['daily_sales_target']) && $data['daily_sales_target'] !== '') {
                        $dailyTarget = max(0, (float)$data['daily_sales_target']);
                    }
                    if (!empty($data['password'])) {
                        // Update with password
                        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                        $query = "UPDATE users SET username = ?, full_name = ?, email = ?, password = ?, role = ?, status = ?, daily_sales_target = ? WHERE {$orgFilter} id = ?";
                        $affected = $db->execute($query, [
                            $username,
                            $data['full_name'],
                            $data['email'],
                            $hashedPassword,
                            $data['role'],
                            $data['status'] ?? 'active',
                            $dailyTarget,
                            $data['id']
                        ]);
                    } else {
                        // Update without password
                        $query = "UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, status = ?, daily_sales_target = ? WHERE {$orgFilter} id = ?";
                        $affected = $db->execute($query, [
                            $username,
                            $data['full_name'],
                            $data['email'],
                            $data['role'],
                            $data['status'] ?? 'active',
                            $dailyTarget,
                            $data['id']
                        ]);
                    }
                    
                    if ($affected > 0) {
                        // If role updated to HR, auto-link/create employee record
                        if (($data['role'] ?? '') === 'hr') {
                            try {
                                $ensureEmployeeLinkedForHrUser((int)$data['id'], (string)$data['full_name'], (string)$data['email']);
                            } catch (Exception $e) {
                                error_log("HR employee auto-link failed (update): " . $e->getMessage());
                            }
                            Session::setFlash('User updated. HR employee profile linked/created automatically.', 'success');
                        } else {
                            Session::setFlash('User updated successfully', 'success');
                        }
                    } else {
                        Session::setFlash('User not found or no changes made', 'error');
                    }
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                    }
                }
            }
            
        } elseif ($action === 'delete') {
            // Delete user (soft delete - set status to inactive)
            $id = $_POST['id'] ?? null;
            $currentUserId = Session::getUserId();
            
            if (!$id) {
                $message = 'User ID is required';
                $messageType = 'error';
            } elseif ($id == $currentUserId) {
                $message = 'You cannot delete your own account';
                $messageType = 'error';
            } else {
                // Soft delete - set status to inactive
                $affected = $db->execute("UPDATE users SET status = 'inactive' WHERE {$orgFilter} id = ?", [$id]);
                
                if ($affected > 0) {
                    Session::setFlash('User deactivated successfully', 'success');
                } else {
                    Session::setFlash('User not found', 'error');
                }
                header('Location: ' . $_SERVER['PHP_SELF']);
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

// Get user for editing if edit_id is set
$editUser = null;
$editId = $_GET['edit_id'] ?? null;
if ($editId) {
    $editUser = $db->queryOne("SELECT id, full_name, email, role, status, daily_sales_target FROM users WHERE {$orgFilter} id = ?", [$editId]);
    if (!$editUser) {
        Session::setFlash('User not found', 'error');
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    // Remove password from edit user data
    unset($editUser['password']);
}

// Load all users
$users = $db->query("SELECT id, full_name, email, role, status, last_login, created_at FROM users ORDER BY id DESC");

// Load roles for dropdown
try {
    $roles = $db->query("SELECT name FROM roles ORDER BY name");
} catch (Exception $e) {
    // If roles table doesn't exist, use default roles
    $roles = [
        ['name' => 'super_admin'],
        ['name' => 'admin'],
        ['name' => 'hr'],
        ['name' => 'store_manager'],
        ['name' => 'sales_executive'],
        ['name' => 'accountant'],
        ['name' => 'user']
    ];
    error_log("Roles query error: " . $e->getMessage());
}

// Ensure HR role appears in dropdown even if not seeded yet
$hasHr = false;
foreach ($roles as $r) {
    if (($r['name'] ?? '') === 'hr') { $hasHr = true; break; }
}
if (!$hasHr) {
    $roles[] = ['name' => 'hr'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/modal.css">
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
                        <span class="breadcrumb-item active">Users</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">User Management</h1>
                        <button class="btn btn-primary" onclick="openModal('userModal', true)">
                            <span>➕</span> Add User
                        </button>
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
                        <h3 class="card-title">All Users</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" style="text-align:center;padding:40px;">
                                            No users found. Click "Add User" to create one.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge badge-<?= $user['role'] === 'super_admin' ? 'danger' : ($user['role'] === 'admin' ? 'primary' : 'secondary') ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $user['role'])) ?>
                                                </span>
                                            </td>
                                            <td><?= $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never' ?></td>
                                            <td>
                                                <?php if ($user['status'] === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?edit_id=<?= $user['id'] ?>" class="btn btn-ghost btn-sm" title="Edit" onclick="event.preventDefault(); editUser(<?= htmlspecialchars(json_encode($user)) ?>);">
                                                    ✏️
                                                </a>
                                                <?php if ($user['id'] != Session::getUserId()): ?>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to deactivate <?= htmlspecialchars(addslashes($user['full_name'])) ?>?');">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-ghost btn-sm" title="Deactivate">🗑️</button>
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

    <!-- Add/Edit User Modal -->
    <div class="modal-backdrop" id="userModal" style="display:none;">
        <div class="modal" style="max-width:600px;">
            <div class="modal-header">
                <h3 class="modal-title" id="userModalTitle">Add User</h3>
                <button class="modal-close" onclick="closeModal('userModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="userForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="userId">
                    <div class="form-group">
                        <label class="form-label required">Full Name</label>
                        <input type="text" name="full_name" id="userFullName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Email</label>
                        <input type="email" name="email" id="userEmail" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" id="passwordLabel">Password</label>
                        <input type="password" name="password" id="userPassword" class="form-control" required>
                        <small class="text-muted" id="passwordHint" style="display: none;">Leave blank to keep existing password when editing</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label required">Role</label>
                        <select name="role" id="userRole" class="form-control" required onchange="toggleDailyTarget()">
                            <option value="">Select Role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= htmlspecialchars($role['name']) ?>"><?= ucfirst(str_replace('_', ' ', $role['name'])) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="dailyTargetGroup" style="display:none;">
                        <label class="form-label">Daily Sales Target (₹)</label>
                        <input type="number" name="daily_sales_target" id="userDailySalesTarget" class="form-control" min="0" step="100" placeholder="e.g. 10000">
                        <small class="text-muted">For Sales Executive / Store Manager. Shown on their dashboard. Default: ₹10,000</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="userStatus" class="form-control">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('userModal')">Cancel</button>
                <button type="submit" form="userForm" class="btn btn-primary">Save User</button>
            </div>
        </div>
    </div>

    <!-- Minimal JavaScript for Modal Only -->
    <script>
        function openModal(modalId, isNew = false) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                if (isNew) {
                    document.getElementById('userForm').reset();
                    document.getElementById('formAction').value = 'create';
                    document.getElementById('userId').value = '';
                    document.getElementById('userModalTitle').textContent = 'Add User';
                    document.getElementById('passwordLabel').textContent = 'Password';
                    document.getElementById('userPassword').required = true;
                    document.getElementById('passwordHint').style.display = 'none';
                    toggleDailyTarget();
                }
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = '';
                document.getElementById('userForm').reset();
                document.getElementById('formAction').value = 'create';
                document.getElementById('userId').value = '';
                document.getElementById('userModalTitle').textContent = 'Add User';
            }
        }

        function toggleDailyTarget() {
            const role = document.getElementById('userRole').value;
            const group = document.getElementById('dailyTargetGroup');
            if (group) group.style.display = (role === 'sales_executive' || role === 'store_manager') ? 'block' : 'none';
        }

        function editUser(user) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('userId').value = user.id;
            document.getElementById('userFullName').value = user.full_name || '';
            document.getElementById('userEmail').value = user.email || '';
            document.getElementById('userPassword').value = '';
            document.getElementById('userRole').value = user.role || '';
            document.getElementById('userDailySalesTarget').value = (user.daily_sales_target != null && user.daily_sales_target !== '') ? user.daily_sales_target : '';
            document.getElementById('userStatus').value = user.status || 'active';
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('passwordLabel').textContent = 'Password (Leave blank to keep existing)';
            document.getElementById('userPassword').required = false;
            document.getElementById('passwordHint').style.display = 'block';
            toggleDailyTarget();
            openModal('userModal');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('userModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        closeModal('userModal');
                    }
                });
            }
            <?php if ($editUser): ?>
                editUser(<?= json_encode($editUser) ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>