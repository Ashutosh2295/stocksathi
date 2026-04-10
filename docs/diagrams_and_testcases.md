# StockSathi – Complete UML Diagrams & Test Cases Documentation
**Project:** StockSathi – Inventory Management System  
**Team:** Ashutosh Bhavsar, Ekta Ranghvani, Ishika Sathiya, Jeel Chauhan  
**Institution:** JG University | Semester VI  
**Document Version:** 1.0  
**Date:** February 2026

---

## Table of Contents

1. [Use Case Diagrams](#1-use-case-diagrams)
2. [Class Diagrams](#2-class-diagrams)
3. [Activity Diagrams](#3-activity-diagrams)
4. [Test Cases – Black Box Testing](#4-test-cases--black-box-testing)
5. [Test Cases – White Box Testing](#5-test-cases--white-box-testing)

---

## 1. Use Case Diagrams

### 1.1 System-Level Use Case Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────┐
│                              STOCKSATHI SYSTEM                                  │
│                                                                                 │
│  ┌──────────────────────┐  ┌────────────────────┐  ┌────────────────────────┐  │
│  │  AUTHENTICATION      │  │  PRODUCT MGMT      │  │  STOCK / INVENTORY     │  │
│  │  ● Login             │  │  ● View Products   │  │  ● Stock In            │  │
│  │  ● Logout            │  │  ● Add Product     │  │  ● Stock Out           │  │
│  │  ● Register Org      │  │  ● Edit Product    │  │  ● Stock Transfer      │  │
│  │  ● Forgot Password   │  │  ● Delete Product  │  │  ● Stock Adjustment    │  │
│  │  ● View Dashboard    │  │  ● Manage Category │  │  ● View Stock Levels   │  │
│  │                      │  │  ● Manage Brand    │  │  ● Low Stock Alert     │  │
│  └──────────────────────┘  └────────────────────┘  └────────────────────────┘  │
│                                                                                 │
│  ┌──────────────────────┐  ┌────────────────────┐  ┌────────────────────────┐  │
│  │  SALES & BILLING     │  │  PEOPLE MGMT       │  │  FINANCE               │  │
│  │  ● Create Invoice    │  │  ● Manage Customer │  │  ● Create Expense      │  │
│  │  ● View Invoices     │  │  ● Manage Supplier │  │  ● Approve Expense     │  │
│  │  ● Apply Discount    │  │  ● Manage Store    │  │  ● View Reports        │  │
│  │  ● Create Quotation  │  │  ● Manage Warehouse│  │  ● GST Calculation     │  │
│  │  ● Process Return    │  │                    │  │  ● Profit/Loss Report  │  │
│  │  ● Print/PDF Invoice │  │                    │  │                        │  │
│  └──────────────────────┘  └────────────────────┘  └────────────────────────┘  │
│                                                                                 │
│  ┌──────────────────────┐  ┌────────────────────┐  ┌────────────────────────┐  │
│  │  HR MODULE           │  │  ADMINISTRATION    │  │  SYSTEM                │  │
│  │  ● Manage Employees  │  │  ● Manage Users    │  │  ● View Activity Logs  │  │
│  │  ● Mark Attendance   │  │  ● Manage Roles    │  │  ● System Settings     │  │
│  │  ● Leave Requests    │  │  ● Assign Perms    │  │  ● Notifications       │  │
│  │  ● Manage Departments│  │  ● View Logs       │  │  ● Organization Setup  │  │
│  └──────────────────────┘  └────────────────────┘  └────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────────┘

  ACTORS:
  ┌─────────────┐ ┌──────────┐ ┌───────────────┐ ┌─────────────────┐ ┌────────────┐ ┌───────────────────┐
  │ Super Admin  │ │  Admin   │ │ Store Manager │ │ Sales Executive │ │ Accountant │ │ Warehouse Manager │
  └─────────────┘ └──────────┘ └───────────────┘ └─────────────────┘ └────────────┘ └───────────────────┘
```

### 1.2 Actor–Use Case Access Matrix

| Use Case | Super Admin | Admin | Store Manager | Sales Executive | Accountant | Warehouse Manager |
|----------|:-----------:|:-----:|:-------------:|:---------------:|:----------:|:-----------------:|
| Login / Logout | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Register Organization | ✓ | — | — | — | — | — |
| View Dashboard | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| View Products | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create/Edit Product | ✓ | ✓ | — | — | — | — |
| Delete Product | ✓ | ✓ | — | — | — | — |
| Manage Categories/Brands | ✓ | ✓ | — | — | — | — |
| Stock In | ✓ | ✓ | ✓ | — | — | ✓ |
| Stock Out | ✓ | ✓ | ✓ | — | — | ✓ |
| Stock Transfer | ✓ | ✓ | — | — | — | ✓ |
| Create Invoice | ✓ | ✓ | ✓ | ✓ | — | — |
| View All Invoices | ✓ | ✓ | ✓ | — | ✓ | — |
| View Own Invoices | ✓ | ✓ | ✓ | ✓ | — | — |
| Apply Discount | ✓ | ✓ | ✓ | ✓ | — | — |
| Create Quotation | ✓ | ✓ | ✓ | ✓ | — | — |
| Process Return | ✓ | ✓ | ✓ | ✓ | — | — |
| Manage Customers | ✓ | ✓ | ✓ | ✓ (view+create) | ✓ (view) | — |
| Manage Suppliers | ✓ | ✓ | ✓ (view) | — | ✓ (view) | ✓ (view) |
| Create Expense | ✓ | ✓ | ✓ | — | — | — |
| Approve Expense | ✓ | ✓ | — | — | ✓ | — |
| View Sales Reports | ✓ | ✓ | ✓ | ✓ | ✓ | — |
| View Financial Reports | ✓ | ✓ | — | — | ✓ | — |
| View Stock Reports | ✓ | ✓ | ✓ | — | — | ✓ |
| Manage Users | ✓ | ✓ (no delete) | — | — | — | — |
| Delete Users | ✓ | — | — | — | — | — |
| Manage Roles & Permissions | ✓ | ✓ | — | — | — | — |
| System Settings | ✓ | ✓ (view only) | — | — | — | — |
| View Activity Logs | ✓ | ✓ | ✓ | — | ✓ | ✓ |

### 1.3 Authentication Use Cases

```
                              ┌──────────────────────────┐
                              │     AUTHENTICATION       │
                              └──────────────────────────┘
                                          │
           ┌──────────────┬───────────────┼───────────────┬──────────────┐
           ▼              ▼               ▼               ▼              ▼
    ┌────────────┐ ┌────────────┐ ┌──────────────┐ ┌───────────┐ ┌──────────────┐
    │   Login    │ │  Logout    │ │  Register    │ │  Forgot   │ │    View      │
    │            │ │            │ │  Organization│ │  Password │ │  Dashboard   │
    └─────┬──────┘ └─────┬──────┘ └──────┬───────┘ └─────┬─────┘ └──────┬───────┘
          │              │               │               │              │
          ▼              ▼               ▼               ▼              ▼
   ● Validate        ● Clear         ● Create Org    ● Send OTP    ● Role-based
     credentials       session       ● Create User   ● Verify       dashboard
   ● Set session     ● Clear         ● Seed Roles      OTP          redirect
   ● Log activity      cookies      ● Seed Perms    ● Reset
   ● Redirect        ● Redirect                       Password
     to dashboard      to login

   ACTORS: All Users                  Super Admin        All Users
                                      (new signup)
```

### 1.4 Inventory Management Use Cases

```
                            ┌──────────────────────────┐
                            │   INVENTORY MANAGEMENT   │
                            └──────────────────────────┘
                                        │
          ┌─────────────┬───────────────┼───────────────┬──────────────┐
          ▼             ▼               ▼               ▼              ▼
   ┌────────────┐ ┌────────────┐ ┌──────────────┐ ┌───────────┐ ┌──────────────┐
   │  Stock In  │ │ Stock Out  │ │   Transfer   │ │ Adjustment│ │  View Stock  │
   └─────┬──────┘ └─────┬──────┘ └──────┬───────┘ └─────┬─────┘ └──────┬───────┘
         │              │               │               │              │
         ▼              ▼               ▼               ▼              ▼
   ● Select product  ● Validate    ● From → To      ● Add or      ● Filter by
   ● Set quantity      stock ≥ qty   warehouse        subtract       warehouse
   ● Choose          ● Reduce      ● Source -qty    ● Record       ● Low stock
     warehouse         stock         Dest +qty       reason          alert
   ● Log entry       ● Log entry  ● Log transfer   ● Log entry   ● Search

   ACTORS:                         ACTORS:
   Super Admin, Admin,             Super Admin, Admin,
   Store Manager,                  Warehouse Manager
   Warehouse Manager
```

### 1.5 Sales & Billing Use Cases

```
                            ┌──────────────────────────┐
                            │     SALES & BILLING      │
                            └──────────────────────────┘
                                        │
       ┌────────────┬───────────────────┼──────────────────┬────────────┐
       ▼            ▼                   ▼                  ▼            ▼
┌────────────┐ ┌────────────┐   ┌──────────────┐   ┌───────────┐ ┌──────────┐
│  Create    │ │  Create    │   │   Process    │   │   Print   │ │  View    │
│  Invoice   │ │  Quotation │   │   Return     │   │  PDF      │ │ Invoices │
└─────┬──────┘ └─────┬──────┘   └──────┬───────┘   └─────┬─────┘ └────┬─────┘
      │              │                 │                 │            │
      ▼              ▼                 ▼                 ▼            ▼
● Select customer  ● Select       ● Select invoice   ● Generate  ● Filter by
● Add items          customer     ● Select items       PDF          date, status
● Set quantities   ● Add items   ● Set return qty   ● Print      ● Search
● Apply discount   ● Set validity● Process refund   ● Download   ● Own vs All
● Calculate GST    ● Save draft  ● Update stock
● Deduct stock
● Record payment

  ACTORS:                          ACTORS:
  Super Admin, Admin,              Super Admin, Admin,
  Store Manager,                   Store Manager,
  Sales Executive                  Sales Executive
```

---

## 2. Class Diagrams

### 2.1 Complete Class Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                         «Singleton»                              │
│                         Database                                 │
├──────────────────────────────────────────────────────────────────┤
│ - instance: Database = null                                      │
│ - conn: PDO                                                      │
│ - host: string                                                   │
│ - dbname: string                                                 │
│ - username: string                                               │
│ - password: string                                               │
├──────────────────────────────────────────────────────────────────┤
│ - __construct()                                                  │
│ - loadCredentials(): void                                        │
│ + getInstance(): Database                                        │
│ + getConnection(): PDO                                           │
│ + query(sql: string, params: array = []): array                  │
│ + queryOne(sql: string, params: array = []): array|false         │
│ + execute(sql: string, params: array = []): int|string           │
│ + lastInsertId(): string                                         │
│ + beginTransaction(): bool                                       │
│ + commit(): bool                                                 │
│ + rollback(): bool                                               │
│ + logActivity(action, module, description, status): void «static»│
└──────────────────────────────────────────────────────────────────┘
                               ▲
                               │ uses
        ┌──────────────────────┼──────────────────────┐
        │                      │                      │
┌───────┴──────────┐  ┌────────┴─────────┐  ┌────────┴──────────┐
│    «static»      │  │    «static»      │  │                   │
│    Session       │  │    AuthHelper     │  │   RoleManager     │
├──────────────────┤  ├──────────────────┤  ├───────────────────┤
│                  │  │                  │  │ - db: Database     │
├──────────────────┤  ├──────────────────┤  ├───────────────────┤
│ + start()        │  │ - getDB(): PDO   │  │ + __construct()   │
│ + isLoggedIn()   │  │ + hashPassword() │  │ + getAllRoles()   │
│ + get(key)       │  │ + verifyPassword │  │ + getRoleById()   │
│ + set(key, val)  │  │   (pwd, hash)    │  │ + getRoleByName() │
│ + has(key)       │  │ + login(user,    │  │ + getRolePerms()  │
│ + remove(key)    │  │   password)      │  │ + assignPerm      │
│ + regenerate()   │  │ + register(data) │  │   ToRole()        │
│ + destroy()      │  │ + logout()       │  │ + removePerm      │
│ + getUserId()    │  │ + check(): bool  │  │   FromRole()      │
│ + getUserRole()  │  │ + user(): array  │  │ + assignRoleTo    │
│ + getUserName()  │  │                  │  │   User()           │
│ + getOrgId()     │  └──────────────────┘  │ + getUsersByRole()│
│ + setUser(id,    │                        │ + createRole()    │
│   name, role,    │                        │ + updateRole()    │
│   orgId)         │                        │ + deleteRole()    │
│ + clearUser()    │                        │ + getAllPerms()   │
│ + getUser()      │                        │ + getPermsByMod() │
│ + setFlash()     │                        │ + syncRolePerms() │
│ + getFlash()     │                        └───────────────────┘
│ + hasFlash()     │
│ + clearFlash()   │
└──────────────────┘
        ▲
        │ uses
        │
┌───────┴──────────────┐       ┌─────────────────────────┐
│       «static»       │       │       «static»          │
│ PermissionMiddleware  │       │    OrganizationHelper   │
├──────────────────────┤       ├─────────────────────────┤
│                      │       │                         │
├──────────────────────┤       ├─────────────────────────┤
│ + hasPermission      │       │ + getCurrentOrgId()     │
│   (permName): bool   │       │ + hasOrganization()     │
│ + requirePermission  │       │ + getOrganization()     │
│   (perm, redirect)   │       │ + addOrgFilter()        │
│ + hasAnyPermission   │       │ + filterQuery()         │
│   (perms[]): bool    │       │ + validateOwnership()   │
│ + hasAllPermissions  │       │ + getOrgUsers()         │
│   (perms[]): bool    │       │ + isSuperAdmin()        │
│ + getUserPermissions │       │ + getOrgStats()         │
│   (): array          │       └─────────────────────────┘
│ + getUserPermissions │
│   ByModule(): array  │
└──────────────────────┘

┌──────────────────────────┐   ┌─────────────────────────┐
│        «static»          │   │                         │
│       RBACSeeder         │   │      Validator          │
├──────────────────────────┤   ├─────────────────────────┤
│                          │   │ - errors: array = []    │
├──────────────────────────┤   ├─────────────────────────┤
│ + seedIfNeeded(): bool   │   │ + required(val, field)  │
│ + seedForOrganization    │   │ + email(val, field)     │
│   (orgId): bool          │   │ + minLength(val, len)   │
│ - ensureTables(conn)     │   │ + maxLength(val, len)   │
│ - ensureRoles(conn)      │   │ + numeric(val, field)   │
│ - ensurePermissions(conn)│   │ + integer(val, field)   │
│ - getDefaultPermissions()│   │ + phone(val, field)     │
│ - ensureSuperAdminHas    │   │ + getErrors(): array    │
│   AllPermissions(conn)   │   │ + getFirstError()       │
│ - ensureOtherRole        │   │ + hasErrors(): bool     │
│   Permissions(conn)      │   │ + fails(): bool         │
│ - ensureRolesTableHas    │   │ + passes(): bool        │
│   OrganizationId(conn)   │   │ + clearErrors()         │
│ - createRolesFor         │   │ + addError(field, msg)  │
│   Organization(conn, id) │   │ + sanitize(val) «static»│
│ - assignAllPermsFor      │   └─────────────────────────┘
│   OrgRoles(conn, ids)    │
│ - assignPermsByName      │   ┌─────────────────────────┐
│   (conn, roleId, names)  │   │       «static»          │
└──────────────────────────┘   │       EmailOTP           │
                               ├─────────────────────────┤
                               │ OTP_LENGTH = 6           │
                               │ OTP_EXPIRY = 10 min      │
                               ├─────────────────────────┤
                               │ + generateOTP(): string  │
                               │ + storeOTP(userId, otp)  │
                               │ + verifyOTP(userId, otp) │
                               │ + sendOTPEmail(email,    │
                               │   name, otp)             │
                               │ - buildEmailBody(name,   │
                               │   otp): string           │
                               │ - sendViaSMTP(email,     │
                               │   name, subject, body)   │
                               └─────────────────────────┘
```

### 2.2 Relationships Between Classes

```
Database ◄──── used by ────── AuthHelper
Database ◄──── used by ────── RoleManager
Database ◄──── used by ────── PermissionMiddleware
Database ◄──── used by ────── OrganizationHelper
Database ◄──── used by ────── RBACSeeder
Database ◄──── used by ────── EmailOTP

Session  ◄──── used by ────── AuthHelper (setUser / clearUser)
Session  ◄──── used by ────── PermissionMiddleware (getUserId / getUserRole)
Session  ◄──── used by ────── OrganizationHelper (getOrganizationId)
Session  ◄──── used by ────── session_guard.php (isLoggedIn / regenerate)

AuthHelper  ──── depends on ──► Session, Database
RoleManager ──── depends on ──► Database
RBACSeeder  ──── depends on ──► Database
```

### 2.3 Database Entity Relationship (ER) Diagram

```
┌──────────────┐     ┌───────────────┐     ┌──────────────┐
│ organizations│     │     users     │     │    roles     │
├──────────────┤     ├───────────────┤     ├──────────────┤
│ PK id        │◄───┐│ PK id         │     │ PK id        │
│ name         │    ││ FK org_id ────┘     │ FK org_id    │
│ email        │    ││ username      │     │ name         │
│ phone        │    ││ email         │  ┌──│ display_name │
│ address      │    ││ password      │  │  │ description  │
│ gst_number   │    ││ full_name     │  │  └──────────────┘
│ status       │    ││ role ─────────┼──┘         │
└──────────────┘    ││ phone         │            │
                    ││ status        │     ┌──────┴────────┐
                    ││ last_login    │     │role_permissions│
                    │└───────────────┘     ├───────────────┤
                    │        │             │ PK id         │
                    │        │             │ FK role_id    │
                    │  ┌─────┴──────┐      │ FK perm_id   │
                    │  │ employees  │      └───────┬───────┘
                    │  ├────────────┤              │
                    │  │ PK id      │       ┌──────┴──────┐
                    │  │ FK user_id │       │ permissions │
                    │  │ FK dept_id │       ├─────────────┤
                    │  │ emp_code   │       │ PK id       │
                    │  │ salary     │       │ name        │
                    │  └────────────┘       │ module      │
                    │                       │ action      │
                    │                       └─────────────┘
                    │
    ┌───────────────┼───────────────┐
    │               │               │
┌───┴────────┐ ┌────┴───────┐ ┌────┴────────┐
│ products   │ │ customers  │ │  invoices   │
├────────────┤ ├────────────┤ ├─────────────┤
│ PK id      │ │ PK id      │ │ PK id       │
│ FK org_id  │ │ FK org_id  │ │ FK org_id   │
│ FK cat_id  │ │ name       │ │ FK cust_id  │
│ FK brand_id│ │ email      │ │ FK user_id  │
│ name       │ │ phone      │ │ inv_number  │
│ sku        │ │ gst_number │ │ subtotal    │
│ purchase_  │ │ credit_lim │ │ tax_amount  │
│   price    │ │ outstanding│ │ discount    │
│ selling_   │ │   _balance │ │ total       │
│   price    │ └────────────┘ │ paid_amount │
│ stock_qty  │                │ status      │
│ min_stock  │                └──────┬──────┘
│ tax_rate   │                       │
└──────┬─────┘                ┌──────┴──────┐
       │                      │invoice_items│
       │                      ├─────────────┤
  ┌────┴──────┐               │ PK id       │
  │stock_logs │               │ FK inv_id   │
  ├───────────┤               │ FK prod_id  │
  │ PK id     │               │ quantity    │
  │ FK prod_id│               │ unit_price  │
  │ type      │               │ tax_rate    │
  │ quantity  │               │ tax_amount  │
  │ FK wh_id  │               │ line_total  │
  │ FK user_id│               └─────────────┘
  │ FK org_id │
  └───────────┘

  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐
  │  categories │  │    brands    │  │  suppliers   │
  ├─────────────┤  ├──────────────┤  ├──────────────┤
  │ PK id       │  │ PK id        │  │ PK id        │
  │ name        │  │ name         │  │ name         │
  │ FK parent_id│  │ description  │  │ email        │
  │ description │  │ logo         │  │ phone        │
  └─────────────┘  └──────────────┘  │ gst_number   │
                                     └──────────────┘

  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐
  │  expenses    │  │ activity_logs│  │  warehouses  │
  ├──────────────┤  ├──────────────┤  ├──────────────┤
  │ PK id        │  │ PK id        │  │ PK id        │
  │ category     │  │ FK org_id    │  │ name         │
  │ amount       │  │ FK user_id   │  │ code         │
  │ expense_date │  │ module       │  │ address      │
  │ vendor       │  │ action       │  │ FK manager_id│
  │ status       │  │ description  │  │ capacity     │
  │ FK approved  │  │ ip_address   │  └──────────────┘
  │   _by        │  └──────────────┘
  │ FK created_by│
  └──────────────┘
```

---

## 3. Activity Diagrams

### 3.1 User Login Activity

```
        ┌───────┐
        │ START │
        └───┬───┘
            ▼
    ┌───────────────┐
    │ Open Login    │
    │ Page          │
    └───────┬───────┘
            ▼
    ┌───────────────┐
    │ Enter Email   │
    │ & Password    │
    └───────┬───────┘
            ▼
    ┌───────────────┐
    │ Click         │
    │ "Sign In"     │
    └───────┬───────┘
            ▼
    ◇───────────────────◇
    │ Fields empty?     │
    ◇───────┬───────┬───◇
        YES │       │ NO
            ▼       ▼
    ┌──────────┐  ┌───────────────┐
    │ Show     │  │ Query users   │
    │ Error    │  │ table by      │
    │ "Fill    │  │ email/username│
    │ all      │  └───────┬───────┘
    │ fields"  │          ▼
    └──────────┘  ◇───────────────────◇
                  │ User found?       │
                  ◇───────┬───────┬───◇
                      NO  │       │ YES
                          ▼       ▼
                  ┌──────────┐  ┌───────────────┐
                  │ Show     │  │ password_     │
                  │ "Invalid │  │ verify()      │
                  │ creds"   │  └───────┬───────┘
                  └──────────┘          ▼
                              ◇───────────────────◇
                              │ Password match?   │
                              ◇───────┬───────┬───◇
                                  NO  │       │ YES
                                      ▼       ▼
                              ┌──────────┐  ┌───────────────┐
                              │ Show     │  │ Session::     │
                              │ "Invalid │  │ setUser()     │
                              │ creds"   │  │ (id, name,    │
                              └──────────┘  │  role, orgId) │
                                            └───────┬───────┘
                                                    ▼
                                            ┌───────────────┐
                                            │ Update        │
                                            │ last_login    │
                                            └───────┬───────┘
                                                    ▼
                                            ┌───────────────┐
                                            │ Log activity  │
                                            │ ("login")     │
                                            └───────┬───────┘
                                                    ▼
                                            ◇───────────────────◇
                                            │ Check user role   │
                                            ◇───┬───┬───┬───┬───◇
                                  super_admin│   │   │   │   │accountant
                                            ▼   ▼   ▼   ▼   ▼
                                   ┌──────┐ ┌──┐ ┌──┐ ┌──┐ ┌──────┐
                                   │Super │ │Ad│ │St│ │Sa│ │Acct  │
                                   │Admin │ │mi│ │or│ │le│ │Dash  │
                                   │Dash  │ │n │ │e │ │s │ │board │
                                   └──┬───┘ └┬─┘ └┬─┘ └┬─┘ └──┬───┘
                                      └──────┴────┴────┴──────┘
                                                  ▼
                                            ┌─────┐
                                            │ END │
                                            └─────┘
```

### 3.2 Organization Registration Activity

```
        ┌───────┐
        │ START │
        └───┬───┘
            ▼
    ┌───────────────────┐
    │ Open Register Page│
    └───────┬───────────┘
            ▼
    ┌───────────────────┐
    │ Step 1: Enter Org │
    │ Details (name,    │
    │ email, phone,     │
    │ address, GST)     │
    └───────┬───────────┘
            ▼
    ┌───────────────────┐
    │ Step 2: Enter     │
    │ Admin Details     │
    │ (name, email,     │
    │ phone)            │
    └───────┬───────────┘
            ▼
    ┌───────────────────┐
    │ Step 3: Set       │
    │ Username &        │
    │ Password          │
    └───────┬───────────┘
            ▼
    ◇───────────────────◇
    │ Validate all      │
    │ fields            │
    ◇───────┬───────┬───◇
       FAIL │       │ PASS
            ▼       ▼
    ┌──────────┐  ┌───────────────────┐
    │ Show     │  │ Check email/      │
    │ errors   │  │ username unique   │
    └──────────┘  └───────┬───────────┘
                          ▼
                  ◇───────────────────◇
                  │ Already exists?   │
                  ◇───────┬───────┬───◇
                     YES  │       │ NO
                          ▼       ▼
                  ┌──────────┐  ┌───────────────────┐
                  │ "Email/  │  │ BEGIN TRANSACTION  │
                  │ username │  └───────┬───────────┘
                  │ exists"  │          ▼
                  └──────────┘  ┌───────────────────┐
                                │ INSERT INTO       │
                                │ organizations     │
                                │ → get $orgId      │
                                └───────┬───────────┘
                                        ▼
                                ┌───────────────────┐
                                │ INSERT INTO users │
                                │ (role=super_admin │
                                │  org_id=$orgId)   │
                                └───────┬───────────┘
                                        ▼
                                ┌───────────────────┐
                                │ COMMIT            │
                                └───────┬───────────┘
                                        ▼
                                ┌───────────────────┐
                                │ RBACSeeder::      │
                                │ seedForOrg($orgId)│
                                │ → Create 6 roles  │
                                │ → Seed 45 perms   │
                                │ → Assign perms    │
                                └───────┬───────────┘
                                        ▼
                                ┌───────────────────┐
                                │ Log activity      │
                                │ ("register")      │
                                └───────┬───────────┘
                                        ▼
                                ┌───────────────────┐
                                │ Show success msg  │
                                │ "Registration     │
                                │  successful!"     │
                                └───────┬───────────┘
                                        ▼
                                ┌───────────────────┐
                                │ Redirect to Login │
                                └───────┬───────────┘
                                        ▼
                                  ┌─────┐
                                  │ END │
                                  └─────┘
```

### 3.3 Invoice Creation Activity

```
        ┌───────┐
        │ START │
        └───┬───┘
            ▼
    ┌────────────────────┐
    │ Open Invoice Form  │
    │ (permission check) │
    └───────┬────────────┘
            ▼
    ┌────────────────────┐
    │ Select Customer    │
    │ from dropdown      │
    └───────┬────────────┘
            ▼
    ┌────────────────────┐
    │ Add Product(s)     │
    │ ● Select product   │
    │ ● Set quantity     │
    │ ● Auto-fill price  │
    └───────┬────────────┘
            ▼
    ◇────────────────────◇
    │ Qty ≤ stock?       │
    ◇───────┬────────┬───◇
        NO  │        │ YES
            ▼        ▼
    ┌──────────┐  ┌────────────────────┐
    │ "Insuffi-│  │ JS calculates:     │
    │  cient   │  │ line_total = qty   │
    │  stock"  │  │   × unit_price     │
    └──────────┘  │ tax = line_total   │
                  │   × tax_rate / 100 │
                  │ grand_total = Σ    │
                  │   (line + tax)     │
                  │   - discount       │
                  └───────┬────────────┘
                          ▼
                  ┌────────────────────┐
                  │ (Optional)         │
                  │ Apply discount     │
                  └───────┬────────────┘
                          ▼
                  ┌────────────────────┐
                  │ Click Save Invoice │
                  └───────┬────────────┘
                          ▼
                  ┌────────────────────┐
                  │ BEGIN TRANSACTION  │
                  └───────┬────────────┘
                          ▼
                  ┌────────────────────┐
                  │ Generate INV-XXXX  │
                  │ (auto-increment)   │
                  └───────┬────────────┘
                          ▼
                  ┌────────────────────┐
                  │ INSERT invoices    │
                  │ (customer, totals, │
                  │  payment, status)  │
                  └───────┬────────────┘
                          ▼
                  ┌────────────────────┐
                  │ FOR EACH ITEM:     │
                  │ ● INSERT item      │
                  │ ● UPDATE product   │
                  │   stock -= qty     │
                  │ ● INSERT stock_log │
                  │   (type='out')     │
                  └───────┬────────────┘
                          ▼
                  ◇────────────────────◇
                  │ Any error?         │
                  ◇───────┬────────┬───◇
                     YES  │        │ NO
                          ▼        ▼
                  ┌──────────┐  ┌────────────────────┐
                  │ ROLLBACK │  │ COMMIT             │
                  │ "Failed" │  └───────┬────────────┘
                  └──────────┘          ▼
                                ┌────────────────────┐
                                │ Set flash message  │
                                │ Redirect to        │
                                │ invoice list       │
                                └───────┬────────────┘
                                        ▼
                                  ┌─────┐
                                  │ END │
                                  └─────┘
```

### 3.4 Stock In Activity

```
        ┌───────┐
        │ START │
        └───┬───┘
            ▼
    ┌───────────────┐
    │ Open Stock In │
    │ Page          │
    └───────┬───────┘
            ▼
    ◇───────────────────◇
    │ Role allowed?     │
    │ (super_admin/     │
    │  admin/store_mgr/ │
    │  warehouse_mgr)   │
    ◇───────┬───────┬───◇
        NO  │       │ YES
            ▼       ▼
    ┌──────────┐  ┌───────────────┐
    │ 403      │  │ Select        │
    │ Access   │  │ Product       │
    │ Denied   │  └───────┬───────┘
    └──────────┘          ▼
                  ┌───────────────┐
                  │ Enter Qty,    │
                  │ Warehouse,    │
                  │ Notes         │
                  └───────┬───────┘
                          ▼
                  ┌───────────────────┐
                  │ BEGIN TRANSACTION │
                  └───────┬───────────┘
                          ▼
                  ┌───────────────────┐
                  │ INSERT stock_logs │
                  │ (type='in',       │
                  │  product, qty,    │
                  │  warehouse, user) │
                  └───────┬───────────┘
                          ▼
                  ┌───────────────────┐
                  │ UPDATE products   │
                  │ stock_quantity    │
                  │ += quantity       │
                  └───────┬───────────┘
                          ▼
                  ┌───────────────────┐
                  │ COMMIT            │
                  └───────┬───────────┘
                          ▼
                  ┌───────────────────┐
                  │ Success: "Stock   │
                  │ added"            │
                  └───────┬───────────┘
                          ▼
                    ┌─────┐
                    │ END │
                    └─────┘
```

### 3.5 Permission Check Activity

```
        ┌───────┐
        │ START │
        └───┬───┘
            ▼
    ┌────────────────────┐
    │ User accesses      │
    │ protected page     │
    └───────┬────────────┘
            ▼
    ◇────────────────────◇
    │ Session active?    │
    │ (isLoggedIn)       │
    ◇───────┬────────┬───◇
        NO  │        │ YES
            ▼        ▼
    ┌──────────┐  ◇────────────────────◇
    │ Redirect │  │ Role = super_admin?│
    │ to login │  ◇───────┬────────┬───◇
    └──────────┘     YES  │        │ NO
                          ▼        ▼
                  ┌──────────┐  ┌────────────────────┐
                  │ GRANT    │  │ Get role_id from   │
                  │ ACCESS   │  │ roles table        │
                  │ (all     │  └───────┬────────────┘
                  │ perms)   │          ▼
                  └──────────┘  ┌────────────────────┐
                                │ Query              │
                                │ role_permissions   │
                                │ JOIN permissions   │
                                │ WHERE role_id AND  │
                                │ permission_name    │
                                └───────┬────────────┘
                                        ▼
                                ◇────────────────────◇
                                │ Has permission?    │
                                ◇───────┬────────┬───◇
                                   NO   │        │ YES
                                        ▼        ▼
                                ┌──────────┐  ┌──────────┐
                                │ 403      │  │ GRANT    │
                                │ ACCESS   │  │ ACCESS   │
                                │ DENIED   │  │          │
                                └──────────┘  └──────────┘
                                        │        │
                                        └────┬───┘
                                             ▼
                                       ┌─────┐
                                       │ END │
                                       └─────┘
```

### 3.6 Logout Activity

```
        ┌───────┐
        │ START │
        └───┬───┘
            ▼
    ┌───────────────┐
    │ Click Logout  │
    └───────┬───────┘
            ▼
    ◇───────────────────◇
    │ Confirm dialog    │
    │ "Are you sure?"   │
    ◇───────┬───────┬───◇
     Cancel │       │ OK
            ▼       ▼
    ┌──────────┐  ┌───────────────┐
    │ Stay on  │  │ Session::     │
    │ page     │  │ clearUser()   │
    └──────────┘  └───────┬───────┘
                          ▼
                  ┌───────────────┐
                  │ Session::     │
                  │ destroy()     │
                  └───────┬───────┘
                          ▼
                  ┌───────────────┐
                  │ Clear cookies │
                  │ (session,     │
                  │  remember_    │
                  │  token, email)│
                  └───────┬───────┘
                          ▼
                  ┌───────────────┐
                  │ Redirect to   │
                  │ login.php     │
                  └───────┬───────┘
                          ▼
                    ┌─────┐
                    │ END │
                    └─────┘
```

---

## 4. Test Cases – Black Box Testing

### 4.1 Authentication Module

| TC ID | Title | Precondition | Input / Steps | Expected Result | Priority |
|-------|-------|--------------|---------------|-----------------|----------|
| BB-AUTH-01 | Valid Login | User exists | Email: admin@stocksathi.com, Pass: password123 → Sign In | Redirect to admin dashboard, session active | High |
| BB-AUTH-02 | Invalid Password | User exists | Correct email, wrong password → Sign In | Error "Invalid credentials", stay on login | High |
| BB-AUTH-03 | Empty Fields | Login page | Leave both blank → Sign In | Validation error "Please fill in all fields" | Medium |
| BB-AUTH-04 | Non-existent User | No such email | Email: fake@test.com → Sign In | Error "Invalid credentials" | Medium |
| BB-AUTH-05 | Logout | User logged in | Click Logout → Confirm | Session destroyed, redirect to login | High |
| BB-AUTH-06 | Post-Logout Access | Just logged out | Navigate to /index.php directly | Redirect to login page | High |
| BB-AUTH-07 | Register Org | Reg page open | Fill org + admin + password → Submit | Org created, super_admin user, redirect to login | High |
| BB-AUTH-08 | Register Duplicate Email | Email exists | Use existing email → Submit | Error "Email or username already exists" | High |
| BB-AUTH-09 | Register Password Mismatch | Reg page | password ≠ confirm_password → Submit | Error "Passwords do not match" | Medium |
| BB-AUTH-10 | Dashboard Redirect by Role | Each role user | Login with each role | Each role → correct dashboard (5 different) | High |

### 4.2 Products Module

| TC ID | Title | Precondition | Input / Steps | Expected Result | Priority |
|-------|-------|--------------|---------------|-----------------|----------|
| BB-PROD-01 | Create Product | create_products perm | Fill name, SKU, category, prices → Save | Product in list with correct data | High |
| BB-PROD-02 | Create – Required Only | create_products perm | Fill only name → Save | Product created with defaults | Medium |
| BB-PROD-03 | Duplicate SKU | SKU "TEST-001" exists | Use same SKU → Save | Error "SKU already exists" | High |
| BB-PROD-04 | Edit Product | Product exists | Change price from 1000→1500 → Save | Price updated in list and DB | High |
| BB-PROD-05 | Delete Product | delete_products perm | Select product → Delete → Confirm | Product removed from list | High |
| BB-PROD-06 | Search Product | Products exist | Type name in search box | Only matching products shown | Medium |
| BB-PROD-07 | Filter by Category | Multi-category | Select "Electronics" filter | Only electronics shown | Medium |
| BB-PROD-08 | Negative Price | create_products perm | Enter -100 as price → Save | Validation error or rejection | Medium |

### 4.3 Stock Management

| TC ID | Title | Precondition | Input / Steps | Expected Result | Priority |
|-------|-------|--------------|---------------|-----------------|----------|
| BB-STK-01 | Stock In | Product exists | Select product, qty=50, warehouse → Save | Stock increased by 50 | High |
| BB-STK-02 | Stock Out – Valid | Stock ≥ 20 | Select product, qty=10 → Save | Stock reduced by 10 | High |
| BB-STK-03 | Stock Out – Insufficient | Stock = 10 | qty=20 → Save | Error "Insufficient stock", no change | High |
| BB-STK-04 | Stock Transfer | Stock in WH-A | From WH-A, To WH-B, qty=10 → Complete | WH-A -10, WH-B +10 | High |
| BB-STK-05 | Stock Adjustment (+) | Product exists | Adjustment +5 → Save | Stock increased by 5 | Medium |
| BB-STK-06 | Stock Adjustment (-) | Stock ≥ 5 | Adjustment -5 → Save | Stock decreased by 5 | Medium |
| BB-STK-07 | Low Stock Alert | min_stock=10, stock=5 | Check dashboard | Low stock alert visible | Medium |
| BB-STK-08 | Delete Stock In | Stock-in exists | Delete entry → Confirm | Stock reversed (decreased) | Medium |

### 4.4 Invoice Module

| TC ID | Title | Precondition | Input / Steps | Expected Result | Priority |
|-------|-------|--------------|---------------|-----------------|----------|
| BB-INV-01 | Create – Single Item | Customer + Product | Select customer, add 1 product, qty=1 → Save | Invoice with correct total | High |
| BB-INV-02 | Create – Multiple Items | Multiple products | Add 3 products → Save | 3 line items, correct grand total | High |
| BB-INV-03 | GST Calculation | Product 18% tax | Price=1000, Qty=2 | Subtotal=2000, Tax=360, Total=2360 | High |
| BB-INV-04 | Discount | give_discount perm | Subtotal 1000, 10% discount | Discount=100 applied | Medium |
| BB-INV-05 | Stock Deduction | Stock = 50 | Invoice qty=5 → Save | Product stock = 45 | High |
| BB-INV-06 | Insufficient Stock | Stock = 10 | Invoice qty=15 | Error "Insufficient stock" | High |
| BB-INV-07 | Print PDF | Invoice exists | Open details → Print/PDF | PDF with correct data | Medium |
| BB-INV-08 | Invoice Number | No invoices | Create first invoice | Invoice number = INV-0001 | Medium |

### 4.5 RBAC (Role-Based Access Control)

| TC ID | Title | Precondition | Input / Steps | Expected Result | Priority |
|-------|-------|--------------|---------------|-----------------|----------|
| BB-RBAC-01 | Sales Exec – No Delete | Login as sales_exec | Go to Products | No Delete button visible | High |
| BB-RBAC-02 | Admin – No Delete Users | Login as admin | Go to Users | No Delete option for users | High |
| BB-RBAC-03 | Super Admin – All Access | Login as super_admin | Visit every module | All accessible, no 403 | High |
| BB-RBAC-04 | Direct URL Blocked | Login as sales_exec | Type /pages/users.php in URL | 403 or redirect | High |
| BB-RBAC-05 | Own Invoices Only | Login as sales_exec | View invoices | Only own invoices shown | Medium |
| BB-RBAC-06 | Sidebar Visibility | Login as accountant | Check sidebar | Only finance menus visible | Medium |
| BB-RBAC-07 | Roles Page – Org Roles | New org super_admin | Admin → Roles | 6 core roles with perms | High |
| BB-RBAC-08 | Permission Count | super_admin role | View Roles | super_admin: 45 permissions | Medium |

### 4.6 Customers & Suppliers

| TC ID | Title | Precondition | Input / Steps | Expected Result | Priority |
|-------|-------|--------------|---------------|-----------------|----------|
| BB-CUST-01 | Create Customer | create_customers | Fill name, phone → Save | Customer in list | High |
| BB-CUST-02 | Edit Customer | Customer exists | Change phone → Save | Updated phone | Medium |
| BB-CUST-03 | Delete Customer | No balance | Delete → Confirm | Customer removed | Medium |
| BB-SUPP-01 | Create Supplier | create_suppliers | Fill name, email → Save | Supplier in list | High |
| BB-SUPP-02 | Edit Supplier | Supplier exists | Change contact → Save | Updated | Medium |

### 4.7 Expenses

| TC ID | Title | Precondition | Input / Steps | Expected Result | Priority |
|-------|-------|--------------|---------------|-----------------|----------|
| BB-EXP-01 | Create Expense | create_expenses | Fill amount, category, date → Save | Expense in list | High |
| BB-EXP-02 | Approve Expense | approve_expenses | Select pending expense → Approve | Status = "approved" | High |
| BB-EXP-03 | Delete Expense | Expense exists | Delete → Confirm | Expense removed | Medium |

### 4.8 Users & Settings

| TC ID | Title | Precondition | Input / Steps | Expected Result | Priority |
|-------|-------|--------------|---------------|-----------------|----------|
| BB-USER-01 | Create User | view_users perm | Fill username, email, role → Save | User in list | High |
| BB-USER-02 | Edit User Role | User exists | Change role → Save | Role updated | High |
| BB-USER-03 | Delete User | super_admin only | Delete user → Confirm | User removed | High |
| BB-SETT-01 | Update Company Info | view_settings | Change company name → Save | Settings persisted | Medium |

---

## 5. Test Cases – White Box Testing

### 5.1 Session.php

| TC ID | Function Under Test | Code Path | Input | Expected Output | Pass/Fail |
|-------|---------------------|-----------|-------|-----------------|-----------|
| WB-SESS-01 | `Session::start()` | Lines 10–14: checks `session_status()` | Call when session already active | No error, no duplicate `session_start()` | □ |
| WB-SESS-02 | `Session::start()` | Lines 10–14: `PHP_SESSION_NONE` branch | Call when no session | `session_start()` called, status = ACTIVE | □ |
| WB-SESS-03 | `Session::isLoggedIn()` | Lines 19–22: checks `$_SESSION['user_id']` | `user_id` set in session | Returns `true` | □ |
| WB-SESS-04 | `Session::isLoggedIn()` | Lines 19–22: empty check | No `user_id` | Returns `false` | □ |
| WB-SESS-05 | `Session::setUser()` | Lines 97–103: sets 5 keys | `setUser(1, 'admin', 'admin', 5)` | `$_SESSION` has user_id=1, username='admin', role='admin', org_id=5, login_time=now | □ |
| WB-SESS-06 | `Session::clearUser()` | Lines 105–112: unsets 5 keys | Call after `setUser()` | All 5 keys removed from `$_SESSION` | □ |
| WB-SESS-07 | `Session::destroy()` | Lines 58–62 | Active session with data | `session_destroy()` called, `$_SESSION = []` | □ |
| WB-SESS-08 | `Session::get()` | Lines 27–30: `??` operator | Key exists / key missing | Returns value / returns default | □ |
| WB-SESS-09 | `Session::setFlash()` | Lines 137–155: 2-param format | `setFlash('Done', 'success')` | `$_SESSION['flash_messages']['_default']` set | □ |
| WB-SESS-10 | `Session::getFlash()` | Lines 160–183: removes after read | Call after `setFlash()` | Returns message, second call returns `null` | □ |

### 5.2 AuthHelper.php

| TC ID | Function Under Test | Code Path | Input | Expected Output | Pass/Fail |
|-------|---------------------|-----------|-------|-----------------|-----------|
| WB-AUTH-01 | `AuthHelper::login()` | Lines 37–82: valid path | Valid email + password | `['success'=>true, 'user'=>[...]]`, session set | □ |
| WB-AUTH-02 | `AuthHelper::login()` | Line 52: user not found | Non-existent email | `['success'=>false, 'message'=>'Invalid credentials']` | □ |
| WB-AUTH-03 | `AuthHelper::login()` | Line 56: password mismatch | Valid email, wrong pass | `['success'=>false, 'message'=>'Invalid credentials']` | □ |
| WB-AUTH-04 | `AuthHelper::login()` | Line 57: `Session::setUser()` call | Valid login | Session has `user_id`, `username`, `role`, `organization_id` | □ |
| WB-AUTH-05 | `AuthHelper::hashPassword()` | Line 28: PASSWORD_DEFAULT | Any string | Returns bcrypt hash starting with `$2y$` | □ |
| WB-AUTH-06 | `AuthHelper::verifyPassword()` | Line 33: `password_verify()` | Correct pwd + hash | Returns `true` | □ |
| WB-AUTH-07 | `AuthHelper::verifyPassword()` | Line 33: mismatch | Wrong pwd + hash | Returns `false` | □ |
| WB-AUTH-08 | `AuthHelper::register()` | Lines 89–125: duplicate check | Existing username | `['success'=>false, 'message'=>'Username or email already exists']` | □ |
| WB-AUTH-09 | `AuthHelper::register()` | Lines 107–120: insert | New user data | `['success'=>true]`, row in users table | □ |
| WB-AUTH-10 | `AuthHelper::logout()` | Lines 131–134 | Logged-in user | `clearUser()` + `destroy()` called | □ |

### 5.3 PermissionMiddleware.php

| TC ID | Function Under Test | Code Path | Input | Expected Output | Pass/Fail |
|-------|---------------------|-----------|-------|-----------------|-----------|
| WB-PERM-01 | `hasPermission()` | Lines 14–16: not logged in | No session | Returns `false` | □ |
| WB-PERM-02 | `hasPermission()` | Lines 21–24: super_admin bypass | Role = super_admin | Returns `true` (no DB query) | □ |
| WB-PERM-03 | `hasPermission()` | Lines 30–34: role not found | Role 'invalid_role' | Returns `false` | □ |
| WB-PERM-04 | `hasPermission()` | Lines 38–45: permission exists | Admin + 'view_products' | Returns `true` (COUNT > 0) | □ |
| WB-PERM-05 | `hasPermission()` | Lines 38–45: no permission | sales_exec + 'delete_users' | Returns `false` (COUNT = 0) | □ |
| WB-PERM-06 | `hasAnyPermission()` | Lines 71–77: loop | `['delete_users','view_products']` for admin | Returns `true` (view_products matches) | □ |
| WB-PERM-07 | `hasAllPermissions()` | Lines 83–90: loop | `['view_products','delete_users']` for admin | Returns `false` (delete_users fails) | □ |
| WB-PERM-08 | `requirePermission()` | Lines 57–66: deny path | No permission + no redirect | HTTP 403 + die() | □ |
| WB-PERM-09 | `getUserPermissions()` | Lines 93–128: super_admin | super_admin role | Returns ALL permissions from table | □ |
| WB-PERM-10 | `getUserPermissions()` | Lines 93–128: regular role | admin role | Returns only admin's permissions (via JOIN) | □ |

### 5.4 Database.php

| TC ID | Function Under Test | Code Path | Input | Expected Output | Pass/Fail |
|-------|---------------------|-----------|-------|-----------------|-----------|
| WB-DB-01 | `getInstance()` | Lines 74–79: singleton | Called twice | Same object reference (`===`) | □ |
| WB-DB-02 | `query()` | Lines 86–94: prepared statement | `"SELECT * FROM users WHERE id = ?", [1]` | Array of rows, no SQL injection | □ |
| WB-DB-03 | `query()` | Lines 86–94: empty result | Non-existent ID | Empty array `[]` | □ |
| WB-DB-04 | `queryOne()` | Lines 99–107: single row | Valid query | Single assoc array | □ |
| WB-DB-05 | `queryOne()` | Lines 99–107: no match | Non-existent | Returns `false` | □ |
| WB-DB-06 | `execute()` | Lines 112–128: INSERT | Valid INSERT | Returns `lastInsertId` (numeric string) | □ |
| WB-DB-07 | `execute()` | Lines 112–128: UPDATE | Valid UPDATE | Returns affected row count | □ |
| WB-DB-08 | `beginTransaction()` + `rollback()` | Transaction flow | begin → insert → rollback | Insert not persisted | □ |
| WB-DB-09 | `beginTransaction()` + `commit()` | Transaction flow | begin → insert → commit | Insert persisted | □ |
| WB-DB-10 | `logActivity()` | Lines 165–180: static | Action, module, desc | Row in activity_logs with user_id, ip, timestamp | □ |

### 5.5 Validator.php

| TC ID | Function Under Test | Code Path | Input | Expected Output | Pass/Fail |
|-------|---------------------|-----------|-------|-----------------|-----------|
| WB-VAL-01 | `required()` | Empty check | `""` | Error added for field | □ |
| WB-VAL-02 | `required()` | Non-empty | `"hello"` | No error | □ |
| WB-VAL-03 | `email()` | Invalid format | `"not-email"` | Error added | □ |
| WB-VAL-04 | `email()` | Valid format | `"a@b.com"` | No error | □ |
| WB-VAL-05 | `phone()` | 10 digits | `"9876543210"` | No error | □ |
| WB-VAL-06 | `phone()` | Not 10 digits | `"123"` | Error added | □ |
| WB-VAL-07 | `minLength()` | Below min | `"ab"`, min=3 | Error added | □ |
| WB-VAL-08 | `sanitize()` | XSS attempt | `"<script>alert(1)</script>"` | Tags stripped, entities escaped | □ |
| WB-VAL-09 | `fails()` / `passes()` | After errors | After adding error | `fails()=true`, `passes()=false` | □ |
| WB-VAL-10 | `getFirstError()` | Multiple errors | 2 errors added | Returns first error string | □ |

### 5.6 RBACSeeder.php

| TC ID | Function Under Test | Code Path | Input | Expected Output | Pass/Fail |
|-------|---------------------|-----------|-------|-----------------|-----------|
| WB-RBAC-01 | `seedIfNeeded()` | Full flow | Fresh DB | Tables + 6 roles + 45 perms + mappings created | □ |
| WB-RBAC-02 | `seedIfNeeded()` | Idempotency | Called twice | No duplicates, no error | □ |
| WB-RBAC-03 | `seedForOrganization()` | Org-scoped | New org_id=10 | 6 roles with org_id=10, perms assigned | □ |
| WB-RBAC-04 | `seedForOrganization()` | Invalid input | `null` org_id | Returns `false` | □ |
| WB-RBAC-05 | `ensureSuperAdminHasAllPermissions()` | Count check | After seeding | super_admin role has COUNT(perms)=45 | □ |

---

### Test Summary Table

| Category | Total Cases | Section |
|----------|-------------|---------|
| Black Box – Authentication | 10 | 4.1 |
| Black Box – Products | 8 | 4.2 |
| Black Box – Stock | 8 | 4.3 |
| Black Box – Invoice | 8 | 4.4 |
| Black Box – RBAC | 8 | 4.5 |
| Black Box – Customers/Suppliers | 5 | 4.6 |
| Black Box – Expenses | 3 | 4.7 |
| Black Box – Users/Settings | 4 | 4.8 |
| **Black Box Total** | **54** | |
| White Box – Session | 10 | 5.1 |
| White Box – AuthHelper | 10 | 5.2 |
| White Box – PermissionMiddleware | 10 | 5.3 |
| White Box – Database | 10 | 5.4 |
| White Box – Validator | 10 | 5.5 |
| White Box – RBACSeeder | 5 | 5.6 |
| **White Box Total** | **55** | |
| **Grand Total** | **109** | |

---

*End of Document*
