<?php
require_once dirname(__DIR__) . '/_includes/database.php';
try {
    $db = Database::getInstance();
    
    // Get total active users
    $userStats = $db->queryOne("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $totalUsers = $userStats ? $userStats['count'] : 0;
    
    // Get total products (assets)
    $productStats = $db->queryOne("SELECT COUNT(*) as count FROM products");
    $totalProducts = $productStats ? $productStats['count'] : 0;
    
    // Get total customers
    $customerStats = $db->queryOne("SELECT COUNT(*) as count FROM customers");
    $totalCustomers = $customerStats ? $customerStats['count'] : 0;
    
} catch (Exception $e) {
    // Fallback numbers
    $totalUsers = 0;
    $totalProducts = 0;
    $totalCustomers = 0;
}
include 'header.php'; 
?>

<!-- Hero Section -->
<section id="home" class="hero-section">
    <div class="container">
        <div class="row align-items-center min-vh-100 py-5">
            <div class="col-lg-6 text-white">
                <h1 class="display-3 fw-bold mb-4 animate-fade-in">
                   Inventory Management With <span class="text-gradient">Stock Sathi</span>
                </h1>
                <p class="lead mb-4 text-white-75">
               StockSathi is a web-based Inventory Management System that helps businesses manage products, stock, sales, and finances efficiently through a secure admin panel. It automates daily operations to reduce manual work and improve accuracy.
                <div class="d-flex gap-3 mb-4">
                    <a href="#pricing" class="btn btn-light btn-lg rounded-pill px-5 shadow-lg">
                        <i class="bi bi-rocket-takeoff me-2"></i>Start Free Trial
                    </a>
                    <a href="#features" class="btn btn-outline-light btn-lg rounded-pill px-5">
                        Learn More
                    </a>
                </div>
                <div class="d-flex gap-4 mt-4">
                    <div class="stat-item">
                        <h3 class="fw-bold mb-0"><?= number_format($totalUsers) ?>+</h3>
                        <p class="text-white-75 mb-0">Active Users</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="fw-bold mb-0"><?= number_format($totalProducts) ?>+</h3>
                        <p class="text-white-75 mb-0">Assets Tracked</p>
                    </div>
                    <div class="stat-item">
                        <h3 class="fw-bold mb-0"><?= number_format($totalCustomers) ?>+</h3>
                        <p class="text-white-75 mb-0">Happy Customers</p>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-image-wrapper">
                    <img src="images/landing_2.png" alt="Stock Trading Dashboard" class="img-fluid rounded-4 shadow-lg">
                </div>
            </div>
        </div>
    </div>
    <div class="hero-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
</section>

<!-- Features Section -->
<section id="features" class="py-5 bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Smart Inventory Management</h2>
            <p class="lead text-muted">Powerful features to help you make better Inventory Management </p>
        </div>
        <div class="row g-4">
            <!-- Feature 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-primary bg-gradient rounded-4 p-3 d-inline-block mb-3">
                            <i class="bi bi-people-fill text-white fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-3">User Management Module</h4>
                        <p class="text-muted mb-0">
                            Manages users with secure role-based access and permissions. Ensures smooth control over staff activities and system security.              
                          </p>
                    </div>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-success bg-gradient rounded-4 p-3 d-inline-block mb-3">
                            <i class="bi bi-box-seam-fill text-white fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Product Management Module</h4>
                        <p class="text-muted mb-0">
                            Easily add, update, and organize all products in one place.Keeps product details accurate and up to date.
                    </p>
                    </div>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-info bg-gradient rounded-4 p-3 d-inline-block mb-3">
                            <i class="bi bi-layers-fill text-white fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Stock Management Module</h4>
                        <p class="text-muted mb-0">
                            Tracks stock levels in real time to avoid shortages or overstocking.Helps maintain efficient inventory control.
                    </p>
                    </div>
                </div>
            </div>
             <!-- Feature 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-warning bg-gradient rounded-4 p-3 d-inline-block mb-3">
                            <i class="bi bi-receipt-cutoff text-white fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Sales & Billing Module</h4>
                        <p class="text-muted mb-0">
                            Creates quick invoices and records sales effortlessly. Improves billing accuracy and speeds up transactions.
                    </p>
                    </div>
                </div>
            </div>
             <!-- Feature 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-danger bg-gradient rounded-4 p-3 d-inline-block mb-3">
                            <i class="bi bi-person-lines-fill text-white fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Customer & Supplier Module</h4>
                        <p class="text-muted mb-0">
                            Stores and manages customer and supplier information centrally.Strengthens relationships and simplifies order management.        
                        </p>
                    </div>
                </div>
            </div>
             <!-- Feature 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-secondary bg-gradient rounded-4 p-3 d-inline-block mb-3">
                            <i class="bi bi-wallet-fill text-white fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Finance & Expense Module</h4>
                        <p class="text-muted mb-0">
                            Monitors income, expenses, and overall financial performance. Helps maintain better financial planning and control.         
                       </p>
                    </div>
                </div>
            </div>
             <!-- Feature 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-primary bg-gradient rounded-4 p-3 d-inline-block mb-3">
                            <i class="bi bi-bar-chart-fill text-white fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Reports Module</h4>
                        <p class="text-muted mb-0">
                            Generates clear and detailed business reports instantly.Supports smart decision-making with accurate data insights.      
                          </p>
                    </div>
                </div>
            </div>
            <!-- Feature 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-dark bg-gradient rounded-4 p-3 d-inline-block mb-3">
                            <i class="bi bi-gear-fill text-white fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Admin Settings Module</h4>
                        <p class="text-muted mb-0">
                            Customizes system settings and controls platform configurations.Ensures smooth and flexible system management.    
                        </p>
                    </div>
                </div>
            </div>
            <!-- Feature 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="feature-card card border-0 shadow-sm h-100 hover-lift">
                    <div class="card-body p-4">
                        <div class="feature-icon bg-info bg-gradient rounded-4 p-3 d-inline-block mb-3">
                            <i class="bi bi-buildings-fill text-white fs-2"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Multi-Branch & Multi-Store Support</h4>
                        <p class="text-muted mb-0">
                            Manage multiple branches and stores from a single dashboard.Get real-time visibility and control across all business locations.      
                          </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Collaboration Section -->
<section class="py-5 bg-white">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="collaboration-image">
                    <img src="images/landing_3.png" alt="Work Together" class="img-fluid rounded-4 shadow-lg">
                </div>
            </div>
            <div class="col-lg-6">
                <h2 class="display-5 fw-bold mb-4">Work Together</h2>
                <p class="lead text-muted mb-4">
                    Collaborate with your team and partners by sharing inventory insights, stock data, and business strategies.Build a smarter business ecosystem through real-time collaboration and secure data sharing.
                </p>
                <ul class="list-unstyled mb-4">
                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Share stock reports and product lists with team members</li>
                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Collaborate on sales and inventory planning</li>
                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Real-time updates and operational insights</li>
                    <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Private, secure, role-based access</li>
                </ul>
                <a href="#pricing" class="btn btn-primary btn-lg rounded-pill px-5">Try Now</a>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section id="pricing" class="py-5 bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Choose Your Plan</h2>
            <p class="lead text-muted">Flexible pricing for investors at every level</p>
        </div>
        <div class="row g-4 justify-content-center">
            <!-- Basic Plan -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="text-uppercase text-muted mb-3">Basic</h5>
                        <h2 class="fw-bold mb-4">Free<span class="fs-6 text-muted">/forever</span></h2>
                         <span class="fs-6 text-muted">Ideal for small shop and startups.</span>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Manage up to 50 products</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Basic stock & sales tracking</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Single store support</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Daily stock updates</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Email support</li>

                        </ul>
                        <br><br><br>
                        <a href="#" class="btn btn-outline-primary w-100 rounded-pill">Get Started</a>
                    </div>
                </div>
            </div>

            <!-- Pro Plan (Highlighted) -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card card border-0 shadow-lg h-100 border-primary position-relative">
                    <div class="position-absolute top-0 start-50 translate-middle">
                        <span class="badge bg-primary rounded-pill px-3 py-2">Most Popular</span>
                    </div>
                    <div class="card-body p-4 pt-5">
                        <h5 class="text-uppercase text-primary mb-3">Pro</h5>
                        <h2 class="fw-bold mb-4">₹499<span class="fs-6 text-muted">/month</span></h2>
                        <span class="fs-6 text-muted">Perfect for growing businesses.</span>

                        <ul class="list-unstyled mb-4">
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Unlimited products</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Advanced stock & sales analytics</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i>Multi-store / branch support</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Low-stock & sales alerts</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Invoice & billing management</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Priority support</li>
                        </ul>
                        <a href="#" class="btn btn-primary w-100 rounded-pill">Start Free Trial</a>
                    </div>
                </div>
            </div>

            <!-- Premium Plan -->
            <div class="col-lg-4 col-md-6">
                <div class="pricing-card card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h5 class="text-uppercase text-muted mb-3">Premium</h5>
                        <h2 class="fw-bold mb-4">₹999<span class="fs-6 text-muted">/month</span></h2>
                        <span class="fs-6 text-muted">Built for large and expanding enterprises.</span>
                        <ul class="list-unstyled mb-4">
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Everything in Pro</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Advanced business & profit reports </li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Expense & finance management</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Role-based user access</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> API integration support</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> Dedicated account manager</li>
                            <li class="mb-3"><i class="bi bi-check-circle-fill text-success me-2"></i> White-label & customization options</li>

                        </ul>
                        <a href="#" class="btn btn-outline-primary w-100 rounded-pill">Contact Sales</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- Testimonials Section -->
<section id="testimonials" class="py-5 bg-light">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">What Our Clients Say</h2>
            <p class="lead text-muted">See how StockSathi helps businesses simplify stock, sales, and operations.</p>
        </div>
        <div class="row g-4">
            <!-- Testimonial 1 -->
            <div class="col-lg-6">
                <div class="testimonial-card card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <p class="mb-4 fst-italic">
                            “StockSathi has completely transformed how we manage our inventory and sales. Real-time stock tracking and smart alerts help us avoid shortages and improve daily operations.”
                    </p>
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <span class="fw-bold">RK</span>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Rajesh Kumar</h6>
                                <small class="text-muted">Retail Investor, Mumbai</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Testimonial 2 -->
            <div class="col-lg-6">
                <div class="testimonial-card card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                            <i class="bi bi-star-fill text-warning"></i>
                        </div>
                        <p class="mb-4 fst-italic">
                            “As a growing business, we needed a reliable and easy-to-use system. StockSathi provides accurate reports, smooth billing, and powerful analytics that help us make better business decisions.”
                    </p>
                        <div class="d-flex align-items-center">
                            <div class="avatar bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                <span class="fw-bold">PS</span>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">Priya Sharma</h6>
                                <small class="text-muted">Day Trader, Delhi</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section py-5">
    <div class="container py-5">
        <div class="row justify-content-center text-center text-white">
            <div class="col-lg-8">
                <h2 class="display-4 fw-bold mb-4">Ready to Grow Your Business with StockSathi?</h2>
                <p class="lead mb-4">Join smart businesses managing stock the smarter way with StockSathi.</p>
                <a href="#pricing" class="btn btn-light btn-lg rounded-pill px-5 shadow-lg">
                    <i class="bi bi-rocket-takeoff me-2"></i>Start Free Trial Today
                </a>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>