<?php
// Tax Rates API handlers

function handleTaxRates($method, $input) {
    switch ($method) {
        case 'GET':
            $sortField = $_GET['sort'] ?? 'name';
            $sortOrder = $_GET['order'] ?? 'asc';
            $query = "order=" . $sortField . "." . $sortOrder;
            $result = supabaseRequest('tax_rates', 'GET', null, $query);
            echo json_encode(['data' => $result['data'] ?? []]);
            break;
            
        case 'POST':
            if (!isset($input['name'], $input['rate'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name and rate are required']);
                return;
            }
            
            $data = [
                'name' => $input['name'],
                'rate' => $input['rate'],
                'description' => $input['description'] ?? '',
                'is_default' => $input['is_default'] ?? false,
                'status' => 'active'
            ];
            
            $result = supabaseRequest('tax_rates', 'POST', $data);
            echo json_encode(['message' => 'Tax rate created', 'data' => $result['data']]);
            break;
            
        case 'PATCH':
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Tax rate ID required']);
                return;
            }
            
            $updateData = [];
            if (isset($input['name'])) $updateData['name'] = $input['name'];
            if (isset($input['rate'])) $updateData['rate'] = $input['rate'];
            if (isset($input['description'])) $updateData['description'] = $input['description'];
            if (isset($input['is_default'])) $updateData['is_default'] = $input['is_default'];
            if (isset($input['status'])) $updateData['status'] = $input['status'];
            
            $result = supabaseRequest('tax_rates', 'PATCH', $updateData, 'id=eq.' . $input['id']);
            echo json_encode(['message' => 'Tax rate updated', 'data' => $result['data']]);
            break;
            
        case 'DELETE':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                http_response_code(400);
                echo json_encode(['error' => 'Tax rate ID required']);
                return;
            }
            
            supabaseRequest('tax_rates', 'DELETE', null, 'id=eq.' . $id);
            echo json_encode(['message' => 'Tax rate deleted']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
