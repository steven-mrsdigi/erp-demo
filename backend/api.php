<?php
// ERP API using Supabase REST API (No direct PostgreSQL connection needed)

define('SUPABASE_URL', 'https://kyrxwojoacxscpuyerwk.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imt5cnh3b2pvYWN4c2NwdXllcndrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzExMDY0ODEsImV4cCI6MjA4NjY4MjQ4MX0.QJ8TH7sae0-ISbXr9au89lhAD881IiBTv_o0LApDysU'); // 請使用你的 anon key

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, apikey');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Supabase REST API client
function supabaseRequest($table, $method = 'GET', $data = null, $query = '') {
    $url = SUPABASE_URL . '/rest/v1/' . $table;
    if ($query) {
        $url .= '?' . $query;
    }
    
    $ch = curl_init($url);
    
    $headers = [
        'apikey: ' . SUPABASE_KEY,
        'Authorization: Bearer ' . SUPABASE_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    switch ($method) {
        case 'POST':
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'PATCH':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            break;
        case 'DELETE':
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            break;
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => true, 'message' => $error];
    }
    
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

// Include modular API handlers (after supabaseRequest is defined)
require_once __DIR__ . '/api_customers.php';
require_once __DIR__ . '/api_vendors.php';
require_once __DIR__ . '/api_inventory.php';
require_once __DIR__ . '/api_reports.php';
require_once __DIR__ . '/api_orders.php';
require_once __DIR__ . '/api_payment_methods.php';

// Router
$request_uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = explode('?', $request_uri)[0];
$request_uri = str_replace('/api/', '', $request_uri);
$input = json_decode(file_get_contents('php://input'), true);

switch ($request_uri) {
    case 'health':
        // Test Supabase connection
        $result = supabaseRequest('customers', 'GET', null, 'limit=1');
        if (isset($result['error'])) {
            echo json_encode(['status' => 'ERROR', 'message' => 'Cannot connect to Supabase', 'error' => $result['message']]);
        } else {
            echo json_encode(['status' => 'OK', 'message' => 'Connected to Supabase', 'code' => $result['code']]);
        }
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
        
    case 'vendors':
        handleVendors($method, $input);
        break;
        
    case 'inventory':
        handleInventory($method, $input);
        break;
        
    case 'reports':
        handleReports($method, $input);
        break;
        
    case 'payment-methods':
        handlePaymentMethods($method, $input);
        break;
        
    case 'order_items':
        handleOrderItems($method, $input);
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
}

function handleProducts($method, $input) {
    switch ($method) {
        case 'GET':
            $result = supabaseRequest('products', 'GET', null, 'order=created_at.desc');
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

function handleOrderItems($method, $input) {
    switch ($method) {
        case 'GET':
            $query = $_SERVER['QUERY_STRING'] ?? '';
            $result = supabaseRequest('order_items', 'GET', null, $query);
            echo json_encode(['data' => $result['data'] ?? []]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

// All API handlers are defined in their respective modular files:
// api_customers.php, api_vendors.php, api_inventory.php, api_reports.php, api_orders.php
