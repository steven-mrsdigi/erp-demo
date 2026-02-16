<?php
// Payment Methods API handlers

function handlePaymentMethods($method, $input) {
    switch ($method) {
        case 'GET':
            $result = supabaseRequest('payment_methods', 'GET', null, 'is_active=eq.true&order=name.asc');
            echo json_encode(['data' => $result['data'] ?? []]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
