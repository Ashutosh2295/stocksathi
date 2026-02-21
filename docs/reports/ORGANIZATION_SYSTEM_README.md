# 🏢 Stocksathi Multi-Tenancy & Organization System

## Overview

Stocksathi now supports **multi-tenancy** with organization-based data isolation. Each organization operates independently with its own data, users, and super admin.

---

## 📋 Registration Flow

### Who Can Register?
- **Only Organization Owners / Super Admins** can register
- Other users (Admin, Sales, Accountant) are created by the Super Admin after login

### Registration Process

1. **Open Registration Page** (`pages/register.php`)
2. **Fill Organization Details:**
   - Organization Name *
   - Organization Email *
   - Organization Phone *
   - Address (optional)
   - GST Number (optional)

3. **Fill Super Admin Details:**
   - Full Name *
   - Email Address *
   - Phone Number
   - Username *
   - Password * (minimum 6 characters)
   - Confirm Password *

4. **Submit Registration**
   - System creates a new organization
   - Creates super admin user linked to that organization
   - **Redirects to login page** (NOT dashboard)

5. **Login with Credentials**
   - Use the username/email and password you created
   - System redirects to appropriate dashboard based on role

---

## 🔐 Authentication & Data Isolation

### How It Works

1. **Organization Creation**
   - Each registration creates a unique organization
   - Organization gets a unique ID

2. **User Association**
   - Every user belongs to exactly one organization
   - Users can only see data from their own organization

3. **Data Isolation**
   - All major tables have `organization_id` foreign key
   - Queries automatically filter by organization_id
   - No data leakage between organizations

### Tables with Organization Isolation

- ✅ users
- ✅ products
- ✅ customers
- ✅ suppliers
- ✅ invoices
- ✅ quotations
- ✅ expenses
- ✅ warehouses
- ✅ stores
- ✅ categories
- ✅ brands
- ✅ employees
- ✅ departments

---

## 🔧 Database Setup

### Run Migration

Execute the migration SQL file to add organization support:

```bash
# Using MySQL command line
mysql -u root -p stocksathi < migrations/add_organization_support.sql

# Or using phpMyAdmin
# Import the file: migrations/add_organization_support.sql
```

### Migration Details

The migration:
1. Creates `organizations` table
2. Adds `organization_id` column to all major tables
3. Sets up foreign key relationships
4. Ensures data integrity with CASCADE delete

---

## 💻 Developer Guide

### Using OrganizationHelper

```php
// Include the helper
require_once '_includes/OrganizationHelper.php';

// Get current organization ID
$orgId = OrganizationHelper::getCurrentOrganizationId();

// Get organization details
$org = OrganizationHelper::getOrganization();
echo $org['name']; // Organization name

// Filter queries automatically
$query = "SELECT * FROM products WHERE status = 'active'";
$filtered = OrganizationHelper::filterQuery($query);
// Result: "SELECT * FROM products WHERE organization_id = ? AND status = 'active'"

// Validate record ownership
$isValid = OrganizationHelper::validateOwnership('products', $productId);

// Get organization users
$users = OrganizationHelper::getOrganizationUsers();

// Check if current user is super admin
if (OrganizationHelper::isSuperAdmin()) {
    // Super admin specific code
}

// Get organization statistics
$stats = OrganizationHelper::getOrganizationStats();
echo "Total Users: " . $stats['users'];
echo "Total Products: " . $stats['products'];
```

### Session Management

```php
// Get organization ID from session
$orgId = Session::getOrganizationId();

// Get complete user data including organization
$user = Session::getUser();
echo $user['organization_id'];
```

### Creating Organization-Aware Queries

**Example 1: Simple SELECT**
```php
$db = Database::getInstance();
$orgId = Session::getOrganizationId();

$stmt = $db->getConnection()->prepare("
    SELECT * FROM products 
    WHERE organization_id = ? AND status = 'active'
    ORDER BY name
");
$stmt->execute([$orgId]);
$products = $stmt->fetchAll();
```

**Example 2: Using Helper**
```php
$where = OrganizationHelper::addOrgFilter("WHERE status = 'active'");
// Result: "WHERE status = 'active' AND organization_id = 123"

$query = "SELECT * FROM products {$where} ORDER BY name";
```

**Example 3: INSERT with Organization**
```php
$orgId = Session::getOrganizationId();

$stmt = $db->getConnection()->prepare("
    INSERT INTO products (organization_id, name, sku, price)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$orgId, $name, $sku, $price]);
```

---

## 🎯 Key Features

### ✅ Complete Data Isolation
- Each organization's data is completely separate
- No cross-organization data access
- Secure multi-tenancy

### ✅ Proper Registration Flow
- Organization + Super Admin registration
- Redirect to login after registration
- No automatic login after registration

### ✅ Role-Based Access Control
- Super Admin: Full organization control
- Admin: Administrative tasks
- Sales Executive: Sales operations
- Accountant: Financial operations
- Store Manager: Store management

### ✅ Internal Linking
- All links are relative to project directory
- Works correctly even when project is copied
- No hardcoded absolute URLs

---

## 🚀 Usage Examples

### Example 1: Register New Organization

1. Go to `http://localhost/stocksathi/pages/register.php`
2. Fill in organization details:
   - Name: "ABC Enterprises"
   - Email: "info@abc.com"
   - Phone: "9876543210"
3. Fill in super admin details:
   - Name: "John Doe"
   - Email: "john@abc.com"
   - Username: "johndoe"
   - Password: "secure123"
4. Click "Create Organization"
5. Redirected to login page
6. Login with username: "johndoe" and password: "secure123"
7. Access super admin dashboard

### Example 2: Add Users to Organization

After logging in as super admin:

1. Go to Users Management
2. Click "Add User"
3. Fill user details (they will automatically be linked to your organization)
4. Assign role (admin, sales_executive, accountant, etc.)
5. User can now login and see only your organization's data

---

## 🔒 Security Features

1. **Password Hashing**: All passwords are hashed using PHP's `password_hash()`
2. **SQL Injection Protection**: All queries use prepared statements
3. **Session Security**: Session regeneration on login
4. **Organization Validation**: All data access validates organization ownership
5. **Role-Based Permissions**: Users can only access features allowed by their role

---

## 📊 Database Schema

### Organizations Table
```sql
CREATE TABLE organizations (
  id INT PRIMARY KEY AUTO_INCREMENT,
  name VARCHAR(200) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  phone VARCHAR(20),
  address TEXT,
  gst_number VARCHAR(50),
  status ENUM('active','inactive','suspended') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Users Table (Updated)
```sql
CREATE TABLE users (
  id INT PRIMARY KEY AUTO_INCREMENT,
  organization_id INT,
  username VARCHAR(50) UNIQUE NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100),
  role VARCHAR(20) DEFAULT 'user',
  status ENUM('active','inactive','suspended') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE
);
```

---

## 🐛 Troubleshooting

### Issue: "Email or username already exists"
**Solution**: Each username and email must be unique across ALL organizations. Use organization-specific usernames.

### Issue: "No data showing in dashboard"
**Solution**: Ensure you're logged in and your session has organization_id set. Check `Session::getOrganizationId()`.

### Issue: "Foreign key constraint fails"
**Solution**: Run the migration SQL file first to add organization support to all tables.

### Issue: "Registration redirects to dashboard instead of login"
**Solution**: The registration page has been updated. Clear browser cache and try again.

---

## 📝 Best Practices

1. **Always filter by organization_id** in queries
2. **Use OrganizationHelper** for automatic filtering
3. **Validate ownership** before updating/deleting records
4. **Never hardcode organization IDs** - always get from session
5. **Test with multiple organizations** to ensure isolation
6. **Use transactions** when creating organization + super admin

---

## 🔄 Migration Checklist

- [x] Create organizations table
- [x] Add organization_id to users table
- [x] Add organization_id to products table
- [x] Add organization_id to customers table
- [x] Add organization_id to suppliers table
- [x] Add organization_id to invoices table
- [x] Add organization_id to all other major tables
- [x] Update registration page
- [x] Update login to store organization_id
- [x] Create OrganizationHelper class
- [x] Update Session class
- [x] Update AuthHelper class

---

## 📞 Support

For issues or questions:
1. Check this README
2. Review the code comments
3. Check the migration SQL file
4. Test with fresh database

---

## 🎉 Summary

**Registration Flow:**
1. User registers organization + super admin
2. System creates organization
3. System creates super admin user
4. **Redirects to login page**
5. User logs in
6. User sees their organization's dashboard

**Data Isolation:**
- Every table has organization_id
- All queries filter by organization_id
- No data leakage between organizations
- Complete multi-tenancy support

**Internal Linking:**
- All links use relative paths
- Project can be copied anywhere
- No hardcoded URLs
- Works in any directory structure

---

**Version:** 2.0  
**Last Updated:** 2026-01-28  
**Author:** Stocksathi Development Team
