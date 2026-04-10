<?php
/**
 * EmailOTP.php
 * Handles OTP generation, storage, and email sending via Gmail SMTP.
 * Uses PHP streams (fsockopen/ssl) — no Composer or external library needed.
 */

class EmailOTP {

    // ─── Gmail SMTP Credentials ──────────────────────────────────────────────
    const SMTP_HOST     = 'ssl://smtp.gmail.com';
    const SMTP_PORT     = 465;
    const SMTP_USERNAME = 'ashutoshbhavsar33@gmail.com';
    const SMTP_PASSWORD = 'foxn gamy koqp jlmo';   // Gmail App Password
    const FROM_NAME     = 'Stocksathi Security';
    const FROM_EMAIL    = 'ashutoshbhavsar33@gmail.com';

    // ─── OTP Settings ────────────────────────────────────────────────────────
    const OTP_LENGTH    = 6;
    const OTP_EXPIRY    = 10;   // minutes

    // ─── Generate a numeric OTP ───────────────────────────────────────────────
    public static function generateOTP(): string {
        $min = (int) str_pad('1', self::OTP_LENGTH, '0');      // 100000
        $max = (int) str_repeat('9', self::OTP_LENGTH);         // 999999
        return (string) random_int($min, $max);
    }

    // ─── Store OTP in the DB ──────────────────────────────────────────────────
    public static function storeOTP(int $userId, string $otp): bool {
        try {
            $db   = Database::getInstance()->getConnection();
            $hash = password_hash($otp, PASSWORD_DEFAULT);
            $expiry = date('Y-m-d H:i:s', strtotime('+' . self::OTP_EXPIRY . ' minutes'));

            $stmt = $db->prepare(
                "UPDATE users
                 SET otp_code = ?, otp_expiry = ?, otp_verified = 0
                 WHERE id = ?"
            );
            return $stmt->execute([$hash, $expiry, $userId]);
        } catch (Exception $e) {
            error_log('EmailOTP::storeOTP error: ' . $e->getMessage());
            return false;
        }
    }

    // ─── Verify OTP from DB ───────────────────────────────────────────────────
    public static function verifyOTP(int $userId, string $otp): array {
        try {
            $db   = Database::getInstance()->getConnection();
            $stmt = $db->prepare(
                "SELECT otp_code, otp_expiry, otp_verified FROM users WHERE id = ?"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return ['success' => false, 'message' => 'User not found.'];
            }
            if ($row['otp_verified']) {
                return ['success' => false, 'message' => 'OTP already used.'];
            }
            if (strtotime($row['otp_expiry']) < time()) {
                return ['success' => false, 'message' => 'OTP has expired. Please request a new one.'];
            }
            if (!password_verify($otp, $row['otp_code'])) {
                return ['success' => false, 'message' => 'Invalid OTP. Please try again.'];
            }

            // Mark OTP as verified
            $upd = $db->prepare("UPDATE users SET otp_verified = 1 WHERE id = ?");
            $upd->execute([$userId]);

            return ['success' => true, 'message' => 'OTP verified.'];
        } catch (Exception $e) {
            error_log('EmailOTP::verifyOTP error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Verification error: ' . $e->getMessage()];
        }
    }

    // ─── Send OTP Email via Gmail SMTP ────────────────────────────────────────
    public static function sendOTPEmail(string $toEmail, string $toName, string $otp): array {
        $subject = 'Your Stocksathi Login OTP';
        $body    = self::buildEmailBody($toName, $otp);

        return self::sendViaSMTP($toEmail, $toName, $subject, $body);
    }

    // ─── Build styled HTML email ──────────────────────────────────────────────
    private static function buildEmailBody(string $name, string $otp): string {
        $expiryMin = self::OTP_EXPIRY;
        $digits    = str_split($otp);
        $digitHtml = '';
        foreach ($digits as $d) {
            $digitHtml .= "<span style='display:inline-block;width:42px;height:52px;line-height:52px;"
                        . "background:#1a73e8;color:#fff;font-size:26px;font-weight:700;"
                        . "border-radius:8px;margin:4px;text-align:center;'>{$d}</span>";
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f0f4ff;font-family:'Segoe UI',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4ff;padding:40px 0;">
    <tr><td align="center">
      <table width="520" cellpadding="0" cellspacing="0" style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.08);">
        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1a73e8,#0d47a1);padding:36px 40px;text-align:center;">
            <h1 style="margin:0;color:#fff;font-size:26px;font-weight:700;letter-spacing:-0.5px;">🔐 Stocksathi</h1>
            <p style="margin:8px 0 0;color:rgba(255,255,255,0.8);font-size:14px;">Inventory Management System</p>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:40px;">
            <p style="margin:0 0 8px;color:#374151;font-size:16px;">Hello, <strong>{$name}</strong>!</p>
            <p style="margin:0 0 28px;color:#6b7280;font-size:15px;line-height:1.6;">
              Your One-Time Password (OTP) for logging into Stocksathi is:
            </p>
            <div style="text-align:center;margin:0 0 28px;">{$digitHtml}</div>
            <div style="background:#fff8e1;border-left:4px solid #f59e0b;border-radius:6px;padding:14px 18px;margin-bottom:24px;">
              <p style="margin:0;color:#92400e;font-size:13px;">
                ⏳ This OTP is valid for <strong>{$expiryMin} minutes</strong> only.<br>
                Do not share this code with anyone, including Stocksathi support.
              </p>
            </div>
            <p style="margin:0;color:#9ca3af;font-size:13px;">
              If you did not request this, please ignore this email or contact your administrator.
            </p>
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td style="background:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
            <p style="margin:0;color:#9ca3af;font-size:12px;">© 2025 Stocksathi · Secure Login System</p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    // ─── Low-level SMTP send (SSL port 465) ───────────────────────────────────
    private static function sendViaSMTP(string $toEmail, string $toName, string $subject, string $body): array {
        $host     = self::SMTP_HOST;
        $port     = self::SMTP_PORT;
        $username = self::SMTP_USERNAME;
        $password = self::SMTP_PASSWORD;
        $fromEmail= self::FROM_EMAIL;
        $fromName = self::FROM_NAME;

        $errno = 0; $errstr = '';
        $smtp = @fsockopen($host, $port, $errno, $errstr, 15);
        if (!$smtp) {
            error_log("EmailOTP SMTP connect failed: {$errstr} ({$errno})");
            return ['success' => false, 'message' => "SMTP connect failed: {$errstr}"];
        }

        $read = function() use ($smtp) {
            $data = '';
            while ($line = fgets($smtp, 515)) {
                $data .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $data;
        };

        $send = function(string $cmd) use ($smtp, $read) {
            fwrite($smtp, $cmd . "\r\n");
            return $read();
        };

        try {
            $read(); // 220 banner
            $send("EHLO stocksathi.local");
            $send("AUTH LOGIN");
            $send(base64_encode($username));
            $resp = $send(base64_encode($password));

            if (strpos($resp, '235') === false) {
                fclose($smtp);
                return ['success' => false, 'message' => 'SMTP authentication failed. Check email/app-password.'];
            }

            $send("MAIL FROM:<{$fromEmail}>");
            $send("RCPT TO:<{$toEmail}>");
            $send("DATA");

            // Build RFC-compliant message
            $boundary  = md5(uniqid((string)time()));
            $toHeader  = $toName ? "\"{$toName}\" <{$toEmail}>" : $toEmail;
            $fromHeader= "\"{$fromName}\" <{$fromEmail}>";
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

            $message  = "From: {$fromHeader}\r\n";
            $message .= "To: {$toHeader}\r\n";
            $message .= "Subject: {$encodedSubject}\r\n";
            $message .= "MIME-Version: 1.0\r\n";
            $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $message .= "\r\n";
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
            $message .= "Your Stocksathi OTP is: {$body}\r\n\r\n";  // fallback plain
            $message .= "--{$boundary}\r\n";
            $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
            $message .= $body . "\r\n";
            $message .= "--{$boundary}--\r\n";
            $message .= "\r\n.";

            $resp = $send($message);
            $send("QUIT");
            fclose($smtp);

            if (strpos($resp, '250') !== false) {
                return ['success' => true, 'message' => 'OTP email sent successfully.'];
            }
            return ['success' => false, 'message' => 'SMTP DATA response: ' . trim($resp)];

        } catch (Exception $e) {
            if (is_resource($smtp)) fclose($smtp);
            return ['success' => false, 'message' => 'SMTP exception: ' . $e->getMessage()];
        }
    }
}
