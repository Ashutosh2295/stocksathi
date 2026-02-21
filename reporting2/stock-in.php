<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock In - Stocksathi</title>
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-brand">
                <a href="dashboard.php" style="display: flex; align-items: center; gap: var(--space-2); text-decoration: none;">
                    <img src="logo.png" alt="Logo" style="width: 40px; height: 40px; object-fit: contain;">
                    <span style="font-size: var(--font-size-xl); font-weight: 700; color: var(--color-primary);">Stocksathi</span>
                </a>
            </div>

            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Dashboard</div>
                    <a href="dashboard.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Product Management</div>
                    <a href="products.php" class="nav-link">
                        <i class="fas fa-box"></i>
                        Products
                    </a>
                    <a href="categories.php" class="nav-link">
                        <i class="fas fa-tags"></i>
                        Categories
                    </a>
                </div>

                <div class="nav-section">
                    <div class="nav-section-title">Stock Management</div>
                    <a href="stock.php" class="nav-link">
                        <i class="fas fa-warehouse"></i>
                        Stock Overview
                    </a>
                    <a href="stock-in.php" class="nav-link active">
                        <i class="fas fa-arrow-down"></i>
                        Stock In
                    </a>
                    <a href="stock-out.php" class="nav-link">
                        <i class="fas fa-arrow-up"></i>
                        Stock Out
                    </a>
                    <a href="stock-adjustments.php" class="nav-link">
                        <i class="fas fa-adjust"></i>
                        Adjustments
                    </a>
                </div>

                <div class="nav-section" style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border-light);">
                    <a href="#" onclick="handleLogout()" class="nav-link" style="color: var(--color-danger);">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </nav>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <h1 class="page-title">Stock In</h1>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <div class="user-avatar">JD</div>
                        <div class="user-info">
                            <div class="user-name">John Doe</div>
                            <div class="user-role">Administrator</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <div class="page-header-top">
                        <div>
                            <h2 class="page-header-title">Stock In Records</h2>
                            <p class="page-header-description">Track incoming stock and purchases</p>
                        </div>
                        <button class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Add Stock In
                        </button>
                    </div>
                </div>

                <!-- Stock In Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Supplier</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>SI001</strong></td>
                                        <td>Laptop Pro 15</td>
                                        <td>+50</td>
                                        <td>Tech Supplies Inc</td>
                                        <td>2024-01-29 09:00</td>
                                        <td><span class="badge badge-success">Completed</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-ghost"><i class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-ghost"><i class="fas fa-print"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>SI002</strong></td>
                                        <td>Wireless Mouse</td>
                                        <td>+100</td>
                                        <td>Electronics Hub</td>
                                        <td>2024-01-28 14:30</td>
                                        <td><span class="badge badge-success">Completed</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-ghost"><i class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-ghost"><i class="fas fa-print"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>SI003</strong></td>
                                        <td>Coffee Beans 1kg</td>
                                        <td>+200</td>
                                        <td>Fresh Foods Ltd</td>
                                        <td>2024-01-27 11:15</td>
                                        <td><span class="badge badge-success">Completed</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-ghost"><i class="fas fa-eye"></i></button>
                                            <button class="btn btn-sm btn-ghost"><i class="fas fa-print"></i></button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        if (!sessionStorage.getItem('isLoggedIn')) {
            window.location.href = 'login.php';
        }

        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                sessionStorage.clear();
                window.location.href = 'login.php';
            }
        }
    </script>
</body>
</html>
