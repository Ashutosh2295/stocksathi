<?php
/**
 * Sales Dashboard - Live data API (JSON)
 * Called via AJAX for real-time stats. No cache.
 */
require_once __DIR__ . '/../../_includes/session_guard.php';
require_once __DIR__ . '/../../_includes/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$allowedRoles = ['super_admin', 'admin', 'store_manager', 'sales_executive'];
if (!in_array(Session::getUserRole(), $allowedRoles)) {
    echo json_encode(['ok' => false, 'error' => 'Forbidden']);
    exit;
}

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$today = date('Y-m-d');
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

$out = [
    'ok' => true,
    'today' => $today,
    'todaysSales' => 0,
    'todaysInvoices' => 0,
    'todaysAverage' => 0,
    'customersToday' => 0,
    'last7Sales' => 0,
    'last7Count' => 0,
    'totalInvoices' => 0,
    'salesTarget' => 10000,
    'targetProgress' => 0,
    'targetRemaining' => 10000,
    'labelSales' => "Today's Sales",
    'labelInvoices' => "Invoices Today",
];

try {
    $totalInvoices = (int)$db->queryOne("SELECT COUNT(*) as c FROM invoices {$orgWhere}\")['c'];
    $out['totalInvoices'] = $totalInvoices;

    $overallToday = $db->queryOne("
        SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count 
        FROM invoices 
        WHERE {$orgFilter} DATE(invoice_date) = ?
    ", [$today]);
    $todaysSales = (float)($overallToday['total'] ?? 0);
    $todaysInvoices = (int)($overallToday['count'] ?? 0);

    $last7 = $db->queryOne("
        SELECT COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count 
        FROM invoices 
        WHERE {$orgFilter} DATE(invoice_date) >= ?
    ", [$sevenDaysAgo]);
    $last7Sales = (float)($last7['total'] ?? 0);
    $last7Count = (int)($last7['count'] ?? 0);

    $cust = $db->queryOne("
        SELECT COUNT(DISTINCT COALESCE(customer_id, id)) as count 
        FROM invoices 
        WHERE {$orgFilter} DATE(invoice_date) = ?
    ", [$today]);
    $customersToday = (int)($cust['count'] ?? 0);

    $userId = Session::getUserId();
    $userInfo = $db->queryOne("SELECT daily_sales_target FROM users WHERE {$orgFilter} id = ?", [$userId]);
    $salesTarget = (isset($userInfo['daily_sales_target']) && $userInfo['daily_sales_target'] !== null && $userInfo['daily_sales_target'] !== '')
        ? (float)$userInfo['daily_sales_target'] : 10000;

    $todaysAverage = $todaysInvoices > 0 ? $todaysSales / $todaysInvoices : ($last7Count > 0 ? $last7Sales / $last7Count : 0);
    $targetProgress = $salesTarget > 0 ? min(100, ($todaysSales / $salesTarget) * 100) : 0;
    $targetRemaining = max(0, $salesTarget - $todaysSales);

    $out['todaysSales'] = $todaysSales;
    $out['todaysInvoices'] = $todaysInvoices;
    $out['todaysAverage'] = $todaysAverage;
    $out['customersToday'] = $customersToday;
    $out['last7Sales'] = $last7Sales;
    $out['last7Count'] = $last7Count;
    $out['salesTarget'] = $salesTarget;
    $out['targetProgress'] = $targetProgress;
    $out['targetRemaining'] = $targetRemaining;

    if ($todaysSales > 0 || $todaysInvoices > 0) {
        $out['labelSales'] = "Today's Sales";
        $out['labelInvoices'] = "Invoices Today";
    } else {
        $out['labelSales'] = "Sales (Last 7 days)";
        $out['labelInvoices'] = "Invoices (Last 7 days)";
        $out['todaysSales'] = $last7Sales;
        $out['todaysInvoices'] = $last7Count;
        $out['todaysAverage'] = $last7Count > 0 ? $last7Sales / $last7Count : 0;
    }
} catch (Exception $e) {
    $out['ok'] = false;
    $out['error'] = $e->getMessage();
}

echo json_encode($out);
