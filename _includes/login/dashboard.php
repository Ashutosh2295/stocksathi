<?php
require_once 'auth.php';
requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Stocksathi</title>
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <div class="dashboard-header">
            <div>
                <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
                <p style="color: var(--text-secondary); margin-top: 0.5rem;">Email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
            </div>
            <a href="?logout=1" class="logout-btn">Logout</a>
        </div>
        
        <div style="background: white; padding: 2rem; border-radius: 12px; box-shadow: var(--shadow-md); text-align: center;">
            <h2 style="color: var(--text-primary); margin-bottom: 1rem;">Dashboard</h2>
            <p style="color: var(--text-secondary);">You have successfully logged in to Stocksathi!</p>
            <p style="color: var(--text-secondary); margin-top: 0.5rem;">This is a placeholder dashboard page.</p>
        </div>
    </div>
</body>
</html>