<?php
// Inventory API handlers

function handleInventory($method, $input) {
    switch ($method) {
        case 'GET':
            $sortField = $_GET['sort'] ?? 'name';
            $sortOrder = $_GET['order'] ?? 'asc';
            
            // Get recent movements
            $movements = supabaseRequest('inventory_movements', 'GET', null, 'order=created_at.desc&limit=50');
            
            // Get current stock from products (using onhand_qty as the real stock)
            $query = 'select=id,name,sku,onhand_qty,allocated_qty,available_qty&status=eq.active&order=' . $sortField . '.' . $sortOrder;
            $products = supabaseRequest('products', 'GET', null, $query);
            
            // Calculate totals for each product
            $stockData = [];
            foreach ($products['data'] ?? [] as $product) {
                $stockData[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'sku' => $product['sku'],
                    'stock_quantity' => $product['onhand_qty'] ?? 0,
                    'allocated_qty' => $product['allocated_qty'] ?? 0,
                    'available_qty' => $product['available_qty'] ?? 0,
                    'total_in' => 0,
                    'total_out' => 0
                ];
            }
            
            echo json_encode([
                'movements' => $movements['data'] ?? [],
                'stock' => $stockData
            ]);
            break;
            
        case 'POST':
            $data = [
                'product_id' => $input['product_id'],
                'type' => $input['type'],
                'quantity' => $input['quantity'],
                'unit_cost' => $input['unit_cost'] ?? 0,
                'total_cost' => ($input['quantity'] ?? 0) * ($input['unit_cost'] ?? 0),
                'reference_type' => $input['reference_type'] ?? '',
                'notes' => $input['notes'] ?? ''
            ];
            
            $result = supabaseRequest('inventory_movements', 'POST', $data);
            
            // Update product stock
            $adjustment = ($input['type'] === 'in') ? $input['quantity'] : -$input['quantity'];
            supabaseRequest('products', 'PATCH', [
                'stock_quantity' => ['increment' => $adjustment]
            ], 'id=eq.' . $input['product_id']);
            
            echo json_encode(['message' => 'Inventory updated', 'data' => $result['data']]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
