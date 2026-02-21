-- EMERGENCY FIX - Run this in phpMyAdmin SQL tab
-- This will restore all your data immediately

-- Step 1: Check current state
SELECT 'BEFORE FIX - Users without organization' as status;
SELECT id, username, email, organization_id FROM users WHERE organization_id IS NULL;

SELECT 'BEFORE FIX - Products without organization' as status;
SELECT COUNT(*) as count FROM products WHERE organization_id IS NULL;

-- Step 2: Create default organization if not exists
INSERT INTO organizations (name, email, phone, address, status, created_at) 
VALUES ('Stocksathi Demo', 'demo@stocksathi.com', '9999999999', 'Demo Address', 'active', NOW())
ON DUPLICATE KEY UPDATE id=id;

-- Step 3: Get the organization ID
SET @org_id = (SELECT id FROM organizations ORDER BY id ASC LIMIT 1);

-- Step 4: Update ALL existing data to this organization
UPDATE users SET organization_id = @org_id WHERE organization_id IS NULL;
UPDATE products SET organization_id = @org_id WHERE organization_id IS NULL;
UPDATE customers SET organization_id = @org_id WHERE organization_id IS NULL;
UPDATE suppliers SET organization_id = @org_id WHERE organization_id IS NULL;
UPDATE invoices SET organization_id = @org_id WHERE organization_id IS NULL;
UPDATE quotations SET organization_id = @org_id WHERE organization_id IS NULL;
UPDATE expenses SET organization_id = @org_id WHERE organization_id IS NULL;
UPDATE categories SET organization_id = @org_id WHERE organization_id IS NULL;
UPDATE brands SET organization_id = @org_id WHERE organization_id IS NULL;
UPDATE warehouses SET organization_id = @org_id WHERE organization_id IS NULL;
UPDATE stores SET organization_id = @org_id WHERE organization_id IS NULL;

-- Step 5: Verify the fix
SELECT 'AFTER FIX - Organization ID used' as status, @org_id as organization_id;

SELECT 'AFTER FIX - Users' as status;
SELECT id, username, email, organization_id FROM users;

SELECT 'AFTER FIX - Data counts' as status;
SELECT 
    (SELECT COUNT(*) FROM products WHERE organization_id = @org_id) as products,
    (SELECT COUNT(*) FROM customers WHERE organization_id = @org_id) as customers,
    (SELECT COUNT(*) FROM invoices WHERE organization_id = @org_id) as invoices,
    (SELECT COUNT(*) FROM users WHERE organization_id = @org_id) as users;

-- Done! Now logout and login again to see your data
