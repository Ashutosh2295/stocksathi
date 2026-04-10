-- ============================================================
-- OTP Email Authentication Migration
-- Run once against the stocksathi database
-- ============================================================

ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `otp_code`     VARCHAR(255)  NULL DEFAULT NULL AFTER `password`,
    ADD COLUMN IF NOT EXISTS `otp_expiry`   DATETIME      NULL DEFAULT NULL AFTER `otp_code`,
    ADD COLUMN IF NOT EXISTS `otp_verified` TINYINT(1)    NOT NULL DEFAULT 0  AFTER `otp_expiry`;
