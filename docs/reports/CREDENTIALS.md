# 🔐 Stocksathi - Default Login Credentials

## Default Admin Accounts

### Admin Account
- **Email:** `admin@stocksathi.com`
- **Password:** `admin123`
- **Role:** `admin`
- **Access:** Full business control (products, stock, sales)

### Super Admin Account
- **Email:** `superadmin@stocksathi.com`
- **Password:** `admin123`
- **Role:** `super_admin`
- **Access:** System overview, user & role management, all features

---

## Setup Instructions

### Step 1: Run Admin Setup
After importing the database, visit:
```
http://localhost/stocksathi/setup_admin.php
```

This will create/update the default admin accounts with proper passwords.

### Step 2: Login
Go to login page:
```
http://localhost/stocksathi/pages/login.php
```

Use the credentials above to login.

---

## Security Note

⚠️ **IMPORTANT:** Change the default password (`admin123`) immediately after first login for security purposes.

You can change passwords from:
- **Users Management** page (for admin/super_admin)
- Or directly in the database

---

## Creating Additional Users

After logging in as admin, you can create additional users from:
**Administration → Users → Add User**

---

## Password Reset

If you forget the password, you can:

1. **Run setup script again:**
   ```
   http://localhost/stocksathi/setup_admin.php
   ```
   This will reset admin password to `admin123`

2. **Or update directly in database:**
   ```sql
   UPDATE users 
   SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
   WHERE email = 'admin@stocksathi.com';
   ```
   (This hash is for password: `admin123`)

---

## Role Permissions

| Role | Access Level |
|------|-------------|
| `super_admin` | Full system access, user management |
| `admin` | Full business control (products, stock, sales) |
| `store_manager` | Stock-related operations only |
| `sales_executive` | Billing and invoice management only |

---

**Last Updated:** 2024-01-14
