<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MongoDB\Collection;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Respect\Validation\Validator as v;

class AuthController
{
    private Collection $users;

    public function __construct(Collection $users)
    {
        $this->users = $users;
    }

    public function register(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Validate input
        if (!v::email()->validate($data['email']) || 
            !v::length(6, 20)->validate($data['password'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid email or password format'
            ]));
            return $response->withStatus(400);
        }

        // Check if user exists
        if ($this->users->findOne(['email' => $data['email']])) {
            $response->getBody()->write(json_encode([
                'error' => 'Email already registered'
            ]));
            return $response->withStatus(409);
        }

        // Create user
        $user = [
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'created_at' => new \MongoDB\BSON\UTCDateTime(),
            'portfolio' => [],
            'settings' => [
                'risk_level' => 'moderate',
                'investment_goal' => 'growth'
            ]
        ];

        $this->users->insertOne($user);

        $response->getBody()->write(json_encode([
            'message' => 'User registered successfully'
        ]));
        return $response->withStatus(201);
    }

    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        
        // Find user
        $user = $this->users->findOne(['email' => $data['email']]);
        
        if (!$user || !password_verify($data['password'], $user->password)) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid credentials'
            ]));
            return $response->withStatus(401);
        }

        // Generate JWT token
        $token = JWT::encode([
            'user_id' => (string) $user->_id,
            'email' => $user->email,
            'exp' => time() + (60 * 60 * 24) // 24 hours
        ], $_ENV['JWT_SECRET'], 'HS256');

        $response->getBody()->write(json_encode([
            'token' => $token,
            'user' => [
                'email' => $user->email,
                'settings' => $user->settings
            ]
        ]));
        return $response;
    }

    public function logout(Request $request, Response $response): Response
    {
        // In a stateless JWT setup, we don't need to do anything server-side
        $response->getBody()->write(json_encode([
            'message' => 'Logged out successfully'
        ]));
        return $response;
    }
} 