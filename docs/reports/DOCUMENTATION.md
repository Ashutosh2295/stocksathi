# Stocksathi - Complete Project Documentation
**Last Updated:** 2026-01-14  
**Project Path:** `c:\xampp_new\htdocs\stocksathi`

---

## 📋 Table of Contents
1. [Project Overview](#project-overview)
2. [User Roles & Permissions](#user-roles--permissions)
3. [Dashboard Configuration](#dashboard-configuration)
4. [Working Modules](#working-modules)
5. [Pending/Incomplete Modules](#pendingincomplete-modules)
6. [File Structure](#file-structure)
7. [Database Configuration](#database-configuration)
8. [Login Credentials](#login-credentials)
9. [Recent Changes Made](#recent-changes-made)
10. [Known Issues](#known-issues)

---

## 🎯 Project Overview

Stocksathi is an inventory and stock management system built with:
- **Backend:** PHP 8.x with MySQL/MariaDB
- **Frontend:** HTML, CSS, JavaScript
- **Server:** XAMPP (Apache + MySQL)
- **Database:** `stocksathi`

**URL:** `http://localhost/stocksathi/`

---

## 👥 User Roles & Permissions

### Available Roles:
| Role | Description | Dashboard Access |
|------|-------------|------------------|
| `super_admin` | Full system access | Super Admin + Admin + Sales Dashboard |
| `admin` | Administrative access | Admin + Sales Dashboard |
| `store_manager` | Store operations | Store Manager Dashboard |
| `sales_executive` | Sales operations | Sales Dashboard only |
| `accountant` | Financial operations | (Limited access) |
| `user` | Basic user | No dashboard access |

### Role-Based Sidebar Visibility:
Configured in: `_includes/sidebar.php` (Lines 32-93)

```
super_admin → Sees: Super Admin Dashboard, Admin Dashboard, Sales Dashboard
admin → Sees: Admin Dashboard, Sales Dashboard  
store_manager → Sees: Store Manager Dashboard
sales_executive → Sees: Sales Dashboard
```

---

## 📊 Dashboard Configuration

### Dashboard Files Location:
```
pages/dashboards/
├── super-admin.php      ← Super Admin Dashboard
├── admin.php            ← Admin Dashboard  
├── store-manager.php    ← Store Manager Dashboard
├── sales-executive.php  ← Sales Executive Dashboard
```

### Dashboard Access Rules:
| Dashboard | Allowed Roles | File |
|-----------|---------------|------|
| Super Admin Dashboard | `super_admin` | `super-admin.php` |
| Admin Dashboard | `super_admin`, `admin` | `admin.php` |
| Store Manager Dashboard | `store_manager` | `store-manager.php` |
| Sales Dashboard | `super_admin`, `admin`, `store_manager`, `sales_executive` | `sales-executive.php` |

---

## ✅ Working Modules

### 1. Authentication System
- **Status:** ✅ WORKING
- **Files:** 
  - `pages/login.php` - Login page
  - `pages/register.php` - Registration page
  - `api/login.php` - Login API
  - `api/register.php` - Registration API
  - `_includes/session_guard.php` - Session protection
  - `_includes/Session.php` - Session management

### 2. Dashboard System
- **Status:** ✅ WORKING
- **Features:**
  - Role-based dashboard visibility
  - Super Admin Dashboard with full analytics
  - Admin Dashboard with business metrics
  - Sales Executive Dashboard with billing interface
  - Store Manager Dashboard

### 3. Sidebar Navigation
- **Status:** ✅ WORKING
- **File:** `_includes/sidebar.php`
- **Features:**
  - Role-based menu visibility
  - Active state highlighting
  - Dropdown menus for sections

### 4. Products Module
- **Status:** ✅ WORKING
- **Files:**
  - `pages/products/products.php` - Product listing
  - `pages/products/product-form.php` - Add/Edit product
  - `api/products.php` - Products API (CRUD)

### 5. Categories Module
- **Status:** ✅ WORKING
- **Files:**
  - `pages/products/categories.php` - Categories listing
  - `api/categories.php` - Categories API

### 6. Brands Module
- **Status:** ✅ WORKING
- **Files:**
  - `pages/products/brands.php` - Brands listing
  - `api/brands.php` - Brands API

### 7. Stock Management
- **Status:** ✅ WORKING
- **Files:**
  - `pages/stock/stock-in.php` - Stock In operations
  - `pages/stock/stock-out.php` - Stock Out operations
  - `pages/stock/adjustments.php` - Stock adjustments
  - `pages/stock/transfers.php` - Stock transfers
  - `api/stock.php` - Stock API

### 8. Customers Module
- **Status:** ✅ WORKING
- **Files:**
  - `pages/sales/customers.php` - Customer listing
  - `api/customers.php` - Customers API

### 9. Invoices Module
- **Status:** ✅ WORKING
- **Files:**
  - `pages/sales/invoices.php` - Invoice listing
  - `pages/sales/invoice-form.php` - Create invoice
  - `pages/sales/quotations.php` - Quotations
  - `api/invoices.php` - Invoices API

### 10. Expenses Module
- **Status:** ✅ WORKING
- **Files:**
  - `pages/finance/expenses.php` - Expenses listing
  - `api/expenses.php` - Expenses API

### 11. Promotions Module
- **Status:** ✅ WORKING
- **Files:**
  - `pages/marketing/promotions.php` - Promotions listing
  - `api/promotions.php` - Promotions API

### 12. Quick Add Feature
- **Status:** ✅ WORKING
- **File:** `_includes/header.php`
- **Features:** Quick access dropdown for creating new items

---

## ⚠️ Pending/Incomplete Modules

### 1. Suppliers Module
- **Status:** ⚠️ PARTIAL
- **Issue:** Page exists but may need testing
- **Files:** `pages/products/suppliers.php`

### 2. Departments Module
- **Status:** ⚠️ NEEDS TESTING
- **Files:** `pages/settings/departments.php`

### 3. Users Management
- **Status:** ⚠️ PARTIAL
- **Files:** 
  - `pages/settings/users.php`
  - `pages/settings/user-form.php`

### 4. GST Configuration
- **Status:** ⚠️ NEEDS REVIEW
- **Files:** `pages/settings/gst-config.php`

### 5. Reports Module
- **Status:** ❌ NOT IMPLEMENTED
- **Expected Files:** `pages/reports/` (needs creation)

### 6. Notifications System
- **Status:** ⚠️ PARTIAL
- **Issue:** Backend may need completion

### 7. Leave Requests Module
- **Status:** ⚠️ NEEDS TESTING
- **Files:** `pages/hr/leave-requests.php`

### 8. Employees Module
- **Status:** ⚠️ NEEDS TESTING
- **Files:** `pages/hr/employees.php`

### 9. Attendance System
- **Status:** ⚠️ NEEDS TESTING
- **Files:** `pages/hr/attendance.php`

---

## 📁 File Structure

```
stocksathi/
├── _includes/                    # Shared PHP includes
│   ├── config.php               # Database configuration
│   ├── Database.php             # Database connection class
│   ├── Session.php              # Session management
│   ├── session_guard.php        # Auth protection
│   ├── header.php               # Page header with Quick Add
│   ├── sidebar.php              # Navigation sidebar
│   └── footer.php               # Page footer
│
├── api/                          # REST API endpoints
│   ├── login.php                # Authentication
│   ├── register.php             # User registration
│   ├── products.php             # Products CRUD
│   ├── categories.php           # Categories CRUD
│   ├── brands.php               # Brands CRUD
│   ├── customers.php            # Customers CRUD
│   ├── invoices.php             # Invoices CRUD
│   ├── stock.php                # Stock operations
│   ├── expenses.php             # Expenses CRUD
│   └── ...
│
├── assets/                       # Static assets
│   ├── icons/                   # SVG icons
│   └── images/                  # Images
│
├── css/                          # Stylesheets
│   ├── design-system.css        # Main design system
│   ├── sidebar.css              # Sidebar styles
│   └── ...
│
├── js/                           # JavaScript files
│   ├── api-client.js            # API client
│   └── ...
│
├── pages/                        # Frontend pages
│   ├── dashboards/              # Dashboard pages
│   │   ├── super-admin.php
│   │   ├── admin.php
│   │   ├── store-manager.php
│   │   └── sales-executive.php
│   │
│   ├── products/                # Product management
│   │   ├── products.php
│   │   ├── categories.php
│   │   └── brands.php
│   │
│   ├── stock/                   # Stock management
│   │   ├── stock-in.php
│   │   ├── stock-out.php
│   │   ├── adjustments.php
│   │   └── transfers.php
│   │
│   ├── sales/                   # Sales management
│   │   ├── invoices.php
│   │   ├── customers.php
│   │   └── quotations.php
│   │
│   ├── finance/                 # Finance management
│   │   └── expenses.php
│   │
│   ├── marketing/               # Marketing
│   │   └── promotions.php
│   │
│   ├── settings/                # Settings
│   │   ├── users.php
│   │   └── gst-config.php
│   │
│   ├── login.php                # Login page
│   └── register.php             # Registration page
│
├── migrations/                   # Database migrations
│   ├── setup_rbac.sql           # RBAC setup SQL
│   └── run_setup.php            # Setup runner
│
└── index.php                     # Main entry point
```

---

## 🗄️ Database Configuration

### Connection Details:
- **Host:** localhost
- **Database:** stocksathi
- **Username:** root
- **Password:** (empty)
- **Port:** 3306

### Key Tables:
| Table | Purpose |
|-------|---------|
| `users` | User accounts and roles |
| `products` | Product catalog |
| `categories` | Product categories |
| `brands` | Product brands |
| `customers` | Customer data |
| `invoices` | Sales invoices |
| `invoice_items` | Invoice line items |
| `stock_movements` | Stock in/out records |
| `expenses` | Business expenses |
| `promotions` | Marketing promotions |
| `permissions` | System permissions |
| `role_permissions` | Role-permission mapping |

---

## 🔐 Login Credentials

### Test Accounts:
| Email | Password | Role |
|-------|----------|------|
| `admin9@gmail.com` | `admin123` | Super Admin |
| `admin@stocksathi.com` | `admin123` | Admin |

**Note:** Role for `admin9@gmail.com` was updated to `super_admin` via SQL:
```sql
UPDATE users SET role = 'super_admin' WHERE username = 'admin9' OR email LIKE '%admin9%';
```

---

## 🔧 Recent Changes Made (This Session)

### 1. Fixed Dashboard Sidebar Visibility
**File:** `_includes/sidebar.php` (Lines 32-93)

**Changes:**
- Added Super Admin Dashboard link for `super_admin` role
- Separated role-based visibility for each dashboard
- Super Admin now sees 3 dashboards: Super Admin, Admin, Sales
- Admin sees 2 dashboards: Admin, Sales
- Store Manager sees 1 dashboard: Store Manager
- Sales Executive sees 1 dashboard: Sales

### 2. Updated Sales Executive Dashboard Access
**File:** `pages/dashboards/sales-executive.php`

**Changes:**
- Restored proper role check for `super_admin`, `admin`, `store_manager`, `sales_executive`

### 3. Updated User Role in Database
**SQL Executed:**
```sql
UPDATE users SET role = 'super_admin' WHERE username = 'admin9' OR email LIKE '%admin9%';
```

---

## ⚠️ Known Issues

### 1. Browser Caching
- **Issue:** Old sidebar may be cached
- **Solution:** Press `Ctrl + F5` to hard refresh

### 2. User Role "User" 
- **Issue:** Users with role "User" (not the 4 main roles) won't see any dashboard links
- **Solution:** Update user role to one of: `super_admin`, `admin`, `store_manager`, `sales_executive`

### 3. 404 Errors for Some URLs
- `http://localhost/stocksathi/login.php` → 404 (correct path is `/pages/login.php`)
- `http://localhost/stocksathi/logout.php` → 404 (correct path is `/api/logout.php`)

---

## 🚀 How to Test

### 1. Login as Super Admin:
1. Go to: `http://localhost/stocksathi/pages/login.php`
2. Email: `admin9@gmail.com`
3. Password: `admin123`
4. Verify sidebar shows 3 dashboard links

### 2. Test Dashboard Navigation:
1. Click "Super Admin Dashboard" → Should open super-admin.php
2. Click "Admin Dashboard" → Should open admin.php  
3. Click "Sales Dashboard" → Should open sales-executive.php

### 3. Test Other Modules:
1. Products: Click Products in sidebar
2. Stock In: Click Stock In in sidebar
3. Invoices: Click Invoices in sidebar

---

## 📞 Support

For any issues:
1. Check this documentation first
2. Verify user role in database
3. Clear browser cache (Ctrl + F5)
4. Check browser console for JavaScript errors

---

**Document Created:** 2026-01-14 01:08 AM IST
