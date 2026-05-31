<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/config.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON data received']);
    exit();
}

// Extract data
$user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
$customer_name = isset($data['customer_name']) ? trim($data['customer_name']) : '';
$customer_email = isset($data['customer_email']) ? trim($data['customer_email']) : '';
$customer_phone = isset($data['customer_phone']) ? trim($data['customer_phone']) : '';
$customer_address = isset($data['customer_address']) ? trim($data['customer_address']) : '';
$city = isset($data['city']) ? trim($data['city']) : '';
$postal_code = isset($data['postal_code']) ? trim($data['postal_code']) : '';
$items = isset($data['items']) ? $data['items'] : [];
$subtotal = isset($data['subtotal']) ? floatval($data['subtotal']) : 0;
$discount = isset($data['discount']) ? floatval($data['discount']) : 0; // Total discount (tier + coupon)
$delivery_charge = isset($data['delivery_charge']) ? floatval($data['delivery_charge']) : 60;
$total = isset($data['total']) ? floatval($data['total']) : 0;
$payment_method = isset($data['payment_method']) ? $data['payment_method'] : '';
$transaction_id = isset($data['transaction_id']) ? $data['transaction_id'] : null;
$coupon_code = isset($data['coupon_code']) ? strtoupper(trim($data['coupon_code'])) : null;

// Validate required fields
if (empty($customer_name)) {
    echo json_encode(['success' => false, 'error' => 'Customer name is required']);
    exit();
}
if (empty($customer_email)) {
    echo json_encode(['success' => false, 'error' => 'Customer email is required']);
    exit();
}
if (empty($customer_address)) {
    echo json_encode(['success' => false, 'error' => 'Customer address is required']);
    exit();
}
if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'No items in order']);
    exit();
}
if (empty($payment_method)) {
    echo json_encode(['success' => false, 'error' => 'Payment method is required']);
    exit();
}

// Generate order number and tracking number
$order_number = 'ORD' . time() . rand(100, 999);
$tracking_number = 'TRK' . time() . rand(1000, 9999);

// For COD, payment is NOT confirmed until admin marks it
$payment_confirmed = ($payment_method === 'cod') ? 0 : 1;

// Validate coupon if provided
$coupon_id = null;
$coupon_discount_amount = 0;
$coupon_validation_error = null;

if ($coupon_code) {
    try {
        $now = date('Y-m-d H:i:s');
        
        // Get coupon details
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ?");
        $stmt->execute([$coupon_code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            $coupon_validation_error = 'Invalid coupon code';
        } elseif (!$coupon['is_active']) {
            $coupon_validation_error = 'Coupon is not active';
        } elseif ($coupon['start_date'] && $coupon['start_date'] > $now) {
            $coupon_validation_error = 'Coupon not yet available';
        } elseif ($coupon['end_date'] && $coupon['end_date'] < $now) {
            $coupon_validation_error = 'Coupon has expired';
        } elseif ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
            $coupon_validation_error = 'Coupon usage limit reached';
        } elseif ($subtotal < $coupon['min_order_amount']) {
            $coupon_validation_error = 'Minimum order amount for this coupon is ৳' . number_format($coupon['min_order_amount']);
        } else {
            // Check per-user usage limit
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM coupon_usage WHERE coupon_id = ? AND user_email = ?");
            $stmt->execute([$coupon['id'], $customer_email]);
            $user_usage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_usage['count'] >= $coupon['user_limit_per_user']) {
                $coupon_validation_error = 'You have already used this coupon the maximum number of times';
            } else {
                $coupon_id = $coupon['id'];
                
                // Calculate coupon discount (for verification)
                if ($coupon['discount_type'] === 'percent') {
                    $coupon_discount_amount = ($subtotal * $coupon['discount_value']) / 100;
                    if ($coupon['max_discount'] && $coupon_discount_amount > $coupon['max_discount']) {
                        $coupon_discount_amount = $coupon['max_discount'];
                    }
                } else {
                    $coupon_discount_amount = $coupon['discount_value'];
                    if ($coupon_discount_amount > $subtotal) {
                        $coupon_discount_amount = $subtotal;
                    }
                }
            }
        }
    } catch(PDOException $e) {
        $coupon_validation_error = 'Error validating coupon: ' . $e->getMessage();
    }
    
    // If coupon validation failed, return error
    if ($coupon_validation_error) {
        echo json_encode(['success' => false, 'error' => $coupon_validation_error]);
        exit();
    }
}

try {
    $pdo->beginTransaction();
    
    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (
        order_number, user_id, customer_name, customer_email, customer_phone, 
        customer_address, city, postal_code, subtotal, discount, delivery_charge, total, 
        payment_method, transaction_id, tracking_number, payment_confirmed, status, coupon_code, created_at
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Processing', ?, NOW()
    )");
    
    $stmt->execute([
        $order_number, $user_id, $customer_name, $customer_email, $customer_phone,
        $customer_address, $city, $postal_code, $subtotal, $discount, $delivery_charge, $total,
        $payment_method, $transaction_id, $tracking_number, $payment_confirmed, $coupon_code
    ]);
    
    $order_id = $pdo->lastInsertId();
    
    // Insert order items
    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($items as $item) {
        $product_id = isset($item['id']) ? intval($item['id']) : 0;
        $product_name = isset($item['name']) ? $item['name'] : '';
        $product_price = isset($item['price']) ? floatval($item['price']) : 0;
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
        
        $itemStmt->execute([$order_id, $product_id, $product_name, $product_price, $quantity]);
        
        // Update stock
        try {
            $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stockStmt->execute([$quantity, $product_id]);
        } catch(PDOException $e) {
            // Stock update failed but continue
            error_log("Stock update error: " . $e->getMessage());
        }
    }
    
    // Record coupon usage if a coupon was applied
    if ($coupon_id) {
        try {
            // Update coupon usage count
            $stmt = $pdo->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
            $stmt->execute([$coupon_id]);
            
            // Record usage for this user
            $stmt = $pdo->prepare("INSERT INTO coupon_usage (coupon_id, user_email, order_id, discount_amount) VALUES (?, ?, ?, ?)");
            $stmt->execute([$coupon_id, $customer_email, $order_id, $discount]);
        } catch(PDOException $e) {
            // Log error but don't stop order process
            error_log("Coupon usage recording error: " . $e->getMessage());
        }
    }
    
    // Update user's total_spent and tier (for confirmed payments only)
    if ($payment_confirmed == 1) {
        // Get current total_spent
        $stmt = $pdo->prepare("SELECT total_spent FROM users WHERE email = ?");
        $stmt->execute([$customer_email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate new total spent (only the paid amount, not discount)
        $new_total_spent = ($user ? $user['total_spent'] : 0) + $total;
        
        // Determine tier based on new total_spent
        $tier = 'Bronze';
        if ($new_total_spent >= 100000) {
            $tier = 'Platinum';
        } elseif ($new_total_spent >= 50000) {
            $tier = 'Gold';
        } elseif ($new_total_spent >= 20000) {
            $tier = 'Silver';
        }
        
        $expiryDate = date('Y-m-d', strtotime('+30 days'));
        
        $stmt = $pdo->prepare("UPDATE users SET total_spent = ?, tier = ?, tier_expiry = ? WHERE email = ?");
        $stmt->execute([$new_total_spent, $tier, $expiryDate, $customer_email]);
    }
    
    $pdo->commit();
    
    // Clear cart from localStorage (client will handle this)
    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'order_number' => $order_number,
        'tracking_number' => $tracking_number,
        'payment_method' => $payment_method,
        'coupon_applied' => $coupon_code ? true : false,
        'message' => 'Order placed successfully' . ($coupon_code ? ' Coupon applied!' : '')
    ]);
    
} catch(PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Order creation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch(Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("General order error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}
?>