<?php
// Orders API handlers

function handleOrders($method, $input) {
    switch ($method) {
        case 'GET':
            $result = supabaseRequest('orders', 'GET', null, 'order=created_at.desc&select=*,customers(name)');
            echo json_encode(['data' => $result['data'] ?? []]);
            break;
            
        case 'POST':
            if (!isset($input['customer_id'], $input['items'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            $orderData = [
                'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                'customer_id' => $input['customer_id'],
                'total_amount' => $input['total_amount'] ?? 0,
                'status' => $input['status'] ?? 'pending',
                'payment_status' => 'unpaid'
            ];
            
            $result = supabaseRequest('orders', 'POST', $orderData);
            
            if (isset($result['data'][0]['id'])) {
                $orderId = $result['data'][0]['id'];
                
                foreach ($input['items'] as $item) {
                    $itemData = [
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['quantity'] * $item['unit_price']
                    ];
                    supabaseRequest('order_items', 'POST', $itemData);
                }
                
                echo json_encode(['message' => 'Order created', 'id' => $orderId]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create order']);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
