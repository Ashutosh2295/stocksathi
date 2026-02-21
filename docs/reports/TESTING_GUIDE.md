# 🧪 Stocksathi Testing Guide

This guide will help you test all modules with dummy data and verify everything is working properly.

## 📋 Prerequisites

1. **Database Setup**: Make sure you have imported the `stocksathi_complete.sql` file
2. **Admin Credentials**: 
   - **Super Admin**: `admin@stocksathi.com` / `admin123`
   - **Admin**: `admin@stocksathi.com` / `admin123`
   - Or run `setup_admin.php` to ensure admin users exist

## 🚀 Step 1: Generate Dummy Data

### Option A: Via Browser
1. Navigate to: `http://localhost/stocksathi/generate_dummy_data.php`
2. Wait for the script to complete
3. You should see a success message with summary

### Option B: Via Command Line
```bash
cd c:\xampp_new\htdocs\stocksathi
php generate_dummy_data.php
```

**What it creates:**
- ✅ 8 Categories
- ✅ 8 Brands
- ✅ 4 Suppliers
- ✅ 35 Products (with stock)
- ✅ 15 Customers
- ✅ 30-50 Invoices (last 3 months)
- ✅ 10-15 Expenses (last 2 months)
- ✅ 20 Stock-in records
- ✅ 50 Activity logs

**⚠️ Important**: The script will NOT run if you already have more than 10 products or 5 invoices. Delete existing data first or use a fresh database.

## 📊 Step 2: Test Dashboards

### Admin Dashboard
1. Login as admin
2. Navigate to Dashboard
3. **Verify:**
   - ✅ Financial overview cards show revenue, expenses, profit
   - ✅ Quick stats show products, sales, customers
   - ✅ Sales trend chart displays last 7 days
   - ✅ Category distribution chart shows data
   - ✅ Top products list
   - ✅ Top sales executives list
   - ✅ Recent invoices table
   - ✅ Recent activity log

### Super Admin Dashboard
1. Login as super_admin
2. Should see comprehensive system overview

### Store Manager Dashboard
1. Login as store_manager
2. Should see stock-focused metrics

### Sales Executive Dashboard
1. Login as sales_executive
2. Should see sales and invoice metrics

## 🔍 Step 3: Test All Modules

### 1. Product Management
- [ ] **View Products**: `/pages/products.php`
  - Should show 35 products
  - Search functionality works
  - Filters work
- [ ] **Add Product**: Click "Add Product"
  - Form validation works
  - Product created successfully
- [ ] **Edit Product**: Click edit on any product
  - Can update product details
  - Stock updates correctly
- [ ] **Delete/Deactivate**: Soft delete works

### 2. Stock Management
- [ ] **Stock In**: `/pages/stock-in.php`
  - Can add stock-in records
  - Stock quantity updates
- [ ] **Stock Out**: `/pages/stock-out.php`
  - Can record stock out
  - Stock decreases correctly
- [ ] **Stock Adjustments**: `/pages/stock-adjustments.php`
  - Can adjust stock
  - Reason is required

### 3. Sales & Billing
- [ ] **Invoices List**: `/pages/invoices.php`
  - Should show 30-50 invoices
  - Search works
  - Filters work
  - Export CSV works
- [ ] **Create Invoice**: Click "New Invoice"
  - Can select customer
  - Can add multiple products
  - Calculations are correct (subtotal, tax, total)
  - Invoice created successfully
  - Stock deducted automatically
- [ ] **Invoice Details**: Click on any invoice
  - Shows all invoice details
  - Can print/download PDF

### 4. Customers
- [ ] **Customers List**: `/pages/customers.php`
  - Should show 15 customers
  - Search works
- [ ] **Add Customer**: Form works
- [ ] **Edit Customer**: Can update details
- [ ] **Delete Customer**: Soft delete works

### 5. Finance
- [ ] **Expenses**: `/pages/expenses.php`
  - Should show 10-15 expenses
  - Can add new expense
  - Can edit expense
  - Can approve/reject expenses
  - Filters work

### 6. Reports
- [ ] **Reports Page**: `/pages/reports.php`
  - **Sales Report**: Shows revenue, profit, margin
  - **Inventory Report**: Shows stock levels
  - **Expense Report**: Shows expenses
  - **Profit & Loss**: Shows P&L statement
  - **Customer Report**: Shows customer data
  - **Export Excel**: Downloads CSV file
  - **Export PDF**: Opens print view
  - Charts display properly
  - Date filters work

### 7. User Management
- [ ] **Users List**: `/pages/users.php`
  - Shows all users
- [ ] **Add User**: Form works
- [ ] **Edit User**: Can update details, role, password
- [ ] **Deactivate User**: Soft delete works

## ✅ Step 4: Verify Charts

All charts should display properly with data:

### Dashboard Charts
- [ ] Sales Trend Chart (Line chart)
- [ ] Stock Distribution Chart (Doughnut chart)
- [ ] Category Distribution Chart (Doughnut chart)

### Reports Charts
- [ ] Sales Trend Chart
- [ ] Top Products Chart
- [ ] Other report visualizations

**If charts are empty:**
- Run dummy data script
- Check browser console for errors
- Verify Chart.js library is loaded

## 🎨 Step 5: Verify UI/UX

### Color Consistency
- [ ] Primary color is `#0F766E` (teal)
- [ ] All buttons use consistent styling
- [ ] Cards have consistent shadows
- [ ] Hover effects work

### Responsive Design
- [ ] Dashboard works on desktop
- [ ] Tables are scrollable on mobile
- [ ] Forms are usable on all screen sizes

## 🐛 Common Issues & Fixes

### Issue: "No data" in charts
**Solution**: Run `generate_dummy_data.php` to populate data

### Issue: Charts not displaying
**Solution**: 
1. Check browser console for errors
2. Verify Chart.js CDN is accessible
3. Clear browser cache

### Issue: Database errors
**Solution**:
1. Verify database connection in `_includes/database.php`
2. Check if tables exist
3. Import SQL file again if needed

### Issue: "Permission denied" errors
**Solution**: 
1. Check user role in database
2. Run `setup_admin.php` to create admin users
3. Clear PHP OpCache: Run `clear_cache.php` or restart Apache

### Issue: Forms not submitting
**Solution**:
1. Check if session is active
2. Verify form action URLs
3. Check browser console for JavaScript errors

## 📝 Testing Checklist

After running dummy data, verify:

- [x] Dashboard shows real data
- [x] All charts render properly
- [x] Products module works (CRUD)
- [x] Stock management works
- [x] Invoices can be created and viewed
- [x] Customers module works
- [x] Expenses module works
- [x] Reports generate correctly
- [x] Reports can be exported
- [x] User management works
- [x] Role-based access works
- [x] All pages load without errors
- [x] Search/filter functionality works
- [x] UI is consistent and professional

## 🎉 Success Criteria

Your Stocksathi system is ready when:

1. ✅ All modules have test data
2. ✅ All dashboards display correctly
3. ✅ All charts render with data
4. ✅ All CRUD operations work
5. ✅ Reports can be generated and exported
6. ✅ UI looks professional and consistent
7. ✅ Role-based access is enforced
8. ✅ No console errors or warnings

## 📞 Next Steps

Once testing is complete:

1. **Customize**: Update company name, logo, colors
2. **Configure**: Set up GST rates, payment terms
3. **Train**: Train users on their respective roles
4. **Backup**: Regular database backups
5. **Monitor**: Check activity logs regularly

---

**Need Help?** Check the main `README.md` or `DOCUMENTATION.md` files for more details.
