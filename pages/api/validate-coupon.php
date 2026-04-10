<?php
/**
 * Validate Coupon API
 * Checks if a coupon code is valid and returns its details
 */

require_once __DIR__ . '/../../_includes/config.php';
require_once __DIR__ . '/../../_includes/database.php';
require_once __DIR__ . '/../../_includes/Session.php';

header('Content-Type: application/json');

// Check session
if (!Session::getUserId()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();
$orgId = Session::getOrganizationId();

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';
$subtotal = (float)($input['subtotal'] ?? 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Coupon code is required']);
    exit;
}

try {
    // Check if coupon exists for this organization
    $query = "SELECT * FROM promotions WHERE code = ? AND organization_id = ? AND status = 'active' LIMIT 1";
    $coupon = $db->queryOne($query, [$code, $orgId]);

    if (!$coupon) {
        echo json_encode(['success' => false, 'message' => 'Invalid or inactive coupon code']);
        exit;
    }

    // Check dates
    $today = date('Y-m-d');
    if ($today < $coupon['start_date']) {
        echo json_encode(['success' => false, 'message' => 'This coupon is not yet active']);
        exit;
    }
    if ($today > $coupon['end_date']) {
        echo json_encode(['success' => false, 'message' => 'This coupon has expired']);
        exit;
    }

    // Check usage limit
    if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        echo json_encode(['success' => false, 'message' => 'This coupon has reached its usage limit']);
        exit;
    }

    // Check minimum purchase
    if ($coupon['min_purchase_amount'] > 0 && $subtotal < $coupon['min_purchase_amount']) {
        echo json_encode(['success' => false, 'message' => 'Minimum purchase of ₹' . number_format($coupon['min_purchase_amount'], 2) . ' required for this coupon']);
        exit;
    }

    // Calculate discount amount if percentage
    $discountValue = (float)$coupon['value'];
    $calculatedDiscount = 0;

    if ($coupon['type'] === 'percentage') {
        $calculatedDiscount = ($subtotal * $discountValue) / 100;
        // Check max discount limit
        if ($coupon['max_discount_amount'] > 0 && $calculatedDiscount > $coupon['max_discount_amount']) {
            $calculatedDiscount = $coupon['max_discount_amount'];
        }
    } else {
        $calculatedDiscount = $discountValue;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Coupon applied successfully!',
        'data' => [
            'id' => $coupon['id'],
            'name' => $coupon['name'],
            'type' => $coupon['type'],
            'value' => $discountValue,
            'discount_amount' => $calculatedDiscount
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
