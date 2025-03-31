<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $response = new Response();
        
        // Get the Authorization header
        $header = $request->getHeaderLine('Authorization');
        $token = null;
        
        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            $token = $matches[1];
        }
        
        if (!$token) {
            $response->getBody()->write(json_encode(['error' => 'No token provided']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
        
        try {
            // Verify the token
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            
            // Add the decoded token to the request attributes
            $request = $request->withAttribute('user', $decoded);
            
            // Continue with the request
            return $handler->handle($request);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'Invalid token']));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }
} 