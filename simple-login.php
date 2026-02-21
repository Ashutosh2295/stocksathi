<?php
/**
 * Simple Login Test - No Fancy Stuff
 * This WILL show you what's wrong
 */

// Show ALL errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start output buffering
ob_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        button { background: #2196F3; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; margin-top: 10px; }
        button:hover { background: #0b7dda; }
        .message { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .debug { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔐 Simple Login Test</h1>";

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='debug'>📝 Login form submitted!</div>";
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "<div class='debug'>✓ Form data received</div>";
    
    if (empty($email) || empty($password)) {
        $message = 'Email and password are required!';
        $messageType = 'error';
    } else {
        try {
            echo "<div class='debug'>Loading required files...</div>";
            require_once __DIR__ . '/_includes/database.php';
            require_once __DIR__ . '/_includes/AuthHelper.php';
            require_once __DIR__ . '/_includes/Session.php';
            echo "<div class='debug'>✓ Files loaded</div>";
            
            echo "<div class='debug'>Attempting login...</div>";
            $result = AuthHelper::login($email, $password);
            
            if ($result['success']) {
                echo "<div class='debug'>✓ Login successful!</div>";
                echo "<div class='debug'>User role: " . $result['user']['role'] . "</div>";
                
                $message = "✅ Login successful! Redirecting...";
                $messageType = 'success';
                
                // Determine redirect based on role
                $role = $result['user']['role'];
                $redirects = [
                    'super_admin' => 'pages/dashboards/super-admin.php',
                    'admin' => 'pages/dashboards/admin.php',
                    'store_manager' => 'pages/dashboards/store-manager.php',
                    'sales_executive' => 'pages/dashboards/sales-executive.php',
                    'accountant' => 'pages/dashboards/accountant.php'
                ];
                
                $redirectUrl = $redirects[$role] ?? 'index.php';
                
                echo "<div class='debug'>Redirecting to: $redirectUrl</div>";
                echo "<div class='info' style='margin-top: 20px;'>
                    <strong>Redirecting to your dashboard...</strong><br>
                    <a href='$redirectUrl' style='color: #0c5460; font-weight: bold;'>→ Click here if not redirected</a>
                </div>";
                
                // JavaScript redirect
                echo "<script>
                    setTimeout(function() {
                        window.location.href = '$redirectUrl';
                    }, 2000);
                </script>";
                
            } else {
                $message = "❌ Login failed: " . $result['message'];
                $messageType = 'error';
                echo "<div class='debug'>✗ Login failed: " . $result['message'] . "</div>";
            }
            
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = 'error';
            echo "<div class='debug'>✗ Exception: " . $e->getMessage() . "</div>";
        }
    }
    
    if ($message) {
        echo "<div class='message $messageType'>$message</div>";
    }
}

?>

<form method="POST" action="">
    <div class="form-group">
        <label>Email:</label>
        <input type="email" name="email" required placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    
    <div class="form-group">
        <label>Password:</label>
        <input type="password" name="password" required placeholder="Enter password">
    </div>
    
    <button type="submit">Login</button>
</form>

<div class="info" style="margin-top: 20px;">
    <strong>Don't have an account?</strong><br>
    <a href="simple-register.php" style="color: #0c5460;">Register here</a>
</div>

<div class="info" style="margin-top: 10px;">
    <strong>Debug Tools:</strong><br>
    <a href="test-db.php" style="color: #0c5460;">Test Database</a> |
    <a href="debug-registration.php" style="color: #0c5460;">Debug Registration</a>
</div>

</div>
</body>
</html>
<?php
ob_end_flush();
?>
