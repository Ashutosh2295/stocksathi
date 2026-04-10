<?php
/**
 * send-otp.php
 * AJAX endpoint: validates credentials → generates & emails OTP
 * Called by login.php via fetch()
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../_includes/config.php';
require_once __DIR__ . '/../../_includes/database.php';
require_once __DIR__ . '/../../_includes/AuthHelper.php';
require_once __DIR__ . '/../../_includes/EmailOTP.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // 1. Find user by email or username
    $stmt = $db->prepare("SELECT id, full_name, email, password, role, status FROM users WHERE email = ? OR username = ? LIMIT 1");
    $stmt->execute([$email, $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    if (isset($user['status']) && $user['status'] === 'inactive') {
        echo json_encode(['success' => false, 'message' => 'Your account is inactive. Contact admin.']);
        exit;
    }

    // 2. Generate OTP
    $otp = EmailOTP::generateOTP();

    // 3. Store hashed OTP in DB
    if (!EmailOTP::storeOTP((int)$user['id'], $otp)) {
        echo json_encode(['success' => false, 'message' => 'Failed to store OTP. Try again.']);
        exit;
    }

    // 4. Send OTP email
    $result = EmailOTP::sendOTPEmail(
        $user['email'],
        $user['full_name'] ?: explode('@', $user['email'])[0],
        $otp
    );

    if (!$result['success']) {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP email: ' . $result['message']]);
        exit;
    }

    // 5. Store user_id in session temporarily (not fully logged in yet)
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['otp_pending_user_id']   = (int) $user['id'];
    $_SESSION['otp_pending_user_role'] = $user['role'];

    echo json_encode([
        'success' => true,
        'message' => 'OTP sent to ' . $user['email'],
        'email_masked' => substr($user['email'], 0, 3) . '***@' . explode('@', $user['email'])[1]
    ]);

} catch (Exception $e) {
    error_log('send-otp.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
