<?php
/**
 * DEBUG - Check Session and Database
 */

require_once '_includes/session_guard.php';
require_once '_includes/database.php';

$db = Database::getInstance();

echo "<!DOCTYPE html>
<html>
<head>
    <title>Debug Session - Stocksathi</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .section { background: #252526; padding: 20px; margin: 20px 0; border-radius: 8px; border: 1px solid #3e3e42; }
        h2 { color: #4ec9b0; margin-top: 0; }
        pre { background: #1e1e1e; padding: 15px; border-radius: 4px; overflow-x: auto; border: 1px solid #3e3e42; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border: 1px solid #3e3e42; }
        th { background: #2d2d30; color: #4ec9b0; }
    </style>
</head>
<body>
    <h1>🔍 Debug Information</h1>";

// Session Data
echo "<div class='section'>
    <h2>📋 Current Session Data</h2>
    <table>
        <tr><th>Key</th><th>Value</th></tr>
        <tr><td>User ID</td><td>" . (Session::getUserId() ?? '<span class=\"error\">NULL</span>') . "</td></tr>
        <tr><td>Username</td><td>" . (Session::getUsername() ?? '<span class=\"error\">NULL</span>') . "</td></tr>
        <tr><td>Role</td><td>" . (Session::getUserRole() ?? '<span class=\"error\">NULL</span>') . "</td></tr>
        <tr><td>Organization ID</td><td>" . (Session::getOrganizationId() ?? '<span class=\"error\">NULL</span>') . "</td></tr>
    </table>
</div>";

// Full Session Array
echo "<div class='section'>
    <h2>🗂️ Full Session Array</h2>
    <pre>" . print_r($_SESSION, true) . "</pre>
</div>";

// Database Check
$userId = Session::getUserId();
if ($userId) {
    echo "<div class='section'>
        <h2>💾 Database - Current User</h2>";
    
    $user = $db->queryOne("SELECT id, username, email, role, organization_id, status FROM users WHERE id = ?", [$userId]);
    
    echo "<table>
        <tr><th>Field</th><th>Value</th></tr>";
    
    foreach ($user as $key => $value) {
        $displayValue = $value ?? '<span class="error">NULL</span>';
        if ($key === 'organization_id' && $value === null) {
            $displayValue = '<span class="error">NULL ⚠️ THIS IS THE PROBLEM!</span>';
        }
        echo "<tr><td>{$key}</td><td>{$displayValue}</td></tr>";
    }
    
    echo "</table>
    </div>";
    
    // Check organization
    if ($user['organization_id']) {
        echo "<div class='section'>
            <h2>🏢 Organization Details</h2>";
        
        $org = $db->queryOne("SELECT * FROM organizations WHERE id = ?", [$user['organization_id']]);
        
        if ($org) {
            echo "<table>
                <tr><th>Field</th><th>Value</th></tr>";
            foreach ($org as $key => $value) {
                echo "<tr><td>{$key}</td><td>" . htmlspecialchars($value ?? 'NULL') . "</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ Organization not found!</p>";
        }
        
        echo "</div>";
    } else {
        echo "<div class='section'>
            <h2 class='error'>⚠️ NO ORGANIZATION ASSIGNED!</h2>
            <p>This user has <code>organization_id = NULL</code> in the database.</p>
            <p><strong>Solution:</strong> Run the EMERGENCY_FIX.sql script in phpMyAdmin</p>
        </div>";
    }
    
    // Check data counts
    echo "<div class='section'>
        <h2>📊 Data Counts (Total in Database)</h2>
        <table>
            <tr><th>Table</th><th>Total Records</th><th>With org_id = NULL</th></tr>";
    
    $tables = ['products', 'customers', 'invoices', 'users'];
    foreach ($tables as $table) {
        $total = $db->queryOne("SELECT COUNT(*) as count FROM {$table}")['count'];
        $nullCount = $db->queryOne("SELECT COUNT(*) as count FROM {$table} WHERE organization_id IS NULL")['count'];
        
        $nullDisplay = $nullCount > 0 ? "<span class='error'>{$nullCount} ⚠️</span>" : "<span class='success'>0 ✅</span>";
        
        echo "<tr><td>{$table}</td><td>{$total}</td><td>{$nullDisplay}</td></tr>";
    }
    
    echo "</table>
    </div>";
}

// Organizations List
echo "<div class='section'>
    <h2>🏢 All Organizations</h2>";

$orgs = $db->query("SELECT * FROM organizations");

if (count($orgs) > 0) {
    echo "<table>
        <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th></tr>";
    
    foreach ($orgs as $org) {
        echo "<tr>
            <td>{$org['id']}</td>
            <td>{$org['name']}</td>
            <td>{$org['email']}</td>
            <td>{$org['status']}</td>
        </tr>";
    }
    
    echo "</table>";
} else {
    echo "<p class='error'>❌ No organizations found! Run setup-organization.php first.</p>";
}

echo "</div>";

// Fix Instructions
echo "<div class='section'>
    <h2>🔧 How to Fix</h2>
    <ol>
        <li><strong>Open phpMyAdmin</strong> (http://localhost/phpmyadmin)</li>
        <li><strong>Select your database</strong> (stocksathi)</li>
        <li><strong>Click SQL tab</strong></li>
        <li><strong>Copy and paste</strong> the contents of <code>EMERGENCY_FIX.sql</code></li>
        <li><strong>Click Go</strong></li>
        <li><strong>Logout and login again</strong></li>
    </ol>
    
    <p><strong>Or run:</strong> <code>http://localhost/stocksathi/fix-existing-data.php</code></p>
</div>";

echo "</body></html>";
?>
