<?php
// Products API handlers

function handleProducts($method, $input) {
    switch ($method) {
        case 'GET':
            $sortField = $_GET['sort'] ?? 'created_at';
            $sortOrder = $_GET['order'] ?? 'desc';
            $query = "order=" . $sortField . "." . $sortOrder;
            $result = supabaseRequest('products', 'GET', null, $query);
            echo json_encode(['data' => $result['data'] ?? []]);
            break;
            
        case 'POST':
            if (!isset($input['name'], $input['price'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            $onhandQty = $input['onhand_qty'] ?? $input['stock_quantity'] ?? 0;
            
            $data = [
                'sku' => $input['sku'] ?? uniqid('SKU-'),
                'name' => $input['name'],
                'description' => $input['description'] ?? '',
                'price' => $input['price'],
                'tax_rate_id' => $input['tax_rate_id'] ?? null,
                'stock_quantity' => $onhandQty,
                'onhand_qty' => $onhandQty,
                'allocated_qty' => 0,
                'available_qty' => $onhandQty,
                'category' => $input['category'] ?? '',
                'status' => 'active'
            ];
            
            $result = supabaseRequest('products', 'POST', $data);
            echo json_encode(['message' => 'Product created', 'data' => $result['data']]);
            break;
            
        case 'PATCH':
            if (!isset($input['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Product ID required']);
                return;
            }
            
            // Build update data
            $updateData = [];
            if (isset($input['name'])) $updateData['name'] = $input['name'];
            if (isset($input['price'])) $updateData['price'] = $input['price'];
            if (isset($input['tax_rate_id'])) $updateData['tax_rate_id'] = $input['tax_rate_id'];
            if (isset($input['category'])) $updateData['category'] = $input['category'];
            if (isset($input['description'])) $updateData['description'] = $input['description'];
            
            // Handle stock update
            if (isset($input['onhand_qty'])) {
                $updateData['onhand_qty'] = $input['onhand_qty'];
                $updateData['stock_quantity'] = $input['onhand_qty'];
            } elseif (isset($input['stock_quantity'])) {
                $updateData['onhand_qty'] = $input['stock_quantity'];
                $updateData['stock_quantity'] = $input['stock_quantity'];
            }
            
            $result = supabaseRequest('products', 'PATCH', $updateData, 'id=eq.' . $input['id']);
            echo json_encode(['message' => 'Product updated', 'data' => $result['data']]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
