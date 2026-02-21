# 🚀 StockSathi - Production Setup Guide

## ✅ Quick Start (3 Steps)

### Step 1: Setup Database
1. Start XAMPP Control Panel
2. Start **Apache** and **MySQL**
3. Open phpMyAdmin: `http://localhost/phpmyadmin`
4. Create a new database named: **`stocksathi`**
5. Import the SQL file:
   - Click on `stocksathi` database
   - Go to "Import" tab
   - Choose file: `C:\xampp_new\htdocs\stocksathi\stocksathi\sql\stocksathi.sql`
   - Click "Go"

### Step 2: Create Default User (Optional)
Run this SQL in phpMyAdmin to create a test admin account:

```sql
INSERT INTO users (username, email, password, full_name, role, status) 
VALUES (
    'admin', 
    'admin@stocksathi.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    'Admin User', 
    'admin', 
    'active'
);
```

**Login Credentials:**
- Username: `admin`
- Password: `password`

### Step 3: Access the Application
Open your browser and go to:

📌 **Login Page:** `http://localhost/stocksathi/pages/login.php`

After login, you'll be redirected to the dashboard automatically!

---

## 📂 Application URLs

| Page | URL |
|------|-----|
| **Login** | `http://localhost/stocksathi/pages/login.php` |
| **Dashboard** | `http://localhost/stocksathi/index.php` |
| **Landing Page** | `http://localhost/stocksathi/index.html` |
| **Products** | `http://localhost/stocksathi/pages/products.php` |

---

## 🔧 Database Configuration

File: `stocksathi/config/database.php`

```php
private $host = 'localhost';
private $database = 'stocksathi';
private $username = 'root';
private $password = '';  // Default XAMPP password is empty
```

---

## 🎯 Production Ready Features

✅ PHP/MySQL backend  
✅ Secure authentication with session management  
✅ RESTful API endpoints  
✅ CRUD operations for all modules  
✅ Clean, professional UI  
✅ Responsive design  

---

## 📞 Troubleshooting

### Error: "Not Found"
- Make sure XAMPP Apache is running
- Check the URL is correct: `http://localhost/stocksathi/pages/login.php`

### Error: "Database connection failed"
- Make sure MySQL is running in XAMPP
- Verify database `stocksathi` exists in phpMyAdmin
- Check credentials in `stocksathi/config/database.php`

### Can't login?
- Make sure you imported the SQL file
- If you created a user manually, use those credentials
- Default test credentials: `admin` / `password`

---

**🎉 You're all set! Your application is production-ready!**
