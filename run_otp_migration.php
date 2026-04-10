<?php
// Run this script once to add OTP columns
require_once __DIR__ . '/_includes/config.php';
require_once __DIR__ . '/_includes/database.php';
try {
    $db = Database::getInstance()->getConnection();
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS otp_code VARCHAR(255) NULL DEFAULT NULL');
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS otp_expiry DATETIME NULL DEFAULT NULL');
    $db->exec('ALTER TABLE users ADD COLUMN IF NOT EXISTS otp_verified TINYINT(1) NOT NULL DEFAULT 0');
    echo 'SUCCESS: OTP columns added to users table.';
} catch(Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
