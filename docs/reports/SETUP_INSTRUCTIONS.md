# 🚀 SETUP INSTRUCTIONS - Organization System

## ⚡ Quick Setup (2 Steps)

### Step 1: Create Organizations Table
1. Open: `http://localhost/stocksathi/setup-organization.php`
2. Click: **"Step 1: Create Organizations Table"**
3. Wait for success message

### Step 2: Add Organization Columns
1. Click: **"Step 2: Add Organization Columns"**
   (Or open: `http://localhost/stocksathi/add-organization-columns.php`)
2. Click: **"Add Organization Columns"**
3. Wait for all tables to be updated

### Step 3: Register & Use
1. Click: **"Go to Registration"**
2. Register your organization
3. Login and start using!

---

## 📋 What Each Step Does

### Step 1: Create Organizations Table
- Creates the `organizations` table in your database
- This table stores all organization/company information
- Required before adding organization_id to other tables

### Step 2: Add Organization Columns
- Adds `organization_id` column to all major tables:
  - users, products, customers, suppliers
  - invoices, quotations, expenses
  - warehouses, stores, categories, brands
  - employees, departments
- Adds indexes for better performance
- Does NOT add foreign keys (to preserve existing data)

---

## ❓ Why Two Steps?

The setup is split into two steps to avoid foreign key constraint errors:

1. **First**, we create the `organizations` table
2. **Then**, we add `organization_id` columns to existing tables

This approach:
- ✅ Works with existing data
- ✅ Avoids foreign key errors
- ✅ Safe for production databases
- ✅ Can be run multiple times safely

---

## 🔧 Troubleshooting

### Error: "Table 'organizations' doesn't exist"
**Solution**: Run Step 1 first

### Error: "Column 'organization_id' already exists"
**Solution**: This is fine! The script checks and skips existing columns

### Error: "Access denied"
**Solution**: Check your database credentials in `_includes/database.php`

---

## ✅ Verification

After both steps complete, verify:

```sql
-- Check organizations table exists
SHOW TABLES LIKE 'organizations';

-- Check users table has organization_id
SHOW COLUMNS FROM users LIKE 'organization_id';
```

---

## 🎯 Next Steps

After setup is complete:

1. **Register Organization**
   - Go to: `pages/register.php`
   - Fill organization details
   - Fill super admin details
   - Submit

2. **Login**
   - Use your username and password
   - You'll be redirected to your dashboard

3. **Start Using**
   - Add products, customers, invoices
   - All data will be linked to your organization
   - Complete data isolation guaranteed

---

## 📖 Documentation

- **Full Guide**: `ORGANIZATION_SYSTEM_README.md`
- **Implementation Details**: `IMPLEMENTATION_SUMMARY.md`
- **Quick Reference**: `QUICK_REFERENCE.md`
- **Visual Guide**: `VISUAL_GUIDE.html`

---

**Ready to start?** Open `setup-organization.php` and follow the steps!
