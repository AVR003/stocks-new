<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use GuzzleHttp\Client;

class MarketController
{
    private Client $client;
    private string $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = $_ENV['6R71ZDBJ29KZ67QU'];
    }

    public function getIndices(Request $request, Response $response): Response
    {
        try {
            // Fetch S&P 500 data
            $sp500Response = $this->client->get("https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=^GSPC&apikey={$this->apiKey}");
            $sp500Data = json_decode($sp500Response->getBody(), true);

            // Fetch NASDAQ data
            $nasdaqResponse = $this->client->get("https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=^IXIC&apikey={$this->apiKey}");
            $nasdaqData = json_decode($nasdaqResponse->getBody(), true);

            // Fetch Dow Jones data
            $dowResponse = $this->client->get("https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol=^DJI&apikey={$this->apiKey}");
            $dowData = json_decode($dowResponse->getBody(), true);

            $indices = [
                'sp500' => [
                    'value' => $sp500Data['Global Quote']['05. price'] ?? null,
                    'change' => $sp500Data['Global Quote']['09. change'] ?? null,
                    'change_percent' => $sp500Data['Global Quote']['10. change percent'] ?? null
                ],
                'nasdaq' => [
                    'value' => $nasdaqData['Global Quote']['05. price'] ?? null,
                    'change' => $nasdaqData['Global Quote']['09. change'] ?? null,
                    'change_percent' => $nasdaqData['Global Quote']['10. change percent'] ?? null
                ],
                'dow' => [
                    'value' => $dowData['Global Quote']['05. price'] ?? null,
                    'change' => $dowData['Global Quote']['09. change'] ?? null,
                    'change_percent' => $dowData['Global Quote']['10. change percent'] ?? null
                ]
            ];

            $response->getBody()->write(json_encode($indices));
            return $response;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to fetch market indices'
            ]));
            return $response->withStatus(500);
        }
    }

    public function getTrendingStocks(Request $request, Response $response): Response
    {
        try {
            // Fetch top gainers
            $gainersResponse = $this->client->get("https://www.alphavantage.co/query?function=TOP_GAINERS_LOSERS&apikey={$this->apiKey}");
            $gainersData = json_decode($gainersResponse->getBody(), true);

            // Fetch top losers
            $losersResponse = $this->client->get("https://www.alphavantage.co/query?function=TOP_GAINERS_LOSERS&apikey={$this->apiKey}");
            $losersData = json_decode($losersResponse->getBody(), true);

            $trending = [
                'gainers' => array_slice($gainersData['top_gainers'] ?? [], 0, 5),
                'losers' => array_slice($losersData['top_losers'] ?? [], 0, 5)
            ];

            $response->getBody()->write(json_encode($trending));
            return $response;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to fetch trending stocks'
            ]));
            return $response->withStatus(500);
        }
    }
} 