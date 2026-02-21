# 📚 STOCKSATHI - Comprehensive RBAC Documentation
## Role-Based Access Control, Module Analysis, Bug Report & Test Cases

**Document Version:** 2.0  
**Created:** 2026-01-17  
**Last Updated:** 2026-01-17

---

## 📋 Table of Contents

1. [System Overview](#1-system-overview)
2. [Role Definitions & Permissions Matrix](#2-role-definitions--permissions-matrix)
3. [Dashboard Access by Role](#3-dashboard-access-by-role)
4. [Module-wise Features & Functions](#4-module-wise-features--functions)
5. [Bug Report & Issues Log](#5-bug-report--issues-log)
6. [Missing/Incomplete Modules](#6-missingincomplete-modules)
7. [Test Cases - Black Box Testing](#7-test-cases---black-box-testing)
8. [Test Cases - White Box Testing](#8-test-cases---white-box-testing)
9. [UI/UX Testing Checklist](#9-uiux-testing-checklist)
10. [Security Testing](#10-security-testing)
11. [Performance Testing](#11-performance-testing)
12. [Recommendations](#12-recommendations)

---

## 1. System Overview

### 🏗️ Architecture
```
Stocksathi/
├── _includes/          # Core PHP classes (Session, Auth, Database, RoleManager)
├── assets/             # Icons, images, logos
├── css/                # Stylesheets (design-system.css, components.css)
├── js/                 # JavaScript modules
├── pages/              # All module pages
│   └── dashboards/     # Role-specific dashboards
├── migrations/         # SQL migration scripts
└── index.php           # Main entry point (redirects to role dashboard)
```

### 🛠️ Tech Stack
| Component | Technology |
|-----------|------------|
| Backend | PHP 7.4+ (Core PHP) |
| Database | MySQL/MariaDB |
| Frontend | HTML5, CSS3, JavaScript ES6 |
| Session | PHP Native Sessions |
| Authentication | Password Hashing (bcrypt) |
| Authorization | Custom RBAC System |

---

## 2. Role Definitions & Permissions Matrix

### 🎭 Available Roles

| Role ID | Role Name | Display Name | Description |
|---------|-----------|--------------|-------------|
| 1 | `super_admin` | Super Administrator | Full system access with all permissions |
| 2 | `admin` | Administrator | Administrative access to most features |
| 3 | `store_manager` | Store Manager | Manage store operations and daily sales |
| 4 | `sales_executive` | Sales Executive | Sales and billing operations |
| 5 | `accountant` | Accountant | Finance and GST compliance |
| 6 | `warehouse_manager` | Warehouse Manager | Inventory and warehouse operations |

### 🔐 Complete Permissions List

#### Dashboard Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `view_admin_dashboard` | dashboard | view | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_store_dashboard` | dashboard | view | ✅ | ✅ | ✅ | ❌ | ❌ |
| `view_sales_dashboard` | dashboard | view | ✅ | ✅ | ✅ | ✅ | ❌ |
| `view_accountant_dashboard` | dashboard | view | ✅ | ❌ | ❌ | ❌ | ✅ |

#### Product Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `view_products` | products | view | ✅ | ✅ | ✅ | ✅ | ✅ |
| `create_products` | products | create | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_products` | products | edit | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_products` | products | delete | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_purchase_price` | products | view | ✅ | ✅ | ✅ | ❌ | ✅ |
| `edit_selling_price` | products | edit | ✅ | ✅ | ❌ | ❌ | ❌ |

#### Inventory Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `view_stock` | inventory | view | ✅ | ✅ | ✅ | ✅ | ❌ |
| `adjust_stock` | inventory | edit | ✅ | ✅ | ✅ | ❌ | ❌ |
| `transfer_stock` | inventory | edit | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_all_warehouses` | inventory | view | ✅ | ✅ | ❌ | ❌ | ❌ |
| `stock_in` | inventory | create | ✅ | ✅ | ✅ | ❌ | ❌ |
| `stock_out` | inventory | create | ✅ | ✅ | ✅ | ❌ | ❌ |

#### Sales Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `create_invoice` | sales | create | ✅ | ✅ | ✅ | ✅ | ❌ |
| `edit_invoice` | sales | edit | ✅ | ✅ | ✅ | ❌ | ❌ |
| `delete_invoice` | sales | delete | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_all_invoices` | sales | view | ✅ | ✅ | ✅ | ❌ | ✅ |
| `view_own_invoices` | sales | view | ✅ | ✅ | ✅ | ✅ | ❌ |
| `give_discount` | sales | edit | ✅ | ✅ | ✅ | ✅ | ❌ |
| `process_returns` | sales | edit | ✅ | ✅ | ✅ | ✅ | ❌ |
| `create_quotation` | sales | create | ✅ | ✅ | ✅ | ✅ | ❌ |
| `view_quotations` | sales | view | ✅ | ✅ | ✅ | ✅ | ❌ |

#### Customer Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `view_customers` | customers | view | ✅ | ✅ | ✅ | ✅ | ✅ |
| `create_customers` | customers | create | ✅ | ✅ | ✅ | ✅ | ❌ |
| `edit_customers` | customers | edit | ✅ | ✅ | ✅ | ❌ | ❌ |
| `delete_customers` | customers | delete | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_customer_balance` | customers | view | ✅ | ✅ | ✅ | ✅ | ✅ |

#### Supplier Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `view_suppliers` | suppliers | view | ✅ | ✅ | ✅ | ❌ | ✅ |
| `create_suppliers` | suppliers | create | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_suppliers` | suppliers | edit | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_suppliers` | suppliers | delete | ✅ | ✅ | ❌ | ❌ | ❌ |

#### Expense Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `view_expenses` | expenses | view | ✅ | ✅ | ✅ | ❌ | ✅ |
| `create_expenses` | expenses | create | ✅ | ✅ | ✅ | ❌ | ✅ |
| `approve_expenses` | expenses | approve | ✅ | ✅ | ❌ | ❌ | ✅ |
| `delete_expenses` | expenses | delete | ✅ | ✅ | ❌ | ❌ | ❌ |

#### Report Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `view_sales_reports` | reports | view | ✅ | ✅ | ✅ | ✅ | ✅ |
| `view_purchase_reports` | reports | view | ✅ | ✅ | ❌ | ❌ | ✅ |
| `view_stock_reports` | reports | view | ✅ | ✅ | ✅ | ❌ | ❌ |
| `view_financial_reports` | reports | view | ✅ | ✅ | ❌ | ❌ | ✅ |
| `view_gst_reports` | reports | view | ✅ | ✅ | ❌ | ❌ | ✅ |
| `view_profit_loss` | reports | view | ✅ | ✅ | ❌ | ❌ | ✅ |

#### User Management Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `view_users` | users | view | ✅ | ✅ | ❌ | ❌ | ❌ |
| `create_users` | users | create | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_users` | users | edit | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_users` | users | delete | ✅ | ❌ | ❌ | ❌ | ❌ |
| `assign_roles` | users | edit | ✅ | ✅ | ❌ | ❌ | ❌ |

#### Settings Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `view_settings` | settings | view | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_settings` | settings | edit | ✅ | ❌ | ❌ | ❌ | ❌ |

#### HRM Permissions
| Permission Name | Module | Action | Super Admin | Admin | Store Manager | Sales Exec | Accountant |
|-----------------|--------|--------|:-----------:|:-----:|:-------------:|:----------:|:----------:|
| `view_employees` | hrm | view | ✅ | ✅ | ✅ | ❌ | ❌ |
| `manage_employees` | hrm | edit | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_attendance` | hrm | view | ✅ | ✅ | ✅ | ❌ | ❌ |
| `manage_attendance` | hrm | edit | ✅ | ✅ | ❌ | ❌ | ❌ |
| `view_leave` | hrm | view | ✅ | ✅ | ✅ | ❌ | ❌ |
| `manage_leave` | hrm | edit | ✅ | ✅ | ❌ | ❌ | ❌ |

---

## 3. Dashboard Access by Role

### 📊 Dashboard Routing Matrix

| Role | Primary Dashboard | Secondary Access | Redirect Path |
|------|-------------------|------------------|---------------|
| `super_admin` | Super Admin Dashboard | Admin + Sales | `/pages/dashboards/super-admin.php` |
| `admin` | Admin Dashboard | Sales Dashboard | `/pages/dashboards/admin.php` |
| `store_manager` | Store Manager Dashboard | None | `/pages/dashboards/store-manager.php` |
| `sales_executive` | Sales Executive Dashboard | None | `/pages/dashboards/sales-executive.php` |
| `accountant` | Accountant Dashboard | Expenses (fallback) | `/pages/dashboards/accountant.php` |

### 🗂️ Dashboard Files Status

| Dashboard | File Path | Status | Notes |
|-----------|-----------|--------|-------|
| Super Admin | `/pages/dashboards/super-admin.php` | ✅ Complete | Financial overview, KPIs, Quick actions |
| Admin | `/pages/dashboards/admin.php` | ✅ Complete | Full admin metrics |
| Store Manager | `/pages/dashboards/store-manager.php` | ✅ Complete | Store operations focus |
| Sales Executive | `/pages/dashboards/sales-executive.php` | ✅ Complete | Sales targets, invoices |
| Accountant | `/pages/dashboards/accountant.php` | ❌ **MISSING** | Fallback to expenses.php |

---

## 4. Module-wise Features & Functions

### 📦 Products Module (`/pages/products.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Products | ✅ Working | Pagination, search, filters |
| View Product Details | ✅ Working | `/pages/product-details.php` |
| Create Product | ✅ Working | `/pages/product-form.php` |
| Edit Product | ✅ Working | Form pre-fills data |
| Delete Product | ✅ Working | Soft delete with confirmation |
| Image Upload | ⚠️ Partial | Path handling may have issues |

### 📂 Categories Module (`/pages/categories.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Categories | ✅ Working | Tree structure |
| Create Category | ✅ Working | Parent category support |
| Edit Category | ✅ Working | Modal-based |
| Delete Category | ✅ Working | Check for child categories |

### 🏷️ Brands Module (`/pages/brands.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Brands | ✅ Working | Basic CRUD |
| Create Brand | ✅ Working | Modal form |
| Edit Brand | ✅ Working | Inline edit |
| Delete Brand | ✅ Working | Dependency check |

### 📥 Stock In Module (`/pages/stock-in.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Stock In Entries | ✅ Working | Date filters |
| Create Stock In | ✅ Working | Product selection dropdown |
| Edit Stock In | ⚠️ Partial | Status issues |
| Delete Stock In | ❌ Missing | No delete option |

### 📤 Stock Out Module (`/pages/stock-out.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Stock Out | ✅ Working | Similar to Stock In |
| Create Stock Out | ✅ Working | Reason dropdown |
| Edit Stock Out | ⚠️ Partial | Similar issues |
| Delete Stock Out | ❌ Missing | No delete option |

### 🔄 Stock Transfers Module (`/pages/stock-transfers.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Transfers | ✅ Working | From/To warehouses |
| Create Transfer | ✅ Working | Warehouse selection |
| Edit Transfer | ❌ Missing | No edit functionality |
| Status Update | ⚠️ Partial | Status change needs work |

### 📊 Stock Adjustments Module (`/pages/stock-adjustments.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Adjustments | ✅ Working | Addition/Subtraction types |
| Create Adjustment | ✅ Working | Reason specification |
| Edit Adjustment | ❌ Missing | No edit option |
| Delete Adjustment | ❌ Missing | No delete option |

### 🧾 Invoices Module (`/pages/invoices.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Invoices | ✅ Working | Status filters, date range |
| View Invoice Details | ✅ Working | `/pages/invoice-details.php` |
| Create Invoice | ✅ Working | `/pages/invoice-form.php` - Multi-item |
| Edit Invoice | ⚠️ Partial | Only draft invoices |
| Delete Invoice | ⚠️ Partial | Only draft invoices |
| Print/PDF Invoice | ✅ Working | `/pages/invoice-pdf.php` |
| Payment Recording | ⚠️ Missing | No payment update |

### 📋 Quotations Module (`/pages/quotations.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Quotations | ✅ Working | Status filters |
| Create Quotation | ⚠️ Partial | Form exists but needs testing |
| Edit Quotation | ⚠️ Partial | - |
| Convert to Invoice | ❌ Missing | Critical missing feature |
| Send Email | ❌ Missing | No email integration |

### ↩️ Sales Returns Module (`/pages/sales-returns.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Returns | ✅ Working | Status tracking |
| Create Return | ⚠️ Partial | Item selection issues |
| Process Refund | ❌ Missing | No refund processing |
| Approve/Reject | ⚠️ Partial | Status update works |

### 👥 Customers Module (`/pages/customers.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Customers | ✅ Working | Search, filters |
| Create Customer | ✅ Working | Modal form |
| Edit Customer | ✅ Working | Edit modal |
| Delete Customer | ✅ Working | With confirmation |
| View Balance | ✅ Working | Outstanding balance shown |
| View History | ❌ Missing | No transaction history |

### 🏭 Suppliers Module (`/pages/suppliers.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Suppliers | ✅ Working | Basic list |
| Create Supplier | ✅ Working | Full form |
| Edit Supplier | ✅ Working | - |
| Delete Supplier | ✅ Working | - |

### 💰 Expenses Module (`/pages/expenses.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Expenses | ✅ Working | Category filters |
| Create Expense | ✅ Working | Modal form |
| Edit Expense | ✅ Working | - |
| Delete Expense | ✅ Working | - |
| Approve Expense | ⚠️ Partial | Logic exists but needs testing |
| Receipt Upload | ❌ Missing | Field exists, upload broken |

### 🎁 Promotions Module (`/pages/promotions.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Promotions | ✅ Working | Active/Expired filters |
| Create Promotion | ✅ Working | Percentage/Fixed types |
| Edit Promotion | ✅ Working | - |
| Delete Promotion | ✅ Working | - |
| Apply to Invoice | ❌ Missing | Not integrated with invoices |

### 🏢 Stores Module (`/pages/stores.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Stores | ✅ Working | - |
| Create Store | ✅ Working | `/pages/store-form.php` |
| Edit Store | ✅ Working | - |
| Delete Store | ✅ Working | - |

### 🏪 Warehouses Module (`/pages/warehouses.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Warehouses | ✅ Working | Capacity shown |
| Create Warehouse | ✅ Working | - |
| Edit Warehouse | ✅ Working | - |
| Delete Warehouse | ✅ Working | - |

### 👨‍💼 Employees Module (`/pages/employees.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Employees | ✅ Working | Department filter |
| Create Employee | ✅ Working | `/pages/employee-form.php` |
| Edit Employee | ✅ Working | - |
| Delete Employee | ⚠️ Partial | Soft delete |
| Link to User | ⚠️ Partial | User association |

### 🏛️ Departments Module (`/pages/departments.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Departments | ✅ Working | - |
| Create Department | ✅ Working | `/pages/department-form.php` |
| Edit Department | ✅ Working | - |
| Delete Department | ✅ Working | - |

### 📅 Attendance Module (`/pages/attendance.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| View Attendance | ✅ Working | Date-wise view |
| Mark Attendance | ✅ Working | Check-in/Check-out |
| Edit Attendance | ⚠️ Partial | Limited editing |
| Attendance Report | ❌ Missing | No report export |

### 🌴 Leave Management Module (`/pages/leave-management.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| View Leave Requests | ⚠️ Partial | Basic view only |
| Apply Leave | ❌ Missing | No leave application form |
| Approve/Reject | ❌ Missing | No approval workflow |
| Leave Balance | ❌ Missing | No balance tracking |

### 👤 Users Module (`/pages/users.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Users | ✅ Working | Role filter |
| Create User | ✅ Working | Role assignment |
| Edit User | ✅ Working | - |
| Delete User | ⚠️ Partial | Super admin only |
| Reset Password | ❌ Missing | No password reset |
| Activate/Deactivate | ✅ Working | Status toggle |

### 🔒 Roles Module (`/pages/roles.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| List Roles | ✅ Working | - |
| View Role Permissions | ✅ Working | - |
| Create Role | ✅ Working | - |
| Edit Role | ✅ Working | Permission assignment |
| Delete Role | ⚠️ Partial | Check for assigned users |

### 📈 Reports Module (`/pages/reports.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| Sales Report | ✅ Working | Date range |
| Stock Report | ✅ Working | Low stock highlights |
| Financial Report | ⚠️ Partial | Basic calculations |
| GST Report | ⚠️ Partial | Structure exists |
| Profit/Loss Report | ⚠️ Partial | - |
| Export PDF | ❌ Missing | No export functionality |
| Export Excel | ❌ Missing | No export functionality |

### ⚙️ Settings Module (`/pages/settings.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| General Settings | ✅ Working | Company info |
| Invoice Settings | ✅ Working | Prefix, terms |
| Tax Settings | ✅ Working | Default rates |
| Theme Settings | ✅ Working | Dark/Light mode |
| Backup | ❌ Missing | No database backup |
| Email Settings | ❌ Missing | No email config |

### 📜 Activity Logs Module (`/pages/activity-logs.php`)
| Feature | Status | Notes |
|---------|--------|-------|
| View Logs | ✅ Working | User, module filters |
| Search Logs | ✅ Working | - |
| Export Logs | ❌ Missing | - |
| Clear Old Logs | ❌ Missing | - |

---

## 5. Bug Report & Issues Log

### 🔴 Critical Bugs

| Bug ID | Module | Description | Severity | Status |
|--------|--------|-------------|----------|--------|
| BUG-001 | Authentication | Login credentials shown on login page (security risk) | 🔴 High | Open |
| BUG-002 | Dashboards | Accountant dashboard file missing - redirects to expenses | 🔴 High | Open |
| BUG-003 | Session | Session might not properly synchronize role after role change | 🟠 Medium | Open |
| BUG-004 | Sidebar | Sidebar menu items not permission-controlled (all shown to all roles) | 🔴 High | Open |

### 🟠 Medium Bugs

| Bug ID | Module | Description | Severity | Status |
|--------|--------|-------------|----------|--------|
| BUG-005 | Products | Image upload path handling may not work on all servers | 🟠 Medium | Open |
| BUG-006 | Invoice | Cannot update payment status after creation | 🟠 Medium | Open |
| BUG-007 | Stock In/Out | No delete functionality for stock entries | 🟠 Medium | Open |
| BUG-008 | Quotations | Convert to Invoice not implemented | 🟠 Medium | Open |
| BUG-009 | Sales Returns | Refund processing not functional | 🟠 Medium | Open |
| BUG-010 | Leave Mgmt | Leave application form completely missing | 🟠 Medium | Open |

### 🟡 Low Bugs

| Bug ID | Module | Description | Severity | Status |
|--------|--------|-------------|----------|--------|
| BUG-011 | UI | Mobile menu toggle animation glitchy | 🟡 Low | Open |
| BUG-012 | Reports | Charts don't resize properly on window resize | 🟡 Low | Open |
| BUG-013 | Settings | Theme preference not persisting across sessions | 🟡 Low | Open |
| BUG-014 | Activity Log | User agent parsing shows raw string | 🟡 Low | Open |
| BUG-015 | Pagination | Page count sometimes shows NaN on empty data | 🟡 Low | Open |

---

## 6. Missing/Incomplete Modules

### ❌ Completely Missing

| Module | Priority | Notes |
|--------|----------|-------|
| Accountant Dashboard | 🔴 High | Dashboard file not created |
| Purchase Orders | 🔴 High | No purchase order system |
| Purchase Returns | 🟠 Medium | No return to supplier |
| Payments Module | 🔴 High | No payment recording for invoices |
| Email Integration | 🟠 Medium | No email sending capability |
| Barcode Scanner | 🟡 Low | Barcode field exists but no scanner |
| Report Export | 🟠 Medium | No PDF/Excel export |
| Database Backup | 🟠 Medium | No backup feature |
| Notification System | 🟠 Medium | No real-time notifications |
| Multi-language | 🟡 Low | English only |

### ⚠️ Partially Implemented

| Module | Missing Features |
|--------|------------------|
| Leave Management | Leave balance, Apply form, Approval workflow |
| Promotions | Invoice integration, Auto-apply logic |
| Quotations | Convert to invoice, Email sending |
| Sales Returns | Refund processing, Stock update |
| Attendance | Report export, Overtime calculation |

---

## 7. Test Cases - Black Box Testing

### 🔐 TC-001: Authentication Module

| Test Case ID | TC-AUTH-001 |
|--------------|-------------|
| **Title** | Successful Login with Valid Credentials |
| **Pre-conditions** | User account exists in database |
| **Test Data** | Email: admin@stocksathi.com, Password: admin123 |
| **Steps** | 1. Navigate to /pages/login.php<br>2. Enter email<br>3. Enter password<br>4. Click Sign In |
| **Expected Result** | User redirected to role-appropriate dashboard |
| **Priority** | High |

| Test Case ID | TC-AUTH-002 |
|--------------|-------------|
| **Title** | Login Failure with Invalid Password |
| **Pre-conditions** | Valid username exists |
| **Test Data** | Email: admin@stocksathi.com, Password: wrongpassword |
| **Steps** | 1. Navigate to /pages/login.php<br>2. Enter email<br>3. Enter wrong password<br>4. Click Sign In |
| **Expected Result** | Error message "Invalid credentials" displayed |
| **Priority** | High |

| Test Case ID | TC-AUTH-003 |
|--------------|-------------|
| **Title** | Session Persistence After Login |
| **Pre-conditions** | User logged in successfully |
| **Test Data** | Any valid user |
| **Steps** | 1. Login successfully<br>2. Close browser tab<br>3. Open new tab with protected page |
| **Expected Result** | User remains logged in within session timeout |
| **Priority** | Medium |

| Test Case ID | TC-AUTH-004 |
|--------------|-------------|
| **Title** | Logout Functionality |
| **Pre-conditions** | User is logged in |
| **Test Data** | Any logged in user |
| **Steps** | 1. Click Logout in sidebar<br>2. Confirm logout dialog |
| **Expected Result** | User redirected to login page, session destroyed |
| **Priority** | High |

| Test Case ID | TC-AUTH-005 |
|--------------|-------------|
| **Title** | Role-Based Dashboard Redirect |
| **Pre-conditions** | Users with different roles exist |
| **Test Data** | super_admin, admin, sales_executive |
| **Steps** | 1. Login with each role<br>2. Observe redirect URL |
| **Expected Result** | Each role sees their specific dashboard |
| **Priority** | High |

### 📦 TC-002: Products Module

| Test Case ID | TC-PROD-001 |
|--------------|-------------|
| **Title** | Create Product with All Fields |
| **Pre-conditions** | User has `create_products` permission |
| **Test Data** | Name: Test Product, SKU: TEST-001, Price: 1000 |
| **Steps** | 1. Navigate to Products<br>2. Click Add Product<br>3. Fill all fields<br>4. Save |
| **Expected Result** | Product created, appears in list |
| **Priority** | High |

| Test Case ID | TC-PROD-002 |
|--------------|-------------|
| **Title** | Create Product with Minimum Required Fields |
| **Pre-conditions** | User has `create_products` permission |
| **Test Data** | Name only |
| **Steps** | 1. Navigate to Products<br>2. Click Add Product<br>3. Fill only name<br>4. Save |
| **Expected Result** | Product created with default values |
| **Priority** | Medium |

| Test Case ID | TC-PROD-003 |
|--------------|-------------|
| **Title** | Duplicate SKU Validation |
| **Pre-conditions** | Product with SKU exists |
| **Test Data** | Existing SKU: TEST-001 |
| **Steps** | 1. Create new product<br>2. Use existing SKU<br>3. Save |
| **Expected Result** | Error message about duplicate SKU |
| **Priority** | High |

| Test Case ID | TC-PROD-004 |
|--------------|-------------|
| **Title** | Edit Product |
| **Pre-conditions** | Product exists, user has `edit_products` |
| **Test Data** | Change price from 1000 to 1500 |
| **Steps** | 1. Find product<br>2. Click Edit<br>3. Change price<br>4. Save |
| **Expected Result** | Price updated in database and list |
| **Priority** | High |

| Test Case ID | TC-PROD-005 |
|--------------|-------------|
| **Title** | Delete Product |
| **Pre-conditions** | Product exists, user has `delete_products` |
| **Test Data** | Any product without invoice references |
| **Steps** | 1. Find product<br>2. Click Delete<br>3. Confirm |
| **Expected Result** | Product removed from list |
| **Priority** | High |

| Test Case ID | TC-PROD-006 |
|--------------|-------------|
| **Title** | Search Products |
| **Pre-conditions** | Products exist |
| **Test Data** | Search term matching product name |
| **Steps** | 1. Type in search box<br>2. Observe results |
| **Expected Result** | Only matching products shown |
| **Priority** | Medium |

| Test Case ID | TC-PROD-007 |
|--------------|-------------|
| **Title** | Filter by Category |
| **Pre-conditions** | Products in multiple categories |
| **Test Data** | Category: Electronics |
| **Steps** | 1. Select category filter<br>2. Apply |
| **Expected Result** | Only products in selected category shown |
| **Priority** | Medium |

### 🧾 TC-003: Invoice Module

| Test Case ID | TC-INV-001 |
|--------------|-------------|
| **Title** | Create Invoice with Single Item |
| **Pre-conditions** | Customer and product exist |
| **Test Data** | Customer: Any, Product: Any with stock |
| **Steps** | 1. Click Create Invoice<br>2. Select customer<br>3. Add product<br>4. Save |
| **Expected Result** | Invoice created with correct totals |
| **Priority** | High |

| Test Case ID | TC-INV-002 |
|--------------|-------------|
| **Title** | Create Invoice with Multiple Items |
| **Pre-conditions** | Multiple products with stock |
| **Test Data** | 3 different products |
| **Steps** | 1. Create invoice<br>2. Add 3 products<br>3. Set quantities<br>4. Save |
| **Expected Result** | Invoice with 3 line items, correct total |
| **Priority** | High |

| Test Case ID | TC-INV-003 |
|--------------|-------------|
| **Title** | Invoice Tax Calculation |
| **Pre-conditions** | Product with 18% tax |
| **Test Data** | Product price: 1000, Quantity: 2 |
| **Steps** | 1. Create invoice<br>2. Add product<br>3. Set quantity = 2 |
| **Expected Result** | Subtotal: 2000, Tax: 360, Total: 2360 |
| **Priority** | High |

| Test Case ID | TC-INV-004 |
|--------------|-------------|
| **Title** | Invoice with Discount |
| **Pre-conditions** | User has `give_discount` permission |
| **Test Data** | 10% discount |
| **Steps** | 1. Create invoice<br>2. Add items<br>3. Apply 10% discount |
| **Expected Result** | Discount reflected in total |
| **Priority** | Medium |

| Test Case ID | TC-INV-005 |
|--------------|-------------|
| **Title** | Stock Deduction on Invoice |
| **Pre-conditions** | Product stock: 50 |
| **Test Data** | Invoice quantity: 5 |
| **Steps** | 1. Note initial stock<br>2. Create invoice with 5 units<br>3. Check product stock |
| **Expected Result** | Product stock now 45 |
| **Priority** | High |

| Test Case ID | TC-INV-006 |
|--------------|-------------|
| **Title** | Insufficient Stock Warning |
| **Pre-conditions** | Product stock: 10 |
| **Test Data** | Invoice quantity: 15 |
| **Steps** | 1. Create invoice<br>2. Add product with qty 15 |
| **Expected Result** | Warning about insufficient stock |
| **Priority** | High |

| Test Case ID | TC-INV-007 |
|--------------|-------------|
| **Title** | Print/PDF Invoice |
| **Pre-conditions** | Invoice exists |
| **Test Data** | Any paid invoice |
| **Steps** | 1. Open invoice details<br>2. Click Print/PDF |
| **Expected Result** | PDF opens/downloads with correct data |
| **Priority** | Medium |

### 👥 TC-004: Customer Module

| Test Case ID | TC-CUST-001 |
|--------------|-------------|
| **Title** | Create Customer with Required Fields |
| **Pre-conditions** | User has `create_customers` permission |
| **Test Data** | Name: John Doe, Phone: 9876543210 |
| **Steps** | 1. Navigate to Customers<br>2. Click Add<br>3. Fill required fields<br>4. Save |
| **Expected Result** | Customer created and visible in list |
| **Priority** | High |

| Test Case ID | TC-CUST-002 |
|--------------|-------------|
| **Title** | Duplicate Email Validation |
| **Pre-conditions** | Customer with email exists |
| **Test Data** | Existing email |
| **Steps** | 1. Create new customer<br>2. Use existing email<br>3. Save |
| **Expected Result** | Error about duplicate email |
| **Priority** | Medium |

| Test Case ID | TC-CUST-003 |
|--------------|-------------|
| **Title** | Edit Customer |
| **Pre-conditions** | Customer exists |
| **Test Data** | Update phone number |
| **Steps** | 1. Find customer<br>2. Click Edit<br>3. Change phone<br>4. Save |
| **Expected Result** | Phone updated in database |
| **Priority** | Medium |

| Test Case ID | TC-CUST-004 |
|--------------|-------------|
| **Title** | Delete Customer with Balance |
| **Pre-conditions** | Customer has outstanding balance |
| **Test Data** | Customer with balance > 0 |
| **Steps** | 1. Find customer<br>2. Try to delete |
| **Expected Result** | Warning about outstanding balance |
| **Priority** | High |

### 🔑 TC-005: Role-Based Access Control

| Test Case ID | TC-RBAC-001 |
|--------------|-------------|
| **Title** | Sales Executive Cannot Delete Products |
| **Pre-conditions** | User with sales_executive role |
| **Test Data** | sales1@stocksathi.com |
| **Steps** | 1. Login as Sales Exec<br>2. Navigate to Products<br>3. Look for Delete button |
| **Expected Result** | Delete button hidden or disabled |
| **Priority** | High |

| Test Case ID | TC-RBAC-002 |
|--------------|-------------|
| **Title** | Admin Cannot Delete Users |
| **Pre-conditions** | User with admin role |
| **Test Data** | admin@stocksathi.com |
| **Steps** | 1. Login as Admin<br>2. Navigate to Users<br>3. Try to delete user |
| **Expected Result** | Delete button hidden or Access denied |
| **Priority** | High |

| Test Case ID | TC-RBAC-003 |
|--------------|-------------|
| **Title** | Super Admin Access to All Features |
| **Pre-conditions** | Super admin user |
| **Test Data** | superadmin@stocksathi.com |
| **Steps** | 1. Login as Super Admin<br>2. Navigate to each module |
| **Expected Result** | All features accessible |
| **Priority** | High |

| Test Case ID | TC-RBAC-004 |
|--------------|-------------|
| **Title** | Sales Executive Limited Invoice View |
| **Pre-conditions** | Multiple invoices by different users |
| **Test Data** | Sales exec login |
| **Steps** | 1. Login as Sales Exec<br>2. View Invoices |
| **Expected Result** | Only own invoices visible (if `view_own_invoices` only) |
| **Priority** | Medium |

| Test Case ID | TC-RBAC-005 |
|--------------|-------------|
| **Title** | Direct URL Access Check |
| **Pre-conditions** | Logged in as Sales Executive |
| **Test Data** | Navigate to /pages/users.php directly |
| **Steps** | 1. Login as Sales Exec<br>2. Type /pages/users.php in URL |
| **Expected Result** | Access Denied or redirect |
| **Priority** | High |

### 📊 TC-006: Stock Management

| Test Case ID | TC-STOCK-001 |
|--------------|-------------|
| **Title** | Stock In Entry Creation |
| **Pre-conditions** | Product exists |
| **Test Data** | Product: Any, Quantity: 50 |
| **Steps** | 1. Navigate to Stock In<br>2. Select product<br>3. Enter quantity<br>4. Save |
| **Expected Result** | Stock increased by 50 |
| **Priority** | High |

| Test Case ID | TC-STOCK-002 |
|--------------|-------------|
| **Title** | Stock Out Validation |
| **Pre-conditions** | Product stock: 30 |
| **Test Data** | Stock Out quantity: 40 |
| **Steps** | 1. Navigate to Stock Out<br>2. Select product<br>3. Enter 40<br>4. Try to save |
| **Expected Result** | Error about insufficient stock |
| **Priority** | High |

| Test Case ID | TC-STOCK-003 |
|--------------|-------------|
| **Title** | Stock Transfer Between Warehouses |
| **Pre-conditions** | Stock in source warehouse |
| **Test Data** | From: WH-001, To: WH-002, Qty: 10 |
| **Steps** | 1. Create transfer<br>2. Select warehouses<br>3. Set quantity<br>4. Complete |
| **Expected Result** | Source decreased, destination increased |
| **Priority** | High |

| Test Case ID | TC-STOCK-004 |
|--------------|-------------|
| **Title** | Low Stock Alert |
| **Pre-conditions** | Product with min_stock_level = 10 |
| **Test Data** | Reduce stock to 5 |
| **Steps** | 1. Create stock out reducing to 5<br>2. Check dashboard |
| **Expected Result** | Low stock count increases on dashboard |
| **Priority** | Medium |

---

## 8. Test Cases - White Box Testing

### 🔍 Session Management Tests

| Test Case ID | TC-WB-SESS-001 |
|--------------|----------------|
| **Title** | Session Start Function |
| **Function** | `Session::start()` |
| **Test Data** | Session already started / not started |
| **Expected** | No duplicate session_start() calls |
| **Code Path** | Lines 10-14 in Session.php |

| Test Case ID | TC-WB-SESS-002 |
|--------------|----------------|
| **Title** | Session User Data Retrieval |
| **Function** | `Session::getUser()` |
| **Test Data** | Logged in / not logged in |
| **Expected** | Returns user array or null |
| **Code Path** | Lines 119-130 in Session.php |

### 🔐 Permission Check Tests

| Test Case ID | TC-WB-PERM-001 |
|--------------|----------------|
| **Title** | Super Admin Permission Check |
| **Function** | `PermissionMiddleware::hasPermission()` |
| **Test Data** | Role: super_admin, Any permission |
| **Expected** | Always returns true |
| **Code Path** | Lines 21-24 in PermissionMiddleware.php |

| Test Case ID | TC-WB-PERM-002 |
|--------------|----------------|
| **Title** | Database Permission Lookup |
| **Function** | `PermissionMiddleware::hasPermission()` |
| **Test Data** | Role: sales_executive, Permission: create_invoice |
| **Expected** | Returns true (has permission) |
| **Code Path** | Lines 26-50 in PermissionMiddleware.php |

| Test Case ID | TC-WB-PERM-003 |
|--------------|----------------|
| **Title** | Invalid Role Handling |
| **Function** | `PermissionMiddleware::hasPermission()` |
| **Test Data** | Role: nonexistent_role |
| **Expected** | Returns false, no errors |
| **Code Path** | Lines 33-35 in PermissionMiddleware.php |

### 🔑 Authentication Flow Tests

| Test Case ID | TC-WB-AUTH-001 |
|--------------|----------------|
| **Title** | Password Verification |
| **Function** | `AuthHelper::verifyPassword()` |
| **Test Data** | Plain: password123, Hash: bcrypt hash |
| **Expected** | Returns true for correct password |
| **Code Path** | Lines 30-32 in AuthHelper.php |

| Test Case ID | TC-WB-AUTH-002 |
|--------------|----------------|
| **Title** | Login Session Setup |
| **Function** | `AuthHelper::login()` |
| **Test Data** | Valid credentials |
| **Expected** | Session::setUser() called with correct params |
| **Code Path** | Lines 56-57 in AuthHelper.php |

| Test Case ID | TC-WB-AUTH-003 |
|--------------|----------------|
| **Title** | Last Login Update |
| **Function** | `AuthHelper::login()` |
| **Test Data** | Successful login |
| **Expected** | last_login column updated |
| **Code Path** | Lines 59-61 in AuthHelper.php |

### 🗄️ Database Query Tests

| Test Case ID | TC-WB-DB-001 |
|--------------|--------------|
| **Title** | Query Parameterization |
| **Function** | `Database::query()` |
| **Test Data** | SQL with parameters |
| **Expected** | Prepared statements used (PDO) |
| **Code Path** | database.php |

| Test Case ID | TC-WB-DB-002 |
|--------------|--------------|
| **Title** | Exception Handling on Failed Query |
| **Function** | `Database::queryOne()` |
| **Test Data** | Invalid SQL query |
| **Expected** | Exception caught, logged, returns null |
| **Code Path** | database.php |

---

## 9. UI/UX Testing Checklist

### 📱 Responsive Design

| ID | Test Item | Mobile | Tablet | Desktop | Status |
|----|-----------|:------:|:------:|:-------:|:------:|
| UI-001 | Sidebar collapse | ✅ | ✅ | ✅ | Pass |
| UI-002 | Table horizontal scroll | ✅ | ✅ | N/A | Pass |
| UI-003 | Modal responsive width | ⚠️ | ✅ | ✅ | Partial |
| UI-004 | Form field alignment | ✅ | ✅ | ✅ | Pass |
| UI-005 | Chart resize | ❌ | ⚠️ | ✅ | Fail |
| UI-006 | Button touch targets | ✅ | ✅ | ✅ | Pass |
| UI-007 | Navigation dropdown | ⚠️ | ✅ | ✅ | Partial |

### 🎨 Visual Consistency

| ID | Test Item | Expected | Actual | Status |
|----|-----------|----------|--------|:------:|
| UI-008 | Primary color usage | #0F766E | Consistent | ✅ |
| UI-009 | Font family | System default | Consistent | ✅ |
| UI-010 | Button styles | Consistent across pages | Consistent | ✅ |
| UI-011 | Form input styles | Consistent | Consistent | ✅ |
| UI-012 | Card shadows | Subtle shadow | Consistent | ✅ |
| UI-013 | Badge colors | Role-appropriate | Consistent | ✅ |
| UI-014 | Icon sizing | 16-24px | Consistent | ✅ |

### ⌨️ Accessibility

| ID | Test Item | Status | Notes |
|----|-----------|:------:|-------|
| UI-015 | Keyboard navigation | ⚠️ | Tab order needs work |
| UI-016 | Focus indicators | ⚠️ | Some elements missing |
| UI-017 | Color contrast | ✅ | Meets WCAG AA |
| UI-018 | Alt text for images | ⚠️ | Some missing |
| UI-019 | Form labels | ✅ | All forms labeled |
| UI-020 | Error messages | ✅ | Clear and visible |

### 🚀 Loading & Performance

| ID | Test Item | Threshold | Actual | Status |
|----|-----------|-----------|--------|:------:|
| UI-021 | Initial page load | < 3s | ~2s | ✅ |
| UI-022 | API response display | < 1s | ~500ms | ✅ |
| UI-023 | Image loading | < 2s | Varies | ⚠️ |
| UI-024 | Chart rendering | < 1s | ~800ms | ✅ |
| UI-025 | Search debounce | 300ms | Implemented | ✅ |

---

## 10. Security Testing

### 🔐 Authentication Security

| ID | Test | Status | Notes |
|----|------|:------:|-------|
| SEC-001 | Password hashing (bcrypt) | ✅ | Using PASSWORD_DEFAULT |
| SEC-002 | SQL injection prevention | ✅ | Parameterized queries |
| SEC-003 | Session fixation | ⚠️ | Consider session_regenerate |
| SEC-004 | Remember me vulnerability | ✅ | Not implemented |
| SEC-005 | Brute force protection | ❌ | No rate limiting |
| SEC-006 | CSRF protection | ❌ | No CSRF tokens |
| SEC-007 | XSS prevention | ⚠️ | Most output escaped |

### 🔒 Authorization Security

| ID | Test | Status | Notes |
|----|------|:------:|-------|
| SEC-008 | Permission enforcement | ⚠️ | Some pages not checking |
| SEC-009 | Direct URL access | ⚠️ | Some pages exposed |
| SEC-010 | API auth check | ⚠️ | Inconsistent |
| SEC-011 | Role escalation | ✅ | Only super_admin can change roles |

### 📁 Data Security

| ID | Test | Status | Notes |
|----|------|:------:|-------|
| SEC-012 | Sensitive data exposure | ⚠️ | Credentials on login page |
| SEC-013 | Error message leakage | ⚠️ | Some verbose errors |
| SEC-014 | File upload validation | ❌ | Minimal validation |
| SEC-015 | Directory traversal | ⚠️ | Needs review |

---

## 11. Performance Testing

### 📊 Load Testing Scenarios

| Scenario | Users | Duration | Expected Response |
|----------|:-----:|:--------:|:-----------------|
| Dashboard Load | 10 | 5 min | < 2s |
| Product Search | 20 | 10 min | < 1s |
| Invoice Creation | 10 | 5 min | < 3s |
| Report Generation | 5 | 3 min | < 5s |

### 💾 Database Performance

| Query Type | Expected Time | Notes |
|------------|:-------------:|-------|
| Single record lookup | < 50ms | Use indexes |
| List with pagination | < 200ms | LIMIT/OFFSET |
| Aggregate reports | < 1s | May need optimization |
| Dashboard KPIs | < 500ms | Multiple queries |

---

## 12. Recommendations

### 🔴 Priority 1 - Critical (Must Fix)

1. **Create Accountant Dashboard**
   - Create `/pages/dashboards/accountant.php`
   - Focus on financial metrics, GST, Reports

2. **Remove Login Credentials from UI**
   - Remove demo credentials from login.php
   - Create separate documentation for credentials

3. **Implement Sidebar Permission Control**
   - Hide menu items based on user permissions
   - Use `hasPermission()` or `hasAnyPermission()` checks

4. **Add CSRF Protection**
   - Implement CSRF tokens for all forms
   - Validate tokens on form submission

### 🟠 Priority 2 - High (Should Fix)

5. **Complete Leave Management Module**
   - Create leave application form
   - Implement approval workflow
   - Add leave balance tracking

6. **Add Payment Recording to Invoices**
   - Create payment entry form
   - Update payment_status automatically
   - Record partial payments

7. **Implement Quotation to Invoice Conversion**
   - Add "Convert to Invoice" button
   - Copy all quotation items
   - Update quotation status

8. **Add Report Export Functionality**
   - Implement PDF export using TCPDF/FPDF
   - Implement Excel export using PhpSpreadsheet

### 🟡 Priority 3 - Medium (Nice to Have)

9. **Add Rate Limiting for Login**
    - Track failed login attempts
    - Implement lockout after X failures

10. **Create Purchase Order Module**
    - PO creation workflow
    - Supplier integration

11. **Implement Notification System**
    - Low stock alerts
    - Invoice due reminders
    - Leave request notifications

12. **Add Customer Transaction History**
    - View all invoices
    - Payment history
    - Credit balance

---

## 📧 Contact

For any questions regarding this documentation or the RBAC implementation, please contact the development team.

---

**Document End**
