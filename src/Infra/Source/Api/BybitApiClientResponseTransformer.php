<?php

declare(strict_types=1);

namespace App\Infra\Source\Api;

use App\Domain\Source\Api\ClientResponseTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Contracts\HttpClient\ResponseInterface;

class BybitApiClientResponseTransformer implements ClientResponseTransformer
{
    public function transform(ResponseInterface $response, RouteName $routeName): array|\ArrayIterator
    {
        $payload = json_decode($response->getContent(), true);

        return match ($routeName->name) {
            'instrument_price' => $this->parsePriceResponse($payload),
            'instrument_history_price' => $this->parseHistoryResponse($payload),
            'recent_trade' => $this->parseRecentTradeResponse($payload)
        };
    }

    private function parsePriceResponse(array $payload): array
    {
        return [
            'time' => (int) $payload['time'],
            'price' => (float) $payload['result']['list'][0]['lastPrice'],
        ];
    }

    private function parseHistoryResponse(array $payload): array
    {
        $list = $payload['result']['list'];
        $prices = new ArrayCollection($list);
        $pricesCollection = new ArrayCollection();

        foreach ($prices->getIterator() as $price) {
            $result = [
                'time' => $price[0],
                'open' => $price[1],
                'high' => $price[2],
                'low' => $price[3],
                'close' => $price[4],
                'volume' => $price[5],
            ];
            $pricesCollection->add($result);
        }

        return $pricesCollection->toArray();
    }

    /**
     * array:7 [
     *      "execId" => "2a325193-70b6-50c3-ac89-bd54af9e1ca4"
     *      "symbol" => "BTCUSDT"
     *      "price" => "25821.20"
     *      "size" => "0.027"
     *      "side" => "Sell"
     *      "time" => "1692734861598"
     *      "isBlockTrade" => false
     * ].
     */
    private function parseRecentTradeResponse(array $payload): \ArrayIterator
    {
        $list = $payload['result']['list'];
        $prices = new ArrayCollection($list);
        $pricesCollection = new ArrayCollection();
        foreach ($prices->getIterator() as $trade) {
            $result = [
                'price' => $trade['price'],
                'size' => $trade['size'],
                'side' => $trade['side'],
                'timestamp' => $trade['time'],
            ];
            $pricesCollection->add($result);
        }

        return $pricesCollection->getIterator();
    }
}
