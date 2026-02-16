<?php
// Customer API handlers

function handleCustomers($method, $input) {
    switch ($method) {
        case 'GET':
            $result = supabaseRequest('customers', 'GET', null, 'order=created_at.desc');
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
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
