<?php
// Fix Categories
$content = file_get_contents('pages/categories.php');
$content = str_replace(
    'LEFT JOIN products p ON c.id = p.category_id
          GROUP BY c.id',
    'LEFT JOIN products p ON c.id = p.category_id
          " . ($orgIdPatch ? " WHERE c.organization_id = " . intval($orgIdPatch) . " " : "") . "
          GROUP BY c.id',
    $content
);
file_put_contents('pages/categories.php', $content);

// Fix Brands
$content = file_get_contents('pages/brands.php');
$content = str_replace(
    'LEFT JOIN products p ON b.id = p.brand_id
          GROUP BY b.id',
    'LEFT JOIN products p ON b.id = p.brand_id
          " . ($orgIdPatch ? " WHERE b.organization_id = " . intval($orgIdPatch) . " " : "") . "
          GROUP BY b.id',
    $content
);
file_put_contents('pages/brands.php', $content);

// Fix Customers
$content = file_get_contents('pages/customers.php');
$content = str_replace(
    'LEFT JOIN invoices i ON c.id = i.customer_id
          GROUP BY c.id',
    'LEFT JOIN invoices i ON c.id = i.customer_id
          " . ($orgIdPatch ? " WHERE c.organization_id = " . intval($orgIdPatch) . " " : "") . "
          GROUP BY c.id',
    $content
);
// In case customers doesn't have GROUP BY
$content = str_replace(
    'FROM customers ORDER BY id DESC";',
    'FROM customers {$orgWhere} ORDER BY id DESC";',
    $content
);
file_put_contents('pages/customers.php', $content);

// Fix Suppliers
$content = file_get_contents('pages/suppliers.php');
$content = str_replace(
    'FROM suppliers ORDER BY name";',
    'FROM suppliers {$orgWhere} ORDER BY name";',
    $content
);
file_put_contents('pages/suppliers.php', $content);

// Fix Users
$content = file_get_contents('pages/users.php');
$content = str_replace(
    'FROM users u
          LEFT JOIN employees e ON u.id = e.user_id
          ORDER BY u.id DESC";',
    'FROM users u
          LEFT JOIN employees e ON u.id = e.user_id
          " . ($orgIdPatch ? " WHERE u.organization_id = " . intval($orgIdPatch) . " " : "") . "
          ORDER BY u.id DESC";',
    $content
);
file_put_contents('pages/users.php', $content);

// Fix Employees
$content = file_get_contents('pages/employees.php');
$content = str_replace(
    'FROM employees e
          LEFT JOIN users u ON e.user_id = u.id
          LEFT JOIN departments d ON e.department_id = d.id
          ORDER BY e.first_name";',
    'FROM employees e
          LEFT JOIN users u ON e.user_id = u.id
          LEFT JOIN departments d ON e.department_id = d.id
          " . ($orgIdPatch ? " WHERE e.organization_id = " . intval($orgIdPatch) . " " : "") . "
          ORDER BY e.first_name";',
    $content
);
file_put_contents('pages/employees.php', $content);

echo "Missing WHERE clauses fixed.\n";
