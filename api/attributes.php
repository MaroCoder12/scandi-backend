<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Configure CORS
$allowedOrigin = $_ENV['ALLOWED_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, apollo-require-preflight, X-Requested-With');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Product.php';

try {
    error_log("Attributes API called for product: " . ($_GET['id'] ?? 'none'));
    $productId = $_GET['id'] ?? null;

    if (!$productId) {
        echo json_encode(['error' => 'Product ID is required']);
        exit;
    }

    $database = new Database();
    $pdo = $database->getConnection();
    $product = new Product($pdo);

    $attributes = $product->getAttributes($productId);

    echo json_encode([
        'success' => true,
        'data' => $attributes
    ]);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>
