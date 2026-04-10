# StockSathi – Full Testing Documentation

**Project:** StockSathi – Inventory Management System  
**Document Version:** 1.0  
**Last Updated:** February 2026  
**Classification:** Quality Assurance & Testing

---

## Table of Contents

1. [Document Information](#1-document-information)
2. [Test Strategy](#2-test-strategy)
3. [Test Environment](#3-test-environment)
4. [Black Box Testing](#4-black-box-testing)
5. [White Box Testing](#5-white-box-testing)
6. [Integration Testing](#6-integration-testing)
7. [Security Testing](#7-security-testing)
8. [Performance Testing](#8-performance-testing)
9. [Test Execution Summary](#9-test-execution-summary)
10. [Appendix](#10-appendix)

---

## 1. Document Information

### 1.1 Purpose

This document defines the testing approach, test cases, and procedures for the StockSathi application. It covers:

- **Black Box Testing** – Functional testing without internal code knowledge  
- **White Box Testing** – Code-level and logic testing  
- **Integration Testing** – Module and system integration  
- **Security Testing** – Auth and access control  
- **Performance Testing** – Load and basic performance checks  

### 1.2 Scope

| Scope Item | Description |
|------------|-------------|
| **In Scope** | All modules, RBAC, authentication, CRUD operations, reports, APIs |
| **Out of Scope** | Third-party integrations, mobile app, browser-specific layout testing |
| **Tools** | Manual testing, PHPUnit (optional), browser DevTools |

### 1.3 Definitions

| Term | Definition |
|------|------------|
| **Black Box** | Testing based on requirements and behavior, not implementation |
| **White Box** | Testing with knowledge of internal structure and code |
| **RBAC** | Role-Based Access Control |
| **TC** | Test Case |

---

## 2. Test Strategy

### 2.1 Testing Levels

```
┌─────────────────────────────────────────────────────────────┐
│                    TESTING PYRAMID                           │
├─────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────┐   │
│  │  E2E / System Tests (Black Box)                      │   │
│  └─────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  Integration Tests (Modules + DB + Session)          │   │
│  └─────────────────────────────────────────────────────┘   │
│  ┌─────────────────────────────────────────────────────┐   │
│  │  White Box / Unit Tests (Functions, Classes)         │   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### 2.2 Test Types by Module

| Module | Black Box | White Box | Integration | Security |
|--------|:---------:|:---------:|:-----------:|:--------:|
| Authentication | ✓ | ✓ | ✓ | ✓ |
| Products | ✓ | ✓ | ✓ | ✓ |
| Categories & Brands | ✓ | ✓ | ✓ | — |
| Stock (In/Out/Transfer) | ✓ | ✓ | ✓ | ✓ |
| Invoices & Sales | ✓ | ✓ | ✓ | ✓ |
| Customers & Suppliers | ✓ | ✓ | ✓ | ✓ |
| Expenses | ✓ | ✓ | ✓ | ✓ |
| Users & Roles | ✓ | ✓ | ✓ | ✓ |
| Reports | ✓ | ✓ | ✓ | — |
| Settings | ✓ | ✓ | — | ✓ |

---

## 3. Test Environment

### 3.1 Prerequisites

| Requirement | Specification |
|-------------|---------------|
| **Server** | XAMPP / LAMP / WAMP |
| **PHP** | 7.4 or higher |
| **MySQL** | 5.7+ or MariaDB 10.2+ |
| **Browser** | Chrome, Firefox, Edge (latest) |
| **Base URL** | http://localhost/stocksathi |

### 3.2 Test Data

| Role | Username / Email | Password |
|------|------------------|----------|
| Super Admin | superadmin@stocksathi.com | password123 |
| Admin | admin@stocksathi.com | password123 |
| Store Manager | store@stocksathi.com | password123 |
| Sales Executive | sales1@stocksathi.com | password123 |
| Accountant | accounts@stocksathi.com | password123 |

### 3.3 Pass / Fail Criteria

- **Pass:** Expected result matches actual result.  
- **Fail:** Mismatch, error, or blocking issue.  
- **Blocked:** Cannot execute due to dependency or environment issue.  

---

## 4. Black Box Testing

### 4.1 Authentication Module

#### TC-BB-AUTH-001: Valid Login

| Field | Value |
|-------|-------|
| **ID** | TC-BB-AUTH-001 |
| **Title** | Successful login with valid credentials |
| **Priority** | High |
| **Preconditions** | User exists in DB, browser open on login page |
| **Test Data** | Email: admin@stocksathi.com, Password: password123 |
| **Steps** | 1. Open /pages/login.php<br>2. Enter email<br>3. Enter password<br>4. Click Sign In |
| **Expected** | Redirect to role-specific dashboard, session active |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-AUTH-002: Invalid Password

| Field | Value |
|-------|-------|
| **ID** | TC-BB-AUTH-002 |
| **Title** | Login fails with wrong password |
| **Priority** | High |
| **Preconditions** | User exists |
| **Test Data** | Email: admin@stocksathi.com, Password: wrongpass |
| **Steps** | 1. Open login<br>2. Enter valid email, wrong password<br>3. Click Sign In |
| **Expected** | Error message, no redirect, stay on login page |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-AUTH-003: Empty Fields Validation

| Field | Value |
|-------|-------|
| **ID** | TC-BB-AUTH-003 |
| **Title** | Validation when fields are empty |
| **Priority** | Medium |
| **Steps** | 1. Open login<br>2. Leave fields blank<br>3. Click Sign In |
| **Expected** | Validation message, form not submitted |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-AUTH-004: Logout

| Field | Value |
|-------|-------|
| **ID** | TC-BB-AUTH-004 |
| **Title** | Logout destroys session and redirects |
| **Priority** | High |
| **Preconditions** | User logged in |
| **Steps** | 1. Click Logout in sidebar<br>2. Confirm in dialog |
| **Expected** | Redirect to login, session cleared, direct access to protected pages blocked |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-AUTH-005: Registration (New Organization)

| Field | Value |
|-------|-------|
| **ID** | TC-BB-AUTH-005 |
| **Title** | New organization registration |
| **Priority** | High |
| **Preconditions** | Registration page accessible |
| **Test Data** | Org name, admin details, valid email, password (min 6 chars) |
| **Steps** | 1. Open /pages/register.php<br>2. Fill all required fields<br>3. Submit form |
| **Expected** | Org and Super Admin user created, success message, redirect to login |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-AUTH-006: Role-Based Dashboard Redirect

| Field | Value |
|-------|-------|
| **ID** | TC-BB-AUTH-006 |
| **Title** | Correct dashboard per role |
| **Priority** | High |
| **Steps** | 1. Login as super_admin<br>2. Note dashboard URL<br>3. Repeat for admin, store_manager, sales_executive, accountant |
| **Expected** | Each role lands on correct dashboard (e.g. super-admin, admin, store-manager, sales-executive, accountant) |
| **Status** | □ Pass □ Fail □ Blocked |

---

### 4.2 Products Module

#### TC-BB-PROD-001: Create Product

| Field | Value |
|-------|-------|
| **ID** | TC-BB-PROD-001 |
| **Title** | Create product with all fields |
| **Priority** | High |
| **Preconditions** | User with create_products permission |
| **Test Data** | Name, SKU, Category, Price, Stock, Tax rate |
| **Steps** | 1. Products → Add Product<br>2. Fill form<br>3. Save |
| **Expected** | Product created, visible in list |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-PROD-002: Duplicate SKU Validation

| Field | Value |
|-------|-------|
| **ID** | TC-BB-PROD-002 |
| **Title** | Duplicate SKU rejected |
| **Priority** | High |
| **Preconditions** | Product with SKU TEST-001 exists |
| **Steps** | 1. Add Product<br>2. Use SKU TEST-001<br>3. Save |
| **Expected** | Error message about duplicate SKU |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-PROD-003: Edit Product

| Field | Value |
|-------|-------|
| **ID** | TC-BB-PROD-003 |
| **Title** | Edit product and persist changes |
| **Priority** | High |
| **Steps** | 1. Open existing product<br>2. Change price/name<br>3. Save |
| **Expected** | Changes saved and reflected in list |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-PROD-004: Delete Product

| Field | Value |
|-------|-------|
| **ID** | TC-BB-PROD-004 |
| **Title** | Delete product (with permission) |
| **Priority** | High |
| **Preconditions** | User with delete_products, product without invoice references |
| **Steps** | 1. Select product<br>2. Delete<br>3. Confirm |
| **Expected** | Product removed from list |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-PROD-005: Search and Filter

| Field | Value |
|-------|-------|
| **ID** | TC-BB-PROD-005 |
| **Title** | Search and category filter |
| **Priority** | Medium |
| **Steps** | 1. Search by product name<br>2. Filter by category |
| **Expected** | Correct filtered list |
| **Status** | □ Pass □ Fail □ Blocked |

---

### 4.3 Stock Management Module

#### TC-BB-STK-001: Stock In

| Field | Value |
|-------|-------|
| **ID** | TC-BB-STK-001 |
| **Title** | Stock In increases quantity |
| **Priority** | High |
| **Test Data** | Product, Quantity: 50 |
| **Steps** | 1. Stock In → Add<br>2. Select product, warehouse<br>3. Enter qty, save |
| **Expected** | Stock increased by 50 |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-STK-002: Stock Out – Sufficient Stock

| Field | Value |
|-------|-------|
| **ID** | TC-BB-STK-002 |
| **Title** | Stock Out with valid quantity |
| **Priority** | High |
| **Preconditions** | Product stock ≥ 20 |
| **Steps** | 1. Stock Out<br>2. Select product, qty 10<br>3. Save |
| **Expected** | Stock reduced by 10 |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-STK-003: Stock Out – Insufficient Stock

| Field | Value |
|-------|-------|
| **ID** | TC-BB-STK-003 |
| **Title** | Stock Out rejects excess quantity |
| **Priority** | High |
| **Preconditions** | Product stock = 10 |
| **Test Data** | Quantity: 20 |
| **Expected** | Error about insufficient stock, stock unchanged |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-STK-004: Stock Transfer

| Field | Value |
|-------|-------|
| **ID** | TC-BB-STK-004 |
| **Title** | Inter-warehouse transfer |
| **Priority** | High |
| **Steps** | 1. Stock Transfers → New<br>2. From WH-A, To WH-B, qty 10<br>3. Complete |
| **Expected** | Source -10, destination +10 |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-STK-005: Stock Adjustment

| Field | Value |
|-------|-------|
| **ID** | TC-BB-STK-005 |
| **Title** | Adjustment (add/subtract) |
| **Priority** | Medium |
| **Steps** | 1. Stock Adjustments<br>2. Add adjustment (e.g. +5)<br>3. Save |
| **Expected** | Stock updated by adjustment amount |
| **Status** | □ Pass □ Fail □ Blocked |

---

### 4.4 Invoice Module

#### TC-BB-INV-001: Create Invoice – Single Item

| Field | Value |
|-------|-------|
| **ID** | TC-BB-INV-001 |
| **Title** | Create invoice with one item |
| **Priority** | High |
| **Preconditions** | Customer and product with stock exist |
| **Steps** | 1. Invoices → Create<br>2. Select customer, add product, set qty<br>3. Save |
| **Expected** | Invoice saved with correct subtotal, tax, total |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-INV-002: Invoice Tax Calculation

| Field | Value |
|-------|-------|
| **ID** | TC-BB-INV-002 |
| **Title** | Tax (GST) calculation |
| **Priority** | High |
| **Test Data** | Price 1000, Qty 2, Tax 18% |
| **Expected** | Subtotal 2000, Tax 360, Total 2360 |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-INV-003: Invoice with Discount

| Field | Value |
|-------|-------|
| **ID** | TC-BB-INV-003 |
| **Title** | Discount applied correctly |
| **Priority** | Medium |
| **Preconditions** | User with give_discount permission |
| **Test Data** | Subtotal 1000, 10% discount |
| **Expected** | Discount 100, total 900 + tax |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-INV-004: Stock Deduction on Invoice

| Field | Value |
|-------|-------|
| **ID** | TC-BB-INV-004 |
| **Title** | Product stock reduced on invoice save |
| **Priority** | High |
| **Preconditions** | Product stock = 50 |
| **Test Data** | Invoice qty = 5 |
| **Expected** | Product stock = 45 |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-INV-005: Invoice PDF

| Field | Value |
|-------|-------|
| **ID** | TC-BB-INV-005 |
| **Title** | PDF generation |
| **Priority** | Medium |
| **Steps** | 1. Open invoice<br>2. Print/PDF |
| **Expected** | PDF with correct data |
| **Status** | □ Pass □ Fail □ Blocked |

---

### 4.5 Customers & Suppliers Module

#### TC-BB-CUST-001: Create Customer

| Field | Value |
|-------|-------|
| **ID** | TC-BB-CUST-001 |
| **Title** | Create customer |
| **Priority** | High |
| **Test Data** | Name, Phone, Email, Address |
| **Steps** | 1. Customers → Add<br>2. Fill form<br>3. Save |
| **Expected** | Customer in list |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-CUST-002: Edit & Delete Customer

| Field | Value |
|-------|-------|
| **ID** | TC-BB-CUST-002 |
| **Title** | Edit and delete customer |
| **Priority** | Medium |
| **Steps** | 1. Edit customer, change phone, save<br>2. Delete customer (no balance) |
| **Expected** | Edit persists; delete removes customer |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-SUPP-001: Create Supplier

| Field | Value |
|-------|-------|
| **ID** | TC-BB-SUPP-001 |
| **Title** | Create supplier |
| **Priority** | High |
| **Steps** | 1. Suppliers → Add<br>2. Fill required fields<br>3. Save |
| **Expected** | Supplier created |
| **Status** | □ Pass □ Fail □ Blocked |

---

### 4.6 RBAC (Role-Based Access Control)

#### TC-BB-RBAC-001: Sales Executive – No Delete Products

| Field | Value |
|-------|-------|
| **ID** | TC-BB-RBAC-001 |
| **Title** | Sales exec cannot delete products |
| **Priority** | High |
| **Steps** | 1. Login as sales_executive<br>2. Products |
| **Expected** | Delete product option not visible/disabled |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-RBAC-002: Admin – No Delete Users

| Field | Value |
|-------|-------|
| **ID** | TC-BB-RBAC-002 |
| **Title** | Admin cannot delete users |
| **Priority** | High |
| **Steps** | 1. Login as admin<br>2. Users |
| **Expected** | Delete user option not visible/disabled |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-RBAC-003: Direct URL – Access Denied

| Field | Value |
|-------|-------|
| **ID** | TC-BB-RBAC-003 |
| **Title** | Direct URL access blocked for unauthorized role |
| **Priority** | High |
| **Steps** | 1. Login as sales_executive<br>2. Open /pages/users.php directly |
| **Expected** | 403 or redirect, no user list |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-RBAC-004: Super Admin – Full Access

| Field | Value |
|-------|-------|
| **ID** | TC-BB-RBAC-004 |
| **Title** | Super admin has full access |
| **Priority** | High |
| **Steps** | 1. Login as super_admin<br>2. Visit Users, Roles, Settings, all modules |
| **Expected** | All accessible |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-BB-RBAC-005: Roles & Permissions Page

| Field | Value |
|-------|-------|
| **ID** | TC-BB-RBAC-005 |
| **Title** | Roles & Permissions shows org roles |
| **Priority** | High |
| **Preconditions** | New org registered |
| **Steps** | 1. Login as super_admin<br>2. Admin → Roles & Permissions |
| **Expected** | List of roles with permission counts, View works |
| **Status** | □ Pass □ Fail □ Blocked |

---

### 4.7 Additional Modules (Summary)

| Module | Key Black Box Tests |
|--------|---------------------|
| **Categories** | Create, Edit, Delete, parent category, tree view |
| **Brands** | Create, Edit, Delete |
| **Expenses** | Create, Edit, Delete, Approve (with permission) |
| **Users** | Create, Edit, Delete (super_admin), role assignment |
| **Reports** | Sales, Stock, Financial reports render and filter |
| **Settings** | Update company info, invoice prefix, tax, theme |
| **Activity Logs** | List, filter by user/module, search |
| **Registration** | New org, duplicate email rejected |

---

## 5. White Box Testing

### 5.1 Session Management

#### TC-WB-SESS-001: Session::start()

| Field | Value |
|-------|-------|
| **ID** | TC-WB-SESS-001 |
| **Title** | No duplicate session_start |
| **Function** | `Session::start()` |
| **Code Path** | Session.php lines 10–14 |
| **Test** | Call start() multiple times |
| **Expected** | No error; session_status() = PHP_SESSION_ACTIVE |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-WB-SESS-002: Session::destroy()

| Field | Value |
|-------|-------|
| **ID** | TC-WB-SESS-002 |
| **Title** | Session cleared on destroy |
| **Function** | `Session::destroy()` |
| **Code Path** | Session.php lines 58–62 |
| **Test** | start() → set() → destroy() → get() |
| **Expected** | get() returns null/default after destroy |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-WB-SESS-003: Session::clearUser()

| Field | Value |
|-------|-------|
| **ID** | TC-WB-SESS-003 |
| **Title** | User keys removed |
| **Function** | `Session::clearUser()` |
| **Code Path** | Session.php lines 105–112 |
| **Test** | setUser() → clearUser() → isLoggedIn() |
| **Expected** | isLoggedIn() = false |
| **Status** | □ Pass □ Fail □ Blocked |

---

### 5.2 Permission Middleware

#### TC-WB-PERM-001: hasPermission() – Super Admin

| Field | Value |
|-------|-------|
| **ID** | TC-WB-PERM-001 |
| **Title** | Super admin returns true for any permission |
| **Function** | `PermissionMiddleware::hasPermission()` |
| **Code Path** | PermissionMiddleware.php lines 21–24 |
| **Test** | Session with role=super_admin, any permission |
| **Expected** | true without DB check |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-WB-PERM-002: hasPermission() – No Session

| Field | Value |
|-------|-------|
| **ID** | TC-WB-PERM-002 |
| **Title** | Returns false when not logged in |
| **Function** | `PermissionMiddleware::hasPermission()` |
| **Code Path** | PermissionMiddleware.php lines 14–16 |
| **Test** | No user in session |
| **Expected** | false |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-WB-PERM-003: hasPermission() – Role in DB

| Field | Value |
|-------|-------|
| **ID** | TC-WB-PERM-003 |
| **Title** | Permission from role_permissions |
| **Function** | `PermissionMiddleware::hasPermission()` |
| **Code Path** | PermissionMiddleware.php lines 30–45 |
| **Test** | role=admin, permission=view_products (exists for admin) |
| **Expected** | true |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-WB-PERM-004: hasAnyPermission()

| Field | Value |
|-------|-------|
| **ID** | TC-WB-PERM-004 |
| **Title** | True if any of given permissions exist |
| **Function** | `PermissionMiddleware::hasAnyPermission()` |
| **Code Path** | PermissionMiddleware.php lines 71–77 |
| **Test** | Pass [perm1, perm2]; user has perm2 only |
| **Expected** | true |
| **Status** | □ Pass □ Fail □ Blocked |

---

### 5.3 AuthHelper

#### TC-WB-AUTH-001: login() – Valid

| Field | Value |
|-------|-------|
| **ID** | TC-WB-AUTH-001 |
| **Title** | Login sets session and returns success |
| **Function** | `AuthHelper::login($username, $password)` |
| **Code Path** | AuthHelper.php lines 37–82 |
| **Test** | Valid username and password |
| **Expected** | success=true, session has user_id, username, role, organization_id |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-WB-AUTH-002: login() – Invalid

| Field | Value |
|-------|-------|
| **ID** | TC-WB-AUTH-002 |
| **Title** | Invalid credentials return failure |
| **Function** | `AuthHelper::login()` |
| **Test** | Wrong password |
| **Expected** | success=false, message about invalid credentials |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-WB-AUTH-003: verifyPassword()

| Field | Value |
|-------|-------|
| **ID** | TC-WB-AUTH-003 |
| **Title** | Password verification |
| **Function** | `AuthHelper::verifyPassword($password, $hash)` |
| **Test** | Correct password vs hash from password_hash |
| **Expected** | true |
| **Status** | □ Pass □ Fail □ Blocked |

---

### 5.4 Database Class

#### TC-WB-DB-001: getInstance()

| Field | Value |
|-------|-------|
| **ID** | TC-WB-DB-001 |
| **Title** | Singleton returns same instance |
| **Function** | `Database::getInstance()` |
| **Test** | getInstance() called multiple times |
| **Expected** | Same object reference |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-WB-DB-002: query() – Prepared Statement

| Field | Value |
|-------|-------|
| **ID** | TC-WB-DB-002 |
| **Title** | query() uses prepared statements |
| **Function** | `Database::query($sql, $params)` |
| **Code Path** | database.php lines 86–94 |
| **Test** | query("SELECT * FROM users WHERE id = ?", [1]) |
| **Expected** | Array of rows, no SQL injection |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-WB-DB-003: Transaction

| Field | Value |
|-------|-------|
| **ID** | TC-WB-DB-003 |
| **Title** | Transaction rollback on error |
| **Functions** | beginTransaction(), rollback() |
| **Test** | begin → insert → rollback |
| **Expected** | No persistent insert |
| **Status** | □ Pass □ Fail □ Blocked |

---

### 5.5 RBACSeeder

#### TC-WB-RBAC-001: seedForOrganization()

| Field | Value |
|-------|-------|
| **ID** | TC-WB-RBAC-001 |
| **Title** | Creates roles for org |
| **Function** | `RBACSeeder::seedForOrganization($orgId)` |
| **Test** | New org ID, call seedForOrganization |
| **Expected** | Roles with organization_id = $orgId, role_permissions populated |
| **Status** | □ Pass □ Fail □ Blocked |

#### TC-WB-RBAC-002: seedIfNeeded() Idempotency

| Field | Value |
|-------|-------|
| **ID** | TC-WB-RBAC-002 |
| **Title** | Multiple calls safe |
| **Function** | `RBACSeeder::seedIfNeeded()` |
| **Test** | Call seedIfNeeded() twice |
| **Expected** | No duplicate rows, no error |
| **Status** | □ Pass □ Fail □ Blocked |

---

## 6. Integration Testing

### 6.1 Login → Dashboard Flow

| Test ID | Description | Expected |
|---------|-------------|----------|
| INT-001 | Login → session_guard → dashboard | Redirect to correct dashboard, no 403 |
| INT-002 | Login → logout → direct dashboard access | Redirect to login |
| INT-003 | Session expiry → access protected page | Redirect to login |

### 6.2 Invoice Creation Flow

| Test ID | Description | Expected |
|---------|-------------|----------|
| INT-004 | Create invoice → stock reduction → customer balance | Stock decreased, balance updated |
| INT-005 | Create invoice → activity log | Log entry created |
| INT-006 | Rollback on stock failure | Invoice not created, stock unchanged |

### 6.3 Registration Flow

| Test ID | Description | Expected |
|---------|-------------|----------|
| INT-007 | Register org → create super_admin → seed roles | Org, user, and roles created |
| INT-008 | Register → login → Roles page | Org-specific roles visible |

---

## 7. Security Testing

### 7.1 Authentication

| Test ID | Description | Expected |
|---------|-------------|----------|
| SEC-001 | SQL injection in login (e.g. `' OR '1'='1`) | No login, no error disclosure |
| SEC-002 | XSS in login fields | Input escaped, no script execution |
| SEC-003 | Brute force (many failed logins) | No clear lockout; consider rate limiting |
| SEC-004 | Session fixation | session_regenerate_id on login |
| SEC-005 | Password storage | Bcrypt, no plain text |

### 7.2 Authorization

| Test ID | Description | Expected |
|---------|-------------|----------|
| SEC-006 | Direct URL to admin page as sales_executive | 403 or redirect |
| SEC-007 | CSRF on form submit | Token or SameSite cookie |
| SEC-008 | Horizontal privilege escalation | User A cannot access User B data |

### 7.3 Data Validation

| Test ID | Description | Expected |
|---------|-------------|----------|
| SEC-009 | Negative quantity in invoice | Rejected |
| SEC-010 | Oversized input (e.g. 10MB string) | Truncated or rejected |
| SEC-011 | File upload (if any) | Type and size validation |

---

## 8. Performance Testing

### 8.1 Basic Load

| Test ID | Metric | Target |
|---------|--------|--------|
| PERF-001 | Login response time | < 2 s |
| PERF-002 | Product list (100 items) | < 3 s |
| PERF-003 | Invoice creation | < 2 s |
| PERF-004 | Report generation | < 5 s |

### 8.2 Database

| Test ID | Check |
|---------|-------|
| PERF-005 | Indexes on id, organization_id, foreign keys |
| PERF-006 | No N+1 queries in product/invoice lists |
| PERF-007 | Pagination used for large result sets |

---

## 9. Test Execution Summary

### 9.1 Summary Template

| Category | Total | Passed | Failed | Blocked | Pass % |
|----------|-------|--------|--------|---------|--------|
| Black Box – Auth | 6 | — | — | — | — |
| Black Box – Products | 5 | — | — | — | — |
| Black Box – Stock | 5 | — | — | — | — |
| Black Box – Invoice | 5 | — | — | — | — |
| Black Box – RBAC | 5 | — | — | — | — |
| White Box | 14 | — | — | — | — |
| Integration | 8 | — | — | — | — |
| Security | 11 | — | — | — | — |
| Performance | 7 | — | — | — | — |
| **Total** | **—** | — | — | — | — |

### 9.2 Defect Log Template

| ID | Module | Description | Severity | Status |
|----|--------|-------------|----------|--------|
| DEF-001 | — | — | High / Medium / Low | Open / Fixed / Deferred |

---

## 10. Appendix

### 10.1 Module vs Page Mapping

| Module | Primary Page(s) |
|--------|-----------------|
| Authentication | /pages/login.php, /pages/register.php, /pages/logout.php |
| Products | /pages/products.php, /pages/product-form.php |
| Categories | /pages/categories.php, /pages/category-form.php |
| Brands | /pages/brands.php, /pages/brand-form.php |
| Stock In | /pages/stock-in.php |
| Stock Out | /pages/stock-out.php |
| Stock Transfer | /pages/stock-transfers.php |
| Stock Adjustments | /pages/stock-adjustments.php |
| Invoices | /pages/invoices.php, /pages/invoice-form.php |
| Customers | /pages/customers.php |
| Suppliers | /pages/suppliers.php |
| Expenses | /pages/expenses.php |
| Users | /pages/users.php |
| Roles | /pages/roles.php |
| Reports | /pages/reports.php |
| Settings | /pages/settings.php |
| Activity Logs | /pages/activity-logs.php |

### 10.2 Permissions Reference

| Permission | Module | Description |
|------------|--------|-------------|
| view_admin_dashboard | dashboard | Admin dashboard |
| view_store_dashboard | dashboard | Store dashboard |
| view_products | products | View products |
| create_products | products | Create products |
| edit_products | products | Edit products |
| delete_products | products | Delete products |
| view_stock | inventory | View stock |
| stock_in | inventory | Stock in |
| stock_out | inventory | Stock out |
| create_invoice | sales | Create invoice |
| give_discount | sales | Apply discount |
| view_users | users | View users |
| assign_roles | users | Assign roles |
| view_settings | settings | View settings |

### 10.3 Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 1.0 | Feb 2026 | — | Initial full testing documentation |

---

*End of Testing Documentation*
