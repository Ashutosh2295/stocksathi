<?php
/**
 * Invoices Management Page - Core PHP Version
 * Uses core PHP concepts with direct database queries
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Session.php';
require_once __DIR__ . '/../_includes/PermissionMiddleware.php';

// Role-based access: All authenticated users can view invoices, but only admin/super_admin/sales_executive can manage
$userRole = Session::getUserRole();
if (!in_array($userRole, ['super_admin', 'admin', 'sales_executive', 'store_manager', 'accountant'])) {
    header('Location: ' . BASE_PATH . '/403.php');
    exit;
}

// Initialize database connection
$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $search = $_GET['search'] ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $dateFilter = $_GET['date'] ?? '';
    
    $query = "SELECT i.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone
              FROM invoices i
              LEFT JOIN customers c ON i.customer_id = c.id
              " . ($orgIdPatch ? " WHERE i.organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1");
    $params = [];
    
    if (!empty($search)) {
        $query .= " AND (i.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if (!empty($statusFilter)) {
        $query .= " AND i.payment_status = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($dateFilter)) {
        $query .= " AND DATE(i.invoice_date) = ?";
        $params[] = $dateFilter;
    }
    
    $query .= " ORDER BY i.id DESC";
    $invoices = $db->query($query, $params);
    
    // Set headers for Excel compatibility
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="invoices_' . date('Y-m-d_His') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    
    // Add UTF-8 BOM for Excel to recognize UTF-8 encoding
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add header row
    fputcsv($output, ['Invoice Number', 'Customer Name', 'Email', 'Phone', 'Invoice Date', 'Due Date', 'Subtotal', 'Tax Amount', 'Discount', 'Total Amount', 'Paid Amount', 'Payment Status', 'Status']);
    
    // Add data rows
    foreach ($invoices as $inv) {
        fputcsv($output, [
            $inv['invoice_number'] ?? 'INV-' . $inv['id'],
            $inv['customer_name'] ?? '-',
            $inv['customer_email'] ?? '-',
            $inv['customer_phone'] ?? '-',
            $inv['invoice_date'] ? date('Y-m-d', strtotime($inv['invoice_date'])) : '-',
            $inv['due_date'] ? date('Y-m-d', strtotime($inv['due_date'])) : '-',
            number_format((float)($inv['subtotal'] ?? 0), 2, '.', ''),
            number_format((float)($inv['tax_amount'] ?? 0), 2, '.', ''),
            number_format((float)($inv['discount_amount'] ?? 0), 2, '.', ''),
            number_format((float)($inv['total_amount'] ?? 0), 2, '.', ''),
            number_format((float)($inv['paid_amount'] ?? 0), 2, '.', ''),
            ucfirst($inv['payment_status'] ?? 'pending'),
            ucfirst($inv['status'] ?? 'draft')
        ]);
    }
    
    fclose($output);
    exit;
}

// Invoice creation is now handled in invoice-form.php

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'] ?? null;
    
    if ($id) {
        try {
            $db->beginTransaction();
            
            // Get invoice items to restore stock
            $items = $db->query("SELECT product_id, quantity FROM invoice_items WHERE invoice_id = ?", [$id]);
            
            // Restore stock for each item
            foreach ($items as $item) {
                $db->execute(
                    "UPDATE products SET stock_quantity = stock_quantity + ? WHERE {$orgFilter} id = ?",
                    [$item['quantity'], $item['product_id']]
                );
            }
            
            // Delete invoice items
            $db->execute("DELETE FROM invoice_items WHERE invoice_id = ?", [$id]);
            
            // Delete invoice
            $affected = $db->execute("DELETE FROM invoices WHERE {$orgFilter} id = ?", [$id]);
            
            $db->commit();
            
            if ($affected > 0) {
                Session::setFlash('Invoice deleted successfully. Stock has been restored.', 'success');
            } else {
                Session::setFlash('Invoice not found', 'error');
            }
        } catch (Exception $e) {
            $db->rollBack();
            Session::setFlash('Error deleting invoice: ' . $e->getMessage(), 'error');
        }
        header('Location: ' . $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET));
        exit;
    }
}

// Get flash message
$flash = Session::getFlash();
$message = $flash['message'] ?? '';
$messageType = $flash['type'] ?? '';

// Get query parameters for filtering and pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Build query
$query = "SELECT i.*, 
          c.name as customer_name,
          c.email as customer_email,
          c.phone as customer_phone
          FROM invoices i
          LEFT JOIN customers c ON i.customer_id = c.id
          " . ($orgIdPatch ? " WHERE i.organization_id = " . intval($orgIdPatch) . " AND 1=1" : " WHERE 1=1");
$params = [];

if (!empty($search)) {
    $query .= " AND (i.invoice_number LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if (!empty($statusFilter)) {
    $query .= " AND i.payment_status = ?";
    $params[] = $statusFilter;
}

if (!empty($dateFilter)) {
    $query .= " AND DATE(i.invoice_date) = ?";
    $params[] = $dateFilter;
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM (" . $query . ") as count_table";
$totalResult = $db->queryOne($countQuery, $params);
$total = (int)$totalResult['total'];
$totalPages = ceil($total / $limit);

// Add ordering and pagination (LIMIT/OFFSET need to be literal integers)
$query .= " ORDER BY i.id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$invoices = $db->query($query, $params);

// Helper function to get status badge
function getStatusBadge($status) {
    $badges = [
        'paid' => 'badge-success',
        'pending' => 'badge-warning',
        'overdue' => 'badge-danger',
        'partial' => 'badge-info'
    ];
    $class = $badges[$status] ?? 'badge-secondary';
    return '<span class="badge ' . $class . '">' . ucfirst($status) . '</span>';
}

// Load customers and payment modes for the form
try {
    $customers = $db->query("SELECT id, name FROM customers WHERE {$orgFilter} status = 'active' ORDER BY name");
} catch (Exception $e) {
    $customers = [];
}

try {
    $paymentModes = $db->query("SELECT id, name FROM payment_modes WHERE is_active = 1 ORDER BY name");
} catch (Exception $e) {
    $paymentModes = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - Stocksathi</title>
    <link rel="stylesheet" href="<?= CSS_PATH ?>/design-system.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/components.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/layout.css">
    <link rel="stylesheet" href="<?= CSS_PATH ?>/nav-dropdown.css">
</head>
<body>
    <div class="app-container">
        <?php include __DIR__ . '/../_includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include __DIR__ . '/../_includes/header.php'; ?>
            
            <main class="content">
                <div class="content-header">
                    <nav class="breadcrumb">
                        <a href="<?= BASE_PATH ?>/index.php" class="breadcrumb-item">Home</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active">Invoices</span>
                    </nav>
                    <h1 class="content-title">Invoice Management</h1>
                </div>

                <!-- Flash Message -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : ($messageType === 'error' ? 'danger' : 'info') ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <div class="content-actions">
                    <form method="GET" action="" class="search-filter-group" style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <input type="text" name="search" class="form-control" placeholder="Search invoices..." value="<?= htmlspecialchars($search) ?>" style="flex: 1; min-width: 200px;">
                        <select name="status" class="form-control" style="min-width: 150px;">
                            <option value="">All Status</option>
                            <option value="paid" <?= $statusFilter === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="overdue" <?= $statusFilter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                        </select>
                        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($dateFilter) ?>" style="min-width: 150px;">
                        <button type="submit" class="btn btn-primary">Search</button>
                        <?php if ($search || $statusFilter || $dateFilter): ?>
                            <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-ghost">Clear</a>
                        <?php endif; ?>
                    </form>
                    <div class="action-buttons" style="display: flex; gap: 10px;">
                        <a href="invoice-form.php" class="btn btn-primary">
                            <span>➕</span> Create Invoice
                        </a>
                        <a href="?export=csv&<?= http_build_query(['search' => $search, 'status' => $statusFilter, 'date' => $dateFilter]) ?>" class="btn btn-success">
                            <span>📊</span> Export to Excel
                        </a>
                    </div>
                </div>

                <div class="card">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($invoices)): ?>
                                    <tr>
                                        <td colspan="7" style="text-align: center; padding: 40px;">
                                            No invoices found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td><code><?= htmlspecialchars($invoice['invoice_number'] ?? 'INV-' . $invoice['id']) ?></code></td>
                                            <td><?= htmlspecialchars($invoice['customer_name'] ?? '-') ?></td>
                                            <td><?= $invoice['invoice_date'] ? date('Y-m-d', strtotime($invoice['invoice_date'])) : '-' ?></td>
                                            <td><?= $invoice['due_date'] ? date('Y-m-d', strtotime($invoice['due_date'])) : '-' ?></td>
                                            <td>₹<?= number_format((float)($invoice['total_amount'] ?? 0), 2) ?></td>
                                            <td><?= getStatusBadge($invoice['payment_status'] ?? 'pending') ?></td>
                                            <td class="table-actions">
                                                <a href="invoice-details.php?id=<?= $invoice['id'] ?>" class="btn btn-ghost btn-sm" title="View">👁️</a>
                                                <a href="invoice-pdf.php?id=<?= $invoice['id'] ?>&download=true" class="btn btn-ghost btn-sm" title="Download PDF" target="_blank">⬇️</a>
                                                <a href="invoice-pdf.php?id=<?= $invoice['id'] ?>&print=true" class="btn btn-ghost btn-sm" title="Print Invoice" target="_blank">🖨️</a>
                                                <a href="invoice-form.php?id=<?= $invoice['id'] ?>" class="btn btn-ghost btn-sm" title="Edit">✏️</a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this invoice? This will restore stock for all items.');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $invoice['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" title="Delete">🗑️</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                Showing <?= $total > 0 ? (($page - 1) * $limit + 1) : 0 ?>-<?= min($page * $limit, $total) ?> of <?= $total ?> invoices
                            </div>
                            <div class="pagination-controls">
                                <?php if ($page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-btn">Previous</a>
                                <?php else: ?>
                                    <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">Previous</span>
                                <?php endif; ?>
                                
                                <?php
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);
                                for ($i = $startPage; $i <= $endPage; $i++):
                                ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="pagination-btn <?= $i === $page ? 'active' : '' ?>">
                                        <?= $i ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-btn">Next</a>
                                <?php else: ?>
                                    <span class="pagination-btn" style="opacity: 0.5; cursor: not-allowed;">Next</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

</body>
</html>
