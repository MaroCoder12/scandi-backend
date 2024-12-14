<?php

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0); // Exit early for preflight requests
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/graphql/GraphQLResolver.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Read and decode the incoming request
$input = json_decode(file_get_contents('php://input'), true);

// Check if the input is null or doesn't contain expected keys
if (is_null($input) || !isset($input['query'])) {
    // Return an error response if the input is empty or invalid
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid JSON payload or missing "query" key']);
    exit;
}

// Initialize variables from input, with fallback values to avoid errors
$queryType = $input['query'];
$operation = $input['operationName'] ?? '';
$variables = $input['variables'] ?? [];

// Instantiate the resolver
$resolver = new GraphQLResolver();
$response = null;

try {
    // Route the operation to the appropriate resolver method
    switch ($operation) {
        case 'product':
            $response = $resolver->getProduct($variables);
            break;
        case 'products':
            $response = $resolver->getProducts();
            break;
        case 'createProduct':
            $response = $resolver->createProduct($variables);
            break;
        case 'updateProduct':
            $response = $resolver->updateProduct($variables);
            break;
        case 'deleteProduct':
            $response = $resolver->deleteProduct($variables);
            break;
        case 'attributes':
            $response = $resolver->getAttributes($variables);
            break;
        case 'AddToCart':
            $response = $resolver->addToCart($variables);
            break;
        case 'getCart':
            $response = $resolver->getCart();
            break;
        default:
            $response = ['error' => 'Unknown operation'];
            break;
    }

    // Wrap response in GraphQL-compliant structure
    $output = [
        'data' => $response,
    ];

} catch (Exception $e) {
    // Return error message in GraphQL-compliant format
    $output = [
        'errors' => [
            ['message' => $e->getMessage()]
        ]
    ];
}

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($output);
