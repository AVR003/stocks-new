<?php
require_once __DIR__ . '/../config/db.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $_ENV['ALLOWED_ORIGINS']);
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$symbol = isset($input['symbol']) ? trim(strtoupper($input['symbol'])) : '';

if (empty($symbol)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Stock symbol is required'
    ]);
    exit;
}

// Function to fetch stock data
function fetchStockData($symbol) {
    $apiKey = $_ENV['6R71ZDBJ29KZ67QU'];
    $url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol={$symbol}&apikey={$apiKey}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FAILONERROR, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("API Request Error: " . $error);
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['Global Quote'])) {
        return null;
    }
    
    $quote = $data['Global Quote'];
    
    return [
        'symbol' => $symbol,
        'price' => $quote['05. price'] ?? 'N/A',
        'change' => $quote['09. change'] ?? 'N/A',
        'changePercent' => $quote['10. change percent'] ?? 'N/A',
        'dayHigh' => $quote['03. high'] ?? 'N/A',
        'dayLow' => $quote['04. low'] ?? 'N/A',
        'volume' => $quote['06. volume'] ?? 'N/A',
        'lastUpdated' => $quote['07. latest trading day'] ?? 'N/A'
    ];
}

// Handle the request
try {
    $stockData = fetchStockData($symbol);
    
    if (!$stockData) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Stock data not found'
        ]);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stockData
    ]);
} catch (Exception $e) {
    error_log("Stock Search Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch stock data'
    ]);
}