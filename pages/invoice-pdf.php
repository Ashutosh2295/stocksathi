<?php
/**
 * Invoice PDF Generator - Stocksathi
 * Generates downloadable PDF invoices
 * Access via: invoice-pdf.php?id=INVOICE_ID
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
    die('Invoice ID is required');
}

// Fetch invoice details
try {
    $invoice = $db->queryOne("
        SELECT i.*, 
               c.name as customer_name,
               c.email as customer_email,
               c.phone as customer_phone,
               c.address as customer_address,
               c.city as customer_city,
               c.state as customer_state,
               c.pincode as customer_pincode,
               c.gst_number as customer_gst
        FROM invoices i
        LEFT JOIN customers c ON i.customer_id = c.id
        WHERE {$orgFilter} i.id = ?
    ", [$invoiceId]);
    
    if (!$invoice) {
        die('Invoice not found');
    }
    
    // Fetch invoice items
    $items = $db->query("
        SELECT ii.*, p.name as product_name, p.sku
        FROM invoice_items ii
        LEFT JOIN products p ON ii.product_id = p.id
        WHERE {$orgFilter} ii.invoice_id = ?
    ", [$invoiceId]);
    
    // Get company settings
    $settings = [];
    $settingsResult = $db->query("SELECT `key`, `value` FROM settings");
    foreach ($settingsResult as $s) {
        $settings[$s['key']] = $s['value'];
    }
    
} catch (Exception $e) {
    die('Error fetching invoice: ' . $e->getMessage());
}

// Generate PDF-style HTML (we'll use HTML-to-PDF approach)
$invoiceNumber = $invoice['invoice_number'] ?? 'INV-' . $invoice['id'];
$invoiceDate = date('F d, Y', strtotime($invoice['invoice_date']));
$dueDate = $invoice['due_date'] ? date('F d, Y', strtotime($invoice['due_date'])) : 'N/A';

$companyName = $settings['company_name'] ?? 'Stocksathi';
$companyEmail = $settings['company_email'] ?? 'info@stocksathi.com';
$companyPhone = $settings['company_phone'] ?? '1800-123-4567';

// Set headers for download if requested
if (isset($_GET['download'])) {
    header('Content-Type: text/html; charset=UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?= htmlspecialchars($invoiceNumber) ?> - <?= htmlspecialchars($companyName) ?></title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #1f2937;
            background: #f8fafc;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .company-logo h1 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .company-info {
            font-size: 13px;
            color: #6b7280;
            margin-top: 8px;
        }
        
        .invoice-badge {
            text-align: right;
        }
        
        .invoice-badge h2 {
            font-size: 28px;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .invoice-number {
            font-size: 16px;
            color: #667eea;
            font-weight: 600;
            margin-top: 8px;
        }
        
        .invoice-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .meta-section h3 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin-bottom: 10px;
        }
        
        .meta-section p {
            font-size: 14px;
            color: #374151;
            margin-bottom: 4px;
        }
        
        .meta-section .highlight {
            font-weight: 600;
            font-size: 16px;
            color: #111827;
        }
        
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .invoice-table th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 14px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .invoice-table th:first-child { border-radius: 8px 0 0 0; }
        .invoice-table th:last-child { border-radius: 0 8px 0 0; text-align: right; }
        
        .invoice-table td {
            padding: 16px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }
        
        .invoice-table tr:hover td {
            background: #f9fafb;
        }
        
        .invoice-table .text-right {
            text-align: right;
        }
        
        .invoice-table .product-name {
            font-weight: 500;
            color: #111827;
        }
        
        .invoice-table .product-sku {
            font-size: 12px;
            color: #9ca3af;
            margin-top: 4px;
        }
        
        .invoice-summary {
            display: flex;
            justify-content: flex-end;
            margin-top: 30px;
        }
        
        .summary-table {
            width: 300px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .summary-row.total {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
            border-bottom: none;
            padding-top: 16px;
            border-top: 2px solid #667eea;
        }
        
        .summary-row .label {
            color: #6b7280;
        }
        
        .summary-row .value {
            font-weight: 600;
            color: #374151;
        }
        
        .summary-row.total .label,
        .summary-row.total .value {
            color: #667eea;
        }
        
        .payment-status {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-paid { background: #d1fae5; color: #059669; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-overdue { background: #fee2e2; color: #dc2626; }
        .status-partial { background: #E8EDF5; color: #4A6FA5; }
        
        .invoice-footer {
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #9ca3af;
            font-size: 12px;
        }
        
        .invoice-notes {
            margin-top: 30px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        
        .invoice-notes h4 {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            color: #667eea;
            margin-bottom: 8px;
        }
        
        .invoice-notes p {
            color: #4b5563;
            font-size: 13px;
        }
        
        .print-actions {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .print-actions button,
        .print-actions a {
            padding: 12px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-print {
            background: white;
            color: #667eea;
        }
        
        .btn-download {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white !important;
        }
        
        .btn-back {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .print-actions button:hover,
        .print-actions a:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .invoice-container {
                box-shadow: none;
                padding: 20px;
            }
            
            .print-actions {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-actions">
        <button onclick="window.print()" class="btn-print">
            🖨️ Print Invoice
        </button>
        <button onclick="downloadPDF()" class="btn-download">
            📥 Download PDF
        </button>
        <a href="invoices.php" class="btn-back">
            ← Back to Invoices
        </a>
    </div>
    
    <div class="invoice-container" id="invoice">
        <div class="invoice-header">
            <div class="company-logo">
                <!-- Added Logo -->
                <img src="../assets/images/logo.png" alt="Logo" style="height: 60px; margin-bottom: 10px;">
                <h1><?= htmlspecialchars($companyName) ?></h1>
                <div class="company-info">
                    <?= htmlspecialchars($companyEmail) ?><br>
                    <?= htmlspecialchars($companyPhone) ?>
                </div>
            </div>
            <div class="invoice-badge">
                <h2>Invoice</h2>
                <div class="invoice-number"><?= htmlspecialchars($invoiceNumber) ?></div>
            </div>
        </div>
        
        <div class="invoice-meta">
            <div class="meta-section">
                <h3>Bill To</h3>
                <p class="highlight"><?= htmlspecialchars($invoice['customer_name'] ?? 'Walk-in Customer') ?></p>
                <?php if ($invoice['customer_email']): ?>
                    <p><?= htmlspecialchars($invoice['customer_email']) ?></p>
                <?php endif; ?>
                <?php if ($invoice['customer_phone']): ?>
                    <p><?= htmlspecialchars($invoice['customer_phone']) ?></p>
                <?php endif; ?>
                <?php if ($invoice['customer_address']): ?>
                    <p><?= htmlspecialchars($invoice['customer_address']) ?></p>
                    <p>
                        <?= htmlspecialchars($invoice['customer_city'] ?? '') ?>
                        <?= $invoice['customer_state'] ? ', ' . htmlspecialchars($invoice['customer_state']) : '' ?>
                        <?= $invoice['customer_pincode'] ? ' - ' . htmlspecialchars($invoice['customer_pincode']) : '' ?>
                    </p>
                <?php endif; ?>
                <?php if ($invoice['customer_gst']): ?>
                    <p><strong>GST:</strong> <?= htmlspecialchars($invoice['customer_gst']) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="meta-section" style="text-align: right;">
                <h3>Invoice Details</h3>
                <p><strong>Invoice Date:</strong> <?= $invoiceDate ?></p>
                <p><strong>Due Date:</strong> <?= $dueDate ?></p>
                <p style="margin-top: 10px;">
                    <span class="payment-status status-<?= strtolower($invoice['payment_status'] ?? 'pending') ?>">
                        <?= ucfirst($invoice['payment_status'] ?? 'Pending') ?>
                    </span>
                </p>
            </div>
        </div>
        
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Item Description</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Tax</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #9ca3af;">
                            No items in this invoice
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="product-name"><?= htmlspecialchars($item['product_name'] ?? 'Product') ?></div>
                                <?php if ($item['sku']): ?>
                                    <div class="product-sku">SKU: <?= htmlspecialchars($item['sku']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$item['quantity'] ?></td>
                            <td>₹<?= number_format((float)$item['unit_price'], 2) ?></td>
                            <td><?= number_format((float)($item['tax_rate'] ?? 0), 1) ?>%</td>
                            <td class="text-right">₹<?= number_format((float)$item['line_total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="invoice-summary">
            <div class="summary-table">
                <div class="summary-row">
                    <span class="label">Subtotal</span>
                    <span class="value">₹<?= number_format((float)($invoice['subtotal'] ?? 0), 2) ?></span>
                </div>
                <?php if (($invoice['tax_amount'] ?? 0) > 0): ?>
                <div class="summary-row">
                    <span class="label">Tax</span>
                    <span class="value">₹<?= number_format((float)$invoice['tax_amount'], 2) ?></span>
                </div>
                <?php endif; ?>
                <?php if (($invoice['discount_amount'] ?? 0) > 0): ?>
                <div class="summary-row">
                    <span class="label">Discount</span>
                    <span class="value">-₹<?= number_format((float)$invoice['discount_amount'], 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="summary-row total">
                    <span class="label">Total Amount</span>
                    <span class="value">₹<?= number_format((float)($invoice['total_amount'] ?? 0), 2) ?></span>
                </div>
                <?php if (($invoice['paid_amount'] ?? 0) > 0): ?>
                <div class="summary-row">
                    <span class="label">Amount Paid</span>
                    <span class="value" style="color: #059669;">₹<?= number_format((float)$invoice['paid_amount'], 2) ?></span>
                </div>
                <?php if (($invoice['balance_amount'] ?? 0) > 0): ?>
                <div class="summary-row">
                    <span class="label">Balance Due</span>
                    <span class="value" style="color: #dc2626;">₹<?= number_format((float)$invoice['balance_amount'], 2) ?></span>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($invoice['notes']): ?>
        <div class="invoice-notes">
            <h4>Notes</h4>
            <p><?= nl2br(htmlspecialchars($invoice['notes'])) ?></p>
        </div>
        <?php endif; ?>
        
        <div class="invoice-footer">
            <p>Thank you for your business!</p>
            <p style="margin-top: 8px;">Generated by <?= htmlspecialchars($companyName) ?> on <?= date('F d, Y \a\t h:i A') ?></p>
        </div>
    </div>
    
    <!-- html2pdf library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        function downloadPDF() {
            const element = document.getElementById('invoice');
            const opt = {
                margin: 0, // [top, left, bottom, right] - adjusted for better fit
                filename: 'Invoice_<?= $invoiceNumber ?>.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            // Hide buttons during generation
            document.querySelector('.print-actions').style.display = 'none';
            
            html2pdf().set(opt).from(element).save().then(function() {
                // Show buttons again
                document.querySelector('.print-actions').style.display = 'flex';
                
                // If auto-download, maybe close window?
                <?php if (isset($_GET['download'])): ?>
                // window.close(); // Optional: close tab after download
                <?php endif; ?>
            });
        }
        
        // Add print-specific styles
        window.matchMedia('print').addListener(function(media) {
            if (media.matches) {
                document.body.style.background = 'white';
            }
        });

        // Auto-trigger based on URL params
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('print')) {
                window.print();
            } else if (urlParams.has('download')) {
                downloadPDF();
            }
        };
    </script>
</body>
</html>
