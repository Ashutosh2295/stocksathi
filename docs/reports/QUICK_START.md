# 🚀 StockSathi - Quick Start Guide

## For New Installation (First Time Setup)

When you give this project to someone, they should follow these steps:

---

## 📋 **Step 1: Extract & Place Files**

```
1. Extract the project folder
2. Place in: C:\xampp\htdocs\stocksathi
   (or your web server's document root)
```

---

## 📋 **Step 2: Create Database**

```
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click "New" to create database
3. Database name: stocksathi
4. Collation: utf8mb4_general_ci
5. Click "Create"
```

---

## 📋 **Step 3: Import Database**

```
1. Select "stocksathi" database
2. Click "Import" tab
3. Choose file: stocksathi.sql (in project root)
4. Click "Go"
5. Wait for success message
```

---

## 📋 **Step 4: Configure Database Connection**

Edit `_includes/config.php`:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'stocksathi');
define('DB_USER', 'root');
define('DB_PASS', '');  // Change if you have password
```

---

## 📋 **Step 5: Open Application**

```
Open browser: http://localhost/stocksathi
```

**What Happens:**
- ✅ System checks if setup is complete
- ✅ If NO setup: Redirects to setup-organization.php
- ✅ If setup done: Redirects to login page
- ✅ If logged in: Goes to dashboard

---

## 🎯 **First Time Flow**

### **Scenario 1: Fresh Installation**

```
1. Open: http://localhost/stocksathi
   ↓
2. Auto-redirects to: setup-organization.php
   ↓
3. Click: "Step 1: Create Organizations Table"
   ↓
4. Click: "Step 2: Add Organization Columns"
   ↓
5. Click: "Go to Registration"
   ↓
6. Fill 3-step registration form
   ↓
7. Auto-redirects to login page
   ↓
8. Login with your credentials
   ↓
9. Dashboard opens!
```

### **Scenario 2: Already Set Up**

```
1. Open: http://localhost/stocksathi
   ↓
2. Auto-redirects to: pages/login.php
   ↓
3. Login with credentials
   ↓
4. Dashboard opens!
```

### **Scenario 3: Already Logged In**

```
1. Open: http://localhost/stocksathi
   ↓
2. Auto-redirects to: pages/dashboards/super-admin.php
   ↓
3. Dashboard opens directly!
```

---

## 📁 **Files to Include When Sharing**

### **Essential Files:**
```
✅ All PHP files
✅ _includes/ folder
✅ pages/ folder
✅ css/ folder
✅ js/ folder
✅ migrations/ folder
✅ stocksathi.sql (database dump)
✅ README.md
✅ QUICK_START.md (this file)
```

### **Optional Files (Documentation):**
```
📄 ORGANIZATION_SYSTEM_README.md
📄 SETUP_INSTRUCTIONS.md
📄 IMPLEMENTATION_SUMMARY.md
📄 VISUAL_GUIDE.html
```

### **Files to EXCLUDE:**
```
❌ .git/ folder
❌ node_modules/ (if any)
❌ vendor/ (if any)
❌ .env files with passwords
❌ uploads/ folder with user data
```

---

## ✅ **Checklist for Sharing Project**

Before giving to someone:

- [ ] Database exported (stocksathi.sql)
- [ ] Config.php has default settings
- [ ] No personal data in database
- [ ] README.md included
- [ ] QUICK_START.md included
- [ ] All required folders included
- [ ] Tested fresh installation
- [ ] Default passwords documented

---

**That's it! Your StockSathi is ready to share!** 🚀
