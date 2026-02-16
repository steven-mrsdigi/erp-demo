<?php
// ERP API using Supabase REST API (No direct PostgreSQL connection needed)

define('SUPABASE_URL', 'https://kyrxwojoacxscpuyerwk.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imt5cnh3b2pvYWN4c2NwdXllcndrIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NzExMDY0ODEsImV4cCI6MjA4NjY4MjQ4MX0.QJ8TH7sae0-ISbXr9au89lhAD881IiBTv_o0LApDysU'); // 請使用你的 anon key

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, apikey');
header('Content-Type: application/json; charset=utf-8');

// Enable PHP error logging
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

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
    // curl_close($ch); // Deprecated in PHP 8.0+, curl handle is auto-cleanup
    
    if ($error) {
        return ['error' => true, 'message' => $error];
    }
    
    return ['code' => $httpCode, 'data' => json_decode($response, true)];
}

// Include modular API handlers (after supabaseRequest is defined)
require_once __DIR__ . '/api_products.php';
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

// All API handlers are defined in their respective modular files:
// api_products.php, api_customers.php, api_vendors.php, api_inventory.php,
// api_reports.php, api_orders.php, api_payment_methods.php
