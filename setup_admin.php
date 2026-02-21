<?php
/**
 * Admin Setup Script
 * Creates default admin user if it doesn't exist
 * Run this once after database setup
 */

require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/database.php';
require_once __DIR__ . '/_includes/AuthHelper.php';

$db = Database::getInstance();
$message = '';
$error = '';

// Default admin credentials
$defaultAdmin = [
    'username' => 'admin',
    'email' => 'admin@stocksathi.com',
    'password' => 'admin123',
    'full_name' => 'Admin User',
    'role' => 'admin',
    'status' => 'active'
];

try {
    // Check if admin user already exists
    $existing = $db->queryOne("SELECT id FROM users WHERE email = ? OR username = ?", [
        $defaultAdmin['email'],
        $defaultAdmin['username']
    ]);
    
    if ($existing) {
        // Update password if user exists (in case password was changed)
        $hashedPassword = AuthHelper::hashPassword($defaultAdmin['password']);
        $db->execute(
            "UPDATE users SET password = ?, role = ?, status = ? WHERE id = ?",
            [$hashedPassword, $defaultAdmin['role'], $defaultAdmin['status'], $existing['id']]
        );
        $message = "Admin user already exists. Password has been reset to default.";
    } else {
        // Create new admin user
        $hashedPassword = AuthHelper::hashPassword($defaultAdmin['password']);
        $db->execute(
            "INSERT INTO users (username, email, password, full_name, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $defaultAdmin['username'],
                $defaultAdmin['email'],
                $hashedPassword,
                $defaultAdmin['full_name'],
                $defaultAdmin['role'],
                $defaultAdmin['status']
            ]
        );
        $message = "Admin user created successfully!";
    }
    
    // Also create super_admin if doesn't exist
    $superAdmin = [
        'username' => 'superadmin',
        'email' => 'superadmin@stocksathi.com',
        'password' => 'admin123',
        'full_name' => 'Super Admin',
        'role' => 'super_admin',
        'status' => 'active'
    ];
    
    $existingSuper = $db->queryOne("SELECT id FROM users WHERE email = ? OR username = ?", [
        $superAdmin['email'],
        $superAdmin['username']
    ]);
    
    if (!$existingSuper) {
        $hashedPassword = AuthHelper::hashPassword($superAdmin['password']);
        $db->execute(
            "INSERT INTO users (username, email, password, full_name, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())",
            [
                $superAdmin['username'],
                $superAdmin['email'],
                $hashedPassword,
                $superAdmin['full_name'],
                $superAdmin['role'],
                $superAdmin['status']
            ]
        );
        $message .= " Super Admin user also created!";
    }
    
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Setup - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <style>
        body {
            background: linear-gradient(135deg, #0F766E 0%, #14B8A6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .setup-container {
            background: #FFFFFF;
            border-radius: 20px;
            box-shadow: 0 25px 70px rgba(15, 118, 110, 0.25);
            max-width: 600px;
            width: 100%;
            padding: 48px;
        }
        .credentials-box {
            background: linear-gradient(135deg, rgba(15, 118, 110, 0.08) 0%, rgba(20, 184, 166, 0.08) 100%);
            border: 2px solid rgba(15, 118, 110, 0.2);
            border-radius: 12px;
            padding: 24px;
            margin: 24px 0;
        }
        .credential-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(15, 118, 110, 0.1);
        }
        .credential-item:last-child {
            border-bottom: none;
        }
        .credential-label {
            font-weight: 600;
            color: var(--text-primary);
        }
        .credential-value {
            font-family: monospace;
            color: var(--color-primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1 style="text-align: center; color: var(--color-primary); margin-bottom: 8px;">🔐 Admin Setup</h1>
        <p style="text-align: center; color: var(--text-secondary); margin-bottom: 32px;">
            Default admin credentials have been configured
        </p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="margin-bottom: 24px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
            <div class="alert alert-success" style="margin-bottom: 24px;">
                ✅ <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="credentials-box">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: var(--color-primary);">📋 Default Login Credentials</h3>
            
            <div class="credential-item">
                <span class="credential-label">Admin Email:</span>
                <span class="credential-value">admin@stocksathi.com</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Admin Password:</span>
                <span class="credential-value">admin123</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Admin Role:</span>
                <span class="credential-value">admin</span>
            </div>
            
            <hr style="margin: 20px 0; border: none; border-top: 1px solid rgba(15, 118, 110, 0.2);">
            
            <div class="credential-item">
                <span class="credential-label">Super Admin Email:</span>
                <span class="credential-value">superadmin@stocksathi.com</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Super Admin Password:</span>
                <span class="credential-value">admin123</span>
            </div>
            <div class="credential-item">
                <span class="credential-label">Super Admin Role:</span>
                <span class="credential-value">super_admin</span>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 32px;">
            <a href="<?= BASE_PATH ?>/pages/login.php" class="btn btn-primary" style="text-decoration: none; display: inline-block;">
                Go to Login Page →
            </a>
        </div>
        
        <div style="margin-top: 24px; padding: 16px; background: #FEF3C7; border-radius: 8px; border: 1px solid #FCD34D;">
            <p style="margin: 0; font-size: 13px; color: #92400E;">
                <strong>⚠️ Security Note:</strong> Please change the default password after first login for security purposes.
            </p>
        </div>
    </div>
</body>
</html>
