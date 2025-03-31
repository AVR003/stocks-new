<?php
// Set CORS headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost:8000');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/db.php';

// Connect to MongoDB
try {
    $client = new MongoDB\Client($_ENV['MONGODB_URI']);
    $db = $client->selectDatabase($_ENV['MONGODB_DB']);
    $sessions = $db->sessions;
    $portfolios = $db->portfolios;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// Get user session
$sessionId = $_COOKIE['session_id'] ?? null;
if (!$sessionId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$session = $sessions->findOne(['session_id' => $sessionId]);
if (!$session) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid session']);
    exit;
}

$username = $session['username'];

// Handle GET request for portfolio data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $portfolio = $portfolios->findOne(['username' => $username]);
    
    if (!$portfolio) {
        // Create default portfolio if none exists
        $portfolio = [
            'username' => $username,
            'stocks' => [],
            'total_value' => 0,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ];
        $portfolios->insertOne($portfolio);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $portfolio
    ]);
    exit;
}

// Handle POST request for portfolio updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'add_stock':
            $symbol = $data['symbol'] ?? '';
            $shares = intval($data['shares'] ?? 0);
            $price = floatval($data['price'] ?? 0);
            
            if (empty($symbol) || $shares <= 0 || $price <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Invalid stock data']);
                exit;
            }
            
            $portfolio = $portfolios->findOne(['username' => $username]);
            
            if (!$portfolio) {
                $portfolio = [
                    'username' => $username,
                    'stocks' => [],
                    'total_value' => 0,
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ];
            }
            
            // Update or add stock
            $stockExists = false;
            foreach ($portfolio['stocks'] as &$stock) {
                if ($stock['symbol'] === $symbol) {
                    $stock['shares'] += $shares;
                    $stock['average_price'] = (($stock['average_price'] * ($stock['shares'] - $shares)) + ($price * $shares)) / $stock['shares'];
                    $stockExists = true;
                    break;
                }
            }
            
            if (!$stockExists) {
                $portfolio['stocks'][] = [
                    'symbol' => $symbol,
                    'shares' => $shares,
                    'average_price' => $price,
                    'added_at' => new MongoDB\BSON\UTCDateTime()
                ];
            }
            
            // Update total value
            $portfolio['total_value'] = array_reduce($portfolio['stocks'], function($total, $stock) {
                return $total + ($stock['shares'] * $stock['average_price']);
            }, 0);
            
            $portfolio['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            if (isset($portfolio['_id'])) {
                $portfolios->updateOne(
                    ['_id' => $portfolio['_id']],
                    ['$set' => $portfolio]
                );
            } else {
                $portfolios->insertOne($portfolio);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Stock added successfully',
                'data' => $portfolio
            ]);
            break;
            
        case 'remove_stock':
            $symbol = $data['symbol'] ?? '';
            
            if (empty($symbol)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Symbol is required']);
                exit;
            }
            
            $portfolio = $portfolios->findOne(['username' => $username]);
            
            if (!$portfolio) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Portfolio not found']);
                exit;
            }
            
            // Remove stock
            $portfolio['stocks'] = array_filter($portfolio['stocks'], function($stock) use ($symbol) {
                return $stock['symbol'] !== $symbol;
            });
            
            // Update total value
            $portfolio['total_value'] = array_reduce($portfolio['stocks'], function($total, $stock) {
                return $total + ($stock['shares'] * $stock['average_price']);
            }, 0);
            
            $portfolio['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $portfolios->updateOne(
                ['_id' => $portfolio['_id']],
                ['$set' => $portfolio]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Stock removed successfully',
                'data' => $portfolio
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>