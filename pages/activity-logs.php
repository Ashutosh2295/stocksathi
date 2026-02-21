<?php
/**
 * Activity Logs Page - Core PHP Version
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

// Get query parameters for filtering and pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$moduleFilter = $_GET['module'] ?? '';
$userFilter = $_GET['user'] ?? '';

// Build query
$orgActivityFilter = $orgIdPatch ? " AND al.organization_id = " . intval($orgIdPatch) : "";
$query = "SELECT al.*, u.full_name as user_name
          FROM activity_logs al
          LEFT JOIN users u ON al.user_id = u.id
          WHERE 1=1" . $orgActivityFilter;
$params = [];

if (!empty($search)) {
    $query .= " AND (al.action LIKE ? OR al.description LIKE ? OR al.module LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($moduleFilter)) {
    $query .= " AND al.module = ?";
    $params[] = $moduleFilter;
}

if (!empty($userFilter)) {
    $query .= " AND al.user_id = ?";
    $params[] = $userFilter;
}

// Get total count - build count query separately to avoid LIMIT/OFFSET issues
$countQuery = "SELECT COUNT(*) as total FROM activity_logs al WHERE 1=1" . $orgActivityFilter;
$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (al.action LIKE ? OR al.description LIKE ? OR al.module LIKE ?)";
    $searchParam = "%{$search}%";
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
    $countParams[] = $searchParam;
}

if (!empty($moduleFilter)) {
    $countQuery .= " AND al.module = ?";
    $countParams[] = $moduleFilter;
}

if (!empty($userFilter)) {
    $countQuery .= " AND al.user_id = ?";
    $countParams[] = $userFilter;
}

$totalResult = $db->queryOne($countQuery, $countParams);
$total = (int)($totalResult['total'] ?? 0);
$totalPages = ceil($total / $limit);

// Add ordering and pagination (LIMIT/OFFSET need to be literal integers)
$query .= " ORDER BY al.created_at DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$activities = $db->query($query, $params);

// Get unique modules for filter
$modules = $db->query("SELECT DISTINCT module FROM activity_logs WHERE module IS NOT NULL" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . " ORDER BY module");
$users = $db->query("SELECT id, full_name FROM users WHERE full_name IS NOT NULL" . ($orgIdPatch ? " AND organization_id = " . intval($orgIdPatch) : "") . " ORDER BY full_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Activity Logs</span>
                    </nav>
                    <h1 class="content-title">Activity Logs</h1>
                </div>
                
                <!-- Filters -->
                <div class="card mb-6">
                    <div class="card-body">
                        <form method="GET" action="" class="search-filter-group" style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <input type="text" name="search" class="form-control" placeholder="Search activities..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; min-width: 200px;">
                            <select name="module" class="form-control" style="min-width: 150px;">
                                <option value="">All Modules</option>
                                <?php foreach ($modules as $mod): ?>
                                    <option value="<?= htmlspecialchars($mod['module']) ?>" <?= $moduleFilter === $mod['module'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mod['module']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select name="user" class="form-control" style="min-width: 150px;">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?= $user['id'] ?>" <?= $userFilter == $user['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary">Search</button>
                            <?php if ($search || $moduleFilter || $userFilter): ?>
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-ghost">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Activities</h3>
                    </div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Module</th>
                                    <th>Description</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($activities)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            No activity logs found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activities as $activity): ?>
                                        <tr>
                                            <td><?= date('Y-m-d H:i:s', strtotime($activity['created_at'])) ?></td>
                                            <td><?= htmlspecialchars($activity['user_name'] ?? 'System') ?></td>
                                            <td><?= htmlspecialchars($activity['action'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($activity['module'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($activity['description'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($activity['ip_address'] ?? '-') ?></td>
                                            <td><span class="badge badge-success">Success</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Showing <?= $total > 0 ? (($page - 1) * $limit + 1) : 0 ?>-<?= min($page * $limit, $total) ?> of <?= $total ?> activities
                            </div>
                            <div class="pagination-controls">
                                <?php if ($page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-btn">Previous</a>
                                <?php else: ?>
                                    <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">Previous</span>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-btn">Next</a>
                                <?php else: ?>
                                    <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">Next</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
