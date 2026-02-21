<?php
/**
 * 403 Access Denied Page
 */
require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/Session.php';

Session::start();
$userName = Session::getUserName() ?? 'User';
$userRole = Session::getUserRole() ?? 'user';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; background: var(--bg-secondary);">
    <div style="text-align: center; max-width: 500px; padding: 40px;">
        <div style="font-size: 120px; margin-bottom: 24px;">🔒</div>
        <h1 style="font-size: 48px; color: var(--color-danger); margin: 0 0 16px 0;">403</h1>
        <h2 style="font-size: 24px; color: var(--text-primary); margin: 0 0 16px 0;">Access Denied</h2>
        <p style="color: var(--text-secondary); margin: 0 0 32px 0;">
            Sorry, you don't have permission to access this page.
        </p>
        <div style="background: #f0f9ff; border: 1px solid #0ea5e9; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <p style="margin: 0; color: #0369a1; font-size: 14px;">
                <strong>Your Role:</strong> <?= ucfirst(str_replace('_', ' ', htmlspecialchars($userRole))) ?><br>
                <strong>Username:</strong> <?= htmlspecialchars($userName) ?>
            </p>
        </div>
        <a href="<?= BASE_PATH ?>/index.php" class="btn btn-primary" style="text-decoration: none;">
            ← Back to Dashboard
        </a>
    </div>
</body>
</html>
