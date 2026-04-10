<?php
/**
 * Forgot Password Page
 * Step 1: Enter email -> OTP sent
 * Step 2: Enter OTP + New Password -> Reset complete
 */
require_once __DIR__ . '/../_includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Stocksathi</title>
    <meta name="description" content="Reset your Stocksathi password securely via OTP email verification.">
    <link rel="stylesheet" href="../css/design-system.css">
    <link rel="stylesheet" href="../css/components.css">
    <style>
        body { margin:0;padding:0;height:100vh;width:100vw;overflow:hidden;display:flex;background:var(--bg-body);font-family:'Segoe UI',Arial,sans-serif; }
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
        .branding-content { position:relative;z-index:1;max-width:440px;text-align:center;color:white;animation:fadeInUp .8s ease-out; }
        .logo { display:flex;flex-direction:column;align-items:center;gap:16px;margin-bottom:2rem; }
        .logo-text { font-size:28px;font-weight:700;color:white;letter-spacing:-.5px; }
        .headline { font-size:38px;font-weight:700;color:white;line-height:1.2;margin-bottom:1rem;letter-spacing:-1px; }
        .subtext { font-size:17px;color:rgba(255,255,255,.8);line-height:1.6; }
        .lock-illustration { margin-top:2.5rem; }
        .lock-illustration svg { width:220px;height:auto;filter:drop-shadow(0 8px 24px rgba(0,0,0,.2)); }

        .right-section {
            flex:1;background:var(--bg-surface);display:flex;align-items:center;justify-content:center;
            padding:3rem;overflow-y:auto;
        }
        .form-card { background:var(--bg-surface);padding:2rem;width:100%;max-width:440px;animation:fadeInRight .8s ease-out; }
        .form-title { font-size:30px;font-weight:700;color:var(--text-primary);margin-bottom:.4rem;letter-spacing:-.5px; }
        .form-subtitle { font-size:15px;color:var(--text-secondary);margin-bottom:2rem;line-height:1.5; }

        .step-row { display:flex;align-items:center;gap:8px;margin-bottom:1.5rem; }
        .sdot { width:10px;height:10px;border-radius:50%;background:var(--border-light);transition:all .3s; }
        .sdot.active { background:var(--color-primary);transform:scale(1.3); }
        .sdot.done   { background:#22c55e; }
        .sline { flex:1;height:2px;background:var(--border-light);transition:background .3s; }
        .sline.done { background:#22c55e; }

        .auth-form { display:flex;flex-direction:column;gap:1.25rem; }
        .input-group { display:flex;flex-direction:column;gap:.5rem; }
        .input-group label { font-size:14px;font-weight:500;color:var(--text-primary); }
        .input-wrapper { position:relative;display:flex;align-items:center; }
        .input-icon { position:absolute;left:16px;color:var(--text-secondary);pointer-events:none;z-index:1; }
        .input-wrapper input {
            width:100%;padding:.875rem 1rem .875rem 3rem;border:1.5px solid var(--border-light);border-radius:8px;
            font-size:15px;font-family:inherit;color:var(--text-primary);background:var(--bg-body);transition:all .2s ease;box-sizing:border-box;
        }
        .input-wrapper input:focus { outline:none;border-color:var(--color-primary);box-shadow:0 0 0 3px var(--color-primary-lighter);background:white; }
        .password-toggle { position:absolute;right:12px;background:none;border:none;color:var(--text-secondary);cursor:pointer;padding:4px;display:flex;align-items:center;transition:color .2s;z-index:1; }
        .password-toggle:hover { color:var(--color-primary); }

        .btn-primary {
            background:var(--color-primary);color:white;border:none;padding:1rem 1.5rem;border-radius:8px;
            font-size:16px;font-weight:600;font-family:inherit;cursor:pointer;width:100%;
            display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s;box-shadow:var(--shadow-sm);
        }
        .btn-primary:hover { background:var(--color-primary-dark);box-shadow:var(--shadow-md);transform:translateY(-1px); }
        .btn-primary:disabled { opacity:.7;cursor:not-allowed;transform:none; }
        .btn-outline { background:transparent;color:var(--color-primary);border:1.5px solid var(--color-primary);padding:.75rem 1.5rem;border-radius:8px;font-size:14px;font-weight:600;font-family:inherit;cursor:pointer;width:100%;transition:all .2s; }
        .btn-outline:hover { background:var(--color-primary-lighter); }

        .alert { display:flex;align-items:center;gap:8px;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:1rem;animation:fadeIn .3s; }
        .alert-danger  { background:#fef2f2;color:#dc2626;border:1px solid #fecaca; }
        .alert-success { background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0; }
        .alert-info    { background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe; }

        #otp-step { display:none;animation:fadeInRight .4s ease-out; }
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

        .strength-bar { height:4px;border-radius:4px;background:#e5e7eb;margin-top:6px;overflow:hidden; }
        .strength-fill { height:100%;border-radius:4px;transition:width .3s,background .3s;width:0; }
        .strength-label { font-size:12px;margin-top:4px;color:var(--text-secondary); }

        .spinner { width:18px;height:18px;border:2px solid rgba(255,255,255,.4);border-top-color:white;border-radius:50%;animation:spin .7s linear infinite;display:none; }
        .btn-primary.loading .spinner { display:block; }
        .btn-primary.loading .btn-text { display:none; }

        #success-view { display:none;text-align:center;padding:2rem 0;animation:fadeIn .5s; }
        #success-view .success-icon { font-size:56px;margin-bottom:1rem;display:block; }
        #success-view h3 { font-size:22px;font-weight:700;color:var(--text-primary);margin-bottom:.5rem; }
        #success-view p { color:var(--text-secondary);font-size:15px;margin-bottom:1.5rem; }

        .form-footer { margin-top:1.5rem;text-align:center;font-size:14px;color:var(--text-secondary); }
        .form-footer a { color:var(--color-primary);text-decoration:none;font-weight:600; }
        .form-footer a:hover { text-decoration:underline; }

        @keyframes fadeInUp    { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
        @keyframes fadeInRight { from{opacity:0;transform:translateX(30px)} to{opacity:1;transform:translateX(0)} }
        @keyframes fadeIn      { from{opacity:0} to{opacity:1} }
        @keyframes spin        { to{transform:rotate(360deg)} }

        @media(max-width:968px){
            .container{flex-direction:column;overflow-y:auto;}
            .left-section{min-height:35vh;padding:2rem;}
            .lock-illustration{display:none;}
            .right-section{padding:2rem;}
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Left -->
    <div class="left-section">
        <div class="branding-content">
            <div class="logo">
                <img src="../assets/images/logo.png" alt="Stocksathi Logo"
                     style="height:60px;width:auto;background:white;padding:8px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1);">
                <span class="logo-text">Stocksathi</span>
            </div>
            <h1 class="headline">Account Recovery</h1>
            <p class="subtext">Securely reset your password using a One-Time Password sent to your registered email.</p>
            <div class="lock-illustration">
                <svg viewBox="0 0 200 220" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="30" y="100" width="140" height="110" rx="18" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.5)" stroke-width="3"/>
                    <path d="M65 100 V65 Q65 30 100 30 Q135 30 135 65 V100" stroke="rgba(255,255,255,0.7)" stroke-width="14" stroke-linecap="round" fill="none"/>
                    <circle cx="100" cy="148" r="18" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.6)" stroke-width="2"/>
                    <circle cx="100" cy="145" r="8" fill="rgba(255,255,255,0.4)"/>
                    <rect x="96" y="152" width="8" height="18" rx="3" fill="rgba(255,255,255,0.4)"/>
                    <g fill="rgba(255,255,255,0.6)">
                        <circle cx="20" cy="75" r="3"/><circle cx="175" cy="55" r="4"/>
                        <circle cx="10" cy="150" r="2"/><circle cx="185" cy="140" r="2.5"/>
                        <circle cx="160" cy="210" r="3"/>
                    </g>
                </svg>
            </div>
        </div>
    </div>

    <!-- Right -->
    <div class="right-section">
        <div class="form-card">
            <div class="step-row" id="step-row">
                <div class="sdot active" id="sdot-1"></div>
                <div class="sline" id="sline"></div>
                <div class="sdot" id="sdot-2"></div>
            </div>

            <!-- STEP 1: Email -->
            <div id="email-step">
                <h2 class="form-title">Forgot Password?</h2>
                <p class="form-subtitle">Enter your registered email address. We'll send a 6-digit OTP to reset your password.</p>
                <div id="email-alert"></div>
                <form id="email-form" class="auth-form" onsubmit="return false;">
                    <div class="input-group">
                        <label for="reset-email">Email Address</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M2.5 6.66667L10 11.6667L17.5 6.66667M3.33333 15H16.6667C17.5871 15 18.3333 14.2538 18.3333 13.3333V6.66667C18.3333 5.74619 17.5871 5 16.6667 5H3.33333C2.41286 5 1.66667 5.74619 1.66667 6.66667V13.3333C1.66667 14.2538 2.41286 15 3.33333 15Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input type="email" id="reset-email" placeholder="Enter your registered email" required>
                        </div>
                    </div>
                    <button type="submit" id="send-otp-btn" class="btn-primary" onclick="sendResetOTP()">
                        <span class="spinner"></span>
                        <span class="btn-text" style="display:flex;align-items:center;gap:8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2z"/><polyline points="20,6 12,13 4,6"/></svg>
                            Send Reset OTP
                        </span>
                    </button>
                </form>
            </div>

            <!-- STEP 2: OTP + New Password -->
            <div id="otp-step">
                <h2 class="form-title">Reset Password</h2>
                <p class="form-subtitle">Enter the OTP from your email and choose a new password.</p>
                <div id="otp-alert"></div>
                <div class="otp-info-box">
                    <span class="mail-icon">&#x1F4E7;</span>
                    <h3>OTP Sent!</h3>
                    <p id="otp-email-label">Check your registered email for the code.</p>
                </div>
                <form id="reset-form" class="auth-form" onsubmit="return false;">
                    <div class="input-group">
                        <label style="text-align:center;">Enter 6-digit OTP</label>
                        <div class="otp-input-wrapper" id="otp-boxes">
                            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="r-otp-0" autocomplete="one-time-code">
                            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="r-otp-1">
                            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="r-otp-2">
                            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="r-otp-3">
                            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="r-otp-4">
                            <input class="otp-digit" type="text" maxlength="1" inputmode="numeric" id="r-otp-5">
                        </div>
                        <div class="otp-timer" id="otp-timer">Expires in <span id="timer-val">10:00</span></div>
                        <div class="resend-row">
                            <span>Didn't receive it?</span>
                            <button type="button" id="resend-btn" onclick="resendResetOTP()">Resend OTP</button>
                        </div>
                    </div>
                    <div class="input-group">
                        <label for="new-password">New Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M15.8333 9.16667H4.16667C3.24619 9.16667 2.5 9.91286 2.5 10.8333V16.6667C2.5 17.5871 3.24619 18.3333 4.16667 18.3333H15.8333C16.7538 18.3333 17.5 17.5871 17.5 16.6667V10.8333C17.5 9.91286 16.7538 9.16667 15.8333 9.16667Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5.83333 9.16667V5.83333C5.83333 4.72826 6.27232 3.66846 7.05372 2.88706C7.83512 2.10565 8.89493 1.66667 10 1.66667C11.1051 1.66667 12.1649 2.10565 12.9463 2.88706C13.7277 3.66846 14.1667 4.72826 14.1667 5.83333V9.16667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input type="password" id="new-password" placeholder="Min 6 characters" required>
                            <button type="button" class="password-toggle" onclick="togglePw('new-password')">
                                <svg width="18" height="18" viewBox="0 0 20 20" fill="currentColor"><path d="M10 4.16667C5.83333 4.16667 2.275 6.73333 1.04167 10.4167C2.275 14.1 5.83333 16.6667 10 16.6667C14.1667 16.6667 17.725 14.1 18.9583 10.4167C17.725 6.73333 14.1667 4.16667 10 4.16667ZM10 14.5833C7.7 14.5833 5.83333 12.7167 5.83333 10.4167C5.83333 8.11667 7.7 6.25 10 6.25C12.3 6.25 14.1667 8.11667 14.1667 10.4167C14.1667 12.7167 12.3 14.5833 10 14.5833Z"/></svg>
                            </button>
                        </div>
                        <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                        <div class="strength-label" id="strength-label"></div>
                    </div>
                    <div class="input-group">
                        <label for="confirm-password">Confirm New Password</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" width="20" height="20" viewBox="0 0 20 20" fill="none">
                                <path d="M15.8333 9.16667H4.16667C3.24619 9.16667 2.5 9.91286 2.5 10.8333V16.6667C2.5 17.5871 3.24619 18.3333 4.16667 18.3333H15.8333C16.7538 18.3333 17.5 17.5871 17.5 16.6667V10.8333C17.5 9.91286 16.7538 9.16667 15.8333 9.16667Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5.83333 9.16667V5.83333C5.83333 4.72826 6.27232 3.66846 7.05372 2.88706C7.83512 2.10565 8.89493 1.66667 10 1.66667C11.1051 1.66667 12.1649 2.10565 12.9463 2.88706C13.7277 3.66846 14.1667 4.72826 14.1667 5.83333V9.16667" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <input type="password" id="confirm-password" placeholder="Re-enter new password" required>
                        </div>
                    </div>
                    <button type="submit" id="reset-btn" class="btn-primary" disabled onclick="doResetPassword()">
                        <span class="spinner"></span>
                        <span class="btn-text" style="display:flex;align-items:center;gap:8px;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                            Reset Password
                        </span>
                    </button>
                    <button type="button" class="btn-outline" onclick="backToEmail()">&#8592; Change Email</button>
                </form>
            </div>

            <!-- SUCCESS -->
            <div id="success-view">
                <span class="success-icon" style="font-size:56px;display:block;margin-bottom:1rem;">&#127881;</span>
                <h3 style="font-size:22px;font-weight:700;color:var(--text-primary);margin-bottom:.5rem;">Password Reset!</h3>
                <p style="color:var(--text-secondary);font-size:15px;margin-bottom:1.5rem;">Your password has been successfully updated.<br>You can now login with your new password.</p>
                <a href="login.php" class="btn-primary" style="display:inline-flex;text-decoration:none;margin-top:.5rem;">Go to Login &#8594;</a>
            </div>

            <div class="form-footer">
                <p>Remember your password? <a href="login.php">Sign In</a></p>
            </div>
        </div>
    </div>
</div>

<script>
var timerInterval = null;
var timerSecs = 600;
var savedEmail = '';

function showAlert(id, msg, type) {
    type = type || 'danger';
    var icons = {
        danger:  '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/><path d="M10 6V10M10 14H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>',
        success: '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/><path d="M6 10L9 13L14 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        info:    '<svg width="18" height="18" viewBox="0 0 20 20" fill="none"><circle cx="10" cy="10" r="9" stroke="currentColor" stroke-width="2"/><path d="M10 9V14M10 7H10.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>'
    };
    document.getElementById(id).innerHTML = '<div class="alert alert-' + type + '">' + icons[type] + '<span>' + msg + '</span></div>';
}
function clearAlert(id) { document.getElementById(id).innerHTML = ''; }

function setLoading(btnId, v) {
    var b = document.getElementById(btnId);
    b.disabled = v;
    if (v) b.classList.add('loading'); else b.classList.remove('loading');
}

function togglePw(id) {
    var input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function getOTP() {
    var o = '';
    for (var i = 0; i < 6; i++) o += (document.getElementById('r-otp-' + i).value || '');
    return o;
}
function clearOTPBoxes() {
    for (var i = 0; i < 6; i++) {
        var el = document.getElementById('r-otp-' + i);
        el.value = ''; el.classList.remove('filled');
    }
    document.getElementById('reset-btn').disabled = true;
}

// OTP digit box logic
document.addEventListener('DOMContentLoaded', function() {
    var boxes = document.querySelectorAll('.otp-digit');
    boxes.forEach(function(b, i) {
        b.addEventListener('input', function(e) {
            var v = e.target.value.replace(/\D/g, '');
            e.target.value = v.slice(-1);
            if (e.target.value) e.target.classList.add('filled'); else e.target.classList.remove('filled');
            if (v && i < 5) boxes[i + 1].focus();
            document.getElementById('reset-btn').disabled = (getOTP().length !== 6);
        });
        b.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && !b.value && i > 0) boxes[i - 1].focus();
            if (e.key === 'ArrowLeft' && i > 0) boxes[i - 1].focus();
            if (e.key === 'ArrowRight' && i < 5) boxes[i + 1].focus();
        });
        b.addEventListener('paste', function(e) {
            e.preventDefault();
            var p = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            if (p.length >= 6) {
                for (var j = 0; j < 6; j++) {
                    boxes[j].value = p[j] || '';
                    if (boxes[j].value) boxes[j].classList.add('filled'); else boxes[j].classList.remove('filled');
                }
                boxes[5].focus();
                document.getElementById('reset-btn').disabled = (getOTP().length !== 6);
            }
        });
    });

    // Password strength meter
    document.getElementById('new-password').addEventListener('input', function() {
        var v = this.value;
        var score = 0;
        if (v.length >= 6) score++;
        if (v.length >= 10) score++;
        if (/[A-Z]/.test(v)) score++;
        if (/[0-9]/.test(v)) score++;
        if (/[^A-Za-z0-9]/.test(v)) score++;
        var fills  = ['0%', '25%', '50%', '70%', '90%', '100%'];
        var colors = ['#ef4444','#f97316','#eab308','#84cc16','#22c55e','#16a34a'];
        var labels = ['','Very Weak','Weak','Fair','Strong','Very Strong'];
        document.getElementById('strength-fill').style.width = fills[score];
        document.getElementById('strength-fill').style.background = colors[score];
        document.getElementById('strength-label').textContent = labels[score];
        document.getElementById('strength-label').style.color = colors[score];
    });
});

// Timer
function startTimer() {
    timerSecs = 600; stopTimer(); updateTimer();
    timerInterval = setInterval(function() {
        timerSecs--;
        updateTimer();
        if (timerSecs <= 0) {
            stopTimer();
            document.getElementById('otp-timer').classList.add('expired');
            document.getElementById('timer-val').textContent = 'Expired';
            document.getElementById('resend-btn').style.display = 'inline';
            document.getElementById('reset-btn').disabled = true;
            showAlert('otp-alert', 'OTP expired. Click "Resend OTP".', 'danger');
        }
    }, 1000);
}
function stopTimer() { if (timerInterval) { clearInterval(timerInterval); timerInterval = null; } }
function updateTimer() {
    var m = Math.floor(timerSecs / 60).toString().padStart(2, '0');
    var s = (timerSecs % 60).toString().padStart(2, '0');
    document.getElementById('timer-val').textContent = m + ':' + s;
}

// Step transitions
function showOTPStep(masked) {
    document.getElementById('email-step').style.display = 'none';
    document.getElementById('otp-step').style.display = 'block';
    document.getElementById('otp-email-label').textContent = 'OTP sent to ' + masked;
    document.getElementById('sdot-1').className = 'sdot done';
    document.getElementById('sline').className = 'sline done';
    document.getElementById('sdot-2').classList.add('active');
    startTimer();
    setTimeout(function() { document.getElementById('r-otp-0').focus(); }, 100);
}
function backToEmail() {
    document.getElementById('otp-step').style.display = 'none';
    document.getElementById('email-step').style.display = 'block';
    document.getElementById('sdot-1').className = 'sdot active';
    document.getElementById('sline').className = 'sline';
    document.getElementById('sdot-2').classList.remove('active');
    clearAlert('otp-alert'); clearOTPBoxes(); stopTimer();
    document.getElementById('otp-timer').classList.remove('expired');
    document.getElementById('resend-btn').style.display = 'none';
}
function showSuccess() {
    document.getElementById('step-row').style.display = 'none';
    document.getElementById('otp-step').style.display = 'none';
    document.getElementById('email-step').style.display = 'none';
    document.getElementById('success-view').style.display = 'block';
    document.querySelector('.form-footer').style.display = 'none';
}

// Step 1: Send OTP
function sendResetOTP() {
    clearAlert('email-alert');
    var email = document.getElementById('reset-email').value.trim();
    if (!email) { showAlert('email-alert', 'Please enter your email address.', 'danger'); return; }
    savedEmail = email;
    setLoading('send-otp-btn', true);
    var fd = new FormData();
    fd.append('email', email);
    fetch('api/forgot-password-send.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            setLoading('send-otp-btn', false);
            if (data.success) {
                showAlert('email-alert', data.message, 'success');
                setTimeout(function() { showOTPStep(data.email_masked || email); }, 800);
            } else {
                showAlert('email-alert', data.message, 'danger');
            }
        })
        .catch(function() {
            setLoading('send-otp-btn', false);
            showAlert('email-alert', 'Network error. Please try again.', 'danger');
        });
}

// Resend
function resendResetOTP() {
    clearAlert('otp-alert'); clearOTPBoxes();
    document.getElementById('otp-timer').classList.remove('expired');
    document.getElementById('resend-btn').style.display = 'none';
    showAlert('otp-alert', 'Resending OTP...', 'info');
    var fd = new FormData();
    fd.append('email', savedEmail);
    fetch('api/forgot-password-send.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success) {
                showAlert('otp-alert', 'New OTP sent to ' + (data.email_masked || savedEmail), 'success');
                startTimer();
            } else {
                showAlert('otp-alert', data.message, 'danger');
            }
        })
        .catch(function() {
            showAlert('otp-alert', 'Network error. Please try again.', 'danger');
        });
}

// Step 2: Reset Password
function doResetPassword() {
    clearAlert('otp-alert');
    var otp = getOTP();
    var newPw = document.getElementById('new-password').value;
    var confirmPw = document.getElementById('confirm-password').value;
    if (otp.length !== 6) { showAlert('otp-alert', 'Please enter the complete 6-digit OTP.', 'danger'); return; }
    if (newPw.length < 6) { showAlert('otp-alert', 'Password must be at least 6 characters.', 'danger'); return; }
    if (newPw !== confirmPw) { showAlert('otp-alert', 'Passwords do not match.', 'danger'); return; }
    setLoading('reset-btn', true);
    var fd = new FormData();
    fd.append('otp', otp);
    fd.append('new_password', newPw);
    fd.append('confirm_password', confirmPw);
    fetch('api/forgot-password-reset.php', { method: 'POST', body: fd })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            setLoading('reset-btn', false);
            if (data.success) {
                stopTimer();
                showAlert('otp-alert', data.message, 'success');
                setTimeout(function() { showSuccess(); }, 1000);
            } else {
                showAlert('otp-alert', data.message, 'danger');
                clearOTPBoxes();
                setTimeout(function() { document.getElementById('r-otp-0').focus(); }, 100);
            }
        })
        .catch(function() {
            setLoading('reset-btn', false);
            showAlert('otp-alert', 'Network error. Please try again.', 'danger');
        });
}
</script>
</body>
</html>
