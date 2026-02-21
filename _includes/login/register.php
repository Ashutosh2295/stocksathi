<?php
require_once 'auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

// Get error messages
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';
unset($_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Stocksathi</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <!-- Left Section - Branding -->
        <div class="left-section">
            <div class="branding-content">
                <div class="logo">
                    <svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="48" height="48" rx="12" fill="url(#gradient)"/>
                        <path d="M24 14L30 20H27V28H21V20H18L24 14Z" fill="white"/>
                        <path d="M14 32H34V34H14V32Z" fill="white" opacity="0.8"/>
                        <defs>
                            <linearGradient id="gradient" x1="0" y1="0" x2="48" y2="48" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#0F766E"/>
                                <stop offset="1" stop-color="#10B981"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <span class="logo-text">Stocksathi</span>
                </div>
                
                <h1 class="headline">Manage Your Inventory Smarter</h1>
                <p class="subtext">Products, stock, sales, and users — all in one place</p>
                
                <div class="illustration">
                    <svg width="400" height="300" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Warehouse Building -->
                        <rect x="80" y="120" width="120" height="100" fill="url(#warehouseGradient)" rx="4" opacity="0.9"/>
                        <rect x="90" y="130" width="100" height="80" fill="white" opacity="0.3" rx="2"/>
                        <rect x="100" y="140" width="20" height="20" fill="white" opacity="0.4" rx="2"/>
                        <rect x="130" y="140" width="20" height="20" fill="white" opacity="0.4" rx="2"/>
                        <rect x="160" y="140" width="20" height="20" fill="white" opacity="0.4" rx="2"/>
                        <path d="M 80 120 L 140 80 L 200 120 Z" fill="url(#roofGradient)" opacity="0.9"/>
                        
                        <!-- Product Boxes -->
                        <rect x="240" y="150" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                        <rect x="250" y="160" width="30" height="20" fill="white" opacity="0.2" rx="1"/>
                        <rect x="300" y="140" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                        <rect x="310" y="150" width="30" height="20" fill="white" opacity="0.2" rx="1"/>
                        <rect x="270" y="200" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                        <rect x="280" y="210" width="30" height="20" fill="white" opacity="0.2" rx="1"/>
                        
                        <!-- Barcode -->
                        <rect x="100" y="240" width="3" height="30" fill="#0F766E" rx="1"/>
                        <rect x="108" y="240" width="2" height="30" fill="#0F766E" rx="1"/>
                        <rect x="115" y="240" width="4" height="30" fill="#0F766E" rx="1"/>
                        <rect x="125" y="240" width="2" height="30" fill="#0F766E" rx="1"/>
                        <rect x="133" y="240" width="3" height="30" fill="#0F766E" rx="1"/>
                        <rect x="142" y="240" width="5" height="30" fill="#0F766E" rx="1"/>
                        <rect x="153" y="240" width="2" height="30" fill="#0F766E" rx="1"/>
                        <rect x="161" y="240" width="3" height="30" fill="#0F766E" rx="1"/>
                        <rect x="170" y="240" width="4" height="30" fill="#0F766E" rx="1"/>
                        <rect x="180" y="240" width="2" height="30" fill="#0F766E" rx="1"/>
                        
                        <!-- Product List Icon -->
                        <rect x="250" y="100" width="100" height="30" fill="white" opacity="0.2" rx="4"/>
                        <line x1="260" y1="110" x2="330" y2="110" stroke="#10B981" stroke-width="2" stroke-linecap="round"/>
                        <line x1="260" y1="118" x2="310" y2="118" stroke="#10B981" stroke-width="2" stroke-linecap="round"/>
                        <line x1="260" y1="126" x2="320" y2="126" stroke="#10B981" stroke-width="2" stroke-linecap="round"/>
                        
                        <defs>
                            <linearGradient id="warehouseGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#0F766E" stop-opacity="0.8"/>
                                <stop offset="100%" stop-color="#0F766E" stop-opacity="0.6"/>
                            </linearGradient>
                            <linearGradient id="roofGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#10B981" stop-opacity="0.8"/>
                                <stop offset="100%" stop-color="#0F766E" stop-opacity="0.8"/>
                            </linearGradient>
                            <linearGradient id="boxGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#10B981" stop-opacity="0.7"/>
                                <stop offset="100%" stop-color="#0F766E" stop-opacity="0.7"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>
        </div>
        
        <!-- Right Section - Registration Form -->
        <div class="right-section">
            <div class="form-card">
                <h2 class="form-title">Create Business Account</h2>
                <p class="form-subtitle">Get started with your inventory management system</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/>
                            <path d="M10 6V10M10 14H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        <span><?php echo htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="auth-form">
                    <div class="input-group">
                        <label for="name">Full Name</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M10 10C12.3012 10 14.1667 8.13451 14.1667 5.83333C14.1667 3.53215 12.3012 1.66667 10 1.66667C7.69882 1.66667 5.83333 3.53215 5.83333 5.83333C5.83333 8.13451 7.69882 10 10 10Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M17.1583 18.3333C17.1583 15.1083 13.95 12.5 10 12.5C6.05 12.5 2.84167 15.1083 2.84167 18.3333" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="email">Email</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M2.5 6.66667L10 11.6667L17.5 6.66667M3.33333 15H16.6667C17.5871 15 18.3333 14.2538 18.3333 13.3333V6.66667C18.3333 5.74619 17.5871 5 16.6667 5H3.33333C2.41286 5 1.66667 5.74619 1.66667 6.66667V13.3333C1.66667 14.2538 2.41286 15 3.33333 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input type="email" id="email" name="email" placeholder="Enter your email" required>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M15.8333 9.16667H4.16667C3.24619 9.16667 2.5 9.91286 2.5 10.8333V16.6667C2.5 17.5871 3.24619 18.3333 4.16667 18.3333H15.8333C16.7538 18.3333 17.5 17.5871 17.5 16.6667V10.8333C17.5 9.91286 16.7538 9.16667 15.8333 9.16667Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5.83333 9.16667V5.83333C5.83333 4.72826 6.27232 3.66846 7.05372 2.88706C7.83512 2.10565 8.89493 1.66667 10 1.66667C11.1051 1.66667 12.1649 2.10565 12.9463 2.88706C13.7277 3.66846 14.1667 4.72826 14.1667 5.83333V9.16667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input type="password" id="password" name="password" placeholder="Create a password (min. 8 characters)" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <svg class="eye-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M10 4.16667C5.83333 4.16667 2.275 6.73333 1.04167 10.4167C2.275 14.1 5.83333 16.6667 10 16.6667C14.1667 16.6667 17.725 14.1 18.9583 10.4167C17.725 6.73333 14.1667 4.16667 10 4.16667ZM10 14.5833C7.7 14.5833 5.83333 12.7167 5.83333 10.4167C5.83333 8.11667 7.7 6.25 10 6.25C12.3 6.25 14.1667 8.11667 14.1667 10.4167C14.1667 12.7167 12.3 14.5833 10 14.5833ZM10 7.91667C8.61667 7.91667 7.5 9.03333 7.5 10.4167C7.5 11.8 8.61667 12.9167 10 12.9167C11.3833 12.9167 12.5 11.8 12.5 10.4167C12.5 9.03333 11.3833 7.91667 10 7.91667Z" fill="currentColor"/>
                                </svg>
                                <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" style="display: none;">
                                    <path d="M2.5 2.5L17.5 17.5M8.33333 8.33333C7.89131 8.77535 7.64441 9.37522 7.64441 10C7.64441 10.6248 7.89131 11.2246 8.33333 11.6667M8.33333 8.33333L11.6667 11.6667M8.33333 8.33333L6.25 6.25M11.6667 11.6667C12.1087 11.2246 12.3556 10.6248 12.3556 10C12.3556 9.37522 12.1087 8.77535 11.6667 8.33333M11.6667 11.6667L13.75 13.75M6.25 6.25C4.26667 7.39167 2.825 9.14167 2.10833 11.25M6.25 6.25L2.5 2.5M13.75 13.75C15.7333 12.6083 17.175 10.8583 17.8917 8.75M13.75 13.75L17.5 17.5M2.10833 11.25C2.83333 13.3917 4.28333 15.1583 6.28333 16.3083M17.8917 8.75C17.1667 6.60833 15.7167 4.84167 13.7167 3.69167" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M15.8333 9.16667H4.16667C3.24619 9.16667 2.5 9.91286 2.5 10.8333V16.6667C2.5 17.5871 3.24619 18.3333 4.16667 18.3333H15.8333C16.7538 18.3333 17.5 17.5871 17.5 16.6667V10.8333C17.5 9.91286 16.7538 9.16667 15.8333 9.16667Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5.83333 9.16667V5.83333C5.83333 4.72826 6.27232 3.66846 7.05372 2.88706C7.83512 2.10565 8.89493 1.66667 10 1.66667C11.1051 1.66667 12.1649 2.10565 12.9463 2.88706C13.7277 3.66846 14.1667 4.72826 14.1667 5.83333V9.16667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <svg class="eye-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                    <path d="M10 4.16667C5.83333 4.16667 2.275 6.73333 1.04167 10.4167C2.275 14.1 5.83333 16.6667 10 16.6667C14.1667 16.6667 17.725 14.1 18.9583 10.4167C17.725 6.73333 14.1667 4.16667 10 4.16667ZM10 14.5833C7.7 14.5833 5.83333 12.7167 5.83333 10.4167C5.83333 8.11667 7.7 6.25 10 6.25C12.3 6.25 14.1667 8.11667 14.1667 10.4167C14.1667 12.7167 12.3 14.5833 10 14.5833ZM10 7.91667C8.61667 7.91667 7.5 9.03333 7.5 10.4167C7.5 11.8 8.61667 12.9167 10 12.9167C11.3833 12.9167 12.5 11.8 12.5 10.4167C12.5 9.03333 11.3833 7.91667 10 7.91667Z" fill="currentColor"/>
                                </svg>
                                <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 20 20" fill="none" style="display: none;">
                                    <path d="M2.5 2.5L17.5 17.5M8.33333 8.33333C7.89131 8.77535 7.64441 9.37522 7.64441 10C7.64441 10.6248 7.89131 11.2246 8.33333 11.6667M8.33333 8.33333L11.6667 11.6667M8.33333 8.33333L6.25 6.25M11.6667 11.6667C12.1087 11.2246 12.3556 10.6248 12.3556 10C12.3556 9.37522 12.1087 8.77535 11.6667 8.33333M11.6667 11.6667L13.75 13.75M6.25 6.25C4.26667 7.39167 2.825 9.14167 2.10833 11.25M6.25 6.25L2.5 2.5M13.75 13.75C15.7333 12.6083 17.175 10.8583 17.8917 8.75M13.75 13.75L17.5 17.5M2.10833 11.25C2.83333 13.3917 4.28333 15.1583 6.28333 16.3083M17.8917 8.75C17.1667 6.60833 15.7167 4.84167 13.7167 3.69167" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" name="register" class="btn-primary">
                        <span>Create Account</span>
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                            <path d="M7.5 15L12.5 10L7.5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </form>
                
                <div class="form-footer">
                    <p>Already have an account? <a href="index.php">Login</a></p>
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