<?php
// Reports API handlers

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
