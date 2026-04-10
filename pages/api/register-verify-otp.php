<?php
/**
 * register-verify-otp.php
 * AJAX endpoint: verifies OTP then completes the full organization + user registration.
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../_includes/config.php';
require_once __DIR__ . '/../../_includes/database.php';
require_once __DIR__ . '/../../_includes/EmailOTP.php';
require_once __DIR__ . '/../../_includes/RBACSeeder.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();

$otp  = trim($_POST['otp'] ?? '');
$data = $_SESSION['reg_pending'] ?? null;

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please start registration again.']);
    exit;
}
if (empty($otp) || !ctype_digit($otp) || strlen($otp) !== EmailOTP::OTP_LENGTH) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid ' . EmailOTP::OTP_LENGTH . '-digit OTP.']);
    exit;
}
if (time() > $data['otp_expiry']) {
    unset($_SESSION['reg_pending']);
    echo json_encode(['success' => false, 'message' => 'OTP has expired. Please go back and try again.']);
    exit;
}
if (!password_verify($otp, $data['otp_hash'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
    exit;
}

// OTP correct — commit registration to DB
try {
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();

    // 1. Create Organization
    $orgStmt = $db->prepare("
        INSERT INTO organizations (name, email, phone, address, gst_number, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'active', NOW())
    ");
    $orgStmt->execute([
        $data['org_name'], $data['org_email'], $data['org_phone'],
        $data['org_address'], $data['org_gst']
    ]);
    $organizationId = $db->lastInsertId();

    // 2. Create Super Admin User
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
    $userStmt = $db->prepare("
        INSERT INTO users (organization_id, username, email, password, full_name, role, phone, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'super_admin', ?, 'active', NOW())
    ");
    $userStmt->execute([
        $organizationId,
        $data['admin_username'],
        $data['admin_email'],
        $hashedPassword,
        $data['admin_name'],
        $data['admin_phone']
    ]);

    $db->commit();

    // 3. Seed roles & permissions for org
    RBACSeeder::seedForOrganization($organizationId);

    // 4. Log activity
    Database::logActivity('register', 'auth', "New organization created: {$data['org_name']}");

    // 5. Clear session pending data
    unset($_SESSION['reg_pending']);

    echo json_encode([
        'success' => true,
        'message' => '🎉 Registration successful! Your organization "' . $data['org_name'] . '" has been created. Please login to get started.'
    ]);

} catch (Exception $e) {
    if (isset($db)) $db->rollBack();
    error_log('register-verify-otp error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
}
