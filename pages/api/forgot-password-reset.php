<?php
/**
 * forgot-password-reset.php
 * AJAX endpoint: verifies OTP and updates the user's password.
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

$userId      = $_SESSION['reset_user_id'] ?? null;
$otp         = trim($_POST['otp']          ?? '');
$newPassword = $_POST['new_password']      ?? '';
$confirmPw   = $_POST['confirm_password']  ?? '';

if (!$userId) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please start over.']);
    exit;
}
if (empty($otp) || strlen($otp) !== EmailOTP::OTP_LENGTH) {
    echo json_encode(['success' => false, 'message' => 'Please enter the complete OTP.']);
    exit;
}
if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
    exit;
}
if ($newPassword !== $confirmPw) {
    echo json_encode(['success' => false, 'message' => 'Passwords do not match.']);
    exit;
}

// Verify OTP
$result = EmailOTP::verifyOTP((int)$userId, $otp);
if (!$result['success']) {
    echo json_encode($result);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ?, otp_verified = 1 WHERE id = ?");
    $stmt->execute([$hashed, $userId]);

    unset($_SESSION['reset_user_id']);

    Database::logActivity('password_reset', 'auth', "Password reset via OTP for user #{$userId}");

    echo json_encode([
        'success' => true,
        'message' => '✅ Password updated successfully! You can now login with your new password.'
    ]);

} catch (Exception $e) {
    error_log('forgot-password-reset error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update password: ' . $e->getMessage()]);
}
