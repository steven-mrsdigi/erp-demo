<?php
// Vendor API handlers

function handleVendors($method, $input) {
    switch ($method) {
        case 'GET':
            $sortField = $_GET['sort'] ?? 'name';
            $sortOrder = $_GET['order'] ?? 'asc';
            $query = "order=" . $sortField . "." . $sortOrder;
            $result = supabaseRequest('vendors', 'GET', null, $query);
            echo json_encode(['data' => $result['data'] ?? []]);
            break;
            
        case 'POST':
            $data = [
                'name' => $input['name'] ?? '',
                'contact_person' => $input['contact_person'] ?? '',
                'email' => $input['email'] ?? '',
                'phone' => $input['phone'] ?? '',
                'address' => $input['address'] ?? '',
                'tax_id' => $input['tax_id'] ?? '',
                'payment_terms' => $input['payment_terms'] ?? '',
                'notes' => $input['notes'] ?? '',
                'status' => 'active'
            ];
            
            $result = supabaseRequest('vendors', 'POST', $data);
            echo json_encode(['message' => 'Vendor created', 'data' => $result['data']]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
