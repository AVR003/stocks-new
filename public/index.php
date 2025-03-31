<?php

use DI\Container;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create Container
$container = new Container();

// Set up dependencies
$container->set('db', function () {
    $client = new MongoDB\Client($_ENV['MONGODB_URI']);
    return $client->selectDatabase($_ENV['DB_NAME']);
});

// Create App
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add Error Middleware
$app->addErrorMiddleware(true, true, true);

// Add CORS middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

// Routes
require __DIR__ . '/../src/routes.php';

$app->run(); 