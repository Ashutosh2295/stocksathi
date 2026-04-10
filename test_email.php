<?php
// test_email.php

$host     = 'ssl://smtp.gmail.com';
$port     = 465;
$username = 'ashutoshbhavsar33@gmail.com';
$password = 'foxn gamy koqp jlmo';   // Gmail App Password
$fromEmail= 'ashutoshbhavsar33@gmail.com';
$fromName = 'Stocksathi Cron Report';
$toEmail  = 'ffthefind@gmail.com';
$toName   = 'Admin';
$subject  = 'Daily Stocksathi Cron Report (TEST)';

$body = <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:20px;font-family:Arial,sans-serif;background:#f0f4ff;">
  <div style="max-width:600px;margin:auto;background:white;padding:30px;border-radius:10px;box-shadow:0 0 10px rgba(0,0,0,0.1);">
    <h2 style="color:#1a73e8;">Daily Stocksathi Report</h2>
    <p>Hello,</p>
    <p>This is a test report sent from your automated cron job script.</p>
    <p><strong>Time executed:</strong> <span style="color:#ef4444;">%time%</span></p>
    <ul>
        <li>Total New Users: 5</li>
        <li>Active Sessions: 12</li>
        <li>System Status: OK</li>
    </ul>
    <br>
    <p style="color:#666;font-size:12px;">This was sent securely via PHP SMTP Stream to ffthefind@gmail.com.</p>
  </div>
</body>
</html>
HTML;

$body = str_replace('%time%', date('Y-m-d H:i:s'), $body);

$errno = 0; $errstr = '';
$smtp = @fsockopen($host, $port, $errno, $errstr, 15);
if (!$smtp) {
    die("SMTP connect failed: {$errstr} ({$errno})\n");
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
        die("SMTP authentication failed. Check email/app-password.\n");
    }

    $send("MAIL FROM:<{$fromEmail}>");
    $send("RCPT TO:<{$toEmail}>");
    $send("DATA");

    $boundary  = md5(uniqid((string)time()));
    $toHeader  = "\"{$toName}\" <{$toEmail}>";
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
    $message .= "View the email in HTML format for the report.\r\n\r\n";
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
    $message .= $body . "\r\n";
    $message .= "--{$boundary}--\r\n";
    $message .= "\r\n.";

    $resp = $send($message);
    $send("QUIT");
    fclose($smtp);

    if (strpos($resp, '250') !== false) {
        echo "SUCCESS: Cron report test email sent to {$toEmail}!\n";
    } else {
        echo "FAILED: SMTP DATA response: " . trim($resp) . "\n";
    }
} catch (Exception $e) {
    if (is_resource($smtp)) fclose($smtp);
    echo "SMTP exception: " . $e->getMessage() . "\n";
}
