<?php

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0); // Exit early for preflight requests
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Debug: Log the incoming request for troubleshooting
// error_log("Query: " . $queryType);
// error_log("Operation: " . $operation);
// error_log("Variables: " . json_encode($variables));

// Parse GraphQL query to extract operation name and variables if not provided
if (empty($operation)) {
    if (strpos($queryType, 'mutation') !== false) {
        if (strpos($queryType, 'removeFromCart') !== false) {
            $operation = 'removeFromCart';
            // Extract itemId from query if not in variables
            if (empty($variables['itemId']) && preg_match('/removeFromCart\s*\(\s*itemId:\s*"([^"]+)"/', $queryType, $matches)) {
                $variables['itemId'] = $matches[1];
            }
        } elseif (strpos($queryType, 'updateCart') !== false) {
            $operation = 'updateCart';
            // Extract variables from query if not in variables
            if (empty($variables['itemId']) && preg_match('/updateCart\s*\(\s*itemId:\s*"([^"]+)"/', $queryType, $matches)) {
                $variables['itemId'] = $matches[1];
            }
            if (empty($variables['quantityChange']) && preg_match('/quantityChange:\s*(-?\d+)/', $queryType, $matches)) {
                $variables['quantityChange'] = (int)$matches[1];
            }
        } elseif (strpos($queryType, 'addToCart') !== false) {
            $operation = 'AddToCart';
            // Extract variables from query if not in variables
            if (empty($variables['productId']) && preg_match('/addToCart\s*\(\s*productId:\s*"([^"]+)"/', $queryType, $matches)) {
                $variables['productId'] = $matches[1];
            }
            if (empty($variables['quantity']) && preg_match('/quantity:\s*(\d+)/', $queryType, $matches)) {
                $variables['quantity'] = (int)$matches[1];
            }
        } elseif (strpos($queryType, 'placeOrder') !== false) {
            $operation = 'placeOrder';
        }
    } elseif (strpos($queryType, 'cart') !== false) {
        $operation = 'cart';
    }
}

// Debug: Final parsed values
// error_log("Final operation: " . $operation);
// error_log("Final variables: " . json_encode($variables));
// error_log("Query type: " . $queryType);

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
        case 'updateCart':
            $response = $resolver->updateCartItem($variables);
            if (!$response) {
                $response = ['error' => 'Update failed'];
            }
            break;
        case 'cart':
            $response = $resolver->getCart();
            break;
        case 'removeFromCart':
            $response = $resolver->removeFromCart($variables);
            break;
        case 'placeOrder':
            $response = $resolver->placeOrder();
            break;
        default:
            $response = ['error' => 'Unknown operation'];
            break;
    }

    // Wrap response in GraphQL-compliant structure
    if ($operation === 'cart') {
        $output = [
            'data' => [
                'cart' => $response
            ],
        ];
    } elseif ($operation === 'AddToCart') {
        $output = [
            'data' => [
                'addToCart' => $response
            ],
        ];
    } elseif ($operation === 'updateCart') {
        $output = [
            'data' => [
                'updateCart' => $response
            ],
        ];
    } elseif ($operation === 'removeFromCart') {
        $output = [
            'data' => [
                'removeFromCart' => $response
            ],
        ];
    } elseif ($operation === 'placeOrder') {
        $output = [
            'data' => [
                'placeOrder' => $response
            ],
        ];
    } else {
        $output = [
            'data' => $response,
        ];
    }

} catch (Exception $e) {
    // Return error message in GraphQL-compliant format
    $output = [
        'errors' => [
            ['message' => $e->getMessage()]
        ]
    ];
}

// Debug: Add debug info to output (uncomment for troubleshooting)
// $output['debug'] = [
//     'operation' => $operation,
//     'variables' => $variables,
//     'query' => $queryType
// ];

// Return the response as JSON
header('Content-Type: application/json');
echo json_encode($output);
