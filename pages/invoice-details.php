<?php
/**
 * Invoice Details Page - View Invoice with Items
 * Stocksathi Inventory System
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Session.php';

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$invoiceId = $_GET['id'] ?? null;

if (!$invoiceId) {
    Session::setFlash('Invoice ID is required', 'error');
    header('Location: ' . BASE_PATH . '/pages/invoices.php');
    exit;
}

// Load invoice with customer details
$invoice = $db->queryOne(
    "SELECT i.*, 
     c.name as customer_name,
     c.email as customer_email,
     c.phone as customer_phone,
     c.address as customer_address,
     c.city as customer_city,
     c.state as customer_state,
     pm.name as payment_mode_name
     FROM invoices i
     LEFT JOIN customers c ON i.customer_id = c.id
     LEFT JOIN payment_modes pm ON i.payment_mode_id = pm.id
     WHERE {$orgFilter} i.id = ?",
    [$invoiceId]
);

if (!$invoice) {
    Session::setFlash('Invoice not found', 'error');
    header('Location: ' . BASE_PATH . '/pages/invoices.php');
    exit;
}

// Load invoice items
$items = $db->query(
    "SELECT ii.*, p.name as product_name, p.sku 
     FROM invoice_items ii
     LEFT JOIN products p ON ii.product_id = p.id
     WHERE {$orgFilter} ii.invoice_id = ?
     ORDER BY ii.id",
    [$invoiceId]
);

// Helper function
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Details - Stocksathi</title>
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
                        <a href="<?= BASE_PATH ?>/pages/invoices.php" class="breadcrumb-item">Invoices</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-item active">Invoice Details</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Invoice Details</h1>
                        <div class="flex gap-2">
                            <a href="invoice-pdf.php?id=<?= $invoice['id'] ?>" class="btn btn-primary" target="_blank">
                                <span>📄</span> Download PDF
                            </a>
                            <a href="invoice-form.php?id=<?= $invoice['id'] ?>" class="btn btn-ghost">
                                <span>✏️</span> Edit
                            </a>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-6">
                    <!-- Invoice Info -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Invoice Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="text-sm text-secondary mb-1">Invoice Number</div>
                                <div class="font-bold text-lg"><?= htmlspecialchars($invoice['invoice_number'] ?? 'INV-' . $invoice['id']) ?></div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <div class="text-sm text-secondary mb-1">Invoice Date</div>
                                    <div><?= date('Y-m-d', strtotime($invoice['invoice_date'])) ?></div>
                                </div>
                                <div>
                                    <div class="text-sm text-secondary mb-1">Due Date</div>
                                    <div><?= $invoice['due_date'] ? date('Y-m-d', strtotime($invoice['due_date'])) : '-' ?></div>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="text-sm text-secondary mb-1">Payment Status</div>
                                <div><?= getStatusBadge($invoice['payment_status'] ?? 'pending') ?></div>
                            </div>
                            <div class="mb-4">
                                <div class="text-sm text-secondary mb-1">Status</div>
                                <div>
                                    <?php if ($invoice['status'] === 'finalized'): ?>
                                        <span class="badge badge-success">Finalized</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Draft</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($invoice['notes']): ?>
                                <div>
                                    <div class="text-sm text-secondary mb-1">Notes</div>
                                    <div><?= nl2br(htmlspecialchars($invoice['notes'])) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Customer Info -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Customer Information</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <div class="text-sm text-secondary mb-1">Customer Name</div>
                                <div class="font-bold"><?= htmlspecialchars($invoice['customer_name'] ?? '-') ?></div>
                            </div>
                            <?php if ($invoice['customer_email']): ?>
                                <div class="mb-2">
                                    <div class="text-sm text-secondary mb-1">Email</div>
                                    <div><?= htmlspecialchars($invoice['customer_email']) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($invoice['customer_phone']): ?>
                                <div class="mb-2">
                                    <div class="text-sm text-secondary mb-1">Phone</div>
                                    <div><?= htmlspecialchars($invoice['customer_phone']) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($invoice['customer_address']): ?>
                                <div class="mb-2">
                                    <div class="text-sm text-secondary mb-1">Address</div>
                                    <div><?= htmlspecialchars($invoice['customer_address']) ?></div>
                                    <?php if ($invoice['customer_city'] || $invoice['customer_state']): ?>
                                        <div><?= htmlspecialchars($invoice['customer_city'] ?? '') ?>, <?= htmlspecialchars($invoice['customer_state'] ?? '') ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Invoice Items -->
                <div class="card mb-6">
                    <div class="card-header">
                        <h3 class="card-title">Invoice Items</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Tax %</th>
                                        <th class="text-right">Line Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($items)): ?>
                                        <tr>
                                            <td colspan="7" style="text-align: center; padding: 40px;">
                                                No items in this invoice
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($items as $index => $item): ?>
                                            <tr>
                                                <td><?= $index + 1 ?></td>
                                                <td><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></td>
                                                <td><code><?= htmlspecialchars($item['sku'] ?? '-') ?></code></td>
                                                <td><?= (int)$item['quantity'] ?></td>
                                                <td>₹<?= number_format((float)$item['unit_price'], 2) ?></td>
                                                <td><?= number_format((float)($item['tax_rate'] ?? 0), 1) ?>%</td>
                                                <td class="text-right">₹<?= number_format((float)$item['line_total'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Summary -->
                        <div class="flex justify-end mt-6">
                            <div style="min-width: 300px;">
                                <div class="flex justify-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>₹<?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?></span>
                                </div>
                                <div class="flex justify-between mb-2">
                                    <span>Tax:</span>
                                    <span>₹<?= number_format((float)($invoice['tax_amount'] ?? 0), 2) ?></span>
                                </div>
                                <?php if ($invoice['discount_amount'] > 0): ?>
                                    <div class="flex justify-between mb-2">
                                        <span>Discount:</span>
                                        <span>-₹<?= number_format((float)$invoice['discount_amount'], 2) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex justify-between font-bold text-lg" style="border-top: 2px solid var(--color-primary); padding-top: 8px; margin-top: 8px;">
                                    <span>Total Amount:</span>
                                    <span>₹<?= number_format((float)($invoice['total_amount'] ?? 0), 2) ?></span>
                                </div>
                                <?php if ($invoice['paid_amount'] > 0): ?>
                                    <div class="flex justify-between mt-2">
                                        <span>Paid Amount:</span>
                                        <span>₹<?= number_format((float)$invoice['paid_amount'], 2) ?></span>
                                    </div>
                                    <div class="flex justify-between mt-2">
                                        <span>Balance:</span>
                                        <span class="font-bold">₹<?= number_format((float)($invoice['total_amount'] - $invoice['paid_amount']), 2) ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex gap-4 justify-end">
                    <a href="<?= BASE_PATH ?>/pages/invoices.php" class="btn btn-ghost">Back to Invoices</a>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
