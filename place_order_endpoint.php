<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config/database.php';
require_once 'graphql/GraphQLResolver.php';

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST requests allowed');
    }

    // Get the raw input
    $rawInput = file_get_contents('php://input');
    $input = json_decode($rawInput, true);
    
    // Log the input for debugging
    error_log("Place order endpoint called with: " . $rawInput);
    
    // Check if this is a GraphQL request for placeOrder
    if (!$input || !isset($input['query'])) {
        throw new Exception('Invalid request format');
    }
    
    $query = $input['query'];
    
    // Check if this is a place order mutation
    if (strpos($query, 'placeOrder') === false) {
        throw new Exception('This endpoint only handles placeOrder mutations');
    }
    
    // Create resolver and place order
    $resolver = new GraphQLResolver();
    $result = $resolver->placeOrder();
    
    // Log the result
    error_log("Place order result: " . json_encode($result));
    
    // Return GraphQL-compliant response
    $output = [
        'data' => [
            'placeOrder' => $result
        ]
    ];
    
    echo json_encode($output);
    
} catch (Exception $e) {
    error_log("Place order error: " . $e->getMessage());
    
    $output = [
        'errors' => [
            ['message' => $e->getMessage()]
        ]
    ];
    
    echo json_encode($output);
}
?>
