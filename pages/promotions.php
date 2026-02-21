<?php
/**
 * Promotions Management Page - Core PHP Version
 * Marketing Module
 */

require_once __DIR__ . '/../_includes/session_guard.php';
require_once __DIR__ . '/../_includes/config.php';
require_once __DIR__ . '/../_includes/database.php';
require_once __DIR__ . '/../_includes/Validator.php';
require_once __DIR__ . '/../_includes/Session.php';

$db = Database::getInstance();
$orgIdPatch = isset($_SESSION['organization_id']) ? $_SESSION['organization_id'] : (class_exists('Session') ? Session::getOrganizationId() : null);
$orgFilter = $orgIdPatch ? " organization_id = " . intval($orgIdPatch) . " AND " : "";
$orgWhere = $orgIdPatch ? " WHERE organization_id = " . intval($orgIdPatch) . " " : "";
$message = '';
$messageType = '';
$userId = Session::getUserId();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'create' || $action === 'update') {
            $validator = new Validator($_POST);
            $validator->required('name', 'Promotion name is required');
            $validator->required('discount_type', 'Discount type is required');
            $validator->required('discount_value', 'Discount value is required');
            
            if ($validator->fails()) {
                $message = $validator->getFirstError();
                $messageType = 'error';
            } else {
                $data = Validator::sanitize($_POST);
                
                if ($action === 'create') {
                    // Generate unique coupon code
                    $couponCode = strtoupper(substr($data['name'], 0, 3) . rand(1000, 9999));
                    
                    // Use correct column names from schema
                    $query = "INSERT INTO promotions (name, description, code, type, value, min_purchase_amount, max_discount_amount, start_date, end_date, usage_limit, status, created_by, organization_id) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $db->execute($query, [
                        $data['name'],
                        $data['description'] ?? null,
                        $couponCode,
                        $data['discount_type'],
                        (float)$data['discount_value'],
                        (float)($data['min_purchase_amount'] ?? 0),
                        (float)($data['max_discount_amount'] ?? 0),
                        $data['valid_from'] ?? date('Y-m-d'),
                        $data['valid_until'] ?? date('Y-m-d', strtotime('+30 days')),
                        (int)($data['usage_limit'] ?? 0),
                        $data['status'] ?? 'active',
                        $userId,
                        $orgIdPatch
                    ]);
                    
                    Session::setFlash('Promotion created successfully with code: ' . $couponCode, 'success');
                } else {
                    $query = "UPDATE promotions SET name = ?, description = ?, type = ?, value = ?, min_purchase_amount = ?, max_discount_amount = ?, start_date = ?, end_date = ?, usage_limit = ?, status = ? WHERE {$orgFilter} id = ?";
                    $db->execute($query, [
                        $data['name'],
                        $data['description'] ?? null,
                        $data['discount_type'],
                        (float)$data['discount_value'],
                        (float)($data['min_purchase_amount'] ?? 0),
                        (float)($data['max_discount_amount'] ?? 0),
                        $data['valid_from'],
                        $data['valid_until'],
                        (int)($data['usage_limit'] ?? 0),
                        $data['status'] ?? 'active',
                        $data['id']
                    ]);
                    
                    Session::setFlash('Promotion updated successfully', 'success');
                }
                
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            
        } elseif ($action === 'delete') {
            $id = $_POST['id'] ?? null;
            if ($id) {
                $db->execute("DELETE FROM promotions WHERE {$orgFilter} id = ?", [$id]);
                Session::setFlash('Promotion deleted successfully', 'success');
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
        }
    } catch (Exception $e) {
        $message = 'Error: ' . $e->getMessage();
        $messageType = 'error';
    }
}

$flash = Session::getFlash();
if ($flash) {
    $message = $flash['message'];
    $messageType = $flash['type'];
}

$editPromotion = null;
$editId = $_GET['edit_id'] ?? null;
if ($editId) {
    $editPromotion = $db->queryOne("SELECT * FROM promotions WHERE {$orgFilter} id = ?", [$editId]);
}

try {
    $promotions = $db->query("SELECT p.*, u.full_name as created_by_name FROM promotions p LEFT JOIN users u ON p.created_by = u.id " . ($orgIdPatch ? " WHERE p.organization_id = " . intval($orgIdPatch) : "") . " ORDER BY p.created_at DESC");
} catch (Exception $e) {
    $promotions = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotions - Stocksathi</title>
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
                        <span class="breadcrumb-item active">Promotions</span>
                    </nav>
                    <div class="flex items-center justify-between">
                        <h1 class="content-title">Promotions & Coupons</h1>
                        <button class="btn btn-primary" onclick="openModal('promotionModal', true)">
                            <span>➕</span> Create Promotion
                        </button>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType === 'success' ? 'success' : 'danger' ?>" style="margin-bottom: 20px;">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header"><h3 class="card-title">All Promotions</h3></div>
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Coupon Code</th>
                                    <th>Discount</th>
                                    <th>Valid Period</th>
                                    <th>Usage</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($promotions)): ?>
                                    <tr><td colspan="7" class="text-center" style="padding: 40px;">No promotions found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($promotions as $promo): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($promo['name']) ?></td>
                                            <td><code><?= htmlspecialchars($promo['code'] ?? 'N/A') ?></code></td>
                                            <td>
                                                <?php if ($promo['type'] === 'percentage'): ?>
                                                    <?= $promo['value'] ?>%
                                                <?php else: ?>
                                                    ₹<?= number_format($promo['value'], 2) ?>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('Y-m-d', strtotime($promo['start_date'])) ?> to <?= date('Y-m-d', strtotime($promo['end_date'])) ?></td>
                                            <td><?= $promo['used_count'] ?> / <?= $promo['usage_limit'] ?: '∞' ?></td>
                                            <td>
                                                <?php if ($promo['status'] === 'active'): ?>
                                                    <span class="badge badge-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="table-actions">
                                                <a href="?edit_id=<?= $promo['id'] ?>" class="btn btn-ghost btn-sm" onclick="event.preventDefault(); editPromotion(<?= htmlspecialchars(json_encode($promo)) ?>);">✏️</a>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this promotion?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm">🗑️</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal-backdrop" id="promotionModal" style="display:none;">
        <div class="modal" style="max-width:700px;">
            <div class="modal-header">
                <h3 class="modal-title" id="promotionModalTitle">Create Promotion</h3>
                <button class="modal-close" onclick="closeModal('promotionModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" id="promotionForm">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="promotionId">
                    <div class="form-group">
                        <label class="form-label required">Promotion Name</label>
                        <input type="text" name="name" id="promotionName" class="form-control" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label required">Discount Type</label>
                            <select name="discount_type" id="discountType" class="form-control" required>
                                <option value="percentage">Percentage (%)</option>
                                <option value="fixed">Fixed Amount (₹)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label required">Discount Value</label>
                            <input type="number" step="0.01" name="discount_value" id="discountValue" class="form-control" required>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Min Purchase Amount</label>
                            <input type="number" step="0.01" name="min_purchase_amount" id="minPurchase" class="form-control" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Max Discount Amount</label>
                            <input type="number" step="0.01" name="max_discount_amount" id="maxDiscount" class="form-control" placeholder="0.00">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Valid From</label>
                            <input type="date" name="valid_from" id="validFrom" class="form-control" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Valid Until</label>
                            <input type="date" name="valid_until" id="validUntil" class="form-control" value="<?= date('Y-m-d', strtotime('+30 days')) ?>">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Usage Limit (0 = unlimited)</label>
                            <input type="number" name="usage_limit" id="usageLimit" class="form-control" value="0">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('promotionModal')">Cancel</button>
                <button type="submit" form="promotionForm" class="btn btn-primary">Save Promotion</button>
            </div>
        </div>
    </div>

    <script>
        function openModal(modalId, isNew = false) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
                if (isNew) {
                    document.getElementById('promotionForm').reset();
                    document.getElementById('formAction').value = 'create';
                    document.getElementById('promotionId').value = '';
                    document.getElementById('promotionModalTitle').textContent = 'Create Promotion';
                    document.getElementById('validFrom').value = '<?= date('Y-m-d') ?>';
                    document.getElementById('validUntil').value = '<?= date('Y-m-d', strtotime('+30 days')) ?>';
                }
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = '';
        }

        function editPromotion(promo) {
            document.getElementById('formAction').value = 'update';
            document.getElementById('promotionId').value = promo.id;
            document.getElementById('promotionName').value = promo.name || '';
            document.getElementById('discountType').value = promo.type || 'percentage';
            document.getElementById('discountValue').value = promo.value || '';
            document.getElementById('minPurchase').value = promo.min_purchase_amount || '';
            document.getElementById('maxDiscount').value = promo.max_discount_amount || '';
            document.getElementById('validFrom').value = promo.start_date || '';
            document.getElementById('validUntil').value = promo.end_date || '';
            document.getElementById('usageLimit').value = promo.usage_limit || '0';
            document.getElementById('status').value = promo.status || 'active';
            document.getElementById('description').value = promo.description || '';
            document.getElementById('promotionModalTitle').textContent = 'Edit Promotion';
            openModal('promotionModal');
        }

        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('promotionModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) closeModal('promotionModal');
                });
            }
            <?php if ($editPromotion): ?>
                editPromotion(<?= json_encode($editPromotion) ?>);
            <?php endif; ?>
        });
    </script>
</body>
</html>