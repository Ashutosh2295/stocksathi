<?php
/**
 * Register Page - Professional Version
 * Uses Stocksathi Design System
 */

// Enable error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Increase timeout to prevent loading issues
set_time_limit(60);
ini_set('max_execution_time', 60);

require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/AuthHelper.php';
require_once __DIR__ . '/../_includes/Session.php';
require_once __DIR__ . '/../_includes/Validator.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Organization Details
    $org_name = trim($_POST['org_name'] ?? '');
    $org_email = trim($_POST['org_email'] ?? '');
    $org_phone = trim($_POST['org_phone'] ?? '');
    $org_address = trim($_POST['org_address'] ?? '');
    $org_gst = trim($_POST['org_gst'] ?? '');
    
    // Super Admin Details
    $admin_name = trim($_POST['admin_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');
    $admin_phone = trim($_POST['admin_phone'] ?? '');
    $admin_username = trim($_POST['admin_username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($org_name) || empty($org_email) || empty($admin_name) || empty($admin_email) || empty($admin_username) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } else {
        try {
            $db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
            $conn = $db->getConnection();
            
            // Start transaction
            $conn->beginTransaction();
            
            // Check if email already exists
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE {$orgFilter} email = ? OR username = ?");
            $checkStmt->execute([$admin_email, $admin_username]);
            if ($checkStmt->fetch()) {
                $error = 'Email or username already exists';
            } else {
                // 1. Create Organization
                $orgStmt = $conn->prepare("
                    INSERT INTO organizations (name, email, phone, address, gst_number, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'active', NOW())
                ");
                $orgStmt->execute([$org_name, $org_email, $org_phone, $org_address, $org_gst]);
                $organizationId = $conn->lastInsertId();
                
                // 2. Create Super Admin User
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $userStmt = $conn->prepare("
                    INSERT INTO users (organization_id, username, email, password, full_name, role, phone, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'super_admin', ?, 'active', NOW())
                ");
                $userStmt->execute([
                    $organizationId,
                    $admin_username,
                    $admin_email,
                    $hashedPassword,
                    $admin_name,
                    $admin_phone
                ]);
                
                // Commit transaction
                $conn->commit();
                
                $success = '🎉 Registration successful! Your organization has been created. Please login to continue.';
                
                // Clear form data on success
                $org_name = $org_email = $org_phone = $org_address = $org_gst = '';
                $admin_name = $admin_email = $admin_phone = $admin_username = '';
            }
        } catch (Exception $e) {
            // Rollback on error
            if (isset($conn)) {
                $conn->rollBack();
            }
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Stocksathi</title>
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/components.css">
    <style>
        /* Base Reset override */
        body {
            margin: 0;
            padding: 0;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            display: flex;
            background: var(--bg-body);
        }

        .container {
            display: flex;
            width: 100%;
            height: 100%;
            max-width: none;
            margin: 0;
        }

        /* Left Section - Branding */
        .left-section {
            flex: 1;
            background: linear-gradient(135deg, var(--color-primary-dark) 0%, var(--color-primary-light) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }

        .left-section::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,255,255,0.05) 0%, transparent 50%);
            pointer-events: none;
        }

        .branding-content {
            position: relative;
            z-index: 1;
            max-width: 500px;
            animation: fadeInUp 0.8s ease-out;
            color: white;
            text-align: center;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 2rem;
        }

        .logo-text {
            font-size: 24px;
            font-weight: 700;
            color: white;
            letter-spacing: -0.5px;
        }

        .headline {
            font-size: 42px;
            font-weight: 700;
            color: white;
            line-height: 1.2;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .subtext {
            font-size: 18px;
            color: rgba(255,255,255,0.8);
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        .illustration {
            margin-top: 2rem;
            opacity: 0.9;
        }

        .illustration svg {
            width: 100%;
            height: auto;
            max-width: 400px;
            filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0.2));
        }

        /* Right Section - Form */
        .right-section {
            flex: 1;
            background: var(--bg-surface);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            overflow-y: auto;
        }

        .form-card {
            background: var(--bg-surface);
            padding: 2rem;
            width: 100%;
            max-width: 480px;
            animation: fadeInRight 0.8s ease-out;
        }

        .form-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }

        .form-subtitle {
            font-size: 16px;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }

        /* Form Elements */
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .input-group label {
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary);
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            color: var(--text-secondary);
            pointer-events: none;
            z-index: 1;
        }

        .input-wrapper input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 1.5px solid var(--border-light);
            border-radius: 8px;
            font-size: 15px;
            font-family: inherit;
            color: var(--text-primary);
            background: var(--bg-body);
            transition: all 0.2s ease;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px var(--color-primary-lighter);
            background: white;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            padding: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            z-index: 1;
        }

        .password-toggle:hover {
            color: var(--color-primary);
        }

        .btn-primary {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: var(--shadow-sm);
            width: 100%;
            margin-top: 0.5rem;
        }

        .btn-primary:hover {
            background: var(--color-primary-dark);
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }

        .form-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 14px;
            color: var(--text-secondary);
        }

        .form-footer a {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .form-footer a:hover {
            color: var(--color-primary-dark);
            text-decoration: underline;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
        }


        /* Multi-Step Form Styles */
        .progress-container {
            padding: 0 20px;
            margin-bottom: 2rem;
        }
        
        .progress-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }
        
        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            flex: 1;
            position: relative;
            z-index: 2;
        }
        
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #718096;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .progress-step.active .step-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .progress-step.completed .step-circle {
            background: #48bb78;
            color: white;
        }
        
        .step-label {
            font-size: 12px;
            color: #718096;
            font-weight: 500;
            text-align: center;
        }
        
        .progress-step.active .step-label {
            color: #667eea;
            font-weight: 600;
        }
        
        .progress-line {
            flex: 1;
            height: 2px;
            background: #e2e8f0;
            margin: 0 -10px;
            margin-bottom: 28px;
            position: relative;
            z-index: 1;
        }
        
        /* Form Steps - IMPORTANT: Hide inactive steps */
        .form-step {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .form-step.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Navigation Buttons */
        .form-navigation {
            display: flex;
            gap: 12px;
            margin-top: 2rem;
        }
        
        .btn-secondary {
            flex: 1;
            padding: 12px 24px;
            border: 2px solid #e2e8f0;
            background: white;
            color: #4a5568;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            border-color: #cbd5e0;
            background: #f7fafc;
        }
        
        .btn-primary {
            flex: 1;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .container { flex-direction: column; overflow-y: auto; }
            .left-section { min-height: 30vh; padding: 2rem; }
            .illustration { display: none; }
            .right-section { padding: 2rem; }
            
            .step-label {
                font-size: 10px;
            }
            
            .step-circle {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Left Section - Branding -->
        <div class="left-section">
            <div class="branding-content">
                <div class="logo" style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; margin-bottom: 2rem;">
                    <!-- Use physical image instead of svg icon -->
                    <img src="../assets/images/logo.png" alt="Stocksathi Logo" style="height: 64px; width: auto; max-width: 100%; object-fit: contain; background: white; padding: 8px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                    <span class="logo-text" style="font-size: 28px;">Stocksathi</span>
                </div>
                
                <h1 class="headline">Join Stocksathi Today</h1>
                <p class="subtext">Start managing your business smarter. Create your account and get started in seconds.</p>
                
                <div class="illustration">
                    <!-- Converted to use Blue tones matching login -->
                    <svg width="400" height="300" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="warehouseGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#42A5F5" stop-opacity="0.8"/>
                                <stop offset="100%" stop-color="#1565C0" stop-opacity="0.6"/>
                            </linearGradient>
                            <linearGradient id="roofGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#64B5F6" stop-opacity="0.8"/>
                                <stop offset="100%" stop-color="#1976D2" stop-opacity="0.8"/>
                            </linearGradient>
                            <linearGradient id="boxGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#90CAF9" stop-opacity="0.9"/>
                                <stop offset="100%" stop-color="#42A5F5" stop-opacity="0.9"/>
                            </linearGradient>
                        </defs>
                        
                        <!-- Warehouse Building -->
                        <rect x="80" y="120" width="120" height="100" fill="url(#warehouseGradient)" rx="4" opacity="0.9"/>
                        <rect x="90" y="130" width="100" height="80" fill="white" opacity="0.3" rx="2"/>
                        <path d="M 80 120 L 140 80 L 200 120 Z" fill="url(#roofGradient)" opacity="0.9"/>
                        
                        <!-- Product Boxes -->
                        <rect x="240" y="150" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                        <rect x="300" y="140" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                        <rect x="270" y="200" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Right Section - Form -->
        <div class="right-section">
            <div class="form-card">
                <h2 class="form-title">Create Your Organization</h2>
                <p class="form-subtitle">Register your organization and create super admin account</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" style="display: flex; align-items: center; gap: 8px; margin-bottom: 1rem;">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/>
                            <path d="M10 6V10M10 14H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success" style="display: flex; align-items: center; gap: 8px; margin-bottom: 1rem;">
                         <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/>
                            <path d="M6 10L9 13L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span><?= htmlspecialchars($success) ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Multi-Step Progress Indicator -->
                <div class="progress-container" style="margin-bottom: 2rem;">
                    <div class="progress-steps">
                        <div class="progress-step active" data-step="1">
                            <div class="step-circle">1</div>
                            <div class="step-label">Organization</div>
                        </div>
                        <div class="progress-line"></div>
                        <div class="progress-step" data-step="2">
                            <div class="step-circle">2</div>
                            <div class="step-label">Admin Details</div>
                        </div>
                        <div class="progress-line"></div>
                        <div class="progress-step" data-step="3">
                            <div class="step-circle">3</div>
                            <div class="step-label">Credentials</div>
                        </div>
                    </div>
                </div>
                
                <form method="POST" action="" class="auth-form" id="registrationForm">
                    <!-- Step 1: Organization Details -->
                    <div class="form-step active" data-step="1">
                        <h3 style="font-size: 18px; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem;">
                            📋 Organization Information
                        </h3>
                        
                        <div class="input-group">
                            <label for="org_name">Organization Name *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M3.33333 16.6667H16.6667M5 16.6667V7.5L10 3.33333L15 7.5V16.6667M7.5 16.6667V12.5H12.5V16.6667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <input type="text" id="org_name" name="org_name" placeholder="ABC Enterprises Pvt Ltd" 
                                       value="<?= htmlspecialchars($org_name ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="org_email">Organization Email *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M2.5 6.66667L10 11.6667L17.5 6.66667M3.33333 15H16.6667C17.5871 15 18.3333 14.2538 18.3333 13.3333V6.66667C18.3333 5.74619 17.5871 5 16.6667 5H3.33333C2.41286 5 1.66667 5.74619 1.66667 6.66667V13.3333C1.66667 14.2538 2.41286 15 3.33333 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <input type="email" id="org_email" name="org_email" placeholder="info@abcenterprises.com" 
                                       value="<?= htmlspecialchars($org_email ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="org_phone">Organization Phone *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M18.3333 14.1V16.6C18.3333 17.7 17.4333 18.6 16.3333 18.6C7.11667 18.6 1.66667 13.15 1.66667 3.93333C1.66667 2.83333 2.56667 1.93333 3.66667 1.93333H6.16667C6.71667 1.93333 7.16667 2.38333 7.16667 2.93333V5.93333C7.16667 6.48333 6.71667 6.93333 6.16667 6.93333H4.66667C4.66667 10.8 7.86667 14 11.7333 14V12.5C11.7333 11.95 12.1833 11.5 12.7333 11.5H15.7333C16.2833 11.5 16.7333 11.95 16.7333 12.5V14.1C16.7333 14.65 17.1833 15.1 17.7333 15.1H18.3333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <input type="tel" id="org_phone" name="org_phone" placeholder="9876543210" 
                                       value="<?= htmlspecialchars($org_phone ?? '') ?>" required>
                            </div>
                        </div>

                    </div>

                    <!-- Step 2: Admin Details -->
                    <div class="form-step" data-step="2">
                        <h3 style="font-size: 18px; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem;">
                            👤 Super Admin Details
                        </h3>
                        
                        <div class="input-group">
                            <label for="admin_name">Full Name *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M16.6667 17.5V15.8333C16.6667 14.9493 16.3155 14.1014 15.6904 13.4763C15.0652 12.8512 14.2174 12.5 13.3333 12.5H6.66667C5.78261 12.5 4.93477 12.8512 4.30964 13.4763C3.68452 14.1014 3.33333 14.9493 3.33333 15.8333V17.5M13.3333 5.83333C13.3333 7.67428 11.841 9.16667 10 9.16667C8.15905 9.16667 6.66667 7.67428 6.66667 5.83333C6.66667 3.99238 8.15905 2.5 10 2.5C11.841 2.5 13.3333 3.99238 13.3333 5.83333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <input type="text" id="admin_name" name="admin_name" placeholder="John Doe" 
                                       value="<?= htmlspecialchars($admin_name ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="admin_email">Email Address *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M2.5 6.66667L10 11.6667L17.5 6.66667M3.33333 15H16.6667C17.5871 15 18.3333 14.2538 18.3333 13.3333V6.66667C18.3333 5.74619 17.5871 5 16.6667 5H3.33333C2.41286 5 1.66667 5.74619 1.66667 6.66667V13.3333C1.66667 14.2538 2.41286 15 3.33333 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <input type="email" id="admin_email" name="admin_email" placeholder="john@abcenterprises.com" 
                                       value="<?= htmlspecialchars($admin_email ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="input-group">
                            <label for="admin_phone">Phone Number</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M18.3333 14.1V16.6C18.3333 17.7 17.4333 18.6 16.3333 18.6C7.11667 18.6 1.66667 13.15 1.66667 3.93333C1.66667 2.83333 2.56667 1.93333 3.66667 1.93333H6.16667C6.71667 1.93333 7.16667 2.38333 7.16667 2.93333V5.93333C7.16667 6.48333 6.71667 6.93333 6.16667 6.93333H4.66667C4.66667 10.8 7.86667 14 11.7333 14V12.5C11.7333 11.95 12.1833 11.5 12.7333 11.5H15.7333C16.2833 11.5 16.7333 11.95 16.7333 12.5V14.1C16.7333 14.65 17.1833 15.1 17.7333 15.1H18.3333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                                <input type="tel" id="admin_phone" name="admin_phone" placeholder="9876543210" 
                                       value="<?= htmlspecialchars($admin_phone ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Credentials -->
                    <div class="form-step" data-step="3">
                        <h3 style="font-size: 18px; font-weight: 600; color: var(--text-primary); margin-bottom: 1.5rem;">
                            🔑 Account Credentials
                        </h3>

                        <div class="input-group">
                            <label for="admin_username">Username *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M10 10C12.7614 10 15 7.76142 15 5C15 2.23858 12.7614 0 10 0C7.23858 0 5 2.23858 5 5C5 7.76142 7.23858 10 10 10ZM10 12.5C6.66667 12.5 0 14.175 0 17.5V20H20V17.5C20 14.175 13.3333 12.5 10 12.5Z" fill="currentColor"/>
                                </svg>
                                <input type="text" id="admin_username" name="admin_username" placeholder="johndoe" 
                                       value="<?= htmlspecialchars($admin_username ?? '') ?>" required>
                            </div>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <div class="input-group" style="flex: 1;">
                                <label for="password">Password *</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path d="M15.8333 9.16667H4.16667C3.24619 9.16667 2.5 9.91286 2.5 10.8333V16.6667C2.5 17.5871 3.24619 18.3333 4.16667 18.3333H15.8333C16.7538 18.3333 17.5 17.5871 17.5 16.6667V10.8333C17.5 9.91286 16.7538 9.16667 15.8333 9.16667Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M5.83333 9.16667V5.83333C5.83333 4.72826 6.27232 3.66846 7.05372 2.88706C7.83512 2.10565 8.89493 1.66667 10 1.66667C11.1051 1.66667 12.1649 2.10565 12.9463 2.88706C13.7277 3.66846 14.1667 4.72826 14.1667 5.83333V9.16667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <input type="password" id="password" name="password" placeholder="Min 6 chars" required>
                                </div>
                            </div>
                            <div class="input-group" style="flex: 1;">
                                <label for="confirm_password">Confirm *</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path d="M15.8333 9.16667H4.16667C3.24619 9.16667 2.5 9.91286 2.5 10.8333V16.6667C2.5 17.5871 3.24619 18.3333 4.16667 18.3333H15.8333C16.7538 18.3333 17.5 17.5871 17.5 16.6667V10.8333C17.5 9.91286 16.7538 9.16667 15.8333 9.16667Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M5.83333 9.16667V5.83333C5.83333 4.72826 6.27232 3.66846 7.05372 2.88706C7.83512 2.10565 8.89493 1.66667 10 1.66667C11.1051 1.66667 12.1649 2.10565 12.9463 2.88706C13.7277 3.66846 14.1667 4.72826 14.1667 5.83333V9.16667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Navigation Buttons -->
                    <div class="form-navigation">
                        <button type="button" class="btn-secondary" id="prevBtn" style="display: none;">
                            ← Previous
                        </button>
                        <button type="button" class="btn-primary" id="nextBtn">
                            Next →
                        </button>
                        <button type="submit" name="register" class="btn-primary" id="submitBtn" style="display: none;">
                            Create Organization
                        </button>
                    </div>
                </form>
                
                <div class="form-footer">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Multi-Step Form Logic
        let currentStep = 1;
        const totalSteps = 3;
        
        function showStep(step) {
            // Hide all steps
            document.querySelectorAll('.form-step').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show current step
            document.querySelector(`.form-step[data-step="${step}"]`).classList.add('active');
            
            // Update progress indicator
            document.querySelectorAll('.progress-step').forEach((el, index) => {
                el.classList.remove('active', 'completed');
                if (index + 1 < step) {
                    el.classList.add('completed');
                } else if (index + 1 === step) {
                    el.classList.add('active');
                }
            });
            
            // Update buttons
            document.getElementById('prevBtn').style.display = step === 1 ? 'none' : 'block';
            document.getElementById('nextBtn').style.display = step === totalSteps ? 'none' : 'block';
            document.getElementById('submitBtn').style.display = step === totalSteps ? 'block' : 'none';
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
        
        function validateStep(step) {
            const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
            const inputs = stepElement.querySelectorAll('input[required]');
            
            for (let input of inputs) {
                if (!input.value.trim()) {
                    input.focus();
                    input.style.borderColor = '#f56565';
                    setTimeout(() => {
                        input.style.borderColor = '';
                    }, 2000);
                    return false;
                }
            }
            
            // Validate email format
            const emailInputs = stepElement.querySelectorAll('input[type="email"]');
            for (let input of emailInputs) {
                if (input.value && !input.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    input.focus();
                    input.style.borderColor = '#f56565';
                    alert('Please enter a valid email address');
                    setTimeout(() => {
                        input.style.borderColor = '';
                    }, 2000);
                    return false;
                }
            }
            
            // Validate password match on step 3
            if (step === 3) {
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (password.length < 6) {
                    document.getElementById('password').focus();
                    alert('Password must be at least 6 characters long');
                    return false;
                }
                
                if (password !== confirmPassword) {
                    document.getElementById('confirm_password').focus();
                    document.getElementById('confirm_password').style.borderColor = '#f56565';
                    alert('Passwords do not match');
                    setTimeout(() => {
                        document.getElementById('confirm_password').style.borderColor = '';
                    }, 2000);
                    return false;
                }
            }
            
            return true;
        }
        
        document.getElementById('nextBtn').addEventListener('click', function() {
            if (validateStep(currentStep)) {
                currentStep++;
                showStep(currentStep);
            }
        });
        
        document.getElementById('prevBtn').addEventListener('click', function() {
            currentStep--;
            showStep(currentStep);
        });
        
        // Initialize
        showStep(currentStep);
        
        // Auto-redirect to login after successful registration
        <?php if ($success): ?>
        setTimeout(() => {
            window.location.href = 'login.php';
        }, 3000); // Redirect after 3 seconds
        <?php endif; ?>
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
