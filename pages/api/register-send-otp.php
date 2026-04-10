<?php
/**
 * register-send-otp.php
 * AJAX endpoint: validates registration form data, stores it in session,
 * generates a 6-digit OTP and emails it to the admin email.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../_includes/config.php';
require_once __DIR__ . '/../../_includes/database.php';
require_once __DIR__ . '/../../_includes/EmailOTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();

// Collect all registration data
$org_name        = trim($_POST['org_name']        ?? '');
$org_email       = trim($_POST['org_email']       ?? '');
$org_phone       = trim($_POST['org_phone']       ?? '');
$org_address     = trim($_POST['org_address']     ?? '');
$org_gst         = trim($_POST['org_gst']         ?? '');
$admin_name      = trim($_POST['admin_name']      ?? '');
$admin_email     = trim($_POST['admin_email']     ?? '');
$admin_phone     = trim($_POST['admin_phone']     ?? '');
$admin_username  = trim($_POST['admin_username']  ?? '');
$password        = $_POST['password']             ?? '';
$confirm_password= $_POST['confirm_password']     ?? '';

// Basic validation
if (empty($org_name) || empty($org_email) || empty($admin_name) || empty($admin_email) || empty($admin_username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
    exit;
}
if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}
if (!filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid admin email address.']);
    exit;
}

try {
    $db   = Database::getInstance()->getConnection();

    // Check if email/username already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$admin_email, $admin_username]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email or username already exists. Please use different credentials.']);
        exit;
    }

    // Generate OTP
    $otp = EmailOTP::generateOTP();

    // Store registration data + OTP in session (not in DB yet — only after OTP verified)
    $otpHash   = password_hash($otp, PASSWORD_DEFAULT);
    $otpExpiry = time() + (EmailOTP::OTP_EXPIRY * 60);

    $_SESSION['reg_pending'] = [
        'org_name'       => $org_name,
        'org_email'      => $org_email,
        'org_phone'      => $org_phone,
        'org_address'    => $org_address,
        'org_gst'        => $org_gst,
        'admin_name'     => $admin_name,
        'admin_email'    => $admin_email,
        'admin_phone'    => $admin_phone,
        'admin_username' => $admin_username,
        'password'       => $password,         // raw — will be hashed on final save
        'otp_hash'       => $otpHash,
        'otp_expiry'     => $otpExpiry,
    ];

    // Send OTP email
    $result = EmailOTP::sendOTPEmail($admin_email, $admin_name, $otp);

    if (!$result['success']) {
        unset($_SESSION['reg_pending']);
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP: ' . $result['message']]);
        exit;
    }

    $masked = substr($admin_email, 0, 3) . '***@' . explode('@', $admin_email)[1];
    echo json_encode([
        'success'      => true,
        'message'      => "OTP sent to {$masked}. Please check your inbox.",
        'email_masked' => $masked
    ]);

} catch (Exception $e) {
    error_log('register-send-otp error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
