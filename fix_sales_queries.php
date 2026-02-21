<?php
$file = 'c:/xampp_new/htdocs/stocksathi/pages/dashboards/sales-executive.php';
$content = file_get_contents($file);

// Add 'AND created_by = ?' to invoices queries
$replacements = [
    'SELECT COUNT(*) as c FROM invoices"' => 'SELECT COUNT(*) as c FROM invoices WHERE created_by = ?", [$userId]',
    'SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE DATE(invoice_date) = CURDATE()"' => 'SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE DATE(invoice_date) = CURDATE() AND created_by = ?", [$userId]',
    'SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"' => 'SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND created_by = ?", [$userId]',
    'SELECT COUNT(DISTINCT COALESCE(customer_id, id)) as c FROM invoices WHERE DATE(invoice_date) = CURDATE()"' => 'SELECT COUNT(DISTINCT COALESCE(customer_id, id)) as c FROM invoices WHERE DATE(invoice_date) = CURDATE() AND created_by = ?", [$userId]',
    'SELECT COUNT(DISTINCT COALESCE(customer_id, id)) as c FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"' => 'SELECT COUNT(DISTINCT COALESCE(customer_id, id)) as c FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND created_by = ?", [$userId]',
    'SELECT COUNT(DISTINCT COALESCE(customer_id, id)) as c FROM invoices"' => 'SELECT COUNT(DISTINCT COALESCE(customer_id, id)) as c FROM invoices WHERE created_by = ?", [$userId]',
    'SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as total FROM invoices"' => 'SELECT COUNT(*) as c, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE created_by = ?", [$userId]',
    'SELECT COALESCE(SUM(total_amount),0) as total, COUNT(*) as count FROM invoices WHERE YEARWEEK(invoice_date) = YEARWEEK(CURDATE())"' => 'SELECT COALESCE(SUM(total_amount),0) as total, COUNT(*) as count FROM invoices WHERE YEARWEEK(invoice_date) = YEARWEEK(CURDATE()) AND created_by = ?", [$userId]',
    'SELECT COALESCE(SUM(total_amount),0) as total, COUNT(*) as count FROM invoices WHERE MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE())"' => 'SELECT COALESCE(SUM(total_amount),0) as total, COUNT(*) as count FROM invoices WHERE MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE()) AND created_by = ?", [$userId]',
    'SELECT DATE(invoice_date) as dt, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(invoice_date) ORDER BY dt"' => 'SELECT DATE(invoice_date) as dt, COALESCE(SUM(total_amount),0) as total FROM invoices WHERE invoice_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND created_by = ? GROUP BY DATE(invoice_date) ORDER BY dt", [$userId]'
];

foreach ($replacements as $search => $replace) {
    $content = str_replace($search, $replace, $content);
}

file_put_contents($file, $content);
echo "Updated sales executive queries to fetch personal data only.";
