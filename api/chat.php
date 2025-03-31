<?php
header('Content-Type: application/json');
require_once '../config/db.php';

$db = MongoDBConnection::getInstance()->getDatabase();
$sessions = $db->sessions;
$chatHistory = $db->chat_history;

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$sessionId = $data['sessionId'] ?? '';
$message = $data['message'] ?? '';

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
    case 'send_message':
        if (empty($message)) {
            echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
            exit;
        }
        
        // Save message to chat history
        $result = $chatHistory->insertOne([
            'user_id' => $userId,
            'message' => $message,
            'timestamp' => new MongoDB\BSON\UTCDateTime(),
            'type' => 'user'
        ]);
        
        if ($result->getInsertedCount() === 1) {
            // Here you would typically process the message and generate a response
            // For now, we'll just echo back a simple response
            echo json_encode([
                'success' => true,
                'message' => 'Message received',
                'response' => 'I received your message: ' . htmlspecialchars($message)
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save message']);
        }
        break;
        
    case 'get_history':
        $history = $chatHistory->find(
            ['user_id' => $userId],
            [
                'sort' => ['timestamp' => 1],
                'limit' => 50
            ]
        );
        
        $messages = [];
        foreach ($history as $msg) {
            $messages[] = [
                'message' => $msg['message'],
                'type' => $msg['type'],
                'timestamp' => $msg['timestamp']->toDateTime()->format('Y-m-d H:i:s')
            ];
        }
        
        echo json_encode(['success' => true, 'history' => $messages]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>
