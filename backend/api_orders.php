<?php
// Orders API handlers with inventory management

function handleOrders($method, $input) {
    switch ($method) {
        case 'GET':
            $result = supabaseRequest('orders', 'GET', null, 'order=created_at.desc&select=*,customers(name)');
            $orders = [];
            foreach ($result['data'] ?? [] as $order) {
                $order['customer_name'] = $order['customers']['name'] ?? 'Unknown Customer';
                unset($order['customers']);
                $orders[] = $order;
            }
            echo json_encode(['data' => $orders]);
            break;
            
        case 'POST':
            if (!isset($input['customer_id'], $input['items'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            // Check inventory availability for each item
            foreach ($input['items'] as $item) {
                $product = supabaseRequest('products', 'GET', null, 'id=eq.' . $item['product_id'] . '&select=available_qty,name');
                $available = $product['data'][0]['available_qty'] ?? 0;
                $productName = $product['data'][0]['name'] ?? 'Product';
                
                if ($item['quantity'] > $available) {
                    http_response_code(400);
                    echo json_encode(['error' => "Insufficient stock for {$productName}. Available: {$available}"]);
                    return;
                }
            }
            
            $orderData = [
                'order_number' => 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                'customer_id' => $input['customer_id'],
                'total_amount' => $input['total_amount'] ?? 0,
                'status' => 'pending',
                'payment_status' => 'unpaid'
            ];
            
            $result = supabaseRequest('orders', 'POST', $orderData);
            
            if (isset($result['data'][0]['id'])) {
                $orderId = $result['data'][0]['id'];
                
                // Create order items and allocate inventory
                foreach ($input['items'] as $item) {
                    $itemData = [
                        'order_id' => $orderId,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['quantity'] * $item['unit_price']
                    ];
                    supabaseRequest('order_items', 'POST', $itemData);
                    
                    // Allocate inventory: allocated_qty += quantity
                    $product = supabaseRequest('products', 'GET', null, 'id=eq.' . $item['product_id'] . '&select=allocated_qty');
                    $newAllocated = ($product['data'][0]['allocated_qty'] ?? 0) + $item['quantity'];
                    supabaseRequest('products', 'PATCH', ['allocated_qty' => $newAllocated], 'id=eq.' . $item['product_id']);
                }
                
                echo json_encode(['message' => 'Order created', 'id' => $orderId]);
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create order']);
            }
            break;
            
        case 'PATCH':
            // Handle order updates and payments
            if (isset($input['action']) && $input['action'] === 'pay') {
                handlePayment($input);
            } elseif (isset($input['action']) && $input['action'] === 'update') {
                handleOrderUpdate($input);
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        case 'DELETE':
            handleOrderCancellation($input);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handlePayment($input) {
    if (!isset($input['order_id'], $input['payment_method'], $input['paid_amount'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing payment details']);
        return;
    }
    
    $orderId = $input['order_id'];
    
    // Get order items to deduct from onhand_qty
    $items = supabaseRequest('order_items', 'GET', null, 'order_id=eq.' . $orderId);
    
    foreach ($items['data'] ?? [] as $item) {
        $productId = $item['product_id'];
        $quantity = $item['quantity'];
        
        // Get current quantities
        $product = supabaseRequest('products', 'GET', null, 'id=eq.' . $productId . '&select=onhand_qty,allocated_qty');
        $onhand = $product['data'][0]['onhand_qty'] ?? 0;
        $allocated = $product['data'][0]['allocated_qty'] ?? 0;
        
        // Update: onhand_qty -= quantity, allocated_qty -= quantity
        supabaseRequest('products', 'PATCH', [
            'onhand_qty' => $onhand - $quantity,
            'allocated_qty' => $allocated - $quantity
        ], 'id=eq.' . $productId);
    }
    
    // Update order payment status
    $updateData = [
        'payment_status' => 'paid',
        'status' => 'completed',
        'paid_at' => date('Y-m-d\TH:i:s.uP'),
        'paid_amount' => $input['paid_amount'],
        'payment_method' => $input['payment_method'],
        'payment_reference' => $input['payment_reference'] ?? ''
    ];
    
    $result = supabaseRequest('orders', 'PATCH', $updateData, 'id=eq.' . $orderId);
    echo json_encode(['message' => 'Payment processed', 'data' => $result['data']]);
}

function handleOrderUpdate($input) {
    if (!isset($input['order_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        return;
    }
    
    $orderId = $input['order_id'];
    
    // Release old allocations
    $oldItems = supabaseRequest('order_items', 'GET', null, 'order_id=eq.' . $orderId);
    foreach ($oldItems['data'] ?? [] as $oldItem) {
        $product = supabaseRequest('products', 'GET', null, 'id=eq.' . $oldItem['product_id'] . '&select=allocated_qty');
        $newAllocated = ($product['data'][0]['allocated_qty'] ?? 0) - $oldItem['quantity'];
        supabaseRequest('products', 'PATCH', ['allocated_qty' => max(0, $newAllocated)], 'id=eq.' . $oldItem['product_id']);
    }
    
    // Delete old items
    supabaseRequest('order_items', 'DELETE', null, 'order_id=eq.' . $orderId);
    
    // Add new items with new allocations
    foreach ($input['items'] as $item) {
        $itemData = [
            'order_id' => $orderId,
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['quantity'] * $item['unit_price']
        ];
        supabaseRequest('order_items', 'POST', $itemData);
        
        // Allocate new quantity
        $product = supabaseRequest('products', 'GET', null, 'id=eq.' . $item['product_id'] . '&select=allocated_qty');
        $newAllocated = ($product['data'][0]['allocated_qty'] ?? 0) + $item['quantity'];
        supabaseRequest('products', 'PATCH', ['allocated_qty' => $newAllocated], 'id=eq.' . $item['product_id']);
    }
    
    // Update order total
    supabaseRequest('orders', 'PATCH', [
        'total_amount' => $input['total_amount'],
        'notes' => $input['notes'] ?? ''
    ], 'id=eq.' . $orderId);
    
    echo json_encode(['message' => 'Order updated']);
}

function handleOrderItems($method, $input) {
    switch ($method) {
        case 'GET':
            $query = $_SERVER['QUERY_STRING'] ?? '';
            $result = supabaseRequest('order_items', 'GET', null, $query);
            echo json_encode(['data' => $result['data'] ?? []]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

function handleOrderCancellation($input) {
    $orderId = $_GET['id'] ?? null;
    if (!$orderId) {
        http_response_code(400);
        echo json_encode(['error' => 'Order ID required']);
        return;
    }
    
    // Get order to check if paid
    $order = supabaseRequest('orders', 'GET', null, 'id=eq.' . $orderId . '&select=payment_status');
    $isPaid = ($order['data'][0]['payment_status'] ?? '') === 'paid';
    
    // Get order items
    $items = supabaseRequest('order_items', 'GET', null, 'order_id=eq.' . $orderId);
    
    foreach ($items['data'] ?? [] as $item) {
        $productId = $item['product_id'];
        $quantity = $item['quantity'];
        
        $product = supabaseRequest('products', 'GET', null, 'id=eq.' . $productId . '&select=onhand_qty,allocated_qty');
        $onhand = $product['data'][0]['onhand_qty'] ?? 0;
        $allocated = $product['data'][0]['allocated_qty'] ?? 0;
        
        if ($isPaid) {
            // If paid, restore onhand_qty
            supabaseRequest('products', 'PATCH', [
                'onhand_qty' => $onhand + $quantity
            ], 'id=eq.' . $productId);
        } else {
            // If not paid, just release allocation
            supabaseRequest('products', 'PATCH', [
                'allocated_qty' => max(0, $allocated - $quantity)
            ], 'id=eq.' . $productId);
        }
    }
    
    // Cancel order
    supabaseRequest('orders', 'PATCH', ['status' => 'cancelled'], 'id=eq.' . $orderId);
    echo json_encode(['message' => 'Order cancelled']);
}
