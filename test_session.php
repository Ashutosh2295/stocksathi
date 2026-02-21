<?php
/**
 * Session Test Script
 * Quick test to verify Session class is working
 */

require_once __DIR__ . '/_includes/database.php';
require_once __DIR__ . '/_includes/Session.php';

// Start session
Session::start();

echo "<h1>Session Test</h1>";
echo "<hr>";

// Test 1: Check if logged in
echo "<h2>Test 1: Is Logged In?</h2>";
echo Session::isLoggedIn() ? "✅ Yes, user is logged in" : "❌ No, user is NOT logged in";
echo "<br><br>";

// Test 2: Get user data
echo "<h2>Test 2: User Data</h2>";
if (Session::isLoggedIn()) {
    echo "User ID: " . Session::getUserId() . "<br>";
    echo "Username: " . Session::getUserName() . "<br>";
    echo "Role: " . Session::getUserRole() . "<br>";
    echo "<br>";
    
    echo "<h3>Using getUser() method:</h3>";
    $user = Session::getUser();
    echo "<pre>";
    print_r($user);
    echo "</pre>";
} else {
    echo "No user data available (not logged in)<br>";
}

echo "<hr>";
echo "<p><a href='pages/login.php'>Go to Login</a> | <a href='index.php'>Go to Dashboard</a></p>";
