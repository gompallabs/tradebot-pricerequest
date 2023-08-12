<?php

declare(strict_types=1);

namespace App\Infra\Source\Api;

use App\Domain\Source\Api\Client;
use App\Domain\Source\Api\ClientBuilder;
use App\Domain\Source\SourceType;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BybitApiClientBuilder implements ClientBuilder
{
    private array $credentials;
    private HttpClientInterface $byBitClient;
    private RouterInterface $router;

    public function __construct(
        array $credentials,
        HttpClientInterface $bybitClient,
        RouterInterface $router,
    ) {
        $this->credentials = $credentials;
        $this->byBitClient = $bybitClient;
        $this->router = $router;
    }

    public function getClientForSource(SourceType $type, string $name): Client
    {
        return new BybitApiClient(
            bybitClient: $this->byBitClient,
            router: $this->router,
            credentials: $this->credentials
        );
    }
}
