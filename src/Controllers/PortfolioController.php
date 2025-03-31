<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MongoDB\Collection;
use Respect\Validation\Validator as v;

class PortfolioController
{
    private Collection $users;

    public function __construct(Collection $users)
    {
        $this->users = $users;
    }

    public function getPortfolio(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        
        $userData = $this->users->findOne(['_id' => new \MongoDB\BSON\ObjectId($user->user_id)]);
        
        if (!$userData) {
            $response->getBody()->write(json_encode([
                'error' => 'User not found'
            ]));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'portfolio' => $userData->portfolio,
            'settings' => $userData->settings
        ]));
        return $response;
    }

    public function addStock(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        // Validate input
        if (!v::stringType()->length(1, 10)->validate($data['symbol']) ||
            !v::numeric()->positive()->validate($data['shares'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid stock data'
            ]));
            return $response->withStatus(400);
        }

        $stock = [
            'symbol' => strtoupper($data['symbol']),
            'shares' => (float) $data['shares'],
            'purchase_price' => (float) $data['purchase_price'],
            'purchase_date' => new \MongoDB\BSON\UTCDateTime(),
            'notes' => $data['notes'] ?? ''
        ];

        $result = $this->users->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($user->user_id)],
            ['$push' => ['portfolio' => $stock]]
        );

        if ($result->getModifiedCount() === 0) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to add stock'
            ]));
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            'message' => 'Stock added successfully',
            'stock' => $stock
        ]));
        return $response->withStatus(201);
    }

    public function removeStock(Request $request, Response $response, array $args): Response
    {
        $user = $request->getAttribute('user');
        $symbol = $args['symbol'];

        $result = $this->users->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($user->user_id)],
            ['$pull' => ['portfolio' => ['symbol' => strtoupper($symbol)]]]
        );

        if ($result->getModifiedCount() === 0) {
            $response->getBody()->write(json_encode([
                'error' => 'Stock not found in portfolio'
            ]));
            return $response->withStatus(404);
        }

        $response->getBody()->write(json_encode([
            'message' => 'Stock removed successfully'
        ]));
        return $response;
    }

    public function updateSettings(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        // Validate settings
        $validRiskLevels = ['conservative', 'moderate', 'aggressive'];
        $validGoals = ['income', 'growth', 'balanced'];

        if (!in_array($data['risk_level'], $validRiskLevels) ||
            !in_array($data['investment_goal'], $validGoals)) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid settings'
            ]));
            return $response->withStatus(400);
        }

        $settings = [
            'risk_level' => $data['risk_level'],
            'investment_goal' => $data['investment_goal']
        ];

        $result = $this->users->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($user->user_id)],
            ['$set' => ['settings' => $settings]]
        );

        if ($result->getModifiedCount() === 0) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to update settings'
            ]));
            return $response->withStatus(500);
        }

        $response->getBody()->write(json_encode([
            'message' => 'Settings updated successfully',
            'settings' => $settings
        ]));
        return $response;
    }
} 