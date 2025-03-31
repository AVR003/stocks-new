<?php
// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Get the requested path
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove trailing slash
$path = rtrim($path, '/');

// If path is empty, serve the main HTML file
if ($path === '') {
    include __DIR__ . '/index.html';
    exit;
}

// Handle API routes
if (strpos($path, '/api/') === 0) {
    $apiFile = __DIR__ . $path . '.php';
    if (file_exists($apiFile)) {
        include $apiFile;
        exit;
    }
}

// Handle static files
$staticFile = __DIR__ . $path;
if (file_exists($staticFile)) {
    $mimeType = mime_content_type($staticFile);
    header('Content-Type: ' . $mimeType);
    readfile($staticFile);
    exit;
}

// If no route matches, return 404
http_response_code(404);
echo json_encode(['error' => 'Not Found']); 