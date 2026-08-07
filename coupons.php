<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Get all active coupons (for frontend)
if ($method === 'GET' && $action === 'active') {
    try {
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT * FROM coupons 
                               WHERE is_active = 1 
                               AND (start_date IS NULL OR start_date <= ?)
                               AND (end_date IS NULL OR end_date >= ?)
                               AND (usage_limit IS NULL OR used_count < usage_limit)
                               ORDER BY id DESC");
        $stmt->execute([$now, $now]);
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'coupons' => $coupons]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Admin: Get all coupons (for admin panel)
if ($method === 'GET' && $action === 'all') {
    try {
        $stmt = $pdo->query("SELECT * FROM coupons ORDER BY id DESC");
        $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'coupons' => $coupons]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Admin: Create coupon
if ($method === 'POST' && $action === 'create') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $code = strtoupper(trim($data['code'] ?? ''));
    $description = $data['description'] ?? '';
    $discount_type = $data['discount_type'] ?? 'percent';
    $discount_value = $data['discount_value'] ?? 0;
    $min_order_amount = $data['min_order_amount'] ?? 0;
    $max_discount = $data['max_discount'] ?? null;
    $usage_limit = $data['usage_limit'] ?? null;
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;
    
    if (!$code || !$discount_value) {
        echo json_encode(['success' => false, 'error' => 'Code and discount value required']);
        exit();
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO coupons (code, description, discount_type, discount_value, min_order_amount, max_discount, usage_limit, start_date, end_date) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $description, $discount_type, $discount_value, $min_order_amount, $max_discount, $usage_limit, $start_date, $end_date]);
        
        echo json_encode(['success' => true, 'message' => 'Coupon created', 'id' => $pdo->lastInsertId()]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Admin: Update coupon
if ($method === 'PUT' && $action === 'update') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    $code = strtoupper(trim($data['code'] ?? ''));
    $description = $data['description'] ?? '';
    $discount_type = $data['discount_type'] ?? 'percent';
    $discount_value = $data['discount_value'] ?? 0;
    $min_order_amount = $data['min_order_amount'] ?? 0;
    $max_discount = $data['max_discount'] ?? null;
    $usage_limit = $data['usage_limit'] ?? null;
    $is_active = $data['is_active'] ?? 1;
    $start_date = $data['start_date'] ?? null;
    $end_date = $data['end_date'] ?? null;
    
    try {
        $stmt = $pdo->prepare("UPDATE coupons SET 
            code = ?, description = ?, discount_type = ?, discount_value = ?, 
            min_order_amount = ?, max_discount = ?, usage_limit = ?, 
            is_active = ?, start_date = ?, end_date = ?
            WHERE id = ?");
        $stmt->execute([$code, $description, $discount_type, $discount_value, 
            $min_order_amount, $max_discount, $usage_limit, 
            $is_active, $start_date, $end_date, $id]);
        
        echo json_encode(['success' => true, 'message' => 'Coupon updated']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Admin: Delete coupon
if ($method === 'DELETE' && $action === 'delete') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => 'Coupon deleted']);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}

// Apply coupon (check if valid)
if ($method === 'POST' && $action === 'apply') {
    $data = json_decode(file_get_contents('php://input'), true);
    $code = strtoupper(trim($data['code'] ?? ''));
    $subtotal = $data['subtotal'] ?? 0;
    $user_email = $data['user_email'] ?? '';
    
    if (!$code) {
        echo json_encode(['success' => false, 'error' => 'Coupon code required']);
        exit();
    }
    
    try {
        $now = date('Y-m-d H:i:s');
        
        // Get coupon details
        $stmt = $pdo->prepare("SELECT * FROM coupons WHERE code = ?");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            echo json_encode(['success' => false, 'error' => 'Invalid coupon code']);
            exit();
        }
        
        // Check if active
        if (!$coupon['is_active']) {
            echo json_encode(['success' => false, 'error' => 'Coupon is not active']);
            exit();
        }
        
        // Check date range
        if ($coupon['start_date'] && $coupon['start_date'] > $now) {
            echo json_encode(['success' => false, 'error' => 'Coupon not yet available']);
            exit();
        }
        if ($coupon['end_date'] && $coupon['end_date'] < $now) {
            echo json_encode(['success' => false, 'error' => 'Coupon has expired']);
            exit();
        }
        
        // Check usage limit
        if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
            echo json_encode(['success' => false, 'error' => 'Coupon usage limit reached']);
            exit();
        }
        
        // Check minimum order amount
        if ($subtotal < $coupon['min_order_amount']) {
            echo json_encode(['success' => false, 'error' => 'Minimum order amount is ৳' . number_format($coupon['min_order_amount'])]);
            exit();
        }
        
        // Check if user already used this coupon
        if ($user_email) {
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM coupon_usage WHERE coupon_id = ? AND user_email = ?");
            $stmt->execute([$coupon['id'], $user_email]);
            $usage = $stmt->fetch();
            if ($usage['count'] >= $coupon['user_limit_per_user']) {
                echo json_encode(['success' => false, 'error' => 'You have already used this coupon']);
                exit();
            }
        }
        
        // Calculate discount
        $discount = 0;
        if ($coupon['discount_type'] === 'percent') {
            $discount = ($subtotal * $coupon['discount_value']) / 100;
            if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        } else {
            $discount = $coupon['discount_value'];
            if ($discount > $subtotal) {
                $discount = $subtotal;
            }
        }
        
        echo json_encode([
            'success' => true,
            'coupon' => $coupon,
            'discount' => round($discount, 2),
            'message' => 'Coupon applied!'
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit();
}
?>