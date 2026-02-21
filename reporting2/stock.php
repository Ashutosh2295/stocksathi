<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Management - Reporting System</title>
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background-color: var(--bg-card);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            padding: var(--space-6);
            border-bottom: 1px solid var(--border-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-title {
            font-size: var(--font-size-xl);
            font-weight: 600;
            margin: 0;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
            transition: all var(--transition-base);
        }

        .modal-close:hover {
            background-color: var(--color-gray-100);
            color: var(--text-primary);
        }

        .modal-body {
            padding: var(--space-6);
        }

        .modal-footer {
            padding: var(--space-6);
            border-top: 1px solid var(--border-light);
            display: flex;
            gap: var(--space-3);
            justify-content: flex-end;
        }

        .filter-bar {
            display: flex;
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }

        .filter-input {
            flex: 1;
        }

        .stock-level-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: var(--space-2);
        }

        .stock-level-indicator.high {
            background-color: var(--color-success);
        }

        .stock-level-indicator.medium {
            background-color: var(--color-warning);
        }

        .stock-level-indicator.low {
            background-color: var(--color-danger);
        }

        .action-buttons {
            display: flex;
            gap: var(--space-2);
        }

        .action-btn {
            padding: var(--space-2);
            border: none;
            background: none;
            cursor: pointer;
            color: var(--text-muted);
            border-radius: var(--radius-md);
            transition: all var(--transition-base);
        }

        .action-btn:hover {
            background-color: var(--color-gray-100);
            color: var(--text-primary);
        }

        .action-btn.add:hover {
            background-color: #d1fae5;
            color: var(--color-success);
        }

        .action-btn.remove:hover {
            background-color: #fee2e2;
            color: var(--color-danger);
        }

        .action-btn.history:hover {
            background-color: var(--color-primary-lighter);
            color: var(--color-primary);
        }

        .stock-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--space-4);
            margin-bottom: var(--space-6);
        }

        .radio-group {
            display: flex;
            gap: var(--space-4);
            margin-bottom: var(--space-4);
        }

        .radio-label {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            cursor: pointer;
            color: var(--text-secondary);
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
                    <a href="stock.php" class="nav-link active">
                        <i class="fas fa-warehouse"></i>
                        Stock Overview
                    </a>
                    <a href="stock-in.php" class="nav-link">
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
                    <h1 class="page-title">Stock Management</h1>
                </div>
                <div class="header-right">
                    <div class="user-menu">
                        <div class="user-avatar">JD</div>
                        <div class="user-info">
                            <div class="user-name" id="userName">John Doe</div>
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
                            <h2 class="page-header-title">Stock Overview</h2>
                            <p class="page-header-description">Monitor and manage inventory levels</p>
                        </div>
                    </div>
                </div>

                <!-- Stock Stats -->
                <div class="stock-stats">
                    <div class="stat-card">
                        <div class="stat-card-icon success">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-card-value" id="inStockCount">0</div>
                        <div class="stat-card-label">In Stock</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-icon warning">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-card-value" id="lowStockCount">0</div>
                        <div class="stat-card-label">Low Stock</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-card-icon danger">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-card-value" id="outOfStockCount">0</div>
                        <div class="stat-card-label">Out of Stock</div>
                    </div>
                </div>

                <!-- Filter Bar -->
                <div class="filter-bar">
                    <input 
                        type="text" 
                        class="form-control filter-input" 
                        placeholder="Search stock items..."
                        id="searchInput"
                        oninput="filterStock()"
                    >
                    <select class="form-control" id="statusFilter" onchange="filterStock()">
                        <option value="">All Status</option>
                        <option value="In Stock">In Stock</option>
                        <option value="Low Stock">Low Stock</option>
                        <option value="Out of Stock">Out of Stock</option>
                    </select>
                </div>

                <!-- Stock Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Product Name</th>
                                        <th>Current Stock</th>
                                        <th>Min. Stock</th>
                                        <th>Max. Stock</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="stockTable">
                                    <!-- Stock items will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Stock Adjustment Modal -->
    <div class="modal" id="stockModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Adjust Stock</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="stockForm">
                    <input type="hidden" id="stockProductId">
                    
                    <div class="form-group">
                        <label class="form-label">Product</label>
                        <input type="text" id="stockProductName" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Current Stock</label>
                        <input type="number" id="currentStock" class="form-control" readonly>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Action Type</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input type="radio" name="actionType" value="add" checked>
                                <span>Add Stock</span>
                            </label>
                            <label class="radio-label">
                                <input type="radio" name="actionType" value="remove">
                                <span>Remove Stock</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="adjustmentQuantity">Quantity</label>
                        <input type="number" id="adjustmentQuantity" class="form-control" min="1" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="adjustmentReason">Reason</label>
                        <textarea id="adjustmentReason" class="form-control" rows="3" placeholder="Enter reason for adjustment"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveStockAdjustment()">Save Adjustment</button>
            </div>
        </div>
    </div>

    <script>
        // Check authentication
        if (!sessionStorage.getItem('isLoggedIn')) {
            window.location.href = 'login.php';
        }

        // Sample stock data
        let stockItems = [
            { id: 'P001', name: 'Laptop Pro 15', current: 45, min: 10, max: 100, status: 'In Stock', lastUpdated: '2024-01-29 08:30' },
            { id: 'P002', name: 'Wireless Mouse', current: 150, min: 50, max: 200, status: 'In Stock', lastUpdated: '2024-01-29 07:15' },
            { id: 'P003', name: 'Cotton T-Shirt', current: 8, min: 20, max: 150, status: 'Low Stock', lastUpdated: '2024-01-28 16:45' },
            { id: 'P004', name: 'Office Chair', current: 0, min: 5, max: 50, status: 'Out of Stock', lastUpdated: '2024-01-28 14:20' },
            { id: 'P005', name: 'Coffee Beans 1kg', current: 200, min: 50, max: 300, status: 'In Stock', lastUpdated: '2024-01-28 11:00' },
            { id: 'P006', name: 'Desk Lamp', current: 15, min: 20, max: 80, status: 'Low Stock', lastUpdated: '2024-01-27 09:30' },
        ];

        // Load user data
        const userData = JSON.parse(sessionStorage.getItem('userData') || '{}');
        if (userData.firstName && userData.lastName) {
            document.getElementById('userName').textContent = `${userData.firstName} ${userData.lastName}`;
            document.querySelector('.user-avatar').textContent = 
                userData.firstName.charAt(0) + userData.lastName.charAt(0);
        }

        function handleLogout() {
            sessionStorage.clear();
            window.location.href = 'login.php';
        }

        function updateStats() {
            const inStock = stockItems.filter(item => item.status === 'In Stock').length;
            const lowStock = stockItems.filter(item => item.status === 'Low Stock').length;
            const outOfStock = stockItems.filter(item => item.status === 'Out of Stock').length;

            document.getElementById('inStockCount').textContent = inStock;
            document.getElementById('lowStockCount').textContent = lowStock;
            document.getElementById('outOfStockCount').textContent = outOfStock;
        }

        function getStockLevel(item) {
            if (item.current === 0) return 'low';
            if (item.current < item.min) return 'medium';
            return 'high';
        }

        function renderStock(itemsToRender = stockItems) {
            const tbody = document.getElementById('stockTable');
            tbody.innerHTML = '';

            itemsToRender.forEach(item => {
                const statusClass = item.status === 'In Stock' ? 'success' : 
                                  item.status === 'Low Stock' ? 'warning' : 'danger';
                const levelClass = getStockLevel(item);
                
                const row = `
                    <tr>
                        <td><strong>${item.id}</strong></td>
                        <td>
                            <span class="stock-level-indicator ${levelClass}"></span>
                            ${item.name}
                        </td>
                        <td><strong>${item.current}</strong></td>
                        <td>${item.min}</td>
                        <td>${item.max}</td>
                        <td><span class="badge badge-${statusClass}">${item.status}</span></td>
                        <td>${item.lastUpdated}</td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn add" onclick="openStockModal('${item.id}', 'add')" title="Add Stock">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="action-btn remove" onclick="openStockModal('${item.id}', 'remove')" title="Remove Stock">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <button class="action-btn history" onclick="viewHistory('${item.id}')" title="View History">
                                    <i class="fas fa-history"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });

            updateStats();
        }

        function filterStock() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;

            const filtered = stockItems.filter(item => {
                const matchesSearch = item.name.toLowerCase().includes(searchTerm) || 
                                    item.id.toLowerCase().includes(searchTerm);
                const matchesStatus = !status || item.status === status;
                return matchesSearch && matchesStatus;
            });

            renderStock(filtered);
        }

        function openStockModal(productId, action) {
            const item = stockItems.find(i => i.id === productId);
            if (item) {
                document.getElementById('stockProductId').value = item.id;
                document.getElementById('stockProductName').value = item.name;
                document.getElementById('currentStock').value = item.current;
                document.getElementById('adjustmentQuantity').value = '';
                document.getElementById('adjustmentReason').value = '';
                
                const actionRadio = document.querySelector(`input[name="actionType"][value="${action}"]`);
                if (actionRadio) actionRadio.checked = true;
                
                document.getElementById('stockModal').classList.add('active');
            }
        }

        function closeModal() {
            document.getElementById('stockModal').classList.remove('active');
        }

        function saveStockAdjustment() {
            const productId = document.getElementById('stockProductId').value;
            const quantity = parseInt(document.getElementById('adjustmentQuantity').value);
            const actionType = document.querySelector('input[name="actionType"]:checked').value;
            const reason = document.getElementById('adjustmentReason').value;

            const itemIndex = stockItems.findIndex(i => i.id === productId);
            if (itemIndex !== -1) {
                const item = stockItems[itemIndex];
                
                if (actionType === 'add') {
                    item.current += quantity;
                } else {
                    item.current = Math.max(0, item.current - quantity);
                }

                // Update status
                if (item.current === 0) {
                    item.status = 'Out of Stock';
                } else if (item.current < item.min) {
                    item.status = 'Low Stock';
                } else {
                    item.status = 'In Stock';
                }

                // Update timestamp
                const now = new Date();
                item.lastUpdated = now.toISOString().slice(0, 16).replace('T', ' ');

                closeModal();
                renderStock();
                
                alert(`Stock ${actionType === 'add' ? 'added' : 'removed'} successfully!`);
            }
        }

        function viewHistory(productId) {
            const item = stockItems.find(i => i.id === productId);
            if (item) {
                alert(`Stock History for ${item.name}\n\nThis feature will show detailed stock movement history.`);
            }
        }

        // Initial render
        renderStock();

        // Close modal on outside click
        document.getElementById('stockModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
