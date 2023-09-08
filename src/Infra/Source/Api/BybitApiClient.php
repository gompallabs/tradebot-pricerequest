<?php

declare(strict_types=1);

namespace App\Infra\Source\Api;

use App\Domain\Source\Api\Client;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BybitApiClient implements Client
{
    private HttpClientInterface $bybitClient;
    private RouterInterface $router;
    private array $credentials;

    public function __construct(
        HttpClientInterface $bybitClient,
        RouterInterface $router,
        array $credentials
    ) {
        $this->bybitClient = $bybitClient;
        $this->router = $router;
        $this->credentials = $credentials;
    }

    public function getPrice(string $ticker, ?array $params = []): ResponseInterface
    {
        $params['symbol'] = $ticker;
        $params['category'] = array_key_exists('category', $params) ? $params['category'] : 'linear'; // linear is internal category for perpetual futures

        $url = $this->router->generate(name: 'instrument_price', parameters: $params);
        $options = $this->encryptGetRequest($url);

        return $this->bybitClient->request(
            method: 'GET',
            url: $url,
            options: $options
        );
    }

    public function getHistoricalPrices(string $ticker, \DateTime $from, \DateTime $to = null, ?array $options = []): ResponseInterface
    {
        $category = $options['category'] ?? 'linear'; // linear is internal category for perpetual futures

        $url = $this->router->generate('instrument_history_price', [
                'category' => $category,
                'symbol' => $ticker,
                'interval' => 1, // is the minimum of this api
                'start' => $from->getTimestamp() * 1000, // time in ms
                'end' => $to->getTimestamp() * 1000, // time in ms
                'limit' => 1000, // max expected results - limitation
            ]);
        $options = $this->encryptGetRequest($url);

        return $this->bybitClient->request(
            method: 'GET',
            url: $url,
            options: $options
        );
    }

    public function getRecentTrade(string $ticker, ?string $category = 'linear')
    {
        $url = $this->router->generate('recent_trade', [
                'category' => $category,
                'symbol' => $ticker,
            ]);

        $options = $this->encryptGetRequest($url);

        return $this->bybitClient->request(
            method: 'GET',
            url: $url,
            options: $options
        );
    }

    private function encryptGetRequest(string $url): array
    {
        $ts = time() * 1000;
        $apiKey = $this->credentials['ApiKey'];
        $requestParams = parse_url($url);
        $queryParams = $requestParams['query'];

        $signatureParams = $ts.$apiKey.'5000'.$queryParams;
        $signature = hash_hmac('sha256', $signatureParams, $this->credentials['Secret']);

        return [
            'headers' => [
                'X-BAPI-SIGN' => $signature,
                'X-BAPI-API-KEY' => $apiKey,
                'X-BAPI-TIMESTAMP' => $ts,
                'X-BAPI-RECV-WINDOW' => 5000,
            ],
        ];
    }
}
