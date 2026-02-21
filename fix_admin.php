<?php
$conn = new mysqli('localhost', 'root', '', 'stocksathi');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 1. Get users with admin/super_admin role
$result = $conn->query("SELECT id, username, email FROM users WHERE role IN ('admin', 'super_admin')");

while($row = $result->fetch_assoc()) {
    $userId = $row['id'];
    // check if employee exists with this user_id
    $check = $conn->query("SELECT id FROM employees WHERE user_id = " . $userId);
    if ($check->num_rows == 0) {
        $code = 'ADM-' . str_pad($userId, 4, '0', STR_PAD_LEFT);
        $name = $conn->real_escape_string($row['username']);
        $email = $conn->real_escape_string($row['email'] ?: $name . '@example.com');
        
        // Let's insert minimally, handle missing fields.
        // What fields typically exist? first_name, last_name, employee_code, email, phone, status
        // Instead of hardcoding all fields, let's just insert minimal and let DB defaults handle the rest if possible
        $sql = "INSERT INTO employees (user_id, employee_code, first_name, last_name, email, phone, status) 
                VALUES ('$userId', '$code', '$name', 'Admin', '$email', '0000000000', 'active')";
        if ($conn->query($sql) === TRUE) {
            echo "Successfully linked user $name to new employee record.\n";
        } else {
            echo "Error for $name: " . $conn->error . "\n";
        }
    } else {
        echo "User " . $row['username'] . " is already linked to an employee.\n";
    }
}
$conn->close();
echo "Done.";
