<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$db = MongoDBConnection::getInstance()->getDatabase();
$portfolios = $db->portfolios;
$sessions = $db->sessions;

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$sessionId = $data['sessionId'] ?? '';

// Verify session
$session = $sessions->findOne([
    'session_id' => $sessionId,
    'expires_at' => ['$gt' => new MongoDB\BSON\UTCDateTime()]
]);

if (!$session) {
    echo json_encode(['success' => false, 'message' => 'Invalid session']);
    exit;
}

$userId = $session['user_id'];

switch ($action) {
    case 'get_suggestions':
        $portfolio = $portfolios->findOne(['user_id' => $userId]);
        
        if (!$portfolio) {
            echo json_encode(['success' => false, 'message' => 'No portfolio found']);
            exit;
        }
        
        // Get current holdings
        $currentStocks = $portfolio['stocks'] ?? [];
        $riskTolerance = $portfolio['risk_tolerance'] ?? 'medium';
        $investmentGoal = $portfolio['investment_goal'] ?? 'balanced';
        
        // Analyze portfolio
        $sectorDistribution = [];
        $totalValue = 0;
        
        foreach ($currentStocks as $stock) {
            $value = $stock['shares'] * $stock['avg_price'];
            $totalValue += $value;
            
            if (isset($sectorDistribution[$stock['sector']])) {
                $sectorDistribution[$stock['sector']] += $value;
            } else {
                $sectorDistribution[$stock['sector']] = $value;
            }
        }
        
        // Generate suggestions based on analysis
        $suggestions = generateSuggestions($sectorDistribution, $riskTolerance, $investmentGoal, $totalValue);
        
        // Save suggestions to database
        $db->stock_suggestions->insertOne([
            'user_id' => $userId,
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
            'suggestions' => $suggestions
        ]);
        
        echo json_encode(['success' => true, 'suggestions' => $suggestions]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function generateSuggestions($sectorDistribution, $riskTolerance, $investmentGoal, $totalValue) {
    // This is a simplified example - in a real app you'd connect to a stock API
    
    $suggestions = [];
    
    // Example sectors to consider
    $allSectors = [
        'Technology', 'Healthcare', 'Financial', 'Consumer Cyclical',
        'Communication Services', 'Industrial', 'Energy', 'Utilities',
        'Real Estate', 'Consumer Defensive', 'Materials'
    ];
    
    // Find underrepresented sectors
    $underrepresentedSectors = [];
    foreach ($allSectors as $sector) {
        $sectorPercent = isset($sectorDistribution[$sector]) ? 
            ($sectorDistribution[$sector] / $totalValue) * 100 : 0;
            
        if ($sectorPercent < 5) { // Less than 5% allocation
            $underrepresentedSectors[] = $sector;
        }
    }
    
    // Generate suggestions based on underrepresented sectors
    foreach ($underrepresentedSectors as $sector) {
        $suggestion = [
            'symbol' => '',
            'name' => '',
            'reason' => "Diversify into $sector sector",
            'expected_return' => 0,
            'risk_level' => 'medium'
        ];
        
        // Example stocks for each sector
        switch ($sector) {
            case 'Technology':
                $suggestion['symbol'] = 'AAPL';
                $suggestion['name'] = 'Apple Inc.';
                $suggestion['expected_return'] = $riskTolerance === 'high' ? 12 : 8;
                $suggestion['risk_level'] = $riskTolerance === 'high' ? 'high' : 'medium';
                break;
                
            case 'Healthcare':
                $suggestion['symbol'] = 'JNJ';
                $suggestion['name'] = 'Johnson & Johnson';
                $suggestion['expected_return'] = $riskTolerance === 'high' ? 9 : 6;
                $suggestion['risk_level'] = 'low';
                break;
                
            case 'Financial':
                $suggestion['symbol'] = 'JPM';
                $suggestion['name'] = 'JPMorgan Chase & Co.';
                $suggestion['expected_return'] = $riskTolerance === 'high' ? 10 : 7;
                $suggestion['risk_level'] = 'medium';
                break;
                
            // Add more sectors as needed...
                
            default:
                $suggestion['symbol'] = 'SPY';
                $suggestion['name'] = 'SPDR S&P 500 ETF Trust';
                $suggestion['expected_return'] = $riskTolerance === 'high' ? 10 : 7;
                $suggestion['risk_level'] = 'medium';
                $suggestion['reason'] = "Broad market exposure";
                break;
        }
        
        // Adjust based on investment goal
        if ($investmentGoal === 'income') {
            $suggestion['reason'] .= " (dividend focus)";
            $suggestion['expected_return'] -= 2; // Lower expected growth for dividend stocks
        } elseif ($investmentGoal === 'growth') {
            $suggestion['reason'] .= " (growth focus)";
            $suggestion['expected_return'] += 2;
            $suggestion['risk_level'] = 'high';
        }
        
        $suggestions[] = $suggestion;
    }
    
    // If portfolio is empty, suggest some starter stocks
    if (empty($sectorDistribution)) {
        $suggestions = [
            [
                'symbol' => 'VTI',
                'name' => 'Vanguard Total Stock Market ETF',
                'reason' => 'Good starting point for broad market exposure',
                'expected_return' => $riskTolerance === 'high' ? 9 : 7,
                'risk_level' => 'medium'
            ],
            [
                'symbol' => 'BND',
                'name' => 'Vanguard Total Bond Market ETF',
                'reason' => 'Provides stability to your portfolio',
                'expected_return' => 3,
                'risk_level' => 'low'
            ]
        ];
    }
    
    return $suggestions;
}
?>