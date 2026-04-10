<?php
/**
 * Login Page - Professional Version
 * Uses Stocksathi Design System
 */

require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/AuthHelper.php';
require_once __DIR__ . '/../_includes/Session.php';
require_once __DIR__ . '/../_includes/Validator.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter email/phone and password';
    } else {
        try {
            $result = AuthHelper::login($email, $password);
                
            
            if ($result['success']) {
                // Log login activity
                Database::logActivity('login', 'auth', 'User logged in');

                // Get user role for redirection
                $userRole = $result['user']['role'] ?? 'user';
                
                
                // Determine redirect URL based on role
                $dashboards = [
                    'super_admin' => 'dashboards/super-admin.php',
                    'admin' => 'dashboards/admin.php',
                    'hr' => 'dashboards/hr.php',
                    'store_manager' => 'dashboards/store-manager.php',
                    'sales_executive' => 'dashboards/sales-executive.php',
                    'accountant' => 'dashboards/accountant.php',
                    'warehouse_manager' => 'dashboards/store-manager.php',
                ];
                $redirectUrl = isset($dashboards[$userRole]) ? $dashboards[$userRole] : '../index.php';
                
                // Force session write close to prevent locks
                session_write_close();
                
                if (!headers_sent()) {
                    header('Location: ' . $redirectUrl);
                    exit;
                } else {
                    echo '<script>window.location.href="' . $redirectUrl . '";</script>';
                    exit;
                }
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            $error = 'Login failed: ' . $e->getMessage();
        }
    }
}

// Get remembered email if any
$remembered_email = isset($_COOKIE['user_email']) ? $_COOKIE['user_email'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Stocksathi</title>
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
            max-width: 450px;
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
            gap: 1.5rem;
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--text-secondary);
            user-select: none;
        }

        .checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--color-primary);
        }

        .forgot-password {
            color: var(--color-primary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .forgot-password:hover {
            color: var(--color-primary-dark);
            text-decoration: underline;
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
        
        /* Inventory Specific Animations */
        @keyframes boxStack {
            0% { transform: translateY(-50px); opacity: 0; }
            50% { transform: translateY(0); opacity: 1; }
            100% { transform: translateY(0); opacity: 1; }
        }

        @keyframes boxFloat {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @keyframes paperSlide {
            0% { transform: translateX(-10px); opacity: 0; }
            100% { transform: translateX(0); opacity: 1; }
        }

        .anim-warehouse {
            animation: fadeInUp 0.8s ease-out;
            transform-origin: bottom center;
        }

        .anim-box-1 {
            animation: boxStack 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            animation-delay: 0.2s;
            opacity: 0; /* Star hidden */
        }

        .anim-box-2 {
            animation: boxStack 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            animation-delay: 0.4s;
            opacity: 0;
        }

        .anim-box-3 {
            animation: boxStack 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            animation-delay: 0.6s;
            opacity: 0;
        }
        
        /* Continuous floating for stacked boxes after they appear */
        .anim-box-float {
            animation: boxFloat 3s ease-in-out infinite;
        }

        .anim-paper {
            animation: paperSlide 0.8s ease-out forwards;
            animation-delay: 0.8s;
            opacity: 0;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .container { flex-direction: column; overflow-y: auto; }
            .left-section { min-height: 40vh; padding: 2rem; }
            .illustration { display: none; }
            .right-section { padding: 2rem; }
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
                
                <h1 class="headline">Manage Your Inventory Smarter</h1>
                <p class="subtext">Products, stock, sales, and users — all in one place</p>
                
                <div class="illustration">
                    <!-- Converted to use Blue tones via CSS fill/stroke overrides logic -->
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
                        
                        <!-- Warehouse Building Group -->
                        <g class="anim-warehouse">
                            <rect x="80" y="120" width="120" height="100" fill="url(#warehouseGradient)" rx="4" opacity="0.9"/>
                            <rect x="90" y="130" width="100" height="80" fill="white" opacity="0.3" rx="2"/>
                            <rect x="100" y="140" width="20" height="20" fill="white" opacity="0.4" rx="2"/>
                            <rect x="130" y="140" width="20" height="20" fill="white" opacity="0.4" rx="2"/>
                            <rect x="160" y="140" width="20" height="20" fill="white" opacity="0.4" rx="2"/>
                            <path d="M 80 120 L 140 80 L 200 120 Z" fill="url(#roofGradient)" opacity="0.9"/>
                        </g>
                        
                        <!-- Product Boxes Group -->
                        <g class="anim-box-float">
                            <!-- Box 1 -->
                            <g class="anim-box-1">
                                <rect x="240" y="150" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                                <rect x="250" y="160" width="30" height="20" fill="white" opacity="0.2" rx="1"/>
                            </g>
                            
                            <!-- Box 2 -->
                            <g class="anim-box-2">
                                <rect x="300" y="140" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                                <rect x="310" y="150" width="30" height="20" fill="white" opacity="0.2" rx="1"/>
                            </g>

                            <!-- Box 3 -->
                            <g class="anim-box-3">
                                <rect x="270" y="200" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                                <rect x="280" y="210" width="30" height="20" fill="white" opacity="0.2" rx="1"/>
                            </g>
                        </g>
                        
                        <!-- Clipboard/List Group -->
                        <g class="anim-paper">
                            <!-- Barcode -->
                            <rect x="100" y="240" width="3" height="30" fill="#E3F2FD" rx="1"/>
                            <rect x="108" y="240" width="2" height="30" fill="#E3F2FD" rx="1"/>
                            <rect x="115" y="240" width="4" height="30" fill="#E3F2FD" rx="1"/>
                            <rect x="125" y="240" width="2" height="30" fill="#E3F2FD" rx="1"/>
                            <rect x="133" y="240" width="3" height="30" fill="#E3F2FD" rx="1"/>
                            
                            <!-- Product List Icon -->
                            <rect x="250" y="100" width="100" height="30" fill="white" opacity="0.2" rx="4"/>
                            <line x1="260" y1="110" x2="330" y2="110" stroke="#BBDEFB" stroke-width="2" stroke-linecap="round"/>
                            <line x1="260" y1="118" x2="310" y2="118" stroke="#BBDEFB" stroke-width="2" stroke-linecap="round"/>
                            <line x1="260" y1="126" x2="320" y2="126" stroke="#BBDEFB" stroke-width="2" stroke-linecap="round"/>
                        </g>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Right Section - Login Form -->
        <div class="right-section">
            <div class="form-card">
                <h2 class="form-title">Welcome Back</h2>
                <p class="form-subtitle">Sign in to access your inventory dashboard</p>
                
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
                
                <form method="POST" action="" class="auth-form">
                    <div class="input-group">
                        <label for="email">Email or Mobile Number</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M2.5 6.66667L10 11.6667L17.5 6.66667M3.33333 15H16.6667C17.5871 15 18.3333 14.2538 18.3333 13.3333V6.66667C18.3333 5.74619 17.5871 5 16.6667 5H3.33333C2.41286 5 1.66667 5.74619 1.66667 6.66667V13.3333C1.66667 14.2538 2.41286 15 3.33333 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input type="text" id="email" name="email" placeholder="Enter your email or 10-digit mobile" 
                                   value="<?= htmlspecialchars($remembered_email); ?>" required 
                                   oninput="validateLoginIdentifier()">
                        </div>
                        <div class="validation-msg" id="msg_email"></div>
                    </div>
                    
                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M15.8333 9.16667H4.16667C3.24619 9.16667 2.5 9.91286 2.5 10.8333V16.6667C2.5 17.5871 3.24619 18.3333 4.16667 18.3333H15.8333C16.7538 18.3333 17.5 17.5871 17.5 16.6667V10.8333C17.5 9.91286 16.7538 9.16667 15.8333 9.16667Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5.83333 9.16667V5.83333C5.83333 4.72826 6.27232 3.66846 7.05372 2.88706C7.83512 2.10565 8.89493 1.66667 10 1.66667C11.1051 1.66667 12.1649 2.10565 12.9463 2.88706C13.7277 3.66846 14.1667 4.72826 14.1667 5.83333V9.16667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')" type="button">
                                <svg class="eye-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M10 4.16667C5.83333 4.16667 2.275 6.73333 1.04167 10.4167C2.275 14.1 5.83333 16.6667 10 16.6667C14.1667 16.6667 17.725 14.1 18.9583 10.4167C17.725 6.73333 14.1667 4.16667 10 4.16667ZM10 14.5833C7.7 14.5833 5.83333 12.7167 5.83333 10.4167C5.83333 8.11667 7.7 6.25 10 6.25C12.3 6.25 14.1667 8.11667 14.1667 10.4167C14.1667 12.7167 12.3 14.5833 10 14.5833ZM10 7.91667C8.61667 7.91667 7.5 9.03333 7.5 10.4167C7.5 11.8 8.61667 12.9167 10 12.9167C11.3833 12.9167 12.5 11.8 12.5 10.4167C12.5 9.03333 11.3833 7.91667 10 7.91667Z" fill="currentColor"/>
                                </svg>
                                <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" style="display: none;">
                                    <path d="M2.5 2.5L17.5 17.5M8.33333 8.33333C7.89131 8.77535 7.64441 9.37522 7.64441 10C7.64441 10.6248 7.89131 11.2246 8.33333 11.6667M8.33333 8.33333L11.6667 11.6667M8.33333 8.33333L6.25 6.25M11.6667 11.6667C12.1087 11.2246 12.3556 10.6248 12.3556 10C12.3556 9.37522 12.1087 8.77535 11.6667 8.33333M11.6667 11.6667L13.75 13.75M6.25 6.25C4.26667 7.39167 2.825 9.14167 2.10833 11.25M6.25 6.25L2.5 2.5M13.75 13.75C15.7333 12.6083 17.175 10.8583 17.8917 8.75M13.75 13.75L17.5 17.5M2.10833 11.25C2.83333 13.3917 4.28333 15.1583 6.28333 16.3083M17.8917 8.75C17.1667 6.60833 15.7167 4.84167 13.7167 3.69167" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-wrapper">
                            <input type="checkbox" name="remember" id="remember">
                            <span class="checkmark"></span>
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                    </div>
                    
                    <button type="submit" name="login" class="btn-primary">
                        <span>Login to Dashboard</span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M7.5 15L12.5 10L7.5 5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
                
                <div class="form-footer">
                    <p>New here? <a href="register.php">Register</a></p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const eyeIcon = input.parentElement.querySelector('.eye-icon');
            const eyeOffIcon = input.parentElement.querySelector('.eye-off-icon');
            
            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                input.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        }
        
        function validateLoginIdentifier() {
            const input = document.getElementById('email');
            const val = input.value.trim();
            const msgEl = document.getElementById('msg_email');
            
            // Check if it looks like an email OR 10-digit phone
            let isValid = false;
            let msg = '';
            
            if (/^\d+$/.test(val)) {
                // Numbers only -> assume phone
                if (val.length === 10) {
                    isValid = true;
                } else {
                    msg = 'Please enter exactly 10 digits for mobile number.';
                }
            } else {
                // Check email format
                if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) {
                    isValid = true;
                } else if (val.length > 0) {
                    msg = 'Please enter a valid email address.';
                }
            }
            
            if (isValid) {
                input.style.borderColor = '#10b981';
                msgEl.style.color = '#10b981';
                msgEl.style.display = 'block';
                msgEl.style.fontSize = '12px';
                msgEl.style.marginTop = '4px';
                msgEl.textContent = 'Ok!';
            } else {
                if(val.length > 0) {
                    input.style.borderColor = '#ef4444';
                    msgEl.style.color = '#ef4444';
                    msgEl.style.display = 'block';
                    msgEl.style.fontSize = '12px';
                    msgEl.style.marginTop = '4px';
                    msgEl.textContent = msg;
                } else {
                    input.style.borderColor = '';
                    msgEl.style.display = 'none';
                }
            }
        }

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
