<?php

use App\Controllers\AuthController;
use App\Controllers\PortfolioController;
use App\Controllers\MarketController;
use App\Controllers\StockController;
use App\Controllers\ChatController;
use App\Middleware\AuthMiddleware;

// Auth routes
$app->post('/api/auth/register', [AuthController::class, 'register']);
$app->post('/api/auth/login', [AuthController::class, 'login']);
$app->post('/api/auth/logout', [AuthController::class, 'logout'])->add(AuthMiddleware::class);

// Portfolio routes
$app->get('/api/portfolio', [PortfolioController::class, 'getPortfolio'])->add(AuthMiddleware::class);
$app->post('/api/portfolio/stocks', [PortfolioController::class, 'addStock'])->add(AuthMiddleware::class);
$app->delete('/api/portfolio/stocks/{symbol}', [PortfolioController::class, 'removeStock'])->add(AuthMiddleware::class);
$app->put('/api/portfolio/settings', [PortfolioController::class, 'updateSettings'])->add(AuthMiddleware::class);

// Market routes
$app->get('/api/market/indices', [MarketController::class, 'getIndices']);
$app->get('/api/market/trending', [MarketController::class, 'getTrendingStocks']);

// Stock routes
$app->get('/api/stocks/search', [StockController::class, 'searchStocks']);
$app->get('/api/stocks/suggestions', [StockController::class, 'getSuggestions']);
$app->get('/api/stocks/{symbol}', [StockController::class, 'getStockDetails']);

// Chat routes
$app->post('/api/chat/message', [ChatController::class, 'sendMessage'])->add(AuthMiddleware::class);
$app->get('/api/chat/history', [ChatController::class, 'getHistory'])->add(AuthMiddleware::class); 