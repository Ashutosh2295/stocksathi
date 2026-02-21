<?php
require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Session.php';

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$userId = Session::getUserId();

$notifs = [];
try {
    $notifs = $db->query("SELECT * FROM notifications WHERE (user_id = ? OR user_id IS NULL)" . ($orgIdPatch ? " AND (organization_id = " . intval($orgIdPatch) . " OR organization_id IS NULL)" : "") . " ORDER BY created_at DESC LIMIT 100", [$userId]);
} catch (Exception $e) {}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Notifications - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
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
                        <span class="breadcrumb-item active">Notifications</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Notifications</h1>
                        <a href="<?= BASE_PATH ?>/pages/api/mark_all_read.php" class="btn btn-outline">✓ Mark All as Read</a>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body" style="padding: 0;">
                        <?php if (empty($notifs)): ?>
                            <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                                You have no notifications.
                            </div>
                        <?php else: ?>
                            <?php foreach($notifs as $n): ?>
                                <?php 
                                    $dotColor = '#3b82f6'; 
                                    if ($n['type'] == 'success') $dotColor = '#10b981';
                                    if ($n['type'] == 'warning') $dotColor = '#f59e0b';
                                    if ($n['type'] == 'danger') $dotColor = '#ef4444';
                                    
                                    $bg = $n['is_read'] ? 'white' : 'var(--color-primary-lighter)';
                                ?>
                                <div style="display: flex; gap: 16px; padding: 16px 20px; border-bottom: 1px solid var(--border-light); background: <?= $bg ?>;">
                                    <div style="width: 12px; height: 12px; border-radius: 50%; background: <?= $dotColor ?>; margin-top: 6px;"></div>
                                    <div style="flex: 1;">
                                        <div style="font-size: 16px; font-weight: 600; color: var(--text-primary); margin-bottom: 4px;">
                                            <?= htmlspecialchars($n['title']) ?>
                                        </div>
                                        <div style="font-size: 14px; color: var(--text-secondary); margin-bottom: 8px;">
                                            <?= nl2br(htmlspecialchars($n['message'])) ?>
                                        </div>
                                        <div style="font-size: 12px; color: var(--text-muted);">
                                            <?= date('M d, Y h:i A', strtotime($n['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
