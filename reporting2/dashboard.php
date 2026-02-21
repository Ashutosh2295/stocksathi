<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Reporting System</title>
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Sidebar Dropdown Styles */
        .nav-dropdown-toggle {
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: var(--space-2) 0;
            user-select: none;
        }

        .nav-dropdown-icon {
            font-size: 10px;
            transition: transform var(--transition-base);
        }

        .nav-dropdown-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height var(--transition-base);
        }

        .nav-dropdown-content.show {
            max-height: 500px;
        }

        .nav-sub-item {
            padding-left: var(--space-8);
            font-size: var(--font-size-sm);
        }

        .sidebar-user-info {
            padding: var(--space-4);
            border-top: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            gap: var(--space-3);
            background: var(--color-gray-50);
        }

        .sidebar-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            background: var(--color-primary);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: var(--font-size-sm);
        }

        .sidebar-user-details {
            flex: 1;
        }

        .sidebar-user-name {
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--text-primary);
        }

        .sidebar-user-org {
            font-size: var(--font-size-xs);
            color: var(--text-muted);
        }
    </style>
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
                <!-- Dashboard Section -->
                <div class="nav-section">
                    <div class="nav-section-title">Dashboard</div>
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </div>

                <!-- Product Management -->
                <div class="nav-section">
                    <div class="nav-section-title nav-dropdown-toggle" onclick="toggleDropdown(this)">
                        <span>Product Management</span>
                        <span class="nav-dropdown-icon">▼</span>
                    </div>
                    <div class="nav-dropdown-content">
                        <a href="products.php" class="nav-link nav-sub-item">
                            <i class="fas fa-box"></i>
                            Products
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-tags"></i>
                            Categories
                        </a>
                    </div>
                </div>

                <!-- Stock Management -->
                <div class="nav-section">
                    <div class="nav-section-title nav-dropdown-toggle" onclick="toggleDropdown(this)">
                        <span>Stock Management</span>
                        <span class="nav-dropdown-icon">▼</span>
                    </div>
                    <div class="nav-dropdown-content">
                        <a href="stock.php" class="nav-link nav-sub-item">
                            <i class="fas fa-warehouse"></i>
                            Stock Overview
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-arrow-down"></i>
                            Stock In
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-arrow-up"></i>
                            Stock Out
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-adjust"></i>
                            Adjustments
                        </a>
                    </div>
                </div>

                <!-- Sales & Billing -->
                <div class="nav-section">
                    <div class="nav-section-title nav-dropdown-toggle" onclick="toggleDropdown(this)">
                        <span>Sales & Billing</span>
                        <span class="nav-dropdown-icon">▼</span>
                    </div>
                    <div class="nav-dropdown-content">
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-file-invoice"></i>
                            Invoices
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-file-alt"></i>
                            Quotations
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-undo"></i>
                            Sales Returns
                        </a>
                    </div>
                </div>

                <!-- Marketing -->
                <div class="nav-section">
                    <div class="nav-section-title">Marketing</div>
                    <a href="#" class="nav-link">
                        <i class="fas fa-bullhorn"></i>
                        Promotions
                    </a>
                </div>

                <!-- Finance -->
                <div class="nav-section">
                    <div class="nav-section-title">Finance</div>
                    <a href="#" class="nav-link">
                        <i class="fas fa-money-bill-wave"></i>
                        Expenses
                    </a>
                </div>

                <!-- People -->
                <div class="nav-section">
                    <div class="nav-section-title nav-dropdown-toggle" onclick="toggleDropdown(this)">
                        <span>People</span>
                        <span class="nav-dropdown-icon">▼</span>
                    </div>
                    <div class="nav-dropdown-content">
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-users"></i>
                            Customers
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-truck"></i>
                            Suppliers
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-store"></i>
                            Stores
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-warehouse"></i>
                            Warehouses
                        </a>
                    </div>
                </div>

                <!-- Human Resources -->
                <div class="nav-section">
                    <div class="nav-section-title nav-dropdown-toggle" onclick="toggleDropdown(this)">
                        <span>Human Resources</span>
                        <span class="nav-dropdown-icon">▼</span>
                    </div>
                    <div class="nav-dropdown-content">
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-user-tie"></i>
                            Employees
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-building"></i>
                            Departments
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-calendar-check"></i>
                            Attendance
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-calendar-times"></i>
                            Leave Management
                        </a>
                    </div>
                </div>

                <!-- Analytics -->
                <div class="nav-section">
                    <div class="nav-section-title">Analytics</div>
                    <a href="#" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        Reports
                    </a>
                </div>

                <!-- Administration -->
                <div class="nav-section">
                    <div class="nav-section-title nav-dropdown-toggle" onclick="toggleDropdown(this)">
                        <span>Administration</span>
                        <span class="nav-dropdown-icon">▼</span>
                    </div>
                    <div class="nav-dropdown-content">
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-users-cog"></i>
                            Users
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-user-shield"></i>
                            Roles & Permissions
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-history"></i>
                            Activity Logs
                        </a>
                        <a href="#" class="nav-link nav-sub-item">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </div>
                </div>

                <!-- Logout -->
                <div class="nav-section" style="margin-top: auto; padding-top: 16px; border-top: 1px solid var(--border-light);">
                    <a href="#" onclick="handleLogout()" class="nav-link" style="color: var(--color-danger);">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </nav>

            <!-- User Info Footer -->
            <div class="sidebar-user-info">
                <div class="sidebar-user-avatar" id="userAvatar">JD</div>
                <div class="sidebar-user-details">
                    <div class="sidebar-user-name" id="userName">John Doe</div>
                    <div class="sidebar-user-org" id="userOrg">Organization</div>
                </div>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <h1 class="page-title">Dashboard</h1>
                </div>
                <div class="header-right" style="display: flex; align-items: center; gap: 1rem;">
                    <!-- Quick Add Button -->
                    <div style="position: relative;">
                        <button class="btn-primary" id="quickAddBtn" onclick="toggleQuickAdd()" style="padding: 0.5rem 1rem; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-plus"></i>
                            Quick Add
                            <i class="fas fa-chevron-down" style="font-size: 10px;"></i>
                        </button>
                        
                        <!-- Quick Add Dropdown -->
                        <div id="quickAddDropdown" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 8px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 200px; z-index: 1000;">
                            <div style="padding: 8px 0;">
                                <a href="products.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: var(--text-primary); text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='var(--color-gray-50)'" onmouseout="this.style.background='transparent'">
                                    <i class="fas fa-box" style="width: 20px; color: var(--color-primary);"></i>
                                    <span>Add Product</span>
                                </a>
                                <a href="categories.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: var(--text-primary); text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='var(--color-gray-50)'" onmouseout="this.style.background='transparent'">
                                    <i class="fas fa-tags" style="width: 20px; color: var(--color-primary);"></i>
                                    <span>Add Category</span>
                                </a>
                                <a href="stock-in.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: var(--text-primary); text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='var(--color-gray-50)'" onmouseout="this.style.background='transparent'">
                                    <i class="fas fa-arrow-down" style="width: 20px; color: var(--color-success);"></i>
                                    <span>Stock In</span>
                                </a>
                                <a href="stock-out.php" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; color: var(--text-primary); text-decoration: none; transition: background 0.2s;" onmouseover="this.style.background='var(--color-gray-50)'" onmouseout="this.style.background='transparent'">
                                    <i class="fas fa-arrow-up" style="width: 20px; color: var(--color-danger);"></i>
                                    <span>Stock Out</span>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notification Bell -->
                    <div style="position: relative;">
                        <div onclick="toggleNotifications()" style="cursor: pointer; position: relative;">
                            <i class="fas fa-bell" style="font-size: 20px; color: var(--text-secondary);"></i>
                            <span style="position: absolute; top: -4px; right: -4px; background: var(--color-danger); color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 600;">3</span>
                        </div>
                        
                        <!-- Notifications Panel -->
                        <div id="notificationsPanel" style="display: none; position: absolute; top: 100%; right: 0; margin-top: 12px; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); width: 320px; z-index: 1000;">
                            <div style="padding: 16px; border-bottom: 1px solid var(--border-light);">
                                <h4 style="margin: 0; font-size: 16px; font-weight: 600;">Notifications</h4>
                            </div>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <div style="padding: 12px 16px; border-bottom: 1px solid var(--border-light); cursor: pointer;" onmouseover="this.style.background='var(--color-gray-50)'" onmouseout="this.style.background='transparent'">
                                    <div style="display: flex; gap: 12px;">
                                        <div style="width: 8px; height: 8px; background: var(--color-primary); border-radius: 50%; margin-top: 6px;"></div>
                                        <div style="flex: 1;">
                                            <p style="margin: 0 0 4px 0; font-weight: 500; font-size: 14px;">Low Stock Alert</p>
                                            <p style="margin: 0; font-size: 13px; color: var(--text-secondary);">Cotton T-Shirt stock is running low (8 items)</p>
                                            <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--text-muted);">2 hours ago</p>
                                        </div>
                                    </div>
                                </div>
                                <div style="padding: 12px 16px; border-bottom: 1px solid var(--border-light); cursor: pointer;" onmouseover="this.style.background='var(--color-gray-50)'" onmouseout="this.style.background='transparent'">
                                    <div style="display: flex; gap: 12px;">
                                        <div style="width: 8px; height: 8px; background: var(--color-danger); border-radius: 50%; margin-top: 6px;"></div>
                                        <div style="flex: 1;">
                                            <p style="margin: 0 0 4px 0; font-weight: 500; font-size: 14px;">Out of Stock</p>
                                            <p style="margin: 0; font-size: 13px; color: var(--text-secondary);">Office Chair is out of stock</p>
                                            <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--text-muted);">5 hours ago</p>
                                        </div>
                                    </div>
                                </div>
                                <div style="padding: 12px 16px; cursor: pointer;" onmouseover="this.style.background='var(--color-gray-50)'" onmouseout="this.style.background='transparent'">
                                    <div style="display: flex; gap: 12px;">
                                        <div style="width: 8px; height: 8px; background: var(--color-success); border-radius: 50%; margin-top: 6px;"></div>
                                        <div style="flex: 1;">
                                            <p style="margin: 0 0 4px 0; font-weight: 500; font-size: 14px;">Stock Added</p>
                                            <p style="margin: 0; font-size: 13px; color: var(--text-secondary);">150 units of Laptop Pro 15 added</p>
                                            <p style="margin: 4px 0 0 0; font-size: 12px; color: var(--text-muted);">1 day ago</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div style="padding: 12px 16px; border-top: 1px solid var(--border-light); text-align: center;">
                                <a href="#" style="color: var(--color-primary); text-decoration: none; font-size: 14px; font-weight: 500;">View All Notifications</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- User Menu -->
                    <div class="user-menu">
                        <div class="user-avatar" id="headerAvatar">JD</div>
                        <div class="user-info">
                            <div class="user-name" id="headerName">John Doe</div>
                            <div class="user-role" id="headerOrg">Organization</div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Main Content -->
            <main class="main-content">
                <!-- Stats Grid -->
                <div class="grid grid-cols-4 gap-4 mb-6">
                    <div class="stat-card">
                        <div class="stat-card-icon primary">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="stat-card-value">1,234</div>
                        <div class="stat-card-label">Total Products</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-icon success">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <div class="stat-card-value">8,456</div>
                        <div class="stat-card-label">Stock Items</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-icon warning">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-card-value">23</div>
                        <div class="stat-card-label">Low Stock Alerts</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-icon danger">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-card-value">$45,678</div>
                        <div class="stat-card-label">Total Value</div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Sales Overview</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="salesChart" height="250"></canvas>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Stock Distribution</h3>
                        </div>
                        <div class="card-body">
                            <canvas id="stockChart" height="250"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Recent Activity</h3>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Activity</th>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Stock Added</td>
                                        <td>Product A</td>
                                        <td>+150</td>
                                        <td><span class="badge badge-success">Completed</span></td>
                                        <td>2024-01-29 08:30</td>
                                    </tr>
                                    <tr>
                                        <td>Stock Removed</td>
                                        <td>Product B</td>
                                        <td>-50</td>
                                        <td><span class="badge badge-danger">Completed</span></td>
                                        <td>2024-01-29 07:15</td>
                                    </tr>
                                    <tr>
                                        <td>New Product</td>
                                        <td>Product C</td>
                                        <td>200</td>
                                        <td><span class="badge badge-primary">Added</span></td>
                                        <td>2024-01-28 16:45</td>
                                    </tr>
                                    <tr>
                                        <td>Low Stock Alert</td>
                                        <td>Product D</td>
                                        <td>15</td>
                                        <td><span class="badge badge-warning">Alert</span></td>
                                        <td>2024-01-28 14:20</td>
                                    </tr>
                                    <tr>
                                        <td>Stock Updated</td>
                                        <td>Product E</td>
                                        <td>+75</td>
                                        <td><span class="badge badge-info">Updated</span></td>
                                        <td>2024-01-28 11:00</td>
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
        // Check authentication
        if (!sessionStorage.getItem('isLoggedIn')) {
            window.location.href = 'login.php';
        }

        // Load user data
        const userData = JSON.parse(sessionStorage.getItem('userData') || '{}');
        const userOrg = sessionStorage.getItem('userOrganization') || 'Organization';
        const userEmail = sessionStorage.getItem('userEmail') || '';
        
        let fullName = 'User';
        let initials = 'U';
        
        if (userData.firstName && userData.lastName) {
            fullName = `${userData.firstName} ${userData.lastName}`;
            initials = userData.firstName.charAt(0) + userData.lastName.charAt(0);
        } else if (userEmail) {
            fullName = userEmail.split('@')[0];
            initials = fullName.substring(0, 2).toUpperCase();
        }

        // Update all user displays
        document.getElementById('userName').textContent = fullName;
        document.getElementById('userOrg').textContent = userOrg;
        document.getElementById('userAvatar').textContent = initials;
        document.getElementById('headerName').textContent = fullName;
        document.getElementById('headerOrg').textContent = userOrg;
        document.getElementById('headerAvatar').textContent = initials;

        // Dropdown toggle function
        function toggleDropdown(element) {
            const content = element.nextElementSibling;
            const icon = element.querySelector('.nav-dropdown-icon');
            
            if (content && content.classList.contains('nav-dropdown-content')) {
                const isOpen = content.classList.contains('show');
                
                if (isOpen) {
                    content.classList.remove('show');
                    icon.style.transform = 'rotate(0deg)';
                } else {
                    content.classList.add('show');
                    icon.style.transform = 'rotate(180deg)';
                }
            }
        }

        // Logout function
        function handleLogout() {
            if (confirm('Are you sure you want to logout?')) {
                sessionStorage.clear();
                window.location.href = 'login.php';
            }
        }

        // Quick Add Toggle
        function toggleQuickAdd() {
            const dropdown = document.getElementById('quickAddDropdown');
            const notifPanel = document.getElementById('notificationsPanel');
            
            // Close notifications if open
            if (notifPanel.style.display === 'block') {
                notifPanel.style.display = 'none';
            }
            
            // Toggle quick add
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        }

        // Notifications Toggle
        function toggleNotifications() {
            const notifPanel = document.getElementById('notificationsPanel');
            const dropdown = document.getElementById('quickAddDropdown');
            
            // Close quick add if open
            if (dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            }
            
            // Toggle notifications
            notifPanel.style.display = notifPanel.style.display === 'block' ? 'none' : 'block';
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const quickAddBtn = document.getElementById('quickAddBtn');
            const quickAddDropdown = document.getElementById('quickAddDropdown');
            const notifPanel = document.getElementById('notificationsPanel');
            
            // Close quick add if clicking outside
            if (!quickAddBtn.contains(event.target) && !quickAddDropdown.contains(event.target)) {
                quickAddDropdown.style.display = 'none';
            }
            
            // Close notifications if clicking outside
            if (!event.target.closest('[onclick="toggleNotifications()"]') && !notifPanel.contains(event.target)) {
                notifPanel.style.display = 'none';
            }
        });


        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Sales',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    borderColor: '#0d9488',
                    backgroundColor: 'rgba(13, 148, 136, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Stock Chart
        const stockCtx = document.getElementById('stockChart').getContext('2d');
        new Chart(stockCtx, {
            type: 'doughnut',
            data: {
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: [
                        '#10b981',
                        '#f59e0b',
                        '#ef4444'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>
