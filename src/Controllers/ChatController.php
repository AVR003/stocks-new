<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use MongoDB\Collection;
use Respect\Validation\Validator as v;

class ChatController
{
    private Collection $users;
    private Collection $messages;

    public function __construct(Collection $users, Collection $messages)
    {
        $this->users = $users;
        $this->messages = $messages;
    }

    public function sendMessage(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        if (!v::stringType()->length(1, 1000)->validate($data['message'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid message format'
            ]));
            return $response->withStatus(400);
        }

        $message = [
            'user_id' => new \MongoDB\BSON\ObjectId($user->user_id),
            'message' => $data['message'],
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'type' => 'user'
        ];

        // Save user message
        $this->messages->insertOne($message);

        // Generate AI response
        $aiResponse = $this->generateAIResponse($data['message']);

        // Save AI response
        $aiMessage = [
            'user_id' => new \MongoDB\BSON\ObjectId($user->user_id),
            'message' => $aiResponse,
            'timestamp' => new \MongoDB\BSON\UTCDateTime(),
            'type' => 'ai'
        ];
        $this->messages->insertOne($aiMessage);

        $response->getBody()->write(json_encode([
            'message' => $aiResponse
        ]));
        return $response;
    }

    public function getHistory(Request $request, Response $response): Response
    {
        $user = $request->getAttribute('user');
        $limit = 50; // Default limit for chat history

        $messages = $this->messages->find(
            ['user_id' => new \MongoDB\BSON\ObjectId($user->user_id)],
            [
                'sort' => ['timestamp' => -1],
                'limit' => $limit
            ]
        );

        $history = [];
        foreach ($messages as $message) {
            $history[] = [
                'message' => $message->message,
                'type' => $message->type,
                'timestamp' => $message->timestamp->toDateTime()->format('Y-m-d H:i:s')
            ];
        }

        $response->getBody()->write(json_encode([
            'history' => array_reverse($history)
        ]));
        return $response;
    }

    private function generateAIResponse(string $message): string
    {
        // This is a simple response generation. In a real application,
        // you would integrate with a more sophisticated AI service
        $responses = [
            'hello' => 'Hi! How can I help you with your investment journey today?',
            'help' => 'I can help you with:\n- Stock research\n- Portfolio management\n- Market analysis\n- Investment strategies\nWhat would you like to know more about?',
            'portfolio' => 'I can help you manage your portfolio. Would you like to:\n- View your current holdings\n- Add new stocks\n- Analyze performance\n- Get recommendations',
            'market' => 'I can provide you with:\n- Real-time market data\n- Market trends\n- Sector analysis\n- Economic indicators\nWhat specific information are you looking for?',
            'default' => 'I understand you\'re interested in investing. Could you please provide more details about what you\'d like to know?'
        ];

        $message = strtolower(trim($message));
        
        foreach ($responses as $keyword => $response) {
            if (strpos($message, $keyword) !== false) {
                return $response;
            }
        }

        return $responses['default'];
    }
} 