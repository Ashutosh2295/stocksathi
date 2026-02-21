-- ============================================
-- STOCKSATHI DEMO DATA
-- Complete Test Data for All Modules
-- ============================================

-- Clear existing demo data (optional - comment out if you want to keep existing data)
-- TRUNCATE TABLE products;
-- TRUNCATE TABLE categories;
-- TRUNCATE TABLE brands;
-- TRUNCATE TABLE customers;
-- TRUNCATE TABLE suppliers;
-- TRUNCATE TABLE expenses;
-- TRUNCATE TABLE invoices;
-- TRUNCATE TABLE invoice_items;

-- ============================================
-- 1. CATEGORIES
-- ============================================
INSERT INTO categories (name, description, status, created_at) VALUES
('Electronics', 'Electronic devices and accessories', 'active', NOW()),
('Clothing', 'Apparel and fashion items', 'active', NOW()),
('Food & Beverages', 'Food items and drinks', 'active', NOW()),
('Home & Kitchen', 'Home appliances and kitchen items', 'active', NOW()),
('Sports & Fitness', 'Sports equipment and fitness gear', 'active', NOW()),
('Books & Stationery', 'Books, notebooks, and office supplies', 'active', NOW()),
('Toys & Games', 'Children toys and board games', 'active', NOW()),
('Beauty & Personal Care', 'Cosmetics and personal care products', 'active', NOW());

-- ============================================
-- 2. BRANDS
-- ============================================
INSERT INTO brands (name, description, status, created_at) VALUES
('Samsung', 'Leading electronics manufacturer', 'active', NOW()),
('Apple', 'Premium technology products', 'active', NOW()),
('Nike', 'Sports apparel and footwear', 'active', NOW()),
('Adidas', 'Athletic wear and accessories', 'active', NOW()),
('Sony', 'Electronics and entertainment', 'active', NOW()),
('LG', 'Home appliances and electronics', 'active', NOW()),
('Puma', 'Sports and lifestyle brand', 'active', NOW()),
('Philips', 'Health technology and appliances', 'active', NOW()),
('Nestle', 'Food and beverage products', 'active', NOW()),
('Unilever', 'Consumer goods', 'active', NOW());

-- ============================================
-- 3. PRODUCTS
-- ============================================
INSERT INTO products (name, sku, category_id, brand_id, description, unit, purchase_price, selling_price, mrp, tax_rate, min_stock_level, max_stock_level, reorder_point, current_stock, status, created_at) VALUES
-- Electronics
('Samsung Galaxy S23', 'ELEC-SAM-001', 1, 1, 'Latest Samsung flagship smartphone with 256GB storage', 'pcs', 45000.00, 55000.00, 59999.00, 18.00, 5, 50, 10, 25, 'active', NOW()),
('Apple iPhone 14', 'ELEC-APP-001', 1, 2, 'iPhone 14 128GB - Multiple colors available', 'pcs', 60000.00, 72000.00, 79900.00, 18.00, 3, 30, 8, 15, 'active', NOW()),
('Sony WH-1000XM5 Headphones', 'ELEC-SON-001', 1, 5, 'Premium noise-cancelling wireless headphones', 'pcs', 18000.00, 24000.00, 29990.00, 18.00, 10, 100, 20, 45, 'active', NOW()),
('LG 55" 4K Smart TV', 'ELEC-LG-001', 1, 6, '55-inch 4K UHD Smart LED TV', 'pcs', 35000.00, 45000.00, 49999.00, 18.00, 2, 20, 5, 8, 'active', NOW()),

-- Clothing
('Nike Air Max Shoes', 'CLO-NIK-001', 2, 3, 'Premium running shoes - Size 8-11', 'pairs', 3500.00, 5500.00, 6999.00, 12.00, 20, 200, 40, 85, 'active', NOW()),
('Adidas Track Pants', 'CLO-ADI-001', 2, 4, 'Comfortable track pants - Multiple sizes', 'pcs', 800.00, 1500.00, 1999.00, 12.00, 30, 300, 60, 120, 'active', NOW()),
('Puma T-Shirt', 'CLO-PUM-001', 2, 7, 'Cotton sports t-shirt - Various colors', 'pcs', 400.00, 800.00, 999.00, 12.00, 50, 500, 100, 250, 'active', NOW()),

-- Food & Beverages
('Nestle Coffee', 'FOOD-NES-001', 3, 9, 'Instant coffee powder 200g', 'pcs', 180.00, 250.00, 299.00, 5.00, 100, 1000, 200, 450, 'active', NOW()),
('Nestle Maggi Noodles', 'FOOD-NES-002', 3, 9, 'Instant noodles pack of 12', 'pcs', 120.00, 180.00, 199.00, 5.00, 200, 2000, 400, 850, 'active', NOW()),

-- Home & Kitchen
('Philips Air Fryer', 'HOME-PHI-001', 4, 8, 'Digital air fryer 4.1L capacity', 'pcs', 5500.00, 7500.00, 8999.00, 18.00, 5, 50, 10, 22, 'active', NOW()),
('LG Microwave Oven', 'HOME-LG-002', 4, 6, '20L solo microwave oven', 'pcs', 4000.00, 5500.00, 6499.00, 18.00, 5, 40, 10, 18, 'active', NOW()),

-- Sports & Fitness
('Nike Gym Bag', 'SPORT-NIK-002', 5, 3, 'Durable gym duffle bag', 'pcs', 800.00, 1500.00, 1999.00, 12.00, 20, 150, 30, 65, 'active', NOW()),
('Adidas Football', 'SPORT-ADI-002', 5, 4, 'Professional football size 5', 'pcs', 600.00, 1200.00, 1499.00, 12.00, 30, 200, 50, 95, 'active', NOW()),

-- Books & Stationery
('Notebook A4 Size', 'BOOK-001', 6, NULL, '200 pages ruled notebook', 'pcs', 40.00, 80.00, 99.00, 12.00, 200, 2000, 400, 850, 'active', NOW()),
('Pen Set (Pack of 10)', 'BOOK-002', 6, NULL, 'Blue ballpoint pens', 'pcs', 50.00, 100.00, 120.00, 12.00, 150, 1500, 300, 650, 'active', NOW()),

-- Beauty & Personal Care
('Unilever Dove Soap', 'BEAUTY-UNI-001', 8, 10, 'Moisturizing beauty soap 125g', 'pcs', 30.00, 55.00, 65.00, 18.00, 300, 3000, 600, 1200, 'active', NOW()),
('Unilever Sunsilk Shampoo', 'BEAUTY-UNI-002', 8, 10, 'Hair shampoo 340ml', 'pcs', 120.00, 200.00, 249.00, 18.00, 100, 1000, 200, 450, 'active', NOW());

-- ============================================
-- 4. CUSTOMERS
-- ============================================
INSERT INTO customers (name, email, phone, company, gstin, address, city, state, pincode, credit_limit, credit_days, status, created_at) VALUES
('Rajesh Kumar', 'rajesh.kumar@email.com', '9876543210', 'Kumar Enterprises', '27AABCU9603R1ZM', '123 MG Road', 'Mumbai', 'Maharashtra', '400001', 50000.00, 30, 'active', NOW()),
('Priya Sharma', 'priya.sharma@email.com', '9876543211', 'Sharma Traders', '09AABCU9603R1ZN', '456 Park Street', 'Delhi', 'Delhi', '110001', 75000.00, 45, 'active', NOW()),
('Amit Patel', 'amit.patel@email.com', '9876543212', 'Patel & Sons', '24AABCU9603R1ZO', '789 Brigade Road', 'Bangalore', 'Karnataka', '560001', 100000.00, 60, 'active', NOW()),
('Sneha Reddy', 'sneha.reddy@email.com', '9876543213', 'Reddy Electronics', '36AABCU9603R1ZP', '321 Anna Salai', 'Chennai', 'Tamil Nadu', '600001', 60000.00, 30, 'active', NOW()),
('Vikram Singh', 'vikram.singh@email.com', '9876543214', 'Singh Trading Co', '23AABCU9603R1ZQ', '654 Civil Lines', 'Jaipur', 'Rajasthan', '302001', 80000.00, 45, 'active', NOW()),
('Anita Desai', 'anita.desai@email.com', '9876543215', NULL, NULL, '987 FC Road', 'Pune', 'Maharashtra', '411001', 25000.00, 15, 'active', NOW()),
('Rahul Verma', 'rahul.verma@email.com', '9876543216', 'Verma Retail', '07AABCU9603R1ZR', '147 Park Road', 'Lucknow', 'Uttar Pradesh', '226001', 90000.00, 60, 'active', NOW()),
('Kavita Nair', 'kavita.nair@email.com', '9876543217', NULL, NULL, '258 MG Road', 'Kochi', 'Kerala', '682001', 30000.00, 20, 'active', NOW()),
('Suresh Iyer', 'suresh.iyer@email.com', '9876543218', 'Iyer Stores', '33AABCU9603R1ZS', '369 Commercial Street', 'Hyderabad', 'Telangana', '500001', 70000.00, 45, 'active', NOW()),
('Meena Gupta', 'meena.gupta@email.com', '9876543219', 'Gupta Enterprises', '19AABCU9603R1ZT', '741 Nehru Place', 'Kolkata', 'West Bengal', '700001', 55000.00, 30, 'active', NOW());

-- ============================================
-- 5. SUPPLIERS
-- ============================================
INSERT INTO suppliers (name, email, phone, company, gstin, address, city, state, pincode, payment_terms, status, created_at) VALUES
('Tech Distributors Pvt Ltd', 'sales@techdist.com', '9123456780', 'Tech Distributors', '27AABCT1234A1Z5', '12 Industrial Area', 'Mumbai', 'Maharashtra', '400050', 'Net 30', 'active', NOW()),
('Fashion Wholesale Hub', 'orders@fashionhub.com', '9123456781', 'Fashion Hub', '09AABCT1234A1Z6', '45 Garment District', 'Delhi', 'Delhi', '110020', 'Net 45', 'active', NOW()),
('Food Suppliers Co', 'info@foodsupply.com', '9123456782', 'Food Supply', '24AABCT1234A1Z7', '78 Market Yard', 'Bangalore', 'Karnataka', '560040', 'Net 30', 'active', NOW()),
('Home Appliances Direct', 'contact@homedirect.com', '9123456783', 'Home Direct', '36AABCT1234A1Z8', '23 Electronics Hub', 'Chennai', 'Tamil Nadu', '600030', 'Net 60', 'active', NOW()),
('Sports Gear Wholesale', 'sales@sportsgear.com', '9123456784', 'Sports Gear', '23AABCT1234A1Z9', '56 Stadium Road', 'Jaipur', 'Rajasthan', '302020', 'Net 45', 'active', NOW());

-- ============================================
-- 6. EXPENSES
-- ============================================
INSERT INTO expenses (expense_date, category, amount, payment_mode, reference_no, description, created_by, created_at) VALUES
(CURDATE(), 'Rent', 25000.00, 'Bank Transfer', 'RENT-JAN-2026', 'Monthly office rent for January 2026', 1, NOW()),
(CURDATE() - INTERVAL 1 DAY, 'Electricity', 8500.00, 'Cash', 'ELEC-001', 'Electricity bill payment', 1, NOW()),
(CURDATE() - INTERVAL 2 DAY, 'Salaries', 150000.00, 'Bank Transfer', 'SAL-JAN-2026', 'Staff salaries for January 2026', 1, NOW()),
(CURDATE() - INTERVAL 3 DAY, 'Transportation', 3500.00, 'Cash', 'TRANS-001', 'Delivery vehicle fuel', 1, NOW()),
(CURDATE() - INTERVAL 4 DAY, 'Office Supplies', 2800.00, 'UPI', 'OFF-SUP-001', 'Stationery and office supplies', 1, NOW()),
(CURDATE() - INTERVAL 5 DAY, 'Internet', 1500.00, 'Bank Transfer', 'NET-JAN-2026', 'Monthly internet charges', 1, NOW()),
(CURDATE() - INTERVAL 6 DAY, 'Maintenance', 5000.00, 'Cash', 'MAINT-001', 'Office maintenance and repairs', 1, NOW()),
(CURDATE() - INTERVAL 7 DAY, 'Marketing', 12000.00, 'UPI', 'MARK-001', 'Social media advertising', 1, NOW());

-- ============================================
-- 7. STORES
-- ============================================
INSERT INTO stores (name, code, address, city, state, pincode, phone, manager_name, status, created_at) VALUES
('Main Store - Mumbai', 'STORE-MUM-001', '123 Main Street, Andheri', 'Mumbai', 'Maharashtra', '400058', '9876543220', 'Ramesh Patil', 'active', NOW()),
('Delhi Branch', 'STORE-DEL-001', '456 Connaught Place', 'Delhi', 'Delhi', '110001', '9876543221', 'Sanjay Gupta', 'active', NOW()),
('Bangalore Outlet', 'STORE-BLR-001', '789 Indiranagar', 'Bangalore', 'Karnataka', '560038', '9876543222', 'Karthik Rao', 'active', NOW());

-- ============================================
-- 8. WAREHOUSES
-- ============================================
INSERT INTO warehouses (name, code, address, city, state, pincode, phone, manager_name, capacity, status, created_at) VALUES
('Central Warehouse', 'WH-CENTRAL-001', 'Plot 45, Industrial Area', 'Mumbai', 'Maharashtra', '400070', '9876543223', 'Vijay Kumar', 10000.00, 'active', NOW()),
('North Warehouse', 'WH-NORTH-001', 'Sector 18, Industrial Zone', 'Delhi', 'Delhi', '110040', '9876543224', 'Manoj Sharma', 8000.00, 'active', NOW());

-- ============================================
-- 9. DEPARTMENTS
-- ============================================
INSERT INTO departments (name, code, description, status, created_at) VALUES
('Sales', 'DEPT-SALES', 'Sales and customer relations department', 'active', NOW()),
('Accounts', 'DEPT-ACC', 'Accounting and finance department', 'active', NOW()),
('Warehouse', 'DEPT-WH', 'Warehouse and inventory management', 'active', NOW()),
('IT', 'DEPT-IT', 'Information technology department', 'active', NOW()),
('HR', 'DEPT-HR', 'Human resources department', 'active', NOW());

-- ============================================
-- 10. EMPLOYEES
-- ============================================
INSERT INTO employees (employee_code, first_name, last_name, email, phone, department_id, designation, date_of_joining, salary, status, created_at) VALUES
('EMP-001', 'Ramesh', 'Patil', 'ramesh.patil@stocksathi.com', '9876543225', 1, 'Sales Manager', '2024-01-15', 45000.00, 'active', NOW()),
('EMP-002', 'Sunita', 'Joshi', 'sunita.joshi@stocksathi.com', '9876543226', 1, 'Sales Executive', '2024-02-01', 30000.00, 'active', NOW()),
('EMP-003', 'Prakash', 'Mehta', 'prakash.mehta@stocksathi.com', '9876543227', 2, 'Accountant', '2024-01-20', 40000.00, 'active', NOW()),
('EMP-004', 'Anjali', 'Deshmukh', 'anjali.deshmukh@stocksathi.com', '9876543228', 3, 'Warehouse Supervisor', '2024-03-01', 35000.00, 'active', NOW()),
('EMP-005', 'Kiran', 'Naik', 'kiran.naik@stocksathi.com', '9876543229', 4, 'IT Support', '2024-02-15', 38000.00, 'active', NOW());

-- ============================================
-- 11. SAMPLE INVOICES (Last 10 days)
-- ============================================
-- Invoice 1
INSERT INTO invoices (invoice_no, invoice_date, customer_id, subtotal, tax_amount, discount_amount, total_amount, payment_status, payment_mode, notes, created_by, created_at) VALUES
('INV-2026-001', CURDATE() - INTERVAL 1 DAY, 1, 55000.00, 9900.00, 0.00, 64900.00, 'paid', 'UPI', 'Bulk order - Samsung phones', 1, NOW() - INTERVAL 1 DAY);

INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, tax_rate, tax_amount, total_amount) VALUES
(LAST_INSERT_ID(), 1, 1, 55000.00, 18.00, 9900.00, 64900.00);

-- Invoice 2
INSERT INTO invoices (invoice_no, invoice_date, customer_id, subtotal, tax_amount, discount_amount, total_amount, payment_status, payment_mode, notes, created_by, created_at) VALUES
('INV-2026-002', CURDATE() - INTERVAL 2 DAY, 2, 24000.00, 4320.00, 1000.00, 27320.00, 'paid', 'Cash', 'Premium headphones', 1, NOW() - INTERVAL 2 DAY);

INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, tax_rate, tax_amount, total_amount) VALUES
(LAST_INSERT_ID(), 3, 1, 24000.00, 18.00, 4320.00, 28320.00);

-- Invoice 3
INSERT INTO invoices (invoice_no, invoice_date, customer_id, subtotal, tax_amount, discount_amount, total_amount, payment_status, payment_mode, notes, created_by, created_at) VALUES
('INV-2026-003', CURDATE() - INTERVAL 3 DAY, 3, 11000.00, 1320.00, 0.00, 12320.00, 'partial', 'Bank Transfer', 'Clothing items', 1, NOW() - INTERVAL 3 DAY);

INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, tax_rate, tax_amount, total_amount) VALUES
(LAST_INSERT_ID(), 5, 2, 5500.00, 12.00, 1320.00, 12320.00);

-- Invoice 4
INSERT INTO invoices (invoice_no, invoice_date, customer_id, subtotal, tax_amount, discount_amount, total_amount, payment_status, payment_mode, notes, created_by, created_at) VALUES
('INV-2026-004', CURDATE() - INTERVAL 4 DAY, 4, 7500.00, 1350.00, 500.00, 8350.00, 'paid', 'UPI', 'Air fryer purchase', 1, NOW() - INTERVAL 4 DAY);

INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, tax_rate, tax_amount, total_amount) VALUES
(LAST_INSERT_ID(), 10, 1, 7500.00, 18.00, 1350.00, 8850.00);

-- Invoice 5
INSERT INTO invoices (invoice_no, invoice_date, customer_id, subtotal, tax_amount, discount_amount, total_amount, payment_status, payment_mode, notes, created_by, created_at) VALUES
('INV-2026-005', CURDATE() - INTERVAL 5 DAY, 5, 4500.00, 540.00, 0.00, 5040.00, 'pending', NULL, 'Track pants bulk order', 1, NOW() - INTERVAL 5 DAY);

INSERT INTO invoice_items (invoice_id, product_id, quantity, unit_price, tax_rate, tax_amount, total_amount) VALUES
(LAST_INSERT_ID(), 6, 3, 1500.00, 12.00, 540.00, 5040.00);

-- ============================================
-- 12. QUOTATIONS
-- ============================================
INSERT INTO quotations (quotation_no, quotation_date, customer_id, valid_until, subtotal, tax_amount, discount_amount, total_amount, status, notes, created_by, created_at) VALUES
('QUO-2026-001', CURDATE(), 6, CURDATE() + INTERVAL 15 DAY, 144000.00, 25920.00, 5000.00, 164920.00, 'pending', 'Bulk order quotation for iPhones', 1, NOW()),
('QUO-2026-002', CURDATE() - INTERVAL 1 DAY, 7, CURDATE() + INTERVAL 14 DAY, 90000.00, 16200.00, 0.00, 106200.00, 'sent', 'TV quotation for office setup', 1, NOW() - INTERVAL 1 DAY);

-- ============================================
-- 13. PAYMENT MODES (if table exists)
-- ============================================
INSERT INTO payment_modes (name, description, is_active, created_at) VALUES
('Cash', 'Cash payment', 1, NOW()),
('UPI', 'UPI payment (GPay, PhonePe, etc)', 1, NOW()),
('Bank Transfer', 'Direct bank transfer / NEFT / RTGS', 1, NOW()),
('Credit Card', 'Credit card payment', 1, NOW()),
('Debit Card', 'Debit card payment', 1, NOW()),
('Cheque', 'Cheque payment', 1, NOW())
ON DUPLICATE KEY UPDATE name=name;

-- ============================================
-- SUMMARY
-- ============================================
-- This demo data includes:
-- ✅ 8 Categories
-- ✅ 10 Brands
-- ✅ 17 Products (across all categories)
-- ✅ 10 Customers (with GST and credit details)
-- ✅ 5 Suppliers
-- ✅ 8 Expense entries
-- ✅ 3 Stores
-- ✅ 2 Warehouses
-- ✅ 5 Departments
-- ✅ 5 Employees
-- ✅ 5 Sample Invoices (with items)
-- ✅ 2 Quotations
-- ✅ 6 Payment Modes
--
-- Total realistic test data for comprehensive testing!
-- ============================================

SELECT 'Demo data inserted successfully!' AS Status;
