<?php
// Start output buffering to prevent any accidental output
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Configure CORS
$allowedOrigin = $_ENV['ALLOWED_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $allowedOrigin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, apollo-require-preflight, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/src/Controller/GraphQL.php';

use App\Controller\GraphQL;

error_log("GraphQL endpoint called at " . date('Y-m-d H:i:s'));

// Clean any accidental output and send only the JSON response
ob_clean();
echo GraphQL::handle();
