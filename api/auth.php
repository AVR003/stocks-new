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

try {
    $client = new MongoDB\Client(getenv('MONGODB_URI'));
    $db = $client->selectDatabase(getenv('MONGODB_DB'));
    $users = $db->users;
    $sessions = $db->sessions;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check session
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET['action'] === 'check_session') {
    $sessionId = $_COOKIE['session_id'] ?? null;

    if (!$sessionId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'logged_in' => false, 'error' => 'Session expired']);
        exit;
    }

    $session = $sessions->findOne(['session_id' => $sessionId]);

    if ($session) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'username' => $session['username']
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['success' => false, 'logged_in' => false, 'error' => 'Invalid session']);
    }
    exit;
}

// Handle login, register, and logout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'login':
            $username = trim($data['username'] ?? '');
            $password = trim($data['password'] ?? '');

            if (!$username || !$password) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Username and password are required']);
                exit;
            }

            $user = $users->findOne(['username' => $username]);

            if ($user && password_verify($password, $user['password'])) {
                $sessionId = bin2hex(random_bytes(32));
                $sessions->insertOne([
                    'session_id' => $sessionId,
                    'username' => $username,
                    'created_at' => new MongoDB\BSON\UTCDateTime()
                ]);

                setcookie('session_id', $sessionId, [
                    'expires' => time() + (86400 * 30),
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Login successful',
                    'username' => $username
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
            }
            break;

        case 'register':
            $username = trim($data['username'] ?? '');
            $password = trim($data['password'] ?? '');

            if (!$username || !$password) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Username and password are required']);
                exit;
            }

            $existingUser = $users->findOne(['username' => $username]);

            if ($existingUser) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Username already exists']);
                exit;
            }

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $users->insertOne([
                'username' => $username,
                'password' => (string) $hashedPassword,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ]);

            echo json_encode(['success' => true, 'message' => 'Registration successful']);
            break;

        case 'logout':
            $sessionId = $_COOKIE['session_id'] ?? null;

            if ($sessionId) {
                $sessions->deleteOne(['session_id' => $sessionId]);
                setcookie('session_id', '', [
                    'expires' => time() - 3600,
                    'path' => '/',
                    'secure' => true,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }

            echo json_encode(['success' => true, 'message' => 'Logout successful']);
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
