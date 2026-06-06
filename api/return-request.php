<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get return requests - for admin or user
    $user_email = isset($_GET['user_email']) ? $_GET['user_email'] : '';
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    $all = isset($_GET['all']) ? $_GET['all'] : '';
    
    try {
        $sql = "SELECT * FROM return_requests";
        $params = [];
        $conditions = [];
        
        if (!empty($user_email) && empty($all)) {
            $conditions[] = "user_email = ?";
            $params[] = $user_email;
        }
        if (!empty($status) && empty($all)) {
            $conditions[] = "status = ?";
            $params[] = $status;
        }
        
        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'count' => count($requests),
            'requests' => $requests
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
} elseif ($method === 'POST') {
    // Create or update return request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit();
    }
    
    $action = isset($data['action']) ? $data['action'] : '';
    
    if ($action === 'create') {
        $order_id = isset($data['order_id']) ? $data['order_id'] : '';
        $user_email = isset($data['user_email']) ? $data['user_email'] : '';
        $type = isset($data['type']) ? $data['type'] : '';
        $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
        $reason = isset($data['reason']) ? $data['reason'] : '';
        $comments = isset($data['comments']) ? $data['comments'] : '';
        
        if (empty($order_id) || empty($user_email) || empty($type) || empty($product_id) || empty($reason)) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            exit();
        }
        
        $request_id = 'RET' . time() . rand(100, 999);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO return_requests (request_id, order_id, user_email, type, product_id, reason, comments, status, created_at) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$request_id, $order_id, $user_email, $type, $product_id, $reason, $comments]);
            
            echo json_encode([
                'success' => true, 
                'request_id' => $request_id, 
                'message' => 'Return request submitted successfully'
            ]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        
    } elseif ($action === 'update_status') {
        $request_id = isset($data['request_id']) ? $data['request_id'] : '';
        $status = isset($data['status']) ? $data['status'] : '';
        $admin_response = isset($data['admin_response']) ? $data['admin_response'] : '';
        $exchange_product_id = isset($data['exchange_product_id']) ? $data['exchange_product_id'] : null;
        $exchange_product_name = isset($data['exchange_product_name']) ? $data['exchange_product_name'] : null;
        
        if (empty($request_id) || empty($status)) {
            echo json_encode(['success' => false, 'error' => 'Request ID and status required']);
            exit();
        }
        
        try {
            $sql = "UPDATE return_requests SET status = ?, admin_response = ?, processed_at = NOW()";
            $params = [$status, $admin_response];
            
            if (!empty($exchange_product_id)) {
                $sql .= ", exchange_product_id = ?";
                $params[] = $exchange_product_id;
            }
            if (!empty($exchange_product_name)) {
                $sql .= ", exchange_product_name = ?";
                $params[] = $exchange_product_name;
            }
            
            $sql .= " WHERE request_id = ?";
            $params[] = $request_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Return request updated successfully'
            ]);
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?>