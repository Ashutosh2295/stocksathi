# 📊 STOCKSATHI - COMPLETE DATABASE REFERENCE

## 🎯 Database Overview

**Database Name:** `stocksathi`  
**Total Tables:** 38  
**Character Set:** UTF8MB4  
**Collation:** utf8mb4_unicode_ci

---

## 📁 Module-wise Table Structure

### 1. 🔐 AUTHENTICATION & AUTHORIZATION (2 tables)
- ✅ **users** - User accounts and authentication
- ✅ **roles** - User roles and permissions

### 2. 📦 PRODUCT MANAGEMENT (3 tables)
- ✅ **products** - Product master data
- ✅ **categories** - Product categories
- ✅ **brands** - Product brands

### 3. 🏭 STOCK MANAGEMENT (6 tables)
- ✅ **warehouses** - Warehouse locations
- ✅ **stores** - Store locations
- ✅ **stock_in** - Stock inward transactions
- ✅ **stock_out** - Stock outward transactions
- ✅ **stock_adjustments** - Stock adjustments
- ✅ **stock_transfers** - Inter-warehouse transfers

### 4. 👥 CUSTOMERS & SUPPLIERS (2 tables)
- ✅ **customers** - Customer master data
- ✅ **suppliers** - Supplier master data

### 5. 💰 SALES MODULES (8 tables)
- ✅ **invoices** - Sales invoices
- ✅ **invoice_items** - Invoice line items
- ✅ **quotations** - Sales quotations
- ✅ **quotation_items** - Quotation line items
- ✅ **sales_returns** - Sales return transactions
- ✅ **sales_return_items** - Return line items

### 6. 💵 FINANCE MODULES (2 tables)
- ✅ **expenses** - Expense tracking
- ✅ **promotions** - Promotional campaigns

### 7. 👔 HRM MODULES (5 tables)
- ✅ **departments** - Department master
- ✅ **employees** - Employee master data
- ✅ **attendance** - Employee attendance
- ✅ **leave_requests** - Leave management

### 8. ⚙️ SYSTEM MODULES (2 tables)
- ✅ **activity_logs** - System activity tracking
- ✅ **settings** - Application settings

---

## 🔑 Sample Login Credentials

All demo users have password: **admin123**

| Username | Email | Role | Access Level |
|----------|-------|------|--------------|
| admin | admin@stocksathi.com | admin | Full Access |
| manager | manager@stocksathi.com | manager | Manager Access |
| john | john@stocksathi.com | user | Basic Access |

---

## 📊 Sample Data Included

### Products (10 items)
- iPhone 13 Pro, Samsung Galaxy S21, Dell Laptop
- HP Printer, Nike Shoes, Adidas Shorts
- Sony Headphones, LG TV, MacBook Air, Galaxy Tab

### Customers (5)
- Rajesh Kumar, Priya Sharma, Amit Patel
- Sneha Reddy, Vikram Singh

### Suppliers (4)
- Tech Supplies Ltd, Mobile World Distributors
- Fashion Hub Wholesale, Electronics Mega Store

### Locations
- **Warehouses:** 3 (Mumbai, Delhi, Bangalore)
- **Stores:** 3 (Downtown, Mall, Express)

### Invoices (3)
- INV-2024-001 (Paid - ₹1,35,700)
- INV-2024-002 (Paid - ₹82,598)
- INV-2024-003 (Partial - ₹53,100)

---

## 🚀 Quick Setup Guide

### Step 1: Ensure MySQL is Running
```
XAMPP Control Panel → MySQL → Start
```

### Step 2: Import Database
**Option A - phpMyAdmin:**
1. Open: http://localhost/phpmyadmin
2. Click "Import" tab
3. Choose file: `stocksathi_complete.sql`
4. Click "Go"

**Option B - Command Line:**
```cmd
cd C:\xampp_new\mysql\bin
mysql -u root < C:\xampp_new\htdocs\stocksathi\stocksathi_complete.sql
```

### Step 3: Verify Setup
Open in browser:
```
http://localhost/stocksathi/test_db_simple.php
```

All checks should be ✅ GREEN

### Step 4: Login
```
http://localhost/stocksathi/pages/login.php
Email: admin@stocksathi.com
Password: admin123
```

---

## 🗂️ Table Relationships

### Foreign Keys Summary

**Products:**
- `category_id` → categories(id)
- `brand_id` → brands(id)

**Stock Transactions:**
- `product_id` → products(id)
- `warehouse_id` → warehouses(id)
- `supplier_id` → suppliers(id)

**Sales:**
- `customer_id` → customers(id)
- `invoice_id` → invoices(id)
- `product_id` → products(id)

**HRM:**
- `department_id` → departments(id)
- `employee_id` → employees(id)
- `user_id` → users(id)

---

## 📋 Module-Page Mapping

### Which page uses which table:

| Page | Tables Used |
|------|-------------|
| **products.php** | products, categories, brands |
| **categories.php** | categories |
| **brands.php** | brands |
| **stock-in.php** | stock_in, products, warehouses, suppliers |
| **stock-out.php** | stock_out, products, warehouses |
| **stock-adjustments.php** | stock_adjustments, products, warehouses |
| **stock-transfers.php** | stock_transfers, products, warehouses |
| **customers.php** | customers |
| **suppliers.php** | suppliers |
| **warehouses.php** | warehouses |
| **stores.php** | stores |
| **invoices.php** | invoices, invoice_items, customers, products |
| **quotations.php** | quotations, quotation_items, customers, products |
| **sales-returns.php** | sales_returns, sales_return_items, invoices |
| **expenses.php** | expenses |
| **promotions.php** | promotions |
| **employees.php** | employees, departments, users |
| **departments.php** | departments |
| **attendance.php** | attendance, employees |
| **leave-management.php** | leave_requests, employees |
| **users.php** | users, roles |
| **roles.php** | roles |
| **activity-logs.php** | activity_logs, users |
| **settings.php** | settings |
| **reports.php** | All tables (for reporting) |
| **sales-dashboard.php** | invoices, products, customers (analytics) |

---

## 🎨 Database Features

### ✅ Built-in Features:
1. **Auto-incrementing IDs** on all tables
2. **Timestamps** (created_at, updated_at) on all tables
3. **Soft deletes** support via status fields
4. **Foreign key constraints** for data integrity
5. **Unique constraints** on critical fields
6. **Indexes** on frequently queried columns
7. **Enum types** for status fields
8. **Decimal precision** for currency fields

### 🔒 Data Integrity:
- Cascading deletes on child records
- Set NULL on parent deletion where applicable
- Prevent orphaned records
- Maintain referential integrity

---

## 💡 Pro Tips

### For Development:
1. **Backup before import:** Always backup existing data
2. **Check MySQL version:** Supports MySQL 5.7+
3. **Character set:** UTF8MB4 supports emojis and special chars
4. **Foreign keys:** Enable for production, disable for faster imports

### For Testing:
1. Use provided sample data to test all modules
2. Test user roles and permissions
3. Verify all CRUD operations work
4. Check reports and dashboards

### For Production:
1. Change default passwords immediately
2. Remove sample/demo data
3. Set up regular backups
4. Monitor activity logs
5. Optimize indexes based on usage

---

## 🆘 Troubleshooting

### Import Fails?
- Check MySQL is running
- Verify user has CREATE DATABASE privilege
- Ensure sufficient disk space
- Check error logs: `C:\xampp_new\mysql\data\mysql_error.log`

### Tables Missing?
- Re-run import
- Check for SQL errors in import log
- Verify database selected: `USE stocksathi;`

### Foreign Key Errors?
- Import creates tables in correct order
- If manual changes made, disable FK checks temporarily:
  ```sql
  SET FOREIGN_KEY_CHECKS=0;
  -- Your queries
  SET FOREIGN_KEY_CHECKS=1;
  ```

---

## 📞 Support

For issues or questions:
1. Check `SETUP_GUIDE.md` for setup instructions
2. Review `test_db_simple.php` for connection testing
3. Check MySQL error logs
4. Verify all prerequisites are met

---

**✅ Database Ready for Production Use!**

All 38 tables created with proper relationships, indexes, and sample data.
Perfect for immediate development and testing! 🚀
