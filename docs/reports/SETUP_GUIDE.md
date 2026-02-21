# Stocksathi - Setup और Installation Guide

## ❌ Current Issue: MySQL Service Not Running

आपका login इसलिए काम नहीं कर रहा है क्योंकि **MySQL service running नहीं है**।

## ✅ Solution: MySQL Service Start करें

### Step 1: XAMPP Control Panel Open करें
1. **XAMPP Control Panel** खोलें
2. **MySQL** module के सामने **Start** button पर click करें
3. MySQL के status indicator को **Green** होने तक wait करें

### Step 2: Database Import करें

एक बार MySQL start हो जाए, तो आपको database setup करना होगा:

#### Option A: phpMyAdmin से Import करें
1. Browser में open करें: `http://localhost/phpmyadmin`
2. Left sidebar में **New** पर click करें
3. Database name enter करें: `stocksathi`
4. **Create** button पर click करें
5. Top में **Import** tab पर click करें
6. **Choose File** पर click करें और अपनी SQL file select करें
7. **Go** button पर click करें

#### Option B: Command Line से Import करें
```batch
cd C:\xampp_new\mysql\bin
mysql -u root -p
CREATE DATABASE stocksathi;
USE stocksathi;
SOURCE path/to/your/stocksathi.sql;
EXIT;
```

### Step 3: Test Database Connection

एक बार database import हो जाए, तो test करें:
- Open: `http://localhost/stocksathi/test_db_simple.php`
- सभी steps ✅ green होने चाहिए

### Step 4: Login करें

अब आप login कर सकते हैं:
- Open: `http://localhost/stocksathi/pages/login.php`
- **Demo Credentials:**
  - Email: `admin@stocksathi.com`
  - Password: `admin123`

## 📋 Quick Checklist

- [ ] XAMPP Control Panel में MySQL service **Started** है
- [ ] Database `stocksathi` create हो गया है
- [ ] SQL file successfully import हो गई है
- [ ] Test script (`test_db_simple.php`) सभी steps pass कर रही है
- [ ] Login page load हो रहा है बिना error के

## 🔧 Troubleshooting

### MySQL Start नहीं हो रहा है?
- Port 3306 पहले से use हो रहा है
  - Solution: XAMPP Config में MySQL port change करें (3307 try करें)
  - फिर `database.php` में भी port update करें: `localhost:3307`

### Database Import में Error आ रही है?
- SQL file का encoding check करें (UTF-8 होना चाहिए)
- Older XAMPP version use कर रहे हैं तो update करें

### Still Having Issues?
- Apache server running होना चाहिए (XAMPP में)
- PHP version check करें: `php -v` (7.4+ recommended)
- Error logs check करें: `C:\xampp_new\mysql\data\mysql_error.log`

---

## 📦 Database Structure (Reference)

अगर आपके पास SQL file नहीं है, तो basic structure ऐसा होना चाहिए:

```sql
CREATE DATABASE IF NOT EXISTS stocksathi;
USE stocksathi;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role VARCHAR(20) DEFAULT 'user',
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Create demo admin user
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@stocksathi.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin');
-- Password: admin123
```

एक बार यह steps complete हो जाएं, आपका Stocksathi application पूरी तरह ready हो जाएगा! 🎉
