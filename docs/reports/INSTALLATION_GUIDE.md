# 🚀 StockSathi - One-Click Installation Guide

## 📋 Table of Contents
1. [Quick Start](#quick-start)
2. [System Requirements](#system-requirements)
3. [Installation Steps](#installation-steps)
4. [Post-Installation](#post-installation)
5. [Troubleshooting](#troubleshooting)
6. [Manual Installation](#manual-installation)

---

## ⚡ Quick Start

### **3 Simple Steps to Get Started:**

1. **Start MySQL**
   - Open XAMPP Control Panel
   - Click "Start" for MySQL (should turn green)

2. **Run Installer**
   - Open your browser
   - Navigate to: `http://localhost/stocksathi/INSTALLER.php`
   - Follow the on-screen instructions

3. **Login & Use**
   - Email: `admin@stocksathi.com`
   - Password: `admin123`

**That's it! Your system is ready to use! 🎉**

---

## 💻 System Requirements

### Minimum Requirements:
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher (or MariaDB 10.2+)
- **Web Server:** Apache (included in XAMPP)
- **Disk Space:** 100 MB
- **RAM:** 512 MB minimum

### Required PHP Extensions:
- ✅ PDO
- ✅ PDO_MySQL
- ✅ JSON
- ✅ MBString
- ✅ OpenSSL

### Recommended Software:
- **XAMPP** (Windows/Mac/Linux) - [Download](https://www.apachefriends.org/)
- **WAMP** (Windows) - [Download](https://www.wampserver.com/)
- **MAMP** (Mac) - [Download](https://www.mamp.info/)
- **LAMP** (Linux) - Install via package manager

---

## 📦 Installation Steps

### Method 1: Using the Installer (Recommended)

#### Step 1: Download & Extract
1. Download the StockSathi package
2. Extract to your web server directory:
   - **XAMPP:** `C:\xampp\htdocs\stocksathi`
   - **WAMP:** `C:\wamp64\www\stocksathi`
   - **MAMP:** `/Applications/MAMP/htdocs/stocksathi`

#### Step 2: Start Services
1. Open XAMPP/WAMP/MAMP Control Panel
2. Start **Apache** service
3. Start **MySQL** service
4. Ensure both are running (green status)

#### Step 3: Run Installer
1. Open your web browser
2. Navigate to: `http://localhost/stocksathi/INSTALLER.php`
3. Follow the installation wizard:
   - **Step 1:** Welcome screen - Click "Get Started"
   - **Step 2:** System checks - Verify all requirements pass
   - **Step 3:** Database config - Enter credentials (default: root/blank)
   - **Step 4:** Installation - Click "Install Now"
   - **Step 5:** Complete - Get your login credentials

#### Step 4: First Login
1. Click "Go to Login Page" or navigate to: `http://localhost/stocksathi/pages/login.php`
2. Enter credentials:
   - **Email:** admin@stocksathi.com
   - **Password:** admin123
3. Click "Login"
4. You'll be redirected to your dashboard!

---

## 🎯 Post-Installation

### Important First Steps:

#### 1. Change Default Password
```
1. Go to Settings → Profile
2. Click "Change Password"
3. Enter new secure password
4. Save changes
```

#### 2. Configure System Settings
```
1. Navigate to Settings → General
2. Update:
   - Company Name
   - Company Email
   - Company Phone
   - Tax Rate
   - Currency
3. Save settings
```

#### 3. Explore Sample Data
The installer includes sample data for testing:
- ✅ 10 Products (Electronics, Clothing, etc.)
- ✅ 5 Customers
- ✅ 4 Suppliers
- ✅ 3 Sample Invoices
- ✅ 3 Warehouses & 3 Stores
- ✅ 3 Employees with departments

#### 4. Delete Sample Data (Optional)
Once you're familiar with the system:
```
1. Go to each module (Products, Customers, etc.)
2. Delete sample records
3. Start adding your real data
```

#### 5. Create Additional Users
```
1. Navigate to Users → Add New
2. Fill in user details
3. Assign appropriate role:
   - Super Admin (full access)
   - Admin (administrative access)
   - Store Manager (inventory management)
   - Sales Executive (sales operations)
   - Accountant (financial access)
4. Save user
```

---

## 🔧 Troubleshooting

### Common Issues & Solutions:

#### Issue 1: "Cannot connect to database"
**Solution:**
1. Verify MySQL is running in XAMPP/WAMP
2. Check database credentials in installer
3. Default credentials:
   - Host: `localhost`
   - Username: `root`
   - Password: (leave empty)

#### Issue 2: "INSTALLER.php shows blank page"
**Solution:**
1. Check PHP error logs
2. Verify PHP version (must be 7.4+)
3. Enable error display:
   ```php
   // Add to top of INSTALLER.php temporarily
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

#### Issue 3: "Permission denied" errors
**Solution:**
1. **Windows:** Right-click folder → Properties → Security → Edit → Give full control
2. **Linux/Mac:**
   ```bash
   chmod -R 755 /path/to/stocksathi
   chmod -R 777 /path/to/stocksathi/_includes
   ```

#### Issue 4: "SQL file not found"
**Solution:**
1. Ensure `stocksathi_complete.sql` is in the same directory as `INSTALLER.php`
2. Check file permissions
3. Re-download if corrupted

#### Issue 5: "Installation stuck at Step X"
**Solution:**
1. Clear browser cache
2. Clear PHP session:
   ```php
   // Create clear_session.php in root
   <?php
   session_start();
   session_destroy();
   echo "Session cleared!";
   ?>
   ```
3. Run installer again

#### Issue 6: "Login page not found"
**Solution:**
1. Verify folder structure:
   ```
   stocksathi/
   ├── INSTALLER.php
   ├── index.php
   ├── pages/
   │   └── login.php
   └── _includes/
       └── database.php
   ```
2. Check `.htaccess` file exists
3. Ensure mod_rewrite is enabled in Apache

---

## 🛠️ Manual Installation

If the installer doesn't work, follow these manual steps:

### Step 1: Import Database
```bash
# Using Command Line
cd C:\xampp\mysql\bin
mysql -u root < C:\xampp\htdocs\stocksathi\stocksathi_complete.sql

# Or using phpMyAdmin
1. Open http://localhost/phpmyadmin
2. Click "Import" tab
3. Choose file: stocksathi_complete.sql
4. Click "Go"
```

### Step 2: Create Database Config
Create file: `_includes/database.php`
```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'stocksathi');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
```

### Step 3: Set Permissions
```bash
# Windows - Run as Administrator
icacls "C:\xampp\htdocs\stocksathi" /grant Users:F /t

# Linux/Mac
chmod -R 755 /path/to/stocksathi
chmod -R 777 /path/to/stocksathi/_includes
```

### Step 4: Access Application
Navigate to: `http://localhost/stocksathi/pages/login.php`

---

## 📊 What Gets Installed

### Database Structure:
- **38 Tables** covering all modules
- **350+ Columns** with proper data types
- **25+ Foreign Keys** for data integrity
- **50+ Indexes** for performance

### Sample Data:
| Category | Count | Description |
|----------|-------|-------------|
| Users | 3 | Admin, Manager, User roles |
| Products | 10 | Electronics, Clothing, etc. |
| Customers | 5 | With realistic contact info |
| Suppliers | 4 | With payment terms |
| Invoices | 3 | Paid and partial status |
| Warehouses | 3 | Different locations |
| Stores | 3 | Retail locations |
| Employees | 3 | With departments |
| Departments | 5 | Sales, Operations, Finance, IT, HR |

### Modules Included:
✅ Authentication & Authorization  
✅ Dashboard with Analytics  
✅ Product Management  
✅ Inventory Management  
✅ Customer Management  
✅ Supplier Management  
✅ Sales & Invoicing  
✅ Quotations  
✅ Sales Returns  
✅ Expense Tracking  
✅ Promotions  
✅ Employee Management  
✅ Department Management  
✅ Attendance System  
✅ Leave Management  
✅ User Management  
✅ Role Management  
✅ Activity Logs  
✅ System Settings  
✅ Reports & Analytics  

---

## 🔐 Default Credentials

### Admin Account:
```
Email: admin@stocksathi.com
Password: admin123
Role: Administrator
```

### Manager Account:
```
Email: manager@stocksathi.com
Password: admin123
Role: Manager
```

### User Account:
```
Email: john@stocksathi.com
Password: admin123
Role: User
```

**⚠️ IMPORTANT:** Change all default passwords immediately after installation!

---

## 📁 File Structure

```
stocksathi/
├── INSTALLER.php              ← Run this to install
├── index.php                  ← Main dashboard
├── stocksathi_complete.sql    ← Database schema
├── _includes/                 ← Core PHP files
│   ├── config.php
│   ├── database.php
│   ├── Session.php
│   ├── AuthHelper.php
│   └── ...
├── pages/                     ← All module pages
│   ├── login.php
│   ├── register.php
│   ├── products.php
│   ├── customers.php
│   └── ...
├── css/                       ← Stylesheets
├── js/                        ← JavaScript files
└── assets/                    ← Images, icons
```

---

## 🆘 Getting Help

### Documentation:
- **README.md** - Project overview
- **COMPLETE_SETUP_READY.md** - Setup details
- **DATABASE_REFERENCE.md** - Database schema
- **RBAC_GUIDE.md** - Role-based access control

### Support:
- Check documentation files in the project root
- Review error logs in `_includes/logs/`
- Verify system requirements are met

---

## ✅ Installation Checklist

Before starting:
- [ ] XAMPP/WAMP/MAMP installed
- [ ] Apache service running
- [ ] MySQL service running
- [ ] PHP 7.4+ installed
- [ ] All files extracted to web directory

During installation:
- [ ] All system checks passed
- [ ] Database connection successful
- [ ] Database created successfully
- [ ] Sample data imported
- [ ] Configuration file created

After installation:
- [ ] Can access login page
- [ ] Can login with default credentials
- [ ] Dashboard loads correctly
- [ ] All modules accessible
- [ ] Changed default password

---

## 🎉 Success!

If you've completed all steps, your StockSathi installation is ready!

**Next Steps:**
1. ✅ Login to your dashboard
2. ✅ Explore the sample data
3. ✅ Configure system settings
4. ✅ Add your real data
5. ✅ Start managing your inventory!

---

**Version:** 2.0 - Production Ready  
**Last Updated:** January 2026  
**Created with ❤️ for StockSathi**
