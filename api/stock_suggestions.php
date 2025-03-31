<?php
require_once __DIR__ . '/../config/db.php';

// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . $_ENV['ALLOWED_ORIGINS']);
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get query parameter
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (empty($query)) {
    echo json_encode([
        'success' => false,
        'message' => 'Query parameter is required'
    ]);
    exit;
}

// Function to fetch stock suggestions
function fetchStockSuggestions($query) {
    $apiKey = $_ENV['6R71ZDBJ29KZ67QU'];
    $url = "https://www.alphavantage.co/query?function=SYMBOL_SEARCH&keywords={$query}&apikey={$apiKey}";
    
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
        return [];
    }
    
    $data = json_decode($response, true);
    
    if (!isset($data['bestMatches'])) {
        return [];
    }
    
    // Format the suggestions
    $suggestions = array_map(function($match) {
        return [
            'symbol' => $match['1. symbol'],
            'name' => $match['2. name'],
            'type' => $match['3. type'],
            'region' => $match['4. region'],
            'marketOpen' => $match['5. marketOpen'],
            'marketClose' => $match['6. marketClose'],
            'timezone' => $match['7. timezone'],
            'currency' => $match['8. currency']
        ];
    }, $data['bestMatches']);
    
    return array_slice($suggestions, 0, 5); // Limit to 5 suggestions
}

// Handle the request
try {
    $suggestions = fetchStockSuggestions($query);
    echo json_encode([
        'success' => true,
        'data' => $suggestions
    ]);
} catch (Exception $e) {
    error_log("Stock Suggestions Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch stock suggestions'
    ]);
} 