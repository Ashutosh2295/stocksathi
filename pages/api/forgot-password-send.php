<?php
/**
 * forgot-password-send.php
 * AJAX endpoint: sends a password reset OTP to the user's email.
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

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    $db   = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Always return success to prevent email enumeration
    if (!$user) {
        echo json_encode(['success' => true, 'message' => 'If this email is registered, an OTP has been sent.']);
        exit;
    }

    $otp    = EmailOTP::generateOTP();
    $stored = EmailOTP::storeOTP((int)$user['id'], $otp);

    if (!$stored) {
        echo json_encode(['success' => false, 'message' => 'Failed to process request. Try again.']);
        exit;
    }

    $result = EmailOTP::sendOTPEmail(
        $user['email'],
        $user['full_name'] ?: explode('@', $user['email'])[0],
        $otp
    );

    if (!$result['success']) {
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP: ' . $result['message']]);
        exit;
    }

    // Store user_id in session for reset step
    $_SESSION['reset_user_id'] = (int)$user['id'];

    $masked = substr($user['email'], 0, 3) . '***@' . explode('@', $user['email'])[1];
    echo json_encode([
        'success'      => true,
        'message'      => "OTP sent to {$masked}. Check your inbox.",
        'email_masked' => $masked
    ]);

} catch (Exception $e) {
    error_log('forgot-password-send error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
