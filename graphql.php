<?php
// Start output buffering to prevent any accidental output
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Controller/GraphQL.php';

use App\Controller\GraphQL;

error_log("GraphQL endpoint called at " . date('Y-m-d H:i:s'));

// Clean any accidental output and send only the JSON response
ob_clean();
echo GraphQL::handle();
