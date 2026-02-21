<?php
/**
 * Simple Registration Test - No Fancy Stuff
 * This WILL show you what's wrong
 */

// Show ALL errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start output buffering to prevent header issues
ob_start();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Simple Registration Test</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .form-group { margin: 15px 0; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; font-size: 14px; }
        button { background: #4CAF50; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; width: 100%; margin-top: 10px; }
        button:hover { background: #45a049; }
        .message { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        .debug { background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px; }
    </style>
</head>
<body>
<div class='container'>
<h1>🔧 Simple Registration Test</h1>";

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<div class='debug'>📝 Form submitted! Processing...</div>";
    
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    echo "<div class='debug'>✓ Form data received</div>";
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $message = 'All fields are required!';
        $messageType = 'error';
    } elseif ($password !== $confirm) {
        $message = 'Passwords do not match!';
        $messageType = 'error';
    } elseif (strlen($password) < 6) {
        $message = 'Password must be at least 6 characters!';
        $messageType = 'error';
    } else {
        echo "<div class='debug'>✓ Validation passed</div>";
        
        // Try to load required files
        try {
            echo "<div class='debug'>Loading database class...</div>";
            require_once __DIR__ . '/_includes/database.php';
            echo "<div class='debug'>✓ Database class loaded</div>";
            
            echo "<div class='debug'>Loading AuthHelper class...</div>";
            require_once __DIR__ . '/_includes/AuthHelper.php';
            echo "<div class='debug'>✓ AuthHelper class loaded</div>";
            
            // Get database instance
            echo "<div class='debug'>Connecting to database...</div>";
            $db = Database::getInstance();
            echo "<div class='debug'>✓ Database instance created</div>";
            
            // Check if first user
            echo "<div class='debug'>Checking user count...</div>";
            $userCount = $db->queryOne("SELECT COUNT(*) as count FROM users");
            $isFirstUser = ($userCount['count'] == 0);
            echo "<div class='debug'>✓ User count: " . $userCount['count'] . "</div>";
            
            $role = $isFirstUser ? 'super_admin' : 'user';
            echo "<div class='debug'>✓ Role assigned: $role</div>";
            
            // Register user
            echo "<div class='debug'>Registering user...</div>";
            $result = AuthHelper::register([
                'username' => $email,
                'email' => $email,
                'password' => $password,
                'full_name' => $name,
                'role' => $role,
                'phone' => ''
            ]);
            
            if ($result['success']) {
                $message = $isFirstUser 
                    ? "✅ SUCCESS! You are now the Super Admin! You can login now." 
                    : "✅ SUCCESS! Registration complete! You can login now.";
                $messageType = 'success';
                echo "<div class='debug'>✓ Registration successful!</div>";
                echo "<div class='info' style='margin-top: 20px;'>
                    <strong>Next Step:</strong><br>
                    <a href='pages/login.php' style='color: #0c5460; font-weight: bold;'>→ Click here to login</a>
                </div>";
            } else {
                $message = "❌ Registration failed: " . $result['message'];
                $messageType = 'error';
                echo "<div class='debug'>✗ Registration failed: " . $result['message'] . "</div>";
            }
            
        } catch (Exception $e) {
            $message = "❌ Error: " . $e->getMessage();
            $messageType = 'error';
            echo "<div class='debug'>✗ Exception: " . $e->getMessage() . "</div>";
            echo "<div class='debug'>File: " . $e->getFile() . "</div>";
            echo "<div class='debug'>Line: " . $e->getLine() . "</div>";
        }
    }
    
    if ($message) {
        echo "<div class='message $messageType'>$message</div>";
    }
}

?>

<form method="POST" action="">
    <div class="form-group">
        <label>Full Name:</label>
        <input type="text" name="name" required placeholder="Enter your name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
    </div>
    
    <div class="form-group">
        <label>Email:</label>
        <input type="email" name="email" required placeholder="your@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
    </div>
    
    <div class="form-group">
        <label>Password:</label>
        <input type="password" name="password" required placeholder="Min 6 characters">
    </div>
    
    <div class="form-group">
        <label>Confirm Password:</label>
        <input type="password" name="confirm_password" required placeholder="Confirm password">
    </div>
    
    <button type="submit">Create Account</button>
</form>

<div class="info" style="margin-top: 20px;">
    <strong>Already have an account?</strong><br>
    <a href="pages/login.php" style="color: #0c5460;">Login here</a>
</div>

<div class="info" style="margin-top: 10px;">
    <strong>Debug Tools:</strong><br>
    <a href="test-db.php" style="color: #0c5460;">Test Database Connection</a> |
    <a href="debug-registration.php" style="color: #0c5460;">Full Debug Test</a>
</div>

</div>
</body>
</html>
<?php
ob_end_flush();
?>
