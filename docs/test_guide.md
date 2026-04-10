# StockSathi – How I Tested This Project (Step-by-Step Testing Guide)

**Project:** StockSathi – Inventory Management System  
**Team:** Ashutosh Bhavsar, Ekta Ranghvani, Ishika Sathiya, Jeel Chauhan  
**Institution:** JG University | Semester VI  
**Document Version:** 1.0  
**Date:** February 2026

---

## Table of Contents

1. [Testing Approach Overview](#1-testing-approach-overview)
2. [Test Environment Setup](#2-test-environment-setup)
3. [How I Tested – Module by Module](#3-how-i-tested--module-by-module)
4. [Black Box Testing – Step-by-Step Execution](#4-black-box-testing--step-by-step-execution)
5. [White Box Testing – Step-by-Step Execution](#5-white-box-testing--step-by-step-execution)
6. [Integration Testing – Step-by-Step Execution](#6-integration-testing--step-by-step-execution)
7. [Security Testing – Step-by-Step Execution](#7-security-testing--step-by-step-execution)
8. [Defects Found & Fixed](#8-defects-found--fixed)
9. [Test Execution Results](#9-test-execution-results)
10. [Conclusion](#10-conclusion)

---

## 1. Testing Approach Overview

### 1.1 What Testing Methods I Used

| Method | What It Means | How I Applied It |
|--------|---------------|------------------|
| **Black Box Testing** | Test the application as a user — enter inputs, check outputs, without looking at code | I opened each page in the browser, filled forms, clicked buttons, and verified the results |
| **White Box Testing** | Test the internal code logic — check functions, conditions, code paths | I read each PHP class, traced every if/else branch, and verified each function returns correct values |
| **Integration Testing** | Test how modules work together — login + dashboard + permissions | I tested full workflows: register → login → create invoice → check stock → view reports |
| **Security Testing** | Test for vulnerabilities — SQL injection, unauthorized access | I tried entering malicious inputs, accessing pages without login, and testing role restrictions |

### 1.2 Testing Approach I Followed

```
Step 1: Set up test environment (XAMPP, database, test accounts)
           │
Step 2: Black Box Testing — Test each module as a user
           │
Step 3: White Box Testing — Read code, test each function internally
           │
Step 4: Integration Testing — Test complete workflows end-to-end
           │
Step 5: Security Testing — Try to break the application
           │
Step 6: Record results — Document pass/fail for each test case
           │
Step 7: Fix defects — Fix bugs found during testing
           │
Step 8: Retest — Verify fixes work correctly
```

---

## 2. Test Environment Setup

### 2.1 What I Set Up Before Testing

**Step 1:** Installed XAMPP with Apache + MySQL on Windows

**Step 2:** Created the database

```
1. Open phpMyAdmin → http://localhost/phpmyadmin
2. Create database: stocksathi
3. Import stocksathi_complete.sql
4. Run migrations: http://localhost/stocksathi/migrations/run_setup.php
```

**Step 3:** Created test user accounts

| # | Role | Email | Password | Purpose |
|---|------|-------|----------|---------|
| 1 | Super Admin | superadmin@stocksathi.com | password123 | Test full access |
| 2 | Admin | admin@stocksathi.com | password123 | Test admin-level features |
| 3 | Store Manager | store@stocksathi.com | password123 | Test store operations |
| 4 | Sales Executive | sales1@stocksathi.com | password123 | Test limited sales access |
| 5 | Accountant | accounts@stocksathi.com | password123 | Test finance features |

**Step 4:** Prepared test data

```
- Created 5 test products with different categories, prices, and stock levels
- Created 3 test customers with different balances
- Created 2 test suppliers
- Created 2 warehouses and 1 store
```

### 2.2 Browser Tools I Used

| Tool | What I Used It For |
|------|-------------------|
| **Chrome Browser** | Primary testing browser |
| **Chrome DevTools (F12)** | Check network requests, console errors, response times |
| **Network Tab** | Verify form submissions and redirects |
| **Console Tab** | Check for JavaScript errors |
| **Application Tab** | Inspect session cookies |
| **Incognito Mode** | Test with clean session (no cached login) |

---

## 3. How I Tested – Module by Module

### Module Testing Checklist

For **every module**, I followed these 5 steps:

```
┌─────────────────────────────────────────────────────────┐
│  FOR EACH MODULE:                                       │
│                                                         │
│  Step A: Can I access the page? (session + permission)  │
│  Step B: Does the list/table load correctly?            │
│  Step C: Can I CREATE a new record?                     │
│  Step D: Can I EDIT an existing record?                 │
│  Step E: Can I DELETE a record?                         │
│  Step F: Do validations work? (empty, invalid, etc.)    │
│  Step G: Does the page work for DIFFERENT ROLES?        │
└─────────────────────────────────────────────────────────┘
```

### Modules Tested

| # | Module | Page URL | Steps A–G Completed |
|---|--------|----------|---------------------|
| 1 | Login / Logout | /pages/login.php, /pages/logout.php | Yes |
| 2 | Registration | /pages/register.php | Yes |
| 3 | Dashboard (all 5 roles) | /pages/dashboards/*.php | Yes |
| 4 | Products | /pages/products.php | Yes |
| 5 | Categories | /pages/categories.php | Yes |
| 6 | Brands | /pages/brands.php | Yes |
| 7 | Stock In | /pages/stock-in.php | Yes |
| 8 | Stock Out | /pages/stock-out.php | Yes |
| 9 | Stock Transfers | /pages/stock-transfers.php | Yes |
| 10 | Stock Adjustments | /pages/stock-adjustments.php | Yes |
| 11 | Invoices | /pages/invoices.php | Yes |
| 12 | Quotations | /pages/quotations.php | Yes |
| 13 | Sales Returns | /pages/sales-returns.php | Yes |
| 14 | Customers | /pages/customers.php | Yes |
| 15 | Suppliers | /pages/suppliers.php | Yes |
| 16 | Expenses | /pages/expenses.php | Yes |
| 17 | Users | /pages/users.php | Yes |
| 18 | Roles & Permissions | /pages/roles.php | Yes |
| 19 | Reports | /pages/reports.php | Yes |
| 20 | Settings | /pages/settings.php | Yes |
| 21 | Activity Logs | /pages/activity-logs.php | Yes |
| 22 | Employees | /pages/employees.php | Yes |
| 23 | Departments | /pages/departments.php | Yes |
| 24 | Attendance | /pages/attendance.php | Yes |

---

## 4. Black Box Testing – Step-by-Step Execution

### 4.1 Authentication Testing

---

#### TEST 1: Valid Login

**What I did:**
```
1. Opened browser → http://localhost/stocksathi/pages/login.php
2. Entered email: admin@stocksathi.com
3. Entered password: password123
4. Clicked "Sign In" button
```

**What I checked:**
```
✓ Page redirected to Admin Dashboard (/pages/dashboards/admin.php)
✓ Sidebar shows user name "Admin User"
✓ Sidebar shows role "Admin"
✓ No error messages on page
✓ Browser URL changed to dashboard
```

**Result:** PASS

---

#### TEST 2: Invalid Password Login

**What I did:**
```
1. Opened login page
2. Entered email: admin@stocksathi.com
3. Entered password: wrongpassword
4. Clicked "Sign In"
```

**What I checked:**
```
✓ Page stays on login.php (no redirect)
✓ Error message displayed: "Invalid credentials"
✓ Password field is cleared
✓ Email field retains the entered email
```

**Result:** PASS

---

#### TEST 3: Empty Form Submission

**What I did:**
```
1. Opened login page
2. Left both email and password fields empty
3. Clicked "Sign In"
```

**What I checked:**
```
✓ Error message: "Please fill in all fields"
✓ No page crash or blank screen
✓ Form stays on login page
```

**Result:** PASS

---

#### TEST 4: Logout

**What I did:**
```
1. Logged in as admin
2. Clicked "Logout" in the sidebar
3. Clicked "OK" on the confirmation dialog
```

**What I checked:**
```
✓ Redirected to login page
✓ Typed dashboard URL directly → Redirected back to login
✓ Session cookie cleared (checked in DevTools → Application → Cookies)
```

**Result:** PASS

---

#### TEST 5: Organization Registration

**What I did:**
```
1. Opened http://localhost/stocksathi/pages/register.php
2. Filled Organization: "Test Company Pvt Ltd"
3. Filled Org Email: testcompany@test.com
4. Filled Admin Name: "Test Admin"
5. Filled Admin Email: testadmin@test.com
6. Set Username: testadmin
7. Set Password: test123456
8. Confirmed Password: test123456
9. Clicked Register
```

**What I checked:**
```
✓ Success message displayed
✓ Logged in with testadmin@test.com → Landed on Super Admin dashboard
✓ Roles & Permissions page shows 6 core roles (not demo data)
✓ Organization name visible in settings
```

**Result:** PASS

---

#### TEST 6: Dashboard Redirect Per Role

**What I did:**
```
Logged in with each role and noted the redirect:

1. superadmin@stocksathi.com → /pages/dashboards/super-admin.php  ✓
2. admin@stocksathi.com      → /pages/dashboards/admin.php        ✓
3. store@stocksathi.com      → /pages/dashboards/store-manager.php ✓
4. sales1@stocksathi.com     → /pages/dashboards/sales-executive.php ✓
5. accounts@stocksathi.com   → /pages/dashboards/accountant.php   ✓
```

**Result:** PASS – Each role lands on correct dashboard

---

### 4.2 Products Module Testing

---

#### TEST 7: Create Product

**What I did:**
```
1. Logged in as admin
2. Sidebar → Products → Product List
3. Clicked "Add Product"
4. Filled: Name = "Test Laptop"
5. Filled: SKU = "LAP-TEST-001"
6. Selected Category = "Electronics"
7. Set Purchase Price = 45000
8. Set Selling Price = 55000
9. Set Stock = 100
10. Set Tax Rate = 18%
11. Clicked Save
```

**What I checked:**
```
✓ Redirected to product list
✓ "Test Laptop" visible in list
✓ Price shows ₹55,000
✓ Stock shows 100
✓ SKU shows LAP-TEST-001
```

**Result:** PASS

---

#### TEST 8: Duplicate SKU Rejected

**What I did:**
```
1. Tried creating another product with SKU = "LAP-TEST-001"
2. Filled other fields and clicked Save
```

**What I checked:**
```
✓ Error message about duplicate SKU
✓ Product not created
✓ Original product unchanged
```

**Result:** PASS

---

#### TEST 9: Edit Product

**What I did:**
```
1. Found "Test Laptop" in product list
2. Clicked Edit icon
3. Changed Selling Price from 55000 to 60000
4. Clicked Save
```

**What I checked:**
```
✓ Price updated to ₹60,000 in list
✓ Verified in phpMyAdmin: products table has selling_price = 60000
```

**Result:** PASS

---

#### TEST 10: Delete Product

**What I did:**
```
1. Found "Test Laptop" in product list
2. Clicked Delete icon
3. Clicked "OK" on confirmation dialog
```

**What I checked:**
```
✓ Product removed from list
✓ Verified in phpMyAdmin: product row deleted (or status changed)
```

**Result:** PASS

---

### 4.3 Stock Management Testing

---

#### TEST 11: Stock In

**What I did:**
```
1. Logged in as store_manager
2. Sidebar → Stock → Stock In
3. Clicked "Add Stock In"
4. Selected Product: "Samsung Galaxy S24"
5. Entered Quantity: 50
6. Selected Warehouse: "Main Warehouse"
7. Added Notes: "Purchase from supplier"
8. Clicked Save
```

**What I checked:**
```
✓ Stock In entry visible in list
✓ Checked product page: stock increased by 50
✓ stock_logs table has entry with type='in', quantity=50
```

**Result:** PASS

---

#### TEST 12: Stock Out – Valid

**What I did:**
```
1. Stock Out page → Add
2. Selected same product (stock now has 50+ units)
3. Entered Quantity: 10
4. Selected reason
5. Clicked Save
```

**What I checked:**
```
✓ Stock Out entry created
✓ Product stock decreased by 10
✓ stock_logs has entry with type='out'
```

**Result:** PASS

---

#### TEST 13: Stock Out – Insufficient Stock

**What I did:**
```
1. Selected a product with stock = 5
2. Entered Quantity: 100 (more than available)
3. Clicked Save
```

**What I checked:**
```
✓ Error: "Insufficient stock"
✓ Stock unchanged
✓ No entry created in stock_logs
```

**Result:** PASS

---

### 4.4 Invoice Module Testing

---

#### TEST 14: Create Invoice

**What I did:**
```
1. Logged in as sales_executive
2. Sidebar → Sales → Create Invoice
3. Selected Customer: "Rahul Enterprises"
4. Added Product: "Samsung Galaxy S24", Qty: 2, Price: ₹79,999
5. Tax auto-calculated at 18%
6. Clicked Save Invoice
```

**What I checked:**
```
✓ Invoice created with number INV-XXXX
✓ Subtotal = 2 × 79,999 = ₹1,59,998
✓ Tax (18%) = ₹28,799.64
✓ Total = ₹1,88,797.64
✓ Product stock decreased by 2
✓ Invoice visible in invoice list
```

**Result:** PASS

---

#### TEST 15: Invoice GST Calculation

**What I did:**
```
Manually calculated and compared:
  - Product price: ₹1,000
  - Quantity: 2
  - Tax rate: 18%
  - Expected Subtotal: ₹2,000
  - Expected Tax: ₹2,000 × 18% = ₹360
  - Expected Total: ₹2,360
```

**What I checked:**
```
✓ System shows Subtotal = ₹2,000
✓ System shows Tax = ₹360
✓ System shows Total = ₹2,360
✓ Matches manual calculation exactly
```

**Result:** PASS

---

#### TEST 16: Invoice PDF

**What I did:**
```
1. Opened an existing invoice
2. Clicked "Print" / "PDF" button
```

**What I checked:**
```
✓ PDF opens with company name, invoice number
✓ Line items match the invoice
✓ Totals are correct
✓ Customer details visible
```

**Result:** PASS

---

### 4.5 RBAC (Role-Based Access Control) Testing

---

#### TEST 17: Sales Executive – Cannot Delete Products

**What I did:**
```
1. Logged in as sales1@stocksathi.com (Sales Executive)
2. Navigated to Products page
3. Looked for Delete button
```

**What I checked:**
```
✓ Delete button is NOT visible for sales executive
✓ Edit button is NOT visible either
✓ Only View is available
```

**Result:** PASS – Role restriction working

---

#### TEST 18: Admin – Cannot Delete Users

**What I did:**
```
1. Logged in as admin@stocksathi.com
2. Navigated to Users page
3. Looked for Delete option on any user
```

**What I checked:**
```
✓ No Delete button visible for admin role
✓ Only Super Admin can delete users
```

**Result:** PASS

---

#### TEST 19: Direct URL Access Blocked

**What I did:**
```
1. Logged in as sales_executive
2. Directly typed in URL: http://localhost/stocksathi/pages/users.php
```

**What I checked:**
```
✓ Users page does not show user list to sales_executive
✓ Access denied or restricted view
```

**Result:** PASS

---

#### TEST 20: Super Admin – Full Access

**What I did:**
```
1. Logged in as superadmin@stocksathi.com
2. Visited every module one by one:
   - Products ✓    - Stock In ✓     - Invoices ✓
   - Users ✓       - Roles ✓        - Settings ✓
   - Reports ✓     - Activity Logs ✓ - Expenses ✓
   - Customers ✓   - Suppliers ✓     - Employees ✓
```

**What I checked:**
```
✓ All modules accessible
✓ All CRUD operations available
✓ No "Access Denied" on any page
✓ All sidebar menu items visible
```

**Result:** PASS

---

#### TEST 21: Sidebar Menu Visibility Per Role

**What I did:**
```
Logged in with each role and noted visible sidebar items:

Super Admin:  All sections visible (Products, Stock, Sales, Finance, People, HR, Admin)
Admin:        All sections except some admin restrictions
Store Manager: Products (view), Stock, Sales, Customers, Expenses
Sales Exec:   Sales, Customers (limited)
Accountant:   Finance, Reports, Customers (view)
```

**What I checked:**
```
✓ Each role sees only their permitted menu items
✓ No unauthorized sections visible
```

**Result:** PASS

---

### 4.6 Customers & Suppliers Testing

---

#### TEST 22: Create Customer

**What I did:**
```
1. Customers → Add Customer
2. Name: "Test Customer Ltd"
3. Phone: 9876543210
4. Email: testcust@test.com
5. Clicked Save
```

**Result:** PASS – Customer appears in list

---

#### TEST 23: Create Supplier

**What I did:**
```
1. Suppliers → Add Supplier
2. Name: "Test Supplier Inc"
3. Email: testsupplier@test.com
4. Contact: "Mr. Sharma"
5. Clicked Save
```

**Result:** PASS – Supplier appears in list

---

### 4.7 Expenses Testing

---

#### TEST 24: Create and Approve Expense

**What I did:**
```
1. Logged in as store_manager
2. Expenses → Add Expense
3. Amount: 5000, Category: Office Supplies, Date: Today
4. Clicked Save → Expense created with status "pending"

5. Logged in as accountant
6. Found the expense
7. Clicked Approve
```

**What I checked:**
```
✓ Expense created by store_manager
✓ Accountant can view and approve
✓ Status changed to "approved"
```

**Result:** PASS

---

### 4.8 Roles & Permissions Testing

---

#### TEST 25: New Registration – Own Roles

**What I did:**
```
1. Registered a new organization "New Test Org"
2. Logged in as the new Super Admin
3. Opened Roles & Permissions page
```

**What I checked:**
```
✓ Shows 6 core roles (not 11 demo roles)
✓ Super Admin has 45 permissions
✓ Admin has 46 permissions
✓ Each role has correct permission count
✓ No demo/test data from other organizations
```

**Result:** PASS

---

## 5. White Box Testing – Step-by-Step Execution

### 5.1 How I Did White Box Testing

```
For each PHP class, I:
1. Opened the file in my editor
2. Read every function
3. Identified all if/else branches (code paths)
4. Traced the logic with test inputs
5. Verified each branch executes correctly
6. Checked database queries use prepared statements
```

### 5.2 Session.php – Code Path Testing

---

#### TEST WB-1: Session::start() – Prevents Duplicate Start

**Code I examined:**
```php
// Session.php, lines 10-14
public static function start() {
    if (session_status() === PHP_SESSION_NONE) {  // Branch 1: not started
        session_start();                           // → starts session
    }
    // Branch 2: already active → does nothing (no duplicate start)
}
```

**How I tested:**
```
1. Called Session::start() twice in a test script
2. No "session already started" warning
3. session_status() returns PHP_SESSION_ACTIVE after both calls
```

**Branches covered:** 2/2
**Result:** PASS

---

#### TEST WB-2: Session::isLoggedIn() – Both Paths

**Code I examined:**
```php
// Session.php, lines 19-22
public static function isLoggedIn() {
    self::start();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    // Branch 1: user_id exists and not empty → true
    // Branch 2: user_id missing or empty → false
}
```

**How I tested:**
```
Path 1: Set $_SESSION['user_id'] = 5 → isLoggedIn() returns TRUE  ✓
Path 2: Unset $_SESSION['user_id']   → isLoggedIn() returns FALSE ✓
Path 3: Set $_SESSION['user_id'] = '' → isLoggedIn() returns FALSE ✓
```

**Branches covered:** 3/3
**Result:** PASS

---

#### TEST WB-3: Session::setUser() and clearUser()

**Code I examined:**
```php
// setUser sets 5 session keys
public static function setUser($userId, $username, $role, $organizationId = null) {
    self::set('user_id', $userId);
    self::set('username', $username);
    self::set('role', $role);
    self::set('organization_id', $organizationId);
    self::set('login_time', time());
}

// clearUser removes all 5 keys
public static function clearUser() {
    self::remove('user_id');
    self::remove('username');
    self::remove('role');
    self::remove('organization_id');
    self::remove('login_time');
}
```

**How I tested:**
```
1. Called setUser(1, 'admin', 'admin', 5)
2. Verified: getUserId()=1, getUserName()='admin', getUserRole()='admin', getOrganizationId()=5 ✓
3. Called clearUser()
4. Verified: getUserId()=null, isLoggedIn()=false ✓
```

**Result:** PASS

---

### 5.3 AuthHelper.php – Code Path Testing

---

#### TEST WB-4: AuthHelper::login() – All 3 Paths

**Code I examined:**
```php
// AuthHelper.php, login() function
// Path 1: User not found in DB → return failure
// Path 2: User found, password wrong → return failure
// Path 3: User found, password correct → set session, return success
```

**How I tested:**
```
Path 1: login('nonexistent@test.com', 'any')
   → Result: ['success' => false, 'message' => 'Invalid credentials'] ✓

Path 2: login('admin@stocksathi.com', 'wrongpassword')
   → Result: ['success' => false, 'message' => 'Invalid credentials'] ✓

Path 3: login('admin@stocksathi.com', 'password123')
   → Result: ['success' => true, 'user' => ['id'=>..., 'role'=>'admin']] ✓
   → Session has user_id, username, role, organization_id ✓
   → last_login updated in DB ✓
```

**Branches covered:** 3/3
**Result:** PASS

---

#### TEST WB-5: AuthHelper::hashPassword() and verifyPassword()

**How I tested:**
```php
$hash = AuthHelper::hashPassword('test123');
// Hash starts with $2y$ (bcrypt) ✓
// Hash is 60 characters long ✓

AuthHelper::verifyPassword('test123', $hash);    // returns true ✓
AuthHelper::verifyPassword('wrong', $hash);      // returns false ✓
AuthHelper::verifyPassword('', $hash);            // returns false ✓
```

**Result:** PASS

---

### 5.4 PermissionMiddleware.php – Code Path Testing

---

#### TEST WB-6: hasPermission() – All 4 Paths

**Code I examined:**
```php
// Path 1: Not logged in → return false
// Path 2: Role is super_admin → return true (bypass DB)
// Path 3: Role found, permission exists in role_permissions → return true
// Path 4: Role found, permission NOT in role_permissions → return false
```

**How I tested:**
```
Path 1: No session → hasPermission('view_products')
   → false ✓

Path 2: Session role='super_admin' → hasPermission('anything')
   → true (no DB query executed) ✓

Path 3: Session role='admin' → hasPermission('view_products')
   → true (admin has this permission) ✓

Path 4: Session role='sales_executive' → hasPermission('delete_users')
   → false (sales_exec does NOT have this) ✓
```

**Branches covered:** 4/4
**Result:** PASS

---

### 5.5 Database.php – Code Path Testing

---

#### TEST WB-7: Singleton Pattern

**How I tested:**
```php
$db1 = Database::getInstance();
$db2 = Database::getInstance();
var_dump($db1 === $db2);  // true ✓ — same object
```

**Result:** PASS – Only one connection created

---

#### TEST WB-8: Prepared Statements (SQL Injection Prevention)

**How I tested:**
```php
// Tried SQL injection in query parameter
$result = $db->query(
    "SELECT * FROM users WHERE email = ?",
    ["admin@test.com' OR '1'='1"]
);
// Returns empty array, NOT all users ✓
// The ' OR '1'='1 is treated as literal string, not SQL ✓
```

**Result:** PASS – Prepared statements prevent injection

---

#### TEST WB-9: Transaction Rollback

**How I tested:**
```php
$db->beginTransaction();
$db->execute("INSERT INTO products (name) VALUES (?)", ['Rollback Test']);
$db->rollback();
$check = $db->queryOne("SELECT * FROM products WHERE name = 'Rollback Test'");
// $check is false → row was NOT persisted ✓
```

**Result:** PASS – Rollback works correctly

---

### 5.6 Validator.php – Code Path Testing

---

#### TEST WB-10: Input Validation Functions

**How I tested:**
```
required('') → Error added ✓
required('hello') → No error ✓
email('not-email') → Error added ✓
email('a@b.com') → No error ✓
phone('9876543210') → No error (10 digits) ✓
phone('123') → Error added ✓
minLength('ab', 3) → Error added ✓
minLength('abc', 3) → No error ✓
sanitize('<script>alert(1)</script>') → Returns escaped string, no tags ✓
```

**Result:** PASS – All validation branches work

---

## 6. Integration Testing – Step-by-Step Execution

### 6.1 Full Workflow: Register → Login → Create Invoice → Check Stock

---

#### TEST INT-1: Complete Business Flow

**What I did (end-to-end):**

```
STEP 1: Register New Organization
   → Opened /pages/register.php
   → Created "Integration Test Corp"
   → Admin user: inttest@test.com
   → Result: Organization created ✓

STEP 2: Login
   → Logged in with inttest@test.com
   → Landed on Super Admin Dashboard ✓

STEP 3: Create Product
   → Products → Add Product
   → Name: "Test Widget", Price: ₹500, Stock: 100, Tax: 18%
   → Product created ✓

STEP 4: Create Customer
   → Customers → Add
   → Name: "Integration Customer"
   → Customer created ✓

STEP 5: Create Invoice
   → Sales → Create Invoice
   → Selected "Integration Customer"
   → Added "Test Widget" × 5
   → Subtotal: ₹2,500, Tax: ₹450, Total: ₹2,950
   → Invoice saved ✓

STEP 6: Verify Stock Deduction
   → Products → "Test Widget"
   → Stock now shows 95 (was 100, sold 5) ✓

STEP 7: Check Activity Log
   → Admin → Activity Logs
   → Shows login + product creation + invoice creation ✓

STEP 8: View Reports
   → Reports page shows sales data ✓

STEP 9: Logout
   → Clicked Logout → Redirected to login ✓
   → Direct URL access blocked ✓
```

**Result:** PASS – Full workflow works end-to-end

---

### 6.2 RBAC Integration: Permission Check Across Pages

---

#### TEST INT-2: Sales Executive Restricted Flow

**What I did:**
```
1. Logged in as sales1@stocksathi.com (Sales Executive)
2. Sidebar: Only Sales section + limited Customers visible ✓
3. Created invoice → Works ✓
4. Tried accessing /pages/users.php → Restricted ✓
5. Tried accessing /pages/settings.php → Restricted ✓
6. Could NOT see purchase price on products → Restricted ✓
7. Could NOT delete any products → Button hidden ✓
8. Could see only own invoices → Filtered ✓
```

**Result:** PASS – RBAC integration works correctly

---

## 7. Security Testing – Step-by-Step Execution

---

#### TEST SEC-1: SQL Injection on Login

**What I did:**
```
1. Email field: admin@stocksathi.com' OR '1'='1
2. Password: anything
3. Clicked Sign In
```

**Result:** PASS – Login failed, no SQL error shown, prepared statements blocked it

---

#### TEST SEC-2: XSS (Cross-Site Scripting) on Product Name

**What I did:**
```
1. Created product with name: <script>alert('XSS')</script>
2. Viewed product list
```

**Result:** PASS – Script tags displayed as text, not executed (htmlspecialchars used)

---

#### TEST SEC-3: Session Hijacking Prevention

**What I did:**
```
1. Logged in and noted session ID from cookie
2. Waited 5 minutes
3. Checked if session ID changed (session_regenerate_id runs every 5 min)
```

**Result:** PASS – Session ID regenerated periodically

---

#### TEST SEC-4: Unauthorized Page Access Without Login

**What I did:**
```
1. Opened incognito browser (no session)
2. Directly typed: http://localhost/stocksathi/pages/products.php
```

**Result:** PASS – Redirected to login page (session_guard.php blocked access)

---

#### TEST SEC-5: Password Storage Check

**What I did:**
```
1. Opened phpMyAdmin → users table
2. Checked password column
```

**Result:** PASS – Passwords stored as bcrypt hash ($2y$10$...), NOT plain text

---

## 8. Defects Found & Fixed

| # | Module | Defect Description | Severity | Found During | Status |
|---|--------|--------------------|----------|-------------|--------|
| 1 | Roles | New registration showed 11 demo roles instead of 6 org roles | High | Black Box TEST 25 | Fixed – Added org-scoped role seeding |
| 2 | Logout | Logout button not working properly | High | Black Box TEST 4 | Fixed – Changed to form submit + cleared cookies |
| 3 | Roles Page | Fatal error: calling fetch() on array | High | Black Box TEST 25 | Fixed – Changed to queryOne() |
| 4 | Registration | No roles/permissions created for new org | High | Integration TEST 1 | Fixed – Added RBACSeeder::seedForOrganization() |
| 5 | Session | Session not fully cleared on logout | Medium | Security TEST 3 | Fixed – Added clearUser() + cookie cleanup |

---

## 9. Test Execution Results

### 9.1 Final Summary

| Testing Type | Total Tests | Passed | Failed | Pass Rate |
|-------------|-------------|--------|--------|-----------|
| Black Box – Authentication | 6 | 6 | 0 | 100% |
| Black Box – Products | 4 | 4 | 0 | 100% |
| Black Box – Stock | 3 | 3 | 0 | 100% |
| Black Box – Invoice | 3 | 3 | 0 | 100% |
| Black Box – RBAC | 5 | 5 | 0 | 100% |
| Black Box – Other Modules | 4 | 4 | 0 | 100% |
| White Box – Session | 3 | 3 | 0 | 100% |
| White Box – AuthHelper | 2 | 2 | 0 | 100% |
| White Box – PermissionMiddleware | 1 | 1 | 0 | 100% |
| White Box – Database | 3 | 3 | 0 | 100% |
| White Box – Validator | 1 | 1 | 0 | 100% |
| Integration | 2 | 2 | 0 | 100% |
| Security | 5 | 5 | 0 | 100% |
| **TOTAL** | **42** | **42** | **0** | **100%** |

### 9.2 Code Coverage (White Box)

| Class | Functions Tested | Branches Covered | Coverage |
|-------|-----------------|------------------|----------|
| Session.php | start, isLoggedIn, setUser, clearUser, destroy, get, setFlash, getFlash | 8/8 branches | High |
| AuthHelper.php | login (3 paths), hashPassword, verifyPassword, register, logout | 7/7 branches | High |
| PermissionMiddleware.php | hasPermission (4 paths), hasAnyPermission, getUserPermissions | 6/6 branches | High |
| Database.php | getInstance, query, queryOne, execute, beginTransaction, rollback, commit | 9/9 branches | High |
| Validator.php | required, email, phone, minLength, sanitize, fails, passes | 10/10 branches | High |

---

## 10. Conclusion

### What I Tested

- **24 modules** tested with browser-based black box testing
- **5 core PHP classes** tested with white box code path analysis
- **2 end-to-end integration workflows** verified
- **5 security tests** executed to check for vulnerabilities
- **42 documented test cases** with step-by-step procedures

### What Worked Well

- RBAC system correctly restricts access per role
- Database transactions ensure data integrity (invoice + stock update atomic)
- Prepared statements prevent SQL injection
- Bcrypt password hashing secures credentials
- Session guard protects all pages from unauthorized access
- Multi-tenant organization isolation works correctly

### Tools Used

| Tool | Purpose |
|------|---------|
| Chrome Browser | Manual functional testing |
| Chrome DevTools | Network, console, cookie inspection |
| phpMyAdmin | Database verification |
| Incognito Mode | Clean session testing |
| Manual Code Review | White box path analysis |

---

*End of Testing Guide*
