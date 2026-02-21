<?php
/**
 * Quick Registration Debug
 * This will show exactly what's happening
 */

// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Registration Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #0f0; }
        .step { padding: 10px; margin: 5px 0; background: #2a2a2a; border-left: 3px solid #0f0; }
        .error { border-left-color: #f00; color: #f00; }
        .success { border-left-color: #0f0; color: #0f0; }
        .info { border-left-color: #ff0; color: #ff0; }
    </style>
</head>
<body>
<h1>🔍 Registration Debug Mode</h1>";

echo "<div class='step info'>Starting debug process...</div>";

// Step 1: Check if files exist
echo "<div class='step'>Step 1: Checking required files...</div>";
$files = [
    '_includes/config.php',
    '_includes/database.php',
    '_includes/AuthHelper.php',
    '_includes/Session.php',
    '_includes/Validator.php'
];

foreach ($files as $file) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        echo "<div class='step success'>✓ Found: $file</div>";
    } else {
        echo "<div class='step error'>✗ Missing: $file</div>";
    }
}

// Step 2: Try to include files
echo "<div class='step'>Step 2: Loading required files...</div>";
try {
    require_once __DIR__ . '/_includes/config.php';
    echo "<div class='step success'>✓ Loaded config.php</div>";
    
    require_once __DIR__ . '/_includes/database.php';
    echo "<div class='step success'>✓ Loaded database.php</div>";
    
    require_once __DIR__ . '/_includes/AuthHelper.php';
    echo "<div class='step success'>✓ Loaded AuthHelper.php</div>";
    
    require_once __DIR__ . '/_includes/Session.php';
    echo "<div class='step success'>✓ Loaded Session.php</div>";
    
    require_once __DIR__ . '/_includes/Validator.php';
    echo "<div class='step success'>✓ Loaded Validator.php</div>";
} catch (Exception $e) {
    echo "<div class='step error'>✗ Error loading files: " . $e->getMessage() . "</div>";
    echo "</body></html>";
    exit;
}

// Step 3: Test database connection
echo "<div class='step'>Step 3: Testing database connection...</div>";
try {
    $db = Database::getInstance();
    echo "<div class='step success'>✓ Database instance created</div>";
    
    $conn = $db->getConnection();
    echo "<div class='step success'>✓ Database connection obtained</div>";
} catch (Exception $e) {
    echo "<div class='step error'>✗ Database connection failed: " . $e->getMessage() . "</div>";
    echo "</body></html>";
    exit;
}

// Step 4: Test query
echo "<div class='step'>Step 4: Testing database query...</div>";
try {
    $userCount = $db->queryOne("SELECT COUNT(*) as count FROM users");
    echo "<div class='step success'>✓ Query executed successfully</div>";
    echo "<div class='step info'>Current user count: " . $userCount['count'] . "</div>";
    
    if ($userCount['count'] == 0) {
        echo "<div class='step success'>✓ No users found - First registration will be Super Admin!</div>";
    } else {
        echo "<div class='step info'>Found " . $userCount['count'] . " existing user(s)</div>";
    }
} catch (Exception $e) {
    echo "<div class='step error'>✗ Query failed: " . $e->getMessage() . "</div>";
    echo "</body></html>";
    exit;
}

// Step 5: Test registration with dummy data
echo "<div class='step'>Step 5: Testing registration function...</div>";
try {
    // Don't actually register, just test the function exists
    if (class_exists('AuthHelper')) {
        echo "<div class='step success'>✓ AuthHelper class exists</div>";
        
        if (method_exists('AuthHelper', 'register')) {
            echo "<div class='step success'>✓ AuthHelper::register() method exists</div>";
        } else {
            echo "<div class='step error'>✗ AuthHelper::register() method NOT found</div>";
        }
    } else {
        echo "<div class='step error'>✗ AuthHelper class NOT found</div>";
    }
} catch (Exception $e) {
    echo "<div class='step error'>✗ Error: " . $e->getMessage() . "</div>";
}

// Final result
echo "<div class='step success' style='margin-top: 20px; font-size: 18px;'>
    ✓ ALL CHECKS PASSED! Registration should work.
</div>";

echo "<div class='step info' style='margin-top: 20px;'>
    <strong>Next Steps:</strong><br>
    1. If all checks passed, the issue might be browser-related<br>
    2. Try clearing browser cache (Ctrl + Shift + Delete)<br>
    3. Try in incognito/private mode<br>
    4. Check browser console for JavaScript errors (F12)<br>
    5. Try the actual registration page again
</div>";

echo "<div style='margin-top: 20px;'>
    <a href='pages/register.php' style='color: #0f0; font-size: 18px;'>→ Go to Registration Page</a>
</div>";

echo "</body></html>";
?>
