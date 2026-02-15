<?php
// ERP Database Configuration
// Supabase PostgreSQL Connection

define('DB_HOST', 'db.kyrxwojoacxscpuyerwk.supabase.co');
define('DB_PORT', '5432');
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres');
define('DB_PASS', 'gTeKWozPZDLy3eQg'); // 請替換成你的密碼

define('SUPABASE_URL', 'https://kyrxwojoacxscpuyerwk.supabase.co');
define('SUPABASE_KEY', 'kyrxwojoacxscpuyerwk');

// CORS Headers for Vue frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection function
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database connection failed', 'message' => $e->getMessage()]);
            exit();
        }
    }
    
    return $pdo;
}

// Simple router
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

// Remove query string
$request_uri = explode('?', $request_uri)[0];
$request_uri = str_replace('/api/', '', $request_uri);

// Parse JSON input
$input = json_decode(file_get_contents('php://input'), true);

// API Routes
switch ($request_uri) {
    case 'health':
        echo json_encode(['status' => 'OK', 'message' => 'ERP API is running']);
        break;
        
    case 'products':
        handleProducts($method, $input);
        break;
        
    case 'customers':
        handleCustomers($method, $input);
        break;
        
    case 'orders':
        handleOrders($method, $input);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
}

// Product handlers
function handleProducts($method, $input) {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            $stmt = $db->query('SELECT * FROM products ORDER BY created_at DESC');
            $products = $stmt->fetchAll();
            echo json_encode(['data' => $products]);
            break;
            
        case 'POST':
            if (!isset($input['name'], $input['price'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            $stmt = $db->prepare('INSERT INTO products (name, description, price, stock_quantity, sku) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $input['name'],
                $input['description'] ?? '',
                $input['price'],
                $input['stock_quantity'] ?? 0,
                $input['sku'] ?? uniqid('SKU-')
            ]);
            
            echo json_encode(['message' => 'Product created', 'id' => $db->lastInsertId()]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

// Customer handlers
function handleCustomers($method, $input) {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            $stmt = $db->query('SELECT * FROM customers ORDER BY created_at DESC');
            $customers = $stmt->fetchAll();
            echo json_encode(['data' => $customers]);
            break;
            
        case 'POST':
            if (!isset($input['name'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Name is required']);
                return;
            }
            
            $stmt = $db->prepare('INSERT INTO customers (name, email, phone, address, company) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $input['name'],
                $input['email'] ?? '',
                $input['phone'] ?? '',
                $input['address'] ?? '',
                $input['company'] ?? ''
            ]);
            
            echo json_encode(['message' => 'Customer created', 'id' => $db->lastInsertId()]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

// Order handlers
function handleOrders($method, $input) {
    $db = getDB();
    
    switch ($method) {
        case 'GET':
            $stmt = $db->query('
                SELECT o.*, c.name as customer_name 
                FROM orders o 
                LEFT JOIN customers c ON o.customer_id = c.id 
                ORDER BY o.created_at DESC
            ');
            $orders = $stmt->fetchAll();
            echo json_encode(['data' => $orders]);
            break;
            
        case 'POST':
            if (!isset($input['customer_id'], $input['items'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing required fields']);
                return;
            }
            
            // Start transaction
            $db->beginTransaction();
            
            try {
                // Create order
                $stmt = $db->prepare('INSERT INTO orders (customer_id, total_amount, status, order_number) VALUES (?, ?, ?, ?)');
                $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
                $stmt->execute([
                    $input['customer_id'],
                    $input['total_amount'] ?? 0,
                    $input['status'] ?? 'pending',
                    $orderNumber
                ]);
                $orderId = $db->lastInsertId();
                
                // Add order items
                $stmt = $db->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)');
                foreach ($input['items'] as $item) {
                    $stmt->execute([
                        $orderId,
                        $item['product_id'],
                        $item['quantity'],
                        $item['unit_price'],
                        $item['quantity'] * $item['unit_price']
                    ]);
                }
                
                $db->commit();
                echo json_encode(['message' => 'Order created', 'id' => $orderId, 'order_number' => $orderNumber]);
                
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Failed to create order', 'message' => $e->getMessage()]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
