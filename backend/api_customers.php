<?php
// Customer API handlers

function handleCustomers($method, $input) {
    switch ($method) {
        case 'GET':
            $sortField = $_GET['sort'] ?? 'created_at';
            $sortOrder = $_GET['order'] ?? 'desc';
            $query = "order=" . $sortField . "." . $sortOrder;
            $result = supabaseRequest('customers', 'GET', null, $query);
            echo json_encode(['data' => $result['data'] ?? []]);
            break;
            
        case 'POST':
            if (!isset($input['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name is required']);
                return;
            }
            
            $data = [
                'name' => $input['name'],
                'email' => $input['email'] ?? '',
                'phone' => $input['phone'] ?? '',
                'address' => $input['address'] ?? '',
                'company' => $input['company'] ?? '',
                'status' => 'active'
            ];
            
            $result = supabaseRequest('customers', 'POST', $data);
            echo json_encode(['message' => 'Customer created', 'data' => $result['data']]);
            break;
            
        case 'PATCH':
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Customer ID required']);
                return;
            }
            
            $updateData = [];
            if (isset($input['name'])) $updateData['name'] = $input['name'];
            if (isset($input['email'])) $updateData['email'] = $input['email'];
            if (isset($input['phone'])) $updateData['phone'] = $input['phone'];
            if (isset($input['address'])) $updateData['address'] = $input['address'];
            if (isset($input['company'])) $updateData['company'] = $input['company'];
            if (isset($input['status'])) $updateData['status'] = $input['status'];
            
            $result = supabaseRequest('customers', 'PATCH', $updateData, 'id=eq.' . $input['id']);
            echo json_encode(['message' => 'Customer updated', 'data' => $result['data']]);
            break;
        
        case 'DELETE':
            $id = $_GET['id'] ?? $input['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Customer ID required']);
                return;
            }
            
            // Check if customer is used in any orders
            $orders = supabaseRequest('orders', 'GET', null, 'customer_id=eq.' . $id . '&limit=1');
            if (!empty($orders['data'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Cannot delete customer that has been used in orders']);
                return;
            }
            
            // Delete the customer
            supabaseRequest('customers', 'DELETE', null, 'id=eq.' . $id);
            echo json_encode(['message' => 'Customer deleted']);
            break;
        
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
