# 📌 QUICK REFERENCE CARD - Organization System

## 🚀 Getting Started (3 Steps)

```
1. Run Migration
   → Open: http://localhost/stocksathi/setup-organization.php
   → Click: "Run Migration Now"

2. Register Organization
   → Open: http://localhost/stocksathi/pages/register.php
   → Fill: Organization + Super Admin details
   → Submit: Creates org and redirects to login

3. Login & Use
   → Login with your credentials
   → Access your dashboard
   → Start managing your organization
```

---

## 📝 Registration Fields

### Organization Section
- Organization Name * (required)
- Organization Email * (required)
- Organization Phone * (required)
- Address (optional)
- GST Number (optional)

### Super Admin Section
- Full Name * (required)
- Email Address * (required)
- Phone Number (optional)
- Username * (required)
- Password * (required, min 6 chars)
- Confirm Password * (required)

---

## 💻 Common Code Snippets

### Get Current Organization ID
```php
$orgId = Session::getOrganizationId();
// or
$orgId = OrganizationHelper::getCurrentOrganizationId();
```

### Get Organization Details
```php
$org = OrganizationHelper::getOrganization();
echo $org['name'];
echo $org['email'];
```

### Filter Query by Organization
```php
// Automatic filtering
$where = OrganizationHelper::addOrgFilter("WHERE status = 'active'");
// Result: "WHERE status = 'active' AND organization_id = 123"
```

### Validate Record Ownership
```php
$isValid = OrganizationHelper::validateOwnership('products', $productId);
if (!$isValid) {
    die('Access denied');
}
```

### Get Organization Users
```php
$users = OrganizationHelper::getOrganizationUsers();
foreach ($users as $user) {
    echo $user['username'];
}
```

### Check if Super Admin
```php
if (OrganizationHelper::isSuperAdmin()) {
    // Super admin only code
}
```

### Get Organization Stats
```php
$stats = OrganizationHelper::getOrganizationStats();
echo "Users: " . $stats['users'];
echo "Products: " . $stats['products'];
echo "Customers: " . $stats['customers'];
echo "Invoices: " . $stats['invoices'];
```

---

## 🗄️ Database Queries

### SELECT with Organization Filter
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

### INSERT with Organization
```php
$orgId = Session::getOrganizationId();

$stmt = $db->getConnection()->prepare("
    INSERT INTO products (organization_id, name, sku, price)
    VALUES (?, ?, ?, ?)
");
$stmt->execute([$orgId, $name, $sku, $price]);
```

### UPDATE with Organization Validation
```php
$orgId = Session::getOrganizationId();

$stmt = $db->getConnection()->prepare("
    UPDATE products 
    SET name = ?, price = ?
    WHERE id = ? AND organization_id = ?
");
$stmt->execute([$name, $price, $productId, $orgId]);
```

### DELETE with Organization Validation
```php
$orgId = Session::getOrganizationId();

$stmt = $db->getConnection()->prepare("
    DELETE FROM products 
    WHERE id = ? AND organization_id = ?
");
$stmt->execute([$productId, $orgId]);
```

---

## 🔐 Session Methods

```php
// Get user ID
$userId = Session::getUserId();

// Get user role
$role = Session::getUserRole();

// Get username
$username = Session::getUserName();

// Get organization ID
$orgId = Session::getOrganizationId();

// Get complete user data
$user = Session::getUser();
// Returns: ['id', 'username', 'role', 'organization_id']

// Check if logged in
if (Session::isLoggedIn()) {
    // User is logged in
}
```

---

## 📋 File Locations

```
Project Root
├── setup-organization.php          (Setup wizard)
├── ORGANIZATION_SYSTEM_README.md   (Full documentation)
├── IMPLEMENTATION_SUMMARY.md       (Implementation details)
├── VISUAL_GUIDE.html              (Visual guide)
├── QUICK_REFERENCE.md             (This file)
│
├── migrations/
│   └── add_organization_support.sql (Database migration)
│
├── pages/
│   └── register.php               (Registration page)
│
└── _includes/
    ├── Session.php                (Session management)
    ├── AuthHelper.php             (Authentication)
    ├── OrganizationHelper.php     (Organization utilities)
    └── config.php                 (Configuration)
```

---

## ⚠️ Important Rules

1. **ALWAYS filter by organization_id** in queries
2. **NEVER hardcode organization IDs** - get from session
3. **ALWAYS validate ownership** before update/delete
4. **USE OrganizationHelper** for automatic filtering
5. **TEST with multiple organizations** to ensure isolation

---

## 🐛 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| Migration fails | Check database permissions |
| Registration fails | Ensure migration ran successfully |
| No data showing | Check `Session::getOrganizationId()` |
| Foreign key errors | Run migration again |
| Can't login | Verify credentials are correct |
| See other org's data | Check organization_id filter in query |

---

## ✅ Testing Checklist

- [ ] Migration runs successfully
- [ ] Can register new organization
- [ ] Redirects to login after registration
- [ ] Can login with credentials
- [ ] Dashboard loads correctly
- [ ] Can create products/customers/etc.
- [ ] Register second organization
- [ ] Login as first org - see only first org data
- [ ] Login as second org - see only second org data
- [ ] No cross-organization data visible

---

## 🎯 Key Concepts

**Multi-Tenancy**: Multiple organizations use the same application, but each has isolated data.

**Data Isolation**: Organization A cannot see Organization B's data.

**Super Admin**: First user of an organization with full control.

**organization_id**: Foreign key linking all data to an organization.

**Session Context**: Stores current user's organization_id for filtering.

---

## 📞 Need Help?

1. Read `ORGANIZATION_SYSTEM_README.md` for detailed docs
2. Check `IMPLEMENTATION_SUMMARY.md` for what changed
3. View `VISUAL_GUIDE.html` for visual explanations
4. Debug with:
   ```php
   var_dump(Session::getOrganizationId());
   var_dump(OrganizationHelper::getOrganization());
   var_dump(Session::getUser());
   ```

---

**Quick Tip**: Keep this file open while developing for quick reference!

**Version**: 2.0  
**Last Updated**: 2026-01-28
