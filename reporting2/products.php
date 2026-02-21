<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Reporting System</title>
    <link rel="stylesheet" href="css/design-system.css">
    <link rel="stylesheet" href="css/components.css">
    <link rel="stylesheet" href="css/layout.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        .search-bar {
            display: flex;
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }

        .search-input {
            flex: 1;
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

        .action-btn.edit:hover {
            background-color: var(--color-primary-lighter);
            color: var(--color-primary);
        }

        .action-btn.delete:hover {
            background-color: #fee2e2;
            color: var(--color-danger);
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
                    <a href="dashboard.php" class="nav-link">
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
                    <div class="nav-dropdown-content show">
                        <a href="products.php" class="nav-link nav-sub-item active">
                            <i class="fas fa-box"></i>
                            Products
                        </a>
                        <a href="categories.php" class="nav-link nav-sub-item">
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
                        <a href="stock-in.php" class="nav-link nav-sub-item">
                            <i class="fas fa-arrow-down"></i>
                            Stock In
                        </a>
                        <a href="stock-out.php" class="nav-link nav-sub-item">
                            <i class="fas fa-arrow-up"></i>
                            Stock Out
                        </a>
                        <a href="stock-adjustments.php" class="nav-link nav-sub-item">
                            <i class="fas fa-adjust"></i>
                            Adjustments
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
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                    <h1 class="page-title">Products</h1>
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
                            <h2 class="page-header-title">Product Management</h2>
                            <p class="page-header-description">Manage your product inventory</p>
                        </div>
                        <div class="page-header-actions">
                            <button class="btn btn-primary" onclick="openAddModal()">
                                <i class="fas fa-plus"></i>
                                Add Product
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-bar">
                    <input 
                        type="text" 
                        class="form-control search-input" 
                        placeholder="Search products..."
                        id="searchInput"
                        oninput="filterProducts()"
                    >
                    <select class="form-control" id="categoryFilter" onchange="filterProducts()">
                        <option value="">All Categories</option>
                        <option value="Electronics">Electronics</option>
                        <option value="Clothing">Clothing</option>
                        <option value="Food">Food</option>
                        <option value="Books">Books</option>
                    </select>
                </div>

                <!-- Products Table -->
                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product ID</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="productsTable">
                                    <!-- Products will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div class="modal" id="productModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Add Product</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" id="productId">
                    
                    <div class="form-group">
                        <label class="form-label" for="productName">Product Name</label>
                        <input type="text" id="productName" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="productCategory">Category</label>
                        <select id="productCategory" class="form-control" required>
                            <option value="">Select Category</option>
                            <option value="Electronics">Electronics</option>
                            <option value="Clothing">Clothing</option>
                            <option value="Food">Food</option>
                            <option value="Books">Books</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="productPrice">Price</label>
                        <input type="number" id="productPrice" class="form-control" step="0.01" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="productStock">Stock Quantity</label>
                        <input type="number" id="productStock" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="productDescription">Description</label>
                        <textarea id="productDescription" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ghost" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="saveProduct()">Save Product</button>
            </div>
        </div>
    </div>

    <script>
        // Check authentication
        if (!sessionStorage.getItem('isLoggedIn')) {
            window.location.href = 'login.php';
        }

        // Sample products data
        let products = [
            { id: 'P001', name: 'Laptop Pro 15', category: 'Electronics', price: 1299.99, stock: 45, status: 'In Stock' },
            { id: 'P002', name: 'Wireless Mouse', category: 'Electronics', price: 29.99, stock: 150, status: 'In Stock' },
            { id: 'P003', name: 'Cotton T-Shirt', category: 'Clothing', price: 19.99, stock: 8, status: 'Low Stock' },
            { id: 'P004', name: 'Office Chair', category: 'Furniture', price: 249.99, stock: 0, status: 'Out of Stock' },
            { id: 'P005', name: 'Coffee Beans 1kg', category: 'Food', price: 15.99, stock: 200, status: 'In Stock' },
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

        function renderProducts(productsToRender = products) {
            const tbody = document.getElementById('productsTable');
            tbody.innerHTML = '';

            productsToRender.forEach(product => {
                const statusClass = product.stock > 20 ? 'success' : product.stock > 0 ? 'warning' : 'danger';
                const row = `
                    <tr>
                        <td><strong>${product.id}</strong></td>
                        <td>${product.name}</td>
                        <td>${product.category}</td>
                        <td>$${product.price.toFixed(2)}</td>
                        <td>${product.stock}</td>
                        <td><span class="badge badge-${statusClass}">${product.status}</span></td>
                        <td>
                            <div class="action-buttons">
                                <button class="action-btn edit" onclick="editProduct('${product.id}')" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="action-btn delete" onclick="deleteProduct('${product.id}')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        function filterProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const category = document.getElementById('categoryFilter').value;

            const filtered = products.filter(product => {
                const matchesSearch = product.name.toLowerCase().includes(searchTerm) || 
                                    product.id.toLowerCase().includes(searchTerm);
                const matchesCategory = !category || product.category === category;
                return matchesSearch && matchesCategory;
            });

            renderProducts(filtered);
        }

        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add Product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productModal').classList.add('active');
        }

        function editProduct(id) {
            const product = products.find(p => p.id === id);
            if (product) {
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('productId').value = product.id;
                document.getElementById('productName').value = product.name;
                document.getElementById('productCategory').value = product.category;
                document.getElementById('productPrice').value = product.price;
                document.getElementById('productStock').value = product.stock;
                document.getElementById('productModal').classList.add('active');
            }
        }

        function closeModal() {
            document.getElementById('productModal').classList.remove('active');
        }

        function saveProduct() {
            const id = document.getElementById('productId').value;
            const name = document.getElementById('productName').value;
            const category = document.getElementById('productCategory').value;
            const price = parseFloat(document.getElementById('productPrice').value);
            const stock = parseInt(document.getElementById('productStock').value);

            let status = 'In Stock';
            if (stock === 0) status = 'Out of Stock';
            else if (stock <= 20) status = 'Low Stock';

            if (id) {
                // Edit existing product
                const index = products.findIndex(p => p.id === id);
                if (index !== -1) {
                    products[index] = { id, name, category, price, stock, status };
                }
            } else {
                // Add new product
                const newId = 'P' + String(products.length + 1).padStart(3, '0');
                products.push({ id: newId, name, category, price, stock, status });
            }

            closeModal();
            renderProducts();
        }

        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                products = products.filter(p => p.id !== id);
                renderProducts();
            }
        }

        // Initial render
        renderProducts();

        // Close modal on outside click
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

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
    </script>
</body>
</html>
