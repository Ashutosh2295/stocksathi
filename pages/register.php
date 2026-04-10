<?php
/**
 * Register Page - With OTP Email Verification
 * Step 1: Organization Details
 * Step 2: Admin Details
 * Step 3: Credentials
 * Step 4: OTP Verification (email sent after step 3)
 */

set_time_limit(60);
ini_set('max_execution_time', 60);

require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/AuthHelper.php';
require_once __DIR__ . '/../_includes/Session.php';
require_once __DIR__ . '/../_includes/Validator.php';
require_once __DIR__ . '/../_includes/RBACSeeder.php';
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
        body { margin:0;padding:0;height:100vh;width:100vw;overflow:hidden;display:flex;background:var(--bg-body); }
        .container { display:flex;width:100%;height:100%;max-width:none;margin:0; }

        .left-section {
            flex:1;background:linear-gradient(135deg,var(--color-primary-dark) 0%,var(--color-primary-light) 100%);
            display:flex;align-items:center;justify-content:center;padding:3rem;position:relative;overflow:hidden;
        }
        .left-section::before {
            content:'';position:absolute;top:0;left:0;right:0;bottom:0;
            background:radial-gradient(circle at 20% 50%,rgba(255,255,255,.05) 0%,transparent 50%),
                       radial-gradient(circle at 80% 80%,rgba(255,255,255,.05) 0%,transparent 50%);
            pointer-events:none;
        }
        .branding-content { position:relative;z-index:1;max-width:500px;animation:fadeInUp .8s ease-out;color:white;text-align:center; }
        .logo { display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:2rem; }
        .logo-text { font-size:24px;font-weight:700;color:white;letter-spacing:-.5px; }
        .headline { font-size:42px;font-weight:700;color:white;line-height:1.2;margin-bottom:1rem;letter-spacing:-1px; }
        .subtext { font-size:18px;color:rgba(255,255,255,.8);margin-bottom:3rem;line-height:1.6; }
        .illustration { margin-top:2rem;opacity:.9; }
        .illustration svg { width:100%;height:auto;max-width:400px;filter:drop-shadow(0 4px 6px rgba(0,0,0,.2)); }

        .right-section {
            flex:1;background:var(--bg-surface);display:flex;align-items:center;justify-content:center;
            padding:3rem;overflow-y:auto;
        }
        .form-card { background:var(--bg-surface);padding:2rem;width:100%;max-width:480px;animation:fadeInRight .8s ease-out; }
        .form-title { font-size:32px;font-weight:700;color:var(--text-primary);margin-bottom:.5rem;letter-spacing:-.5px; }
        .form-subtitle { font-size:16px;color:var(--text-secondary);margin-bottom:2rem; }

        .auth-form { display:flex;flex-direction:column;gap:1.25rem; }
        .input-group { display:flex;flex-direction:column;gap:.5rem; }
        .input-group label { font-size:14px;font-weight:500;color:var(--text-primary); }
        .input-wrapper { position:relative;display:flex;align-items:center; }
        .input-icon { position:absolute;left:16px;color:var(--text-secondary);pointer-events:none;z-index:1; }
        .input-wrapper input {
            width:100%;padding:.875rem 1rem .875rem 3rem;border:1.5px solid var(--border-light);border-radius:8px;
            font-size:15px;font-family:inherit;color:var(--text-primary);background:var(--bg-body);transition:all .2s;box-sizing:border-box;
        }
        .input-wrapper input:focus { outline:none;border-color:var(--color-primary);box-shadow:0 0 0 3px var(--color-primary-lighter);background:white; }
        .password-toggle { position:absolute;right:12px;background:none;border:none;color:var(--text-secondary);cursor:pointer;padding:4px;display:flex;align-items:center;transition:color .2s;z-index:1; }
        .password-toggle:hover { color:var(--color-primary); }

        .btn-primary {
            background:var(--color-primary);color:white;border:none;padding:1rem 1.5rem;border-radius:8px;
            font-size:16px;font-weight:600;font-family:inherit;cursor:pointer;
            display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;
            box-shadow:var(--shadow-sm);width:100%;margin-top:.5rem;flex:1;
        }
        .btn-primary:hover { background:var(--color-primary-dark);box-shadow:var(--shadow-md);transform:translateY(-1px); }
        .btn-primary:disabled { opacity:.7;cursor:not-allowed;transform:none; }
        .btn-secondary {
            flex:1;padding:12px 24px;border:2px solid #e2e8f0;background:white;color:#4a5568;
            border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;transition:all .3s;
        }
        .btn-secondary:hover { border-color:#cbd5e0;background:#f7fafc; }
        .btn-outline { background:transparent;color:var(--color-primary);border:1.5px solid var(--color-primary);padding:.75rem 1.5rem;border-radius:8px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;width:100%;transition:all .2s;margin-top:.5rem; }
        .btn-outline:hover { background:var(--color-primary-lighter); }

        .form-footer { margin-top:2rem;text-align:center;font-size:14px;color:var(--text-secondary); }
        .form-footer a { color:var(--color-primary);text-decoration:none;font-weight:600; }
        .form-footer a:hover { text-decoration:underline; }

        .alert { display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:1rem;animation:fadeIn .3s; }
        .alert-danger  { background:#fef2f2;color:#dc2626;border:1px solid #fecaca; }
        .alert-success { background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0; }
        .alert-info    { background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe; }

        /* Progress Steps */
        .progress-container { padding:0 10px;margin-bottom:2rem; }
        .progress-steps { display:flex;align-items:center;justify-content:space-between;position:relative; }
        .progress-step { display:flex;flex-direction:column;align-items:center;gap:6px;flex:1;position:relative;z-index:2; }
        .step-circle {
            width:36px;height:36px;border-radius:50%;background:#e2e8f0;color:#718096;
            display:flex;align-items:center;justify-content:center;font-weight:600;font-size:14px;transition:all .3s;
        }
        .progress-step.active .step-circle { background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);color:white;box-shadow:0 4px 12px rgba(102,126,234,.4); }
        .progress-step.completed .step-circle { background:#48bb78;color:white; }
        .step-label { font-size:11px;color:#718096;font-weight:500;text-align:center; }
        .progress-step.active .step-label { color:#667eea;font-weight:600; }
        .progress-line { flex:1;height:2px;background:#e2e8f0;margin:0 -8px;margin-bottom:22px;position:relative;z-index:1;transition:background .3s; }
        .progress-line.done { background:#48bb78; }

        .form-step { display:none;animation:fadeIn .3s ease; }
        .form-step.active { display:block; }
        .form-navigation { display:flex;gap:12px;margin-top:2rem; }

        /* OTP Specific */
        .otp-info-box { background:linear-gradient(135deg,#eff6ff,#dbeafe);border:1px solid #bfdbfe;border-radius:12px;padding:18px;margin-bottom:1.25rem;text-align:center; }
        .otp-info-box .mail-icon { font-size:32px;margin-bottom:6px;display:block; }
        .otp-info-box h3 { margin:0 0 4px;font-size:15px;color:#1e40af;font-weight:600; }
        .otp-info-box p  { margin:0;font-size:13px;color:#3b82f6; }
        .otp-input-wrapper { display:flex;gap:8px;justify-content:center;margin:.75rem 0; }
        .otp-digit { width:48px;height:56px;text-align:center;border:2px solid var(--border-light);border-radius:10px;font-size:22px;font-weight:700;color:var(--text-primary);background:var(--bg-body);transition:all .2s;caret-color:var(--color-primary); }
        .otp-digit:focus { outline:none;border-color:var(--color-primary);box-shadow:0 0 0 3px var(--color-primary-lighter);background:white;transform:scale(1.05); }
        .otp-digit.filled { border-color:var(--color-primary);background:var(--color-primary-lighter); }
        .otp-timer { text-align:center;font-size:13px;color:var(--text-secondary);margin-top:6px; }
        .otp-timer span { color:var(--color-primary);font-weight:600; }
        .otp-timer.expired span { color:#dc2626; }
        .resend-row { display:flex;align-items:center;justify-content:center;gap:6px;margin-top:10px;font-size:14px;color:var(--text-secondary); }
        #resend-btn { background:none;border:none;color:var(--color-primary);font-weight:600;cursor:pointer;font-size:14px;padding:0;display:none; }
        #resend-btn:hover { text-decoration:underline; }

        .spinner { width:18px;height:18px;border:2px solid rgba(255,255,255,.4);border-top-color:white;border-radius:50%;animation:spin .7s linear infinite;display:none; }
        .btn-primary.loading .spinner { display:block; }
        .btn-primary.loading .btn-text { display:none; }

        /* Validation Overrides */
        .input-wrapper input.is-valid { border-color: #10b981 !important; padding-right: 2.5rem; background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="%2310b981"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>'); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 1.25rem; }
        .input-wrapper input.is-invalid { border-color: #ef4444 !important; }
        .input-group.has-password-toggle .input-wrapper input.is-valid { background-position: right 2.5rem center; }
        .validation-msg { font-size: 12px; margin-top: 4px; display: none; }
        .validation-msg.error { color: #ef4444; display: block; }
        .validation-msg.success { color: #10b981; display: block; }

        /* Success */
        #success-view { display:none;text-align:center;padding:2rem 0;animation:fadeIn .5s; }

        @keyframes fadeInUp    { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
        @keyframes fadeInRight { from{opacity:0;transform:translateX(30px)} to{opacity:1;transform:translateX(0)} }
        @keyframes fadeIn      { from{opacity:0;transform:translateX(20px)} to{opacity:1;transform:translateX(0)} }
        @keyframes spin        { to{transform:rotate(360deg)} }

        @media(max-width:968px){
            .container{flex-direction:column;overflow-y:auto;}
            .left-section{min-height:30vh;padding:2rem;}
            .illustration{display:none;}
            .right-section{padding:2rem;}
            .step-label{font-size:9px;}.step-circle{width:30px;height:30px;font-size:12px;}
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Left Section -->
        <div class="left-section">
            <div class="branding-content">
                <div class="logo" style="display:flex;flex-direction:column;align-items:center;gap:16px;margin-bottom:2rem;">
                    <img src="../assets/images/logo.png" alt="Stocksathi Logo" style="height:64px;width:auto;max-width:100%;object-fit:contain;background:white;padding:8px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);">
                    <span class="logo-text" style="font-size:28px;">Stocksathi</span>
                </div>
                <h1 class="headline">Join Stocksathi Today</h1>
                <p class="subtext">Start managing your business smarter. Create your account and get started in seconds.</p>
                <div class="illustration">
                    <svg width="400" height="300" viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="warehouseGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#42A5F5" stop-opacity="0.8"/><stop offset="100%" stop-color="#1565C0" stop-opacity="0.6"/>
                            </linearGradient>
                            <linearGradient id="roofGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#64B5F6" stop-opacity="0.8"/><stop offset="100%" stop-color="#1976D2" stop-opacity="0.8"/>
                            </linearGradient>
                            <linearGradient id="boxGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                <stop offset="0%" stop-color="#90CAF9" stop-opacity="0.9"/><stop offset="100%" stop-color="#42A5F5" stop-opacity="0.9"/>
                            </linearGradient>
                        </defs>
                        <rect x="80" y="120" width="120" height="100" fill="url(#warehouseGradient)" rx="4" opacity="0.9"/>
                        <rect x="90" y="130" width="100" height="80" fill="white" opacity="0.3" rx="2"/>
                        <path d="M 80 120 L 140 80 L 200 120 Z" fill="url(#roofGradient)" opacity="0.9"/>
                        <rect x="240" y="150" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                        <rect x="300" y="140" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                        <rect x="270" y="200" width="50" height="40" fill="url(#boxGradient)" rx="3" opacity="0.9"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Right Section -->
        <div class="right-section">
            <div class="form-card">
                <h2 class="form-title" id="main-title">Create Your Organization</h2>
                <p class="form-subtitle" id="main-subtitle">Register your organization and create super admin account</p>

                <div id="global-alert"></div>

                <!-- Progress Indicator (4 steps now) -->
                <div class="progress-container" id="progress-container" style="margin-bottom:2rem;">
                    <div class="progress-steps">
                        <div class="progress-step active" data-step="1">
                            <div class="step-circle">1</div>
                            <div class="step-label">Organization</div>
                        </div>
                        <div class="progress-line" id="pline-1"></div>
                        <div class="progress-step" data-step="2">
                            <div class="step-circle">2</div>
                            <div class="step-label">Admin</div>
                        </div>
                        <div class="progress-line" id="pline-2"></div>
                        <div class="progress-step" data-step="3">
                            <div class="step-circle">3</div>
                            <div class="step-label">Credentials</div>
                        </div>
                        <div class="progress-line" id="pline-3"></div>
                        <div class="progress-step" data-step="4">
                            <div class="step-circle">4</div>
                            <div class="step-label">Verify OTP</div>
                        </div>
                    </div>
                </div>

                <form id="registrationForm" class="auth-form" onsubmit="return false;">
                    <!-- Step 1: Organization Details -->
                    <div class="form-step active" data-step="1">
                        <h3 style="font-size:18px;font-weight:600;color:var(--text-primary);margin-bottom:1.5rem;">
                            &#x1F4CB; Organization Information
                        </h3>
                        <div class="input-group">
                            <label for="org_name">Organization Name *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M3.33333 16.6667H16.6667M5 16.6667V7.5L10 3.33333L15 7.5V16.6667M7.5 16.6667V12.5H12.5V16.6667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <input type="text" id="org_name" name="org_name" placeholder="ABC Enterprises Pvt Ltd" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="org_email">Organization Email *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M2.5 6.66667L10 11.6667L17.5 6.66667M3.33333 15H16.6667C17.5871 15 18.3333 14.2538 18.3333 13.3333V6.66667C18.3333 5.74619 17.5871 5 16.6667 5H3.33333C2.41286 5 1.66667 5.74619 1.66667 6.66667V13.3333C1.66667 14.2538 2.41286 15 3.33333 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <input type="email" id="org_email" name="org_email" placeholder="info@abcenterprises.com" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="org_phone">Organization Phone *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M18.3333 14.1V16.6C18.3333 17.7 17.4333 18.6 16.3333 18.6C7.11667 18.6 1.66667 13.15 1.66667 3.93333C1.66667 2.83333 2.56667 1.93333 3.66667 1.93333H6.16667C6.71667 1.93333 7.16667 2.38333 7.16667 2.93333V5.93333C7.16667 6.48333 6.71667 6.93333 6.16667 6.93333H4.66667C4.66667 10.8 7.86667 14 11.7333 14V12.5C11.7333 11.95 12.1833 11.5 12.7333 11.5H15.7333C16.2833 11.5 16.7333 11.95 16.7333 12.5V14.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <input type="tel" id="org_phone" name="org_phone" placeholder="10-digit mobile no." required>
                            </div>
                            <div class="validation-msg" id="msg_org_phone"></div>
                        </div>
                    </div>

                    <!-- Step 2: Admin Details -->
                    <div class="form-step" data-step="2">
                        <h3 style="font-size:18px;font-weight:600;color:var(--text-primary);margin-bottom:1.5rem;">
                            &#x1F464; Super Admin Details
                        </h3>
                        <div class="input-group">
                            <label for="admin_name">Full Name *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M16.6667 17.5V15.8333C16.6667 14.9493 16.3155 14.1014 15.6904 13.4763C15.0652 12.8512 14.2174 12.5 13.3333 12.5H6.66667C5.78261 12.5 4.93477 12.8512 4.30964 13.4763C3.68452 14.1014 3.33333 14.9493 3.33333 15.8333V17.5M13.3333 5.83333C13.3333 7.67428 11.841 9.16667 10 9.16667C8.15905 9.16667 6.66667 7.67428 6.66667 5.83333C6.66667 3.99238 8.15905 2.5 10 2.5C11.841 2.5 13.3333 3.99238 13.3333 5.83333Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <input type="text" id="admin_name" name="admin_name" placeholder="John Doe" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="admin_email">Email Address *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M2.5 6.66667L10 11.6667L17.5 6.66667M3.33333 15H16.6667C17.5871 15 18.3333 14.2538 18.3333 13.3333V6.66667C18.3333 5.74619 17.5871 5 16.6667 5H3.33333C2.41286 5 1.66667 5.74619 1.66667 6.66667V13.3333C1.66667 14.2538 2.41286 15 3.33333 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <input type="email" id="admin_email" name="admin_email" placeholder="john@abcenterprises.com" required>
                            </div>
                        </div>
                        <div class="input-group">
                            <label for="admin_phone">Phone Number</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M18.3333 14.1V16.6C18.3333 17.7 17.4333 18.6 16.3333 18.6C7.11667 18.6 1.66667 13.15 1.66667 3.93333C1.66667 2.83333 2.56667 1.93333 3.66667 1.93333H6.16667C6.71667 1.93333 7.16667 2.38333 7.16667 2.93333V5.93333C7.16667 6.48333 6.71667 6.93333 6.16667 6.93333H4.66667C4.66667 10.8 7.86667 14 11.7333 14V12.5C11.7333 11.95 12.1833 11.5 12.7333 11.5H15.7333C16.2833 11.5 16.7333 11.95 16.7333 12.5V14.1" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                <input type="tel" id="admin_phone" name="admin_phone" placeholder="10-digit mobile no." required>
                            </div>
                            <div class="validation-msg" id="msg_admin_phone"></div>
                        </div>
                    </div>

                    <!-- Step 3: Credentials -->
                    <div class="form-step" data-step="3">
                        <h3 style="font-size:18px;font-weight:600;color:var(--text-primary);margin-bottom:1.5rem;">
                            &#x1F511; Account Credentials
                        </h3>
                        <div class="input-group">
                            <label for="admin_username">Username *</label>
                            <div class="input-wrapper">
                                <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M10 10C12.7614 10 15 7.76142 15 5C15 2.23858 12.7614 0 10 0C7.23858 0 5 2.23858 5 5C5 7.76142 7.23858 10 10 10ZM10 12.5C6.66667 12.5 0 14.175 0 17.5V20H20V17.5C20 14.175 13.3333 12.5 10 12.5Z" fill="currentColor"/></svg>
                                <input type="text" id="admin_username" name="admin_username" placeholder="johndoe" required>
                            </div>
                        </div>
                        <div style="display:flex;gap:1rem;">
                            <div class="input-group has-password-toggle" style="flex:1;">
                                <label for="password">Password *</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path d="M15.8333 9.16667H4.16667C3.24619 9.16667 2.5 9.91286 2.5 10.8333V16.6667C2.5 17.5871 3.24619 18.3333 4.16667 18.3333H15.8333C16.7538 18.3333 17.5 17.5871 17.5 16.6667V10.8333C17.5 9.91286 16.7538 9.16667 15.8333 9.16667Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M5.83333 9.16667V5.83333C5.83333 4.72826 6.27232 3.66846 7.05372 2.88706C7.83512 2.10565 8.89493 1.66667 10 1.66667C11.1051 1.66667 12.1649 2.10565 12.9463 2.88706C13.7277 3.66846 14.1667 4.72826 14.1667 5.83333V9.16667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <input type="password" id="password" name="password" placeholder="Pass@123..." required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 4.16667C5.83333 4.16667 2.275 6.73333 1.04167 10.4167C2.275 14.1 5.83333 16.6667 10 16.6667C14.1667 16.6667 17.725 14.1 18.9583 10.4167C17.725 6.73333 14.1667 4.16667 10 4.16667ZM10 14.5833C7.7 14.5833 5.83333 12.7167 5.83333 10.4167C5.83333 8.11667 7.7 6.25 10 6.25C12.3 6.25 14.1667 8.11667 14.1667 10.4167C14.1667 12.7167 12.3 14.5833 10 14.5833Z"/></svg>
                                    </button>
                                </div>
                                <div class="validation-msg" id="msg_password"></div>
                            </div>
                            <div class="input-group has-password-toggle" style="flex:1;">
                                <label for="confirm_password">Confirm *</label>
                                <div class="input-wrapper">
                                    <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                        <path d="M15.8333 9.16667H4.16667C3.24619 9.16667 2.5 9.91286 2.5 10.8333V16.6667C2.5 17.5871 3.24619 18.3333 4.16667 18.3333H15.8333C16.7538 18.3333 17.5 17.5871 17.5 16.6667V10.8333C17.5 9.91286 16.7538 9.16667 15.8333 9.16667Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M5.83333 9.16667V5.83333C5.83333 4.72826 6.27232 3.66846 7.05372 2.88706C7.83512 2.10565 8.89493 1.66667 10 1.66667C11.1051 1.66667 12.1649 2.10565 12.9463 2.88706C13.7277 3.66846 14.1667 4.72826 14.1667 5.83333V9.16667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M10 4.16667C5.83333 4.16667 2.275 6.73333 1.04167 10.4167C2.275 14.1 5.83333 16.6667 10 16.6667C14.1667 16.6667 17.725 14.1 18.9583 10.4167C17.725 6.73333 14.1667 4.16667 10 4.16667ZM10 14.5833C7.7 14.5833 5.83333 12.7167 5.83333 10.4167C5.83333 8.11667 7.7 6.25 10 6.25C12.3 6.25 14.1667 8.11667 14.1667 10.4167C14.1667 12.7167 12.3 14.5833 10 14.5833Z"/></svg>
                                    </button>
                                </div>
                                <div class="validation-msg" id="msg_confirm_password"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: OTP Verification -->
                    <div class="form-step" data-step="4">
                        <h3 style="font-size:18px;font-weight:600;color:var(--text-primary);margin-bottom:1rem;">
                            &#x1F4E7; Email Verification
                        </h3>
                        <div class="otp-info-box">
                            <span class="mail-icon">&#x1F4E7;</span>
                            <h3>Check your email</h3>
                            <p id="otp-email-label">A 6-digit OTP has been sent to your email.</p>
                        </div>
                        <div id="otp-alert"></div>
                        <div class="input-group">
                            <label style="text-align:center;">Enter 6-digit OTP</label>
                            <div class="otp-input-wrapper" id="otp-inputs">
                                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="otp-0" autocomplete="one-time-code">
                                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="otp-1">
                                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="otp-2">
                                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="otp-3">
                                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="otp-4">
                                <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="otp-5">
                            </div>
                            <div class="otp-timer" id="otp-timer">OTP expires in <span id="timer-countdown">10:00</span></div>
                            <div class="resend-row">
                                <span>Didn't receive it?</span>
                                <button type="button" id="resend-btn" onclick="resendRegOTP()">Resend OTP</button>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="form-navigation">
                        <button type="button" class="btn-secondary" id="prevBtn" style="display:none;">&#8592; Previous</button>
                        <button type="button" class="btn-primary" id="nextBtn">
                            <span class="btn-text">Next &#8594;</span>
                        </button>
                        <button type="button" class="btn-primary" id="sendOtpBtn" style="display:none;" onclick="sendRegistrationOTP()">
                            <span class="spinner"></span>
                            <span class="btn-text" style="display:flex;align-items:center;gap:8px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2z"/><polyline points="20,6 12,13 4,6"/></svg>
                                Send OTP &amp; Verify
                            </span>
                        </button>
                        <button type="button" class="btn-primary" id="verifyOtpBtn" style="display:none;" disabled onclick="verifyRegistrationOTP()">
                            <span class="spinner"></span>
                            <span class="btn-text" style="display:flex;align-items:center;gap:8px;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                                Verify &amp; Register
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Success View -->
                <div id="success-view">
                    <div style="text-align:center;padding:2rem 0;">
                        <span style="font-size:56px;display:block;margin-bottom:1rem;">&#127881;</span>
                        <h3 style="font-size:22px;font-weight:700;color:var(--text-primary);margin-bottom:.5rem;">Registration Successful!</h3>
                        <p id="success-msg" style="color:var(--text-secondary);font-size:15px;margin-bottom:1.5rem;line-height:1.6;">Your organization has been created. You are now the Super Admin.</p>
                        <a href="login.php" class="btn-primary" style="display:inline-flex;text-decoration:none;width:auto;padding:1rem 2rem;">Go to Login &#8594;</a>
                    </div>
                </div>

                <div class="form-footer" id="form-footer">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
    var currentStep = 1;
    var totalSteps = 4;
    var timerInterval = null;
    var timerSeconds = 600;

    // ── Show / Hide Steps ──────────────────────────────────────────────
    function showStep(step) {
        document.querySelectorAll('.form-step').forEach(function(el) { el.classList.remove('active'); });
        var target = document.querySelector('.form-step[data-step="' + step + '"]');
        if (target) target.classList.add('active');

        // progress indicator
        document.querySelectorAll('.progress-step').forEach(function(el, idx) {
            el.classList.remove('active', 'completed');
            if (idx + 1 < step) el.classList.add('completed');
            else if (idx + 1 === step) el.classList.add('active');
        });
        // progress lines
        for (var i = 1; i <= 3; i++) {
            var line = document.getElementById('pline-' + i);
            if (line) { if (i < step) line.classList.add('done'); else line.classList.remove('done'); }
        }

        // buttons
        document.getElementById('prevBtn').style.display = (step === 1 || step === 4) ? 'none' : 'block';
        document.getElementById('nextBtn').style.display = (step <= 2) ? 'block' : 'none';
        document.getElementById('sendOtpBtn').style.display = (step === 3) ? 'block' : 'none';
        document.getElementById('verifyOtpBtn').style.display = (step === 4) ? 'block' : 'none';
    }

    // ── Validation Helpers ─────────────────────────────────────────────
    function setInputStatus(input, isValid, msg = '') {
        var msgEl = document.getElementById('msg_' + input.id);
        if (isValid) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            if(msgEl) { msgEl.className = 'validation-msg success'; msgEl.textContent = 'Ok!'; }
        } else {
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
            if(msgEl) { msgEl.className = 'validation-msg error'; msgEl.textContent = msg; }
        }
    }

    function checkMobileNo(input) {
        var val = input.value.trim();
        if (/^\d{10}$/.test(val)) { setInputStatus(input, true); return true; }
        setInputStatus(input, false, 'Enter a valid 10-digit mobile number.');
        return false;
    }

    function checkPassword(input) {
        var val = input.value;
        var strongRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&#])[A-Za-z\d@$!%*?&#]{8,}$/;
        if (strongRegex.test(val)) { setInputStatus(input, true); return true; }
        setInputStatus(input, false, 'Min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char.');
        return false;
    }

    function checkConfirmPassword(cpInput, pInput) {
        if (cpInput.value === pInput.value && cpInput.value !== '') { setInputStatus(cpInput, true); return true; }
        setInputStatus(cpInput, false, 'Passwords do not match.');
        return false;
    }

    document.addEventListener('DOMContentLoaded', function() {
        var orgPhone = document.getElementById('org_phone');
        var adminPhone = document.getElementById('admin_phone');
        var pass = document.getElementById('password');
        var cpass = document.getElementById('confirm_password');

        if(orgPhone) orgPhone.addEventListener('input', function() { checkMobileNo(orgPhone); });
        if(adminPhone) adminPhone.addEventListener('input', function() { checkMobileNo(adminPhone); });
        if(pass) pass.addEventListener('input', function() { checkPassword(pass); if(cpass.value) checkConfirmPassword(cpass, pass); });
        if(cpass) cpass.addEventListener('input', function() { checkConfirmPassword(cpass, pass); });
    });

    // ── Validate a step ────────────────────────────────────────────────
    function validateStep(step) {
        var stepEl = document.querySelector('.form-step[data-step="' + step + '"]');
        var inputs = stepEl.querySelectorAll('input[required]');
        var valid = true;
        for (var k = 0; k < inputs.length; k++) {
            if (!inputs[k].value.trim()) {
                inputs[k].focus();
                inputs[k].style.borderColor = '#f56565';
                setTimeout(function(el) { return function(){ el.style.borderColor = ''; } }(inputs[k]), 2000);
                valid = false;
                break;
            }
        }
        if(!valid) return false;

        if(step === 1 && !checkMobileNo(document.getElementById('org_phone'))) return false;
        if(step === 2 && !checkMobileNo(document.getElementById('admin_phone'))) return false;

        // email format
        var emails = stepEl.querySelectorAll('input[type="email"]');
        for (var j = 0; j < emails.length; j++) {
            if (emails[j].value && !emails[j].value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                emails[j].focus(); emails[j].style.borderColor = '#f56565';
                showGlobalAlert('Please enter a valid email address.', 'danger');
                setTimeout(function(el) { return function(){ el.style.borderColor = ''; } }(emails[j]), 2000);
                return false;
            }
        }
        // password match on step 3
        if (step === 3) {
            var pInput = document.getElementById('password');
            var cpInput = document.getElementById('confirm_password');
            if (!checkPassword(pInput)) return false;
            if (!checkConfirmPassword(cpInput, pInput)) return false;
        }
        return true;
    }

    // ── Alerts ─────────────────────────────────────────────────────────
    function showGlobalAlert(msg, type) {
        type = type || 'danger';
        var icons = {
            danger:  '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/><path d="M10 6V10M10 14H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            success: '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/><path d="M6 10L9 13L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            info:    '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/><path d="M10 9V14M10 7H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        };
        document.getElementById('global-alert').innerHTML = '<div class="alert alert-' + type + '">' + icons[type] + '<span>' + msg + '</span></div>';
    }
    function showOtpAlert(msg, type) {
        type = type || 'danger';
        var icons = {
            danger:  '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/><path d="M10 6V10M10 14H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
            success: '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/><path d="M6 10L9 13L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            info:    '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/><path d="M10 9V14M10 7H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
        };
        document.getElementById('otp-alert').innerHTML = '<div class="alert alert-' + type + '">' + icons[type] + '<span>' + msg + '</span></div>';
    }
    function clearAlerts() {
        document.getElementById('global-alert').innerHTML = '';
        document.getElementById('otp-alert').innerHTML = '';
    }

    function setLoading(btnId, v) {
        var b = document.getElementById(btnId);
        b.disabled = v;
        if (v) b.classList.add('loading'); else b.classList.remove('loading');
    }

    // ── OTP Input Logic ────────────────────────────────────────────────
    function getFullOTP() {
        var otp = '';
        for (var i = 0; i < 6; i++) otp += (document.getElementById('otp-' + i).value || '');
        return otp;
    }
    function clearOTPInputs() {
        for (var i = 0; i < 6; i++) {
            var el = document.getElementById('otp-' + i);
            el.value = ''; el.classList.remove('filled');
        }
        document.getElementById('verifyOtpBtn').disabled = true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        var digits = document.querySelectorAll('.otp-digit');
        digits.forEach(function(input, idx) {
            input.addEventListener('input', function(e) {
                var val = e.target.value.replace(/\D/g, '');
                e.target.value = val.slice(-1);
                if (e.target.value) e.target.classList.add('filled'); else e.target.classList.remove('filled');
                if (val && idx < 5) digits[idx + 1].focus();
                document.getElementById('verifyOtpBtn').disabled = (getFullOTP().length !== 6);
            });
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !input.value && idx > 0) digits[idx - 1].focus();
                if (e.key === 'ArrowLeft' && idx > 0) digits[idx - 1].focus();
                if (e.key === 'ArrowRight' && idx < 5) digits[idx + 1].focus();
            });
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
                if (pasted.length >= 6) {
                    for (var j = 0; j < 6; j++) {
                        digits[j].value = pasted[j] || '';
                        if (digits[j].value) digits[j].classList.add('filled'); else digits[j].classList.remove('filled');
                    }
                    digits[5].focus();
                    document.getElementById('verifyOtpBtn').disabled = (getFullOTP().length !== 6);
                }
            });
        });
    });

    // ── Timer ──────────────────────────────────────────────────────────
    function startTimer() {
        timerSeconds = 600; stopTimer(); updateTimerDisplay();
        timerInterval = setInterval(function() {
            timerSeconds--;
            updateTimerDisplay();
            if (timerSeconds <= 0) {
                stopTimer();
                document.getElementById('otp-timer').classList.add('expired');
                document.getElementById('timer-countdown').textContent = 'Expired';
                document.getElementById('resend-btn').style.display = 'inline';
                document.getElementById('verifyOtpBtn').disabled = true;
                showOtpAlert('OTP has expired. Please click "Resend OTP".', 'danger');
            }
        }, 1000);
    }
    function stopTimer() { if (timerInterval) { clearInterval(timerInterval); timerInterval = null; } }
    function updateTimerDisplay() {
        var m = Math.floor(timerSeconds / 60).toString().padStart(2, '0');
        var s = (timerSeconds % 60).toString().padStart(2, '0');
        document.getElementById('timer-countdown').textContent = m + ':' + s;
    }

    // ── Next / Previous ────────────────────────────────────────────────
    document.getElementById('nextBtn').addEventListener('click', function() {
        clearAlerts();
        if (validateStep(currentStep)) {
            currentStep++;
            showStep(currentStep);
        }
    });

    document.getElementById('prevBtn').addEventListener('click', function() {
        clearAlerts();
        currentStep--;
        showStep(currentStep);
    });

    // ── Step 3 -> Send OTP ─────────────────────────────────────────────
    function sendRegistrationOTP() {
        clearAlerts();
        if (!validateStep(3)) return;

        setLoading('sendOtpBtn', true);
        var fd = new FormData();
        fd.append('org_name', document.getElementById('org_name').value);
        fd.append('org_email', document.getElementById('org_email').value);
        fd.append('org_phone', document.getElementById('org_phone').value);
        fd.append('org_address', '');
        fd.append('org_gst', '');
        fd.append('admin_name', document.getElementById('admin_name').value);
        fd.append('admin_email', document.getElementById('admin_email').value);
        fd.append('admin_phone', document.getElementById('admin_phone').value);
        fd.append('admin_username', document.getElementById('admin_username').value);
        fd.append('password', document.getElementById('password').value);
        fd.append('confirm_password', document.getElementById('confirm_password').value);

        fetch('api/register-send-otp.php', { method: 'POST', body: fd })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                setLoading('sendOtpBtn', false);
                if (data.success) {
                    showGlobalAlert(data.message, 'success');
                    document.getElementById('otp-email-label').textContent = 'OTP sent to ' + (data.email_masked || '');
                    setTimeout(function() {
                        clearAlerts();
                        currentStep = 4;
                        showStep(4);
                        startTimer();
                        setTimeout(function() { document.getElementById('otp-0').focus(); }, 100);
                    }, 800);
                } else {
                    showGlobalAlert(data.message, 'danger');
                }
            })
            .catch(function() {
                setLoading('sendOtpBtn', false);
                showGlobalAlert('Network error. Please try again.', 'danger');
            });
    }

    // ── Step 4 -> Verify OTP & Complete Registration ───────────────────
    function verifyRegistrationOTP() {
        document.getElementById('otp-alert').innerHTML = '';
        var otp = getFullOTP();
        if (otp.length !== 6) { showOtpAlert('Please enter the complete 6-digit OTP.', 'danger'); return; }

        setLoading('verifyOtpBtn', true);
        var fd = new FormData();
        fd.append('otp', otp);

        fetch('api/register-verify-otp.php', { method: 'POST', body: fd })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                setLoading('verifyOtpBtn', false);
                if (data.success) {
                    stopTimer();
                    showOtpAlert(data.message, 'success');
                    setTimeout(function() {
                        // Show success view
                        document.getElementById('registrationForm').style.display = 'none';
                        document.getElementById('progress-container').style.display = 'none';
                        document.getElementById('main-title').style.display = 'none';
                        document.getElementById('main-subtitle').style.display = 'none';
                        document.getElementById('success-view').style.display = 'block';
                        document.getElementById('success-msg').innerHTML = data.message;
                    }, 1000);
                } else {
                    showOtpAlert(data.message, 'danger');
                    clearOTPInputs();
                    setTimeout(function() { document.getElementById('otp-0').focus(); }, 100);
                }
            })
            .catch(function() {
                setLoading('verifyOtpBtn', false);
                showOtpAlert('Network error. Please try again.', 'danger');
            });
    }

    // ── Resend OTP ─────────────────────────────────────────────────────
    function resendRegOTP() {
        document.getElementById('otp-alert').innerHTML = '';
        clearOTPInputs();
        document.getElementById('otp-timer').classList.remove('expired');
        document.getElementById('resend-btn').style.display = 'none';
        showOtpAlert('Resending OTP...', 'info');

        var fd = new FormData();
        fd.append('org_name', document.getElementById('org_name').value);
        fd.append('org_email', document.getElementById('org_email').value);
        fd.append('org_phone', document.getElementById('org_phone').value);
        fd.append('org_address', '');
        fd.append('org_gst', '');
        fd.append('admin_name', document.getElementById('admin_name').value);
        fd.append('admin_email', document.getElementById('admin_email').value);
        fd.append('admin_phone', document.getElementById('admin_phone').value);
        fd.append('admin_username', document.getElementById('admin_username').value);
        fd.append('password', document.getElementById('password').value);
        fd.append('confirm_password', document.getElementById('confirm_password').value);

        fetch('api/register-send-otp.php', { method: 'POST', body: fd })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    showOtpAlert('New OTP sent to ' + (data.email_masked || ''), 'success');
                    startTimer();
                } else {
                    showOtpAlert(data.message, 'danger');
                }
            })
            .catch(function() {
                showOtpAlert('Network error. Please try again.', 'danger');
            });
    }

    // Toggle password
    function togglePassword(id) {
        var input = document.getElementById(id);
        if(input.type === 'password') input.type = 'text';
        else input.type = 'password';
    }

    // Init
    showStep(currentStep);
    </script>
</body>
</html>
