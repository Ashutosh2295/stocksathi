<?php
require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';

// Get organization ID
$orgId = Session::getOrganizationId();
$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

// Core application roles (shown first; others are from migrations/imports)
$coreRoleNames = ['super_admin', 'admin', 'store_manager', 'sales_executive', 'accountant', 'warehouse_manager'];

// Prefer org-specific roles so new registrations don't see shared/demo data
$rolesFilter = "1=1";
$rolesQueryParams = [$orgId]; // for JOIN u.organization_id = ?
try {
    $hasOrgRoles = $db->queryOne("SELECT 1 FROM roles WHERE organization_id = ? LIMIT 1", [$orgId]);
    if (!$hasOrgRoles && $orgId) {
        // Existing org (e.g. registered before org-scoped roles) — create their roles once
        $orgHasUsers = $db->queryOne("SELECT 1 FROM users WHERE organization_id = ? LIMIT 1", [$orgId]);
        if ($orgHasUsers) {
            require_once __DIR__ . '/../_includes/RBACSeeder.php';
            RBACSeeder::seedForOrganization($orgId);
            $hasOrgRoles = $db->queryOne("SELECT 1 FROM roles WHERE organization_id = ? LIMIT 1", [$orgId]);
        }
    }
    if ($hasOrgRoles) {
        $rolesFilter = "r.organization_id = ?";
        $rolesQueryParams = [$orgId, $orgId]; // JOIN, then WHERE
    }
} catch (Exception $e) {
    // roles table may not have organization_id yet; show all roles (1=1)
}

// Fetch roles with user counts (include display_name if column exists)
try {
    $roles = $db->query("
        SELECT 
            r.id,
            r.name,
            r.description,
            r.created_at,
            COUNT(DISTINCT u.id) as user_count
        FROM roles r
        LEFT JOIN users u ON u.role = r.name AND u.organization_id = ?
        WHERE " . $rolesFilter . "
        GROUP BY r.id, r.name, r.description, r.created_at
        ORDER BY r.name ASC
    ", $rolesQueryParams);
    // Add display_name safely (column may not exist in older schemas)
    foreach ($roles as &$r) {
        $r['display_name'] = null;
    }
    unset($r);
    try {
        $hasDisplayName = $db->queryOne("SHOW COLUMNS FROM roles LIKE 'display_name'");
        if ($hasDisplayName) {
            $withDisplay = $db->query("SELECT id, COALESCE(display_name, name) as display_name FROM roles");
            foreach ($withDisplay as $row) {
                foreach ($roles as &$r) {
                    if ((int)$r['id'] === (int)$row['id']) {
                        $r['display_name'] = $row['display_name'];
                        break;
                    }
                }
            }
            unset($r);
        }
    } catch (Exception $e) {
        // ignore
    }
    // Get permissions for each role
    foreach ($roles as &$role) {
        $permissions = $db->query("
            SELECT p.name, p.module, p.action
            FROM role_permissions rp
            INNER JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ?
        ", [$role['id']]);
        
        $role['permissions'] = $permissions;
        $role['permission_count'] = count($permissions);
        $role['is_core'] = in_array($role['name'], $coreRoleNames, true);
    }
    unset($role);
    // Sort: core roles first, then by name
    usort($roles, function ($a, $b) use ($coreRoleNames) {
        $aCore = in_array($a['name'], $coreRoleNames, true);
        $bCore = in_array($b['name'], $coreRoleNames, true);
        if ($aCore !== $bCore) {
            return $aCore ? -1 : 1;
        }
        return strcasecmp($a['name'], $b['name']);
    });
} catch (Exception $e) {
    error_log("Error loading roles: " . $e->getMessage());
    $roles = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles & Permissions - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Roles & Permissions</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">🔐 Roles & Permissions</h1>
                    </div>
                </div>
                
                <!-- Roles Overview Cards -->
                <div class="grid grid-cols-4 gap-6 mb-6">
                    <div class="kpi-card">
                        <div class="kpi-icon primary">👑</div>
                        <div class="kpi-label">Total Roles</div>
                        <div class="kpi-value"><?= count($roles) ?></div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-icon success">👥</div>
                        <div class="kpi-label">Total Users</div>
                        <div class="kpi-value"><?= array_sum(array_column($roles, 'user_count')) ?></div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-icon info">🔑</div>
                        <div class="kpi-label">Permissions</div>
                        <div class="kpi-value"><?= array_sum(array_column($roles, 'permission_count')) ?></div>
                    </div>
                    
                    <div class="kpi-card">
                        <div class="kpi-icon warning">⚙️</div>
                        <div class="kpi-label">System</div>
                        <div class="kpi-value">RBAC</div>
                    </div>
                </div>
                
                <!-- Roles List -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Roles List</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Role Name</th>
                                    <th>Description</th>
                                    <th>Users</th>
                                    <th>Permissions</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($roles)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <p style="padding: 40px 0; color: var(--text-secondary);">
                                                No roles found. Run the RBAC setup script to create default roles.
                                            </p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($roles as $role): 
                                        $label = !empty($role['display_name']) ? $role['display_name'] : $role['name'];
                                    ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($label) ?></strong>
                                                <?php if (!empty($role['is_core'])): ?>
                                                    <span class="badge badge-primary" style="margin-left: 6px; font-size: 10px;">Core</span>
                                                <?php endif; ?>
                                                <div style="font-size: 11px; color: var(--text-secondary); font-weight: normal;"><?= htmlspecialchars($role['name']) ?></div>
                                            </td>
                                            <td><?= htmlspecialchars($role['description'] ?? '-') ?></td>
                                            <td>
                                                <span class="badge badge-info"><?= $role['user_count'] ?> users</span>
                                            </td>
                                            <td>
                                                <span class="badge badge-success"><?= $role['permission_count'] ?> permissions</span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($role['created_at'])) ?></td>
                                            <td class="table-actions">
                                                <button class="btn btn-ghost btn-sm" onclick="viewPermissions(<?= $role['id'] ?>)">
                                                    👁️ View
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Permissions by Module -->
                <div class="card mt-6">
                    <div class="card-header">
                        <h3 class="card-title">📋 Permissions by Module</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Group all permissions by module
                        $allPermissions = [];
                        foreach ($roles as $role) {
                            foreach ($role['permissions'] as $perm) {
                                $module = $perm['module'];
                                if (!isset($allPermissions[$module])) {
                                    $allPermissions[$module] = [];
                                }
                                $allPermissions[$module][] = $perm;
                            }
                        }
                        ?>
                        
                        <div class="grid grid-cols-3 gap-4">
                            <?php foreach ($allPermissions as $module => $perms): ?>
                                <div style="border: 1px solid var(--border-light); padding: 16px; border-radius: 8px;">
                                    <h4 style="margin: 0 0 12px 0; color: var(--color-primary);">
                                        <?= ucfirst($module) ?>
                                    </h4>
                                    <ul style="list-style: none; padding: 0; margin: 0;">
                                        <?php 
                                        $uniquePerms = array_unique(array_column($perms, 'name'));
                                        foreach ($uniquePerms as $permName): 
                                        ?>
                                            <li style="padding: 4px 0; font-size: 14px;">
                                                ✓ <?= htmlspecialchars($permName) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Role Details Modal -->
                <div id="permissionsModal" class="modal" style="display: none;">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3 id="modalRoleName">Role Permissions</h3>
                            <button class="modal-close" onclick="closeModal()">&times;</button>
                        </div>
                        <div class="modal-body" id="modalBody">
                            <!-- Will be filled by JavaScript -->
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-ghost" onclick="closeModal()">Close</button>
                        </div>
                    </div>
                </div>
                
            </main>
        </div>
    </div>
    
    <script>
        const rolesData = <?= json_encode($roles) ?>;
        
        function viewPermissions(roleId) {
            const role = rolesData.find(r => r.id == roleId);
            if (!role) return;
            
            document.getElementById('modalRoleName').textContent = role.name + ' Permissions';
            
            let html = '<div style="max-height: 400px; overflow-y: auto;">';
            
            if (role.permissions.length === 0) {
                html += '<p style="text-align: center; color: var(--text-secondary); padding: 20px;">No permissions assigned to this role.</p>';
            } else {
                // Group by module
                const byModule = {};
                role.permissions.forEach(perm => {
                    if (!byModule[perm.module]) {
                        byModule[perm.module] = [];
                    }
                    byModule[perm.module].push(perm);
                });
                
                for (const [module, perms] of Object.entries(byModule)) {
                    html += `<div style="margin-bottom: 20px;">`;
                    html += `<h4 style="color: var(--color-primary); margin-bottom: 10px;">${module.toUpperCase()}</h4>`;
                    html += `<ul style="list-style: none; padding: 0;">`;
                    perms.forEach(perm => {
                        html += `<li style="padding: 8px; background: var(--bg-surface); margin: 4px 0; border-radius: 4px;">`;
                        html += `<strong>${perm.name}</strong> - ${perm.action}`;
                        html += `</li>`;
                    });
                    html += `</ul></div>`;
                }
            }
            
            html += '</div>';
            document.getElementById('modalBody').innerHTML = html;
            document.getElementById('permissionsModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('permissionsModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target === document.getElementById('permissionsModal')) {
                closeModal();
            }
        };
    </script>
    
    <style>
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 700px;
            width: 90%;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            padding: 24px;
            border-bottom: 2px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px 12px 0 0;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
            color: white;
        }
        
        .modal-body {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
        }
        
        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #f9fafb;
            border-radius: 0 0 12px 12px;
        }
        
        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 20px;
            cursor: pointer;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        /* Permission Module Styling */
        .permission-module {
            margin-bottom: 24px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .permission-module-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 16px;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .permission-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .permission-item {
            padding: 12px 16px;
            background: white;
            border-bottom: 1px solid #f3f4f6;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background 0.2s ease;
        }
        
        .permission-item:last-child {
            border-bottom: none;
        }
        
        .permission-item:hover {
            background: #f9fafb;
        }
        
        .permission-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .permission-details {
            flex: 1;
        }
        
        .permission-name {
            font-weight: 600;
            color: #1f2937;
            font-size: 14px;
            margin-bottom: 2px;
        }
        
        .permission-action {
            font-size: 12px;
            color: #6b7280;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }
        
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        /* Improved Table Styling */
        .table tbody tr:hover {
            background: #f9fafb;
            transform: scale(1.001);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .badge-success {
            background: #d1fae5;
            color: #065f46;
        }
    </style>
</body>
</html>