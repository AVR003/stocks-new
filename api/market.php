<?php
// Set CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8000');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/db.php';

function fetchMarketData() {
    $apiKey = $_ENV['6R71ZDBJ29KZ67QU'];
    $symbols = ['^GSPC', '^IXIC', '^DJI']; // S&P 500, NASDAQ, DOW
    
    $marketData = [];
    foreach ($symbols as $symbol) {
        $url = "https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol={$symbol}&apikey={$apiKey}";
        $response = file_get_contents($url);
        
        if ($response === false) {
            continue;
        }
        
        $data = json_decode($response, true);
        if (isset($data['Global Quote'])) {
            $quote = $data['Global Quote'];
            $marketData[$symbol] = [
                'price' => floatval($quote['05. price']),
                'change' => floatval($quote['09. change']),
                'change_percent' => floatval($quote['10. change percent']),
                'volume' => intval($quote['06. volume']),
                'last_updated' => $quote['07. latest trading day']
            ];
        }
    }
    
    return $marketData;
}

try {
    $marketData = fetchMarketData();
    echo json_encode([
        'success' => true,
        'data' => $marketData
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch market data'
    ]);
}
?> 