<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use GuzzleHttp\Client;
use Respect\Validation\Validator as v;

class StockController
{
    private Client $client;
    private string $apiKey;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = $_ENV['6R71ZDBJ29KZ67QU'];
    }

    public function searchStocks(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams()['query'] ?? '';

        if (!v::stringType()->length(1, 10)->validate($query)) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid search query'
            ]));
            return $response->withStatus(400);
        }

        try {
            $searchResponse = $this->client->get("https://www.alphavantage.co/query?function=SYMBOL_SEARCH&keywords={$query}&apikey={$this->apiKey}");
            $searchData = json_decode($searchResponse->getBody(), true);

            $stocks = array_map(function($stock) {
                return [
                    'symbol' => $stock['1. symbol'],
                    'name' => $stock['2. name'],
                    'type' => $stock['3. type'],
                    'region' => $stock['4. region'],
                    'market_open' => $stock['5. marketOpen'],
                    'market_close' => $stock['6. marketClose'],
                    'timezone' => $stock['7. timezone'],
                    'currency' => $stock['8. currency'],
                    'match_score' => $stock['9. matchScore']
                ];
            }, $searchData['bestMatches'] ?? []);

            $response->getBody()->write(json_encode([
                'stocks' => $stocks
            ]));
            return $response;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to search stocks'
            ]));
            return $response->withStatus(500);
        }
    }

    public function getStockDetails(Request $request, Response $response, array $args): Response
    {
        $symbol = $args['symbol'];

        if (!v::stringType()->length(1, 10)->validate($symbol)) {
            $response->getBody()->write(json_encode([
                'error' => 'Invalid stock symbol'
            ]));
            return $response->withStatus(400);
        }

        try {
            // Get current quote
            $quoteResponse = $this->client->get("https://www.alphavantage.co/query?function=GLOBAL_QUOTE&symbol={$symbol}&apikey={$this->apiKey}");
            $quoteData = json_decode($quoteResponse->getBody(), true);

            // Get company overview
            $overviewResponse = $this->client->get("https://www.alphavantage.co/query?function=OVERVIEW&symbol={$symbol}&apikey={$this->apiKey}");
            $overviewData = json_decode($overviewResponse->getBody(), true);

            // Get daily time series
            $timeSeriesResponse = $this->client->get("https://www.alphavantage.co/query?function=TIME_SERIES_DAILY&symbol={$symbol}&apikey={$this->apiKey}");
            $timeSeriesData = json_decode($timeSeriesResponse->getBody(), true);

            $stockDetails = [
                'symbol' => $symbol,
                'quote' => [
                    'price' => $quoteData['Global Quote']['05. price'] ?? null,
                    'change' => $quoteData['Global Quote']['09. change'] ?? null,
                    'change_percent' => $quoteData['Global Quote']['10. change percent'] ?? null,
                    'volume' => $quoteData['Global Quote']['06. volume'] ?? null
                ],
                'overview' => [
                    'name' => $overviewData['Name'] ?? null,
                    'sector' => $overviewData['Sector'] ?? null,
                    'industry' => $overviewData['Industry'] ?? null,
                    'market_cap' => $overviewData['MarketCapitalization'] ?? null,
                    'pe_ratio' => $overviewData['PERatio'] ?? null,
                    'dividend_yield' => $overviewData['DividendYield'] ?? null,
                    'beta' => $overviewData['Beta'] ?? null
                ],
                'time_series' => array_slice($timeSeriesData['Time Series (Daily)'] ?? [], 0, 30)
            ];

            $response->getBody()->write(json_encode($stockDetails));
            return $response;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to fetch stock details'
            ]));
            return $response->withStatus(500);
        }
    }

    public function getSuggestions(Request $request, Response $response): Response
    {
        try {
            // Get popular stocks
            $popularResponse = $this->client->get("https://www.alphavantage.co/query?function=TOP_GAINERS_LOSERS&apikey={$this->apiKey}");
            $popularData = json_decode($popularResponse->getBody(), true);

            // Get most active stocks
            $activeResponse = $this->client->get("https://www.alphavantage.co/query?function=MOST_ACTIVE&apikey={$this->apiKey}");
            $activeData = json_decode($activeResponse->getBody(), true);

            $suggestions = [
                'popular' => array_slice($popularData['top_gainers'] ?? [], 0, 5),
                'active' => array_slice($activeData['Most Active'] ?? [], 0, 5)
            ];

            $response->getBody()->write(json_encode($suggestions));
            return $response;
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'error' => 'Failed to fetch stock suggestions'
            ]));
            return $response->withStatus(500);
        }
    }
} 