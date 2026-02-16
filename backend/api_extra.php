<?php
// Extended API handlers for Vendors, Inventory, and Reports

// Vendor handlers
function handleVendors($method, $input) {
    switch ($method) {
        case 'GET':
            $result = supabaseRequest('vendors', 'GET', null, 'order=name.asc');
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

// Inventory handlers
function handleInventory($method, $input) {
    switch ($method) {
        case 'GET':
            // Get recent movements
            $movements = supabaseRequest('inventory_movements', 'GET', null, 'order=created_at.desc&limit=50');
            
            // Get current stock from products
            $products = supabaseRequest('products', 'GET', null, 'select=id,name,sku,stock_quantity&status=eq.active&order=name.asc');
            
            // Calculate totals for each product
            $stockData = [];
            foreach ($products['data'] ?? [] as $product) {
                $stockData[] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'sku' => $product['sku'],
                    'stock_quantity' => $product['stock_quantity'],
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

// Reports handlers
function handleReports($method, $input) {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $reportType = $_GET['type'] ?? 'dashboard';
    
    switch ($reportType) {
        case 'dashboard':
            $products = supabaseRequest('products', 'GET', null, 'status=eq.active');
            $customers = supabaseRequest('customers', 'GET', null, 'status=eq.active');
            
            // Get last 30 days orders
            $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
            $orders = supabaseRequest('orders', 'GET', null, 'created_at=gte.' . $thirtyDaysAgo);
            
            $monthlyOrders = count($orders['data'] ?? []);
            $monthlyRevenue = 0;
            foreach ($orders['data'] ?? [] as $order) {
                $monthlyRevenue += floatval($order['total_amount'] ?? 0);
            }
            
            // Get low stock items
            $lowStock = [];
            foreach ($products['data'] ?? [] as $product) {
                if (($product['stock_quantity'] ?? 0) < 10) {
                    $lowStock[] = [
                        'id' => $product['id'],
                        'name' => $product['name'],
                        'stock_quantity' => $product['stock_quantity']
                    ];
                }
            }
            
            echo json_encode([
                'total_products' => count($products['data'] ?? []),
                'total_customers' => count($customers['data'] ?? []),
                'monthly_orders' => $monthlyOrders,
                'monthly_revenue' => $monthlyRevenue,
                'low_stock' => array_slice($lowStock, 0, 5)
            ]);
            break;
            
        case 'sales':
            $thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
            $orders = supabaseRequest('orders', 'GET', null, 'created_at=gte.' . $thirtyDaysAgo . '&select=total_amount,created_at&order=created_at.desc');
            
            // Group by date
            $salesByDate = [];
            foreach ($orders['data'] ?? [] as $order) {
                $date = substr($order['created_at'], 0, 10);
                if (!isset($salesByDate[$date])) {
                    $salesByDate[$date] = ['date' => $date, 'order_count' => 0, 'total_sales' => 0];
                }
                $salesByDate[$date]['order_count']++;
                $salesByDate[$date]['total_sales'] += floatval($order['total_amount'] ?? 0);
            }
            
            echo json_encode(['data' => array_values($salesByDate)]);
            break;
            
        case 'inventory':
            $products = supabaseRequest('products', 'GET', null, 'status=eq.active');
            
            $byCategory = [];
            foreach ($products['data'] ?? [] as $product) {
                $cat = $product['category'] ?: 'Uncategorized';
                if (!isset($byCategory[$cat])) {
                    $byCategory[$cat] = [
                        'category' => $cat,
                        'product_count' => 0,
                        'total_units' => 0,
                        'total_value' => 0
                    ];
                }
                $byCategory[$cat]['product_count']++;
                $byCategory[$cat]['total_units'] += intval($product['stock_quantity'] ?? 0);
                $byCategory[$cat]['total_value'] += ($product['stock_quantity'] ?? 0) * ($product['price'] ?? 0);
            }
            
            echo json_encode(['data' => array_values($byCategory)]);
            break;
            
        case 'top-products':
            // Get order items with product info
            $orderItems = supabaseRequest('order_items', 'GET', null, 'select=quantity,total_price,products(name,sku),orders!inner(created_at)&orders.created_at=gte.' . date('Y-m-d', strtotime('-30 days')));
            
            $productSales = [];
            foreach ($orderItems['data'] ?? [] as $item) {
                $sku = $item['products']['sku'] ?? 'unknown';
                $name = $item['products']['name'] ?? 'Unknown';
                
                if (!isset($productSales[$sku])) {
                    $productSales[$sku] = [
                        'name' => $name,
                        'sku' => $sku,
                        'total_sold' => 0,
                        'total_revenue' => 0
                    ];
                }
                $productSales[$sku]['total_sold'] += intval($item['quantity'] ?? 0);
                $productSales[$sku]['total_revenue'] += floatval($item['total_price'] ?? 0);
            }
            
            // Sort by total sold and get top 10
            usort($productSales, function($a, $b) {
                return $b['total_sold'] - $a['total_sold'];
            });
            
            echo json_encode(['data' => array_slice($productSales, 0, 10)]);
            break;
            
        default:
            echo json_encode(['error' => 'Unknown report type']);
    }
}