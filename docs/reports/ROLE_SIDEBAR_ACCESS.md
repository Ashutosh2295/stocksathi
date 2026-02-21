# 📊 STOCKSATHI - Role-Based Sidebar Access Matrix

## Visual Summary: What Each Role Sees

---

## 🔴 SUPER ADMIN (`super_admin`)
**Dashboard Access:** Super Admin, Admin, Sales
**Full Access to Everything**

```
📂 Dashboard
   ├── Super Admin Dashboard ✅
   ├── Admin Dashboard ✅
   └── Sales Dashboard ✅

📂 Product Management
   ├── Products ✅
   ├── Categories ✅
   └── Brands ✅

📂 Stock Management
   ├── Stock In ✅
   ├── Stock Out ✅
   ├── Adjustments ✅
   └── Transfers ✅

📂 Sales & Billing
   ├── Invoices ✅
   ├── Quotations ✅
   └── Sales Returns ✅

📂 Marketing
   └── Promotions ✅

📂 Finance
   └── Expenses ✅

📂 People
   ├── Customers ✅
   ├── Suppliers ✅
   ├── Stores ✅
   └── Warehouses ✅

📂 Human Resources
   ├── Employees ✅
   ├── Departments ✅
   ├── Attendance ✅
   └── Leave Management ✅

📂 Analytics
   └── Reports ✅

📂 Administration
   ├── Users ✅
   ├── Roles & Permissions ✅
   ├── Activity Logs ✅
   └── Settings ✅
```

---

## 🟢 ADMIN (`admin`)
**Dashboard Access:** Admin, Sales
**Almost full access, except Settings edit**

```
📂 Dashboard
   ├── Admin Dashboard ✅
   └── Sales Dashboard ✅

📂 Product Management
   ├── Products ✅
   ├── Categories ✅
   └── Brands ✅

📂 Stock Management
   ├── Stock In ✅
   ├── Stock Out ✅
   ├── Adjustments ✅
   └── Transfers ✅

📂 Sales & Billing
   ├── Invoices ✅
   ├── Quotations ✅
   └── Sales Returns ✅

📂 Marketing
   └── Promotions ✅

📂 Finance
   └── Expenses ✅

📂 People
   ├── Customers ✅
   ├── Suppliers ✅
   ├── Stores ✅
   └── Warehouses ✅

📂 Human Resources
   ├── Employees ✅
   ├── Departments ✅
   ├── Attendance ✅
   └── Leave Management ✅

📂 Analytics
   └── Reports ✅

📂 Administration
   ├── Users ✅
   ├── Roles & Permissions ✅
   ├── Activity Logs ✅
   └── Settings ❌ (Hidden)
```

---

## 🟡 STORE MANAGER (`store_manager`)
**Dashboard Access:** Store Manager Dashboard only
**Focus: Store operations, sales, basic stock**

```
📂 Dashboard
   └── Store Dashboard ✅

📂 Product Management
   ├── Products ✅ (View)
   ├── Categories ✅ (View)
   └── Brands ✅ (View)

📂 Stock Management
   ├── Stock In ✅
   ├── Stock Out ✅
   ├── Adjustments ✅
   └── Transfers ❌ (Hidden)

📂 Sales & Billing
   ├── Invoices ✅
   ├── Quotations ✅
   └── Sales Returns ✅

📂 Marketing
   └── ❌ (Section Hidden)

📂 Finance
   └── Expenses ✅

📂 People
   ├── Customers ✅
   ├── Suppliers ✅ (View)
   ├── Stores ✅ (View)
   └── Warehouses ✅ (View)

📂 Human Resources
   ├── Employees ✅ (View)
   ├── Departments ❌ (Hidden)
   ├── Attendance ✅ (View)
   └── Leave Management ✅ (View)

📂 Analytics
   └── Reports ✅ (Sales & Stock only)

📂 Administration
   └── Activity Logs ✅ (View)
   └── Users, Roles, Settings ❌ (Hidden)
```

---

## 🔵 SALES EXECUTIVE (`sales_executive`)
**Dashboard Access:** Sales Dashboard only
**Focus: Sales, invoices, customers**

```
📂 Dashboard
   └── Sales Dashboard ✅

📂 Product Management
   ├── Products ✅ (View only)
   ├── Categories ✅ (View only)
   └── Brands ✅ (View only)

📂 Stock Management
   └── ❌ (Section Hidden)

📂 Sales & Billing
   ├── Invoices ✅ (Create & View Own)
   ├── Quotations ✅
   └── Sales Returns ✅

📂 Marketing
   └── ❌ (Section Hidden)

📂 Finance
   └── ❌ (Section Hidden)

📂 People
   └── Customers ✅ (Create & View)
   └── Suppliers, Stores, Warehouses ❌ (Hidden)

📂 Human Resources
   └── ❌ (Section Hidden)

📂 Analytics
   └── Reports ✅ (Sales reports only)

📂 Administration
   └── ❌ (Section Hidden)
```

---

## 🟣 ACCOUNTANT (`accountant`)
**Dashboard Access:** Accountant Dashboard only
**Focus: Finance, expenses, reports, GST**

```
📂 Dashboard
   └── Accountant Dashboard ✅

📂 Product Management
   └── Products ✅ (View with purchase prices)
   └── Categories, Brands ❌ (Hidden)

📂 Stock Management
   └── ❌ (Section Hidden)

📂 Sales & Billing
   └── Invoices ✅ (View all for accounting)
   └── Quotations, Sales Returns ❌ (Hidden)

📂 Marketing
   └── ❌ (Section Hidden)

📂 Finance
   └── Expenses ✅ (Full access)

📂 People
   ├── Customers ✅ (View balances)
   └── Suppliers ✅ (View balances)
   └── Stores, Warehouses ❌ (Hidden)

📂 Human Resources
   └── ❌ (Section Hidden)

📂 Analytics
   └── Reports ✅ (Financial, GST, P&L)

📂 Administration
   └── Activity Logs ✅ (View only)
   └── Users, Roles, Settings ❌ (Hidden)
```

---

## 🟤 WAREHOUSE MANAGER (`warehouse_manager`)
**Dashboard Access:** Warehouse Dashboard
**Focus: Stock and warehouse operations**

```
📂 Dashboard
   └── Warehouse Dashboard ✅

📂 Product Management
   ├── Products ✅
   ├── Categories ✅
   └── Brands ✅

📂 Stock Management
   ├── Stock In ✅
   ├── Stock Out ✅
   ├── Adjustments ✅
   └── Transfers ✅

📂 Sales & Billing
   └── ❌ (Section Hidden)

📂 Marketing
   └── ❌ (Section Hidden)

📂 Finance
   └── ❌ (Section Hidden)

📂 People
   ├── Suppliers ✅
   └── Warehouses ✅
   └── Customers, Stores ❌ (Hidden)

📂 Human Resources
   └── ❌ (Section Hidden)

📂 Analytics
   └── Reports ✅ (Stock reports only)

📂 Administration
   └── ❌ (Section Hidden)
```

---

## 📋 Quick Comparison Table

| Menu Section | Super Admin | Admin | Store Mgr | Sales Exec | Accountant | Warehouse Mgr |
|--------------|:-----------:|:-----:|:---------:|:----------:|:----------:|:-------------:|
| **Products** | ✅ Full | ✅ Full | ✅ View | ✅ View | ✅ View | ✅ Full |
| **Categories** | ✅ | ✅ | ✅ View | ✅ View | ❌ | ✅ |
| **Brands** | ✅ | ✅ | ✅ View | ✅ View | ❌ | ✅ |
| **Stock In/Out** | ✅ | ✅ | ✅ | ❌ | ❌ | ✅ |
| **Stock Transfer** | ✅ | ✅ | ❌ | ❌ | ❌ | ✅ |
| **Invoices** | ✅ | ✅ | ✅ | ✅ Own | ✅ View | ❌ |
| **Quotations** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Sales Returns** | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ |
| **Promotions** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Expenses** | ✅ | ✅ | ✅ | ❌ | ✅ Full | ❌ |
| **Customers** | ✅ | ✅ | ✅ | ✅ | ✅ View | ❌ |
| **Suppliers** | ✅ | ✅ | ✅ View | ❌ | ✅ View | ✅ |
| **Stores** | ✅ | ✅ | ✅ View | ❌ | ❌ | ❌ |
| **Warehouses** | ✅ | ✅ | ✅ View | ❌ | ❌ | ✅ |
| **Employees** | ✅ | ✅ | ✅ View | ❌ | ❌ | ❌ |
| **Departments** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Attendance** | ✅ | ✅ | ✅ View | ❌ | ❌ | ❌ |
| **Leave Mgmt** | ✅ | ✅ | ✅ View | ❌ | ❌ | ❌ |
| **Reports** | ✅ All | ✅ All | ✅ Limited | ✅ Sales | ✅ Finance | ✅ Stock |
| **Users** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Roles** | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| **Activity Logs** | ✅ | ✅ | ✅ View | ❌ | ✅ View | ❌ |
| **Settings** | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ |

---

## 🔐 Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@stocksathi.com | password123 |
| Admin | admin@stocksathi.com | password123 |
| Store Manager | store@stocksathi.com | password123 |
| Sales Executive | sales1@stocksathi.com | password123 |
| Accountant | accounts@stocksathi.com | password123 |

---

**Document Updated:** 2026-01-17
