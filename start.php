<?php
// Load environment variables
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Set CORS headers
header('Access-Control-Allow-Origin: http://localhost:8000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Allow-Credentials: true');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Set error reporting based on environment
if ($_ENV['DEBUG_MODE'] === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Basic routing
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$path = str_replace($basePath, '', $requestUri);
$path = strtok($path, '?');

// Route to appropriate handler
switch ($path) {
    case '/':
        include 'index.html';
        break;
        
    case '/api/stock_search':
        include 'api/stock_search.php';
        break;
        
    case '/api/stock_suggestions':
        include 'api/stock_suggestions.php';
        break;
        
    case '/api/market':
        include 'api/market.php';
        break;
        
    case '/api/auth':
        include 'api/auth.php';
        break;
        
    case '/api/portfolio':
        include 'api/portfolio.php';
        break;
        
    case '/api/chat':
        include 'api/chat.php';
        break;
        
    case '/api/suggestions':
        include 'api/suggestions.php';
        break;
        
    case '/api/activity':
        include 'api/activity.php';
        break;
        
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Not Found']);
        break;
} 