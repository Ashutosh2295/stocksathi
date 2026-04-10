<?php
/**
 * verify-otp.php
 * AJAX endpoint: verifies the OTP and completes login
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../_includes/config.php';
require_once __DIR__ . '/../../_includes/database.php';
require_once __DIR__ . '/../../_includes/AuthHelper.php';
require_once __DIR__ . '/../../_includes/Session.php';
require_once __DIR__ . '/../../_includes/EmailOTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();

$userId = $_SESSION['otp_pending_user_id'] ?? null;
$role   = $_SESSION['otp_pending_user_role'] ?? null;
$otp    = trim($_POST['otp'] ?? '');

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

if (empty($otp) || !ctype_digit($otp) || strlen($otp) !== EmailOTP::OTP_LENGTH) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid ' . EmailOTP::OTP_LENGTH . '-digit OTP.']);
    exit;
}

try {
    // 1. Verify OTP
    $result = EmailOTP::verifyOTP((int)$userId, $otp);
    if (!$result['success']) {
        echo json_encode($result);
        exit;
    }

    // 2. Load user data and complete session setup
    $db   = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, username, email, full_name, role, organization_id FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
        exit;
    }

    // 3. Set full session
    Session::setUser($user['id'], $user['username'], $user['role'], $user['organization_id']);

    // 4. Update last login
    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$userId]);

    // 5. Clean up OTP pending session vars
    unset($_SESSION['otp_pending_user_id'], $_SESSION['otp_pending_user_role']);

    // 6. Log activity
    Database::logActivity('login', 'auth', 'User logged in with OTP verification');

    // 7. Build redirect URL based on role
    $dashboards = [
        'super_admin'      => 'dashboards/super-admin.php',
        'admin'            => 'dashboards/admin.php',
        'hr'               => 'dashboards/hr.php',
        'store_manager'    => 'dashboards/store-manager.php',
        'sales_executive'  => 'dashboards/sales-executive.php',
        'accountant'       => 'dashboards/accountant.php',
        'warehouse_manager'=> 'dashboards/store-manager.php',
    ];
    $redirectUrl = $dashboards[$user['role']] ?? '../index.php';

    session_write_close();

    echo json_encode([
        'success'  => true,
        'message'  => 'Login successful!',
        'redirect' => $redirectUrl
    ]);

} catch (Exception $e) {
    error_log('verify-otp.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
