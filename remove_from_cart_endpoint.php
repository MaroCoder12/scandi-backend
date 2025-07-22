<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'services/CartService.php';

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed');
    }

    // Get the raw input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);

    // Check if this is a GraphQL request for removeFromCart
    if (!$input || !isset($input['variables']) || !isset($input['variables']['itemId'])) {
        throw new Exception('Invalid request format - itemId required');
    }

    $itemId = $input['variables']['itemId'];

    // Create cart service and remove item
    $cartService = new CartService();
    $result = $cartService->removeFromCart($itemId);

    // Return GraphQL-compliant response
    $output = [
        'data' => [
            'removeFromCart' => $result
        ]
    ];

    echo json_encode($output);

} catch (Exception $e) {
    error_log("Remove from cart error: {$e->getMessage()}");

    $output = [
        'errors' => [
            ['message' => $e->getMessage()]
        ]
    ];

    echo json_encode($output);
}
