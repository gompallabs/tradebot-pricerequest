<?php

namespace App\Domain\Source\Api;

use Symfony\Contracts\HttpClient\ResponseInterface;

interface Client
{
    /**
     * Reqyest for "last" price (last close for open/close markets).
     */
    public function getPrice(string $ticker): ResponseInterface;

    public function getHistoricalPrices(string $ticker, \DateTime $from, \DateTime $to = null, ?array $options = []): ResponseInterface;

    public function getRecentTrade(string $ticker, ?string $category = 'linear');
}
