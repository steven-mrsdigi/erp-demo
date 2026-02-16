<?php
// Extended API - Vendors, Inventory, Reports

// Include base API
require_once __DIR__ . '/api.php';

// Additional routes
$extended_routes = [
    'vendors' => 'handleVendors',
    'inventory' => 'handleInventory',
    'purchase-orders' => 'handlePurchaseOrders',
    'reports' => 'handleReports',
];

// Merge with existing routes
foreach ($extended_routes as $route => $handler) {
    if ($request_uri === $route) {
        $handler($method, $input);
        exit;
    }
}

// Vendor handlers
function handleVendors($method, $input) {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            $stmt = $db->query('SELECT * FROM vendors WHERE status = \'active\' ORDER BY name');
            $vendors = $stmt->fetchAll();
            echo json_encode(['data' => $vendors]);
            break;
            
        case 'POST':
            $stmt = $db->prepare('INSERT INTO vendors (name, contact_person, email, phone, address, tax_id, payment_terms, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $input['name'],
                $input['contact_person'] ?? '',
                $input['email'] ?? '',
                $input['phone'] ?? '',
                $input['address'] ?? '',
                $input['tax_id'] ?? '',
                $input['payment_terms'] ?? '',
                $input['notes'] ?? ''
            ]);
            echo json_encode(['message' => 'Vendor created', 'id' => $db->lastInsertId()]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

// Inventory handlers
function handleInventory($method, $input) {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            // Get inventory movements with product details
            $stmt = $db->query('
                SELECT im.*, p.name as product_name, p.sku 
                FROM inventory_movements im 
                LEFT JOIN products p ON im.product_id = p.id 
                ORDER BY im.created_at DESC 
                LIMIT 100
            ');
            $movements = $stmt->fetchAll();
            
            // Get current stock levels
            $stmt = $db->query('
                SELECT 
                    p.id, p.name, p.sku, p.stock_quantity,
                    COALESCE(SUM(CASE WHEN im.type = \'in\' THEN im.quantity ELSE 0 END), 0) as total_in,
                    COALESCE(SUM(CASE WHEN im.type = \'out\' THEN im.quantity ELSE 0 END), 0) as total_out
                FROM products p
                LEFT JOIN inventory_movements im ON p.id = im.product_id
                WHERE p.status = \'active\'
                GROUP BY p.id
                ORDER BY p.name
            ');
            $stock = $stmt->fetchAll();
            
            echo json_encode([
                'movements' => $movements,
                'stock' => $stock
            ]);
            break;
            
        case 'POST':
            // Record inventory movement
            $stmt = $db->prepare('INSERT INTO inventory_movements (product_id, type, quantity, unit_cost, total_cost, reference_type, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $input['product_id'],
                $input['type'],
                $input['quantity'],
                $input['unit_cost'] ?? 0,
                ($input['quantity'] * ($input['unit_cost'] ?? 0)),
                $input['reference_type'] ?? '',
                $input['notes'] ?? ''
            ]);
            
            // Update product stock
            $adjustment = $input['type'] === 'in' ? $input['quantity'] : -$input['quantity'];
            $stmt = $db->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?');
            $stmt->execute([$adjustment, $input['product_id']]);
            
            echo json_encode(['message' => 'Inventory updated']);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

// Purchase Order handlers
function handlePurchaseOrders($method, $input) {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            $stmt = $db->query('
                SELECT po.*, v.name as vendor_name 
                FROM purchase_orders po 
                LEFT JOIN vendors v ON po.vendor_id = v.id 
                ORDER BY po.created_at DESC
            ');
            $pos = $stmt->fetchAll();
            echo json_encode(['data' => $pos]);
            break;
            
        case 'POST':
            $po_number = 'PO-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $stmt = $db->prepare('INSERT INTO purchase_orders (po_number, vendor_id, expected_date, notes) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $po_number,
                $input['vendor_id'],
                $input['expected_date'] ?? null,
                $input['notes'] ?? ''
            ]);
            echo json_encode(['message' => 'PO created', 'po_number' => $po_number]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

// Reports handlers
function handleReports($method, $input) {
    $db = getDB();
    $report_type = $_GET['type'] ?? 'dashboard';
    
    switch ($report_type) {
        case 'dashboard':
            // Summary statistics
            $stats = [];
            
            $stmt = $db->query('SELECT COUNT(*) as count FROM products WHERE status = \'active\'');
            $stats['total_products'] = $stmt->fetch()['count'];
            
            $stmt = $db->query('SELECT COUNT(*) as count FROM customers WHERE status = \'active\'');
            $stats['total_customers'] = $stmt->fetch()['count'];
            
            $stmt = $db->query('SELECT COUNT(*) as count FROM orders WHERE created_at >= CURRENT_DATE - INTERVAL \'30 days\'');
            $stats['monthly_orders'] = $stmt->fetch()['count'];
            
            $stmt = $db->query('SELECT SUM(total_amount) as total FROM orders WHERE created_at >= CURRENT_DATE - INTERVAL \'30 days\'');
            $stats['monthly_revenue'] = $stmt->fetch()['total'] ?? 0;
            
            $stmt = $db->query('
                SELECT p.name, p.stock_quantity 
                FROM products p 
                WHERE p.stock_quantity < 10 AND p.status = \'active\'
                ORDER BY p.stock_quantity ASC 
                LIMIT 5
            ');
            $stats['low_stock'] = $stmt->fetchAll();
            
            echo json_encode($stats);
            break;
            
        case 'sales':
            // Sales by date
            $stmt = $db->query('
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as order_count,
                    SUM(total_amount) as total_sales
                FROM orders
                WHERE created_at >= CURRENT_DATE - INTERVAL \'30 days\'
                GROUP BY DATE(created_at)
                ORDER BY date DESC
            ');
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;
            
        case 'inventory':
            // Inventory valuation
            $stmt = $db->query('
                SELECT 
                    p.category,
                    COUNT(*) as product_count,
                    SUM(p.stock_quantity) as total_units,
                    SUM(p.stock_quantity * p.price) as total_value
                FROM products p
                WHERE p.status = \'active\'
                GROUP BY p.category
            ');
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;
            
        case 'top-products':
            // Top selling products
            $stmt = $db->query('
                SELECT 
                    p.name,
                    p.sku,
                    SUM(oi.quantity) as total_sold,
                    SUM(oi.total_price) as total_revenue
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                JOIN orders o ON oi.order_id = o.id
                WHERE o.created_at >= CURRENT_DATE - INTERVAL \'30 days\'
                GROUP BY p.id
                ORDER BY total_sold DESC
                LIMIT 10
            ');
            echo json_encode(['data' => $stmt->fetchAll()]);
            break;
            
        default:
            echo json_encode(['error' => 'Unknown report type']);
    }
}

// If no route matched, return error
http_response_code(404);
echo json_encode(['error' => 'Endpoint not found']);
