<?php

file_put_contents("check.txt", "Starting script...\n");

$conn = new mysqli('localhost', 'root', '', 'stocksathi');
if ($conn->connect_error) {
    file_put_contents("check.txt", "Connection failed: " . $conn->connect_error . "\n", FILE_APPEND);
    exit;
}
mysqli_report(MYSQLI_REPORT_OFF);

$result = $conn->query("SELECT id, username, email FROM users WHERE role IN ('admin', 'super_admin')");

while($row = $result->fetch_assoc()) {
    $userId = $row['id'];
    file_put_contents("check.txt", "Checking user: " . $row['username'] . " (ID: $userId)\n", FILE_APPEND);
    
    $check = $conn->query("SELECT id FROM employees WHERE user_id = " . $userId);
    if ($check && $check->num_rows == 0) {
        $name = $conn->real_escape_string($row['username']);
        $email = $conn->real_escape_string($row['email'] ?: $name . '@example.com');
        $code = 'ADM-' . str_pad($userId, 4, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO employees (user_id, employee_code, first_name, last_name, email, phone, status) 
                VALUES ($userId, '$code', '$name', 'Admin', 'emp_$userId\_$email', '0000000000', 'active')";
        
        if ($conn->query($sql) === TRUE) {
            file_put_contents("check.txt", "-> Inserted new employee record for $name.\n", FILE_APPEND);
        } else {
            file_put_contents("check.txt", "-> Error inserting: " . $conn->error . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents("check.txt", "-> User already has an employee record.\n", FILE_APPEND);
    }
}
$conn->close();
file_put_contents("check.txt", "Script finished.\n", FILE_APPEND);
