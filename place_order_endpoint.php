<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configure CORS
$allowedOrigin = $_ENV['ALLOWED_ORIGIN'] ?? '*';
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, apollo-require-preflight, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'services/OrderService.php';

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed');
    }

    // Get the raw input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    // Check if this is a GraphQL request for placeOrder
    if (!$input || !isset($input['query'])) {
        throw new Exception('Invalid request format');
    }

    $query = $input['query'];

    // Check if this is a place order mutation
    if (strpos($query, 'placeOrder') === false) {
        throw new Exception('This endpoint only handles placeOrder mutations');
    }

    // Create order service and place order
    $orderService = new OrderService();
    $result = $orderService->placeOrder();

    // Return GraphQL-compliant response
    $output = [
        'data' => [
            'placeOrder' => $result
        ]
    ];

    echo json_encode($output);

} catch (Exception $e) {
    error_log("Place order error: {$e->getMessage()}");

    $output = [
        'errors' => [
            ['message' => $e->getMessage()]
        ]
    ];

    echo json_encode($output);
}
