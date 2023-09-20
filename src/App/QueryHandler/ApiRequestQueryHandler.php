<?php

declare(strict_types=1);

namespace App\App\QueryHandler;

use App\App\Event\ApiRequestEvent;
use App\App\Query\ApiRequestQuery;
use App\Domain\Source\Api\ClientBuilder;
use App\Domain\Source\Api\ClientResponseTransformer;
use App\Domain\Source\Api\ClientResponseValidator;
use App\Infra\Source\Api\RouteName;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class ApiRequestQueryHandler
{
    private ClientBuilder $clientBuilder;
    private ClientResponseTransformer $responseTransformer;
    private ClientResponseValidator $validator;
    private MessageBusInterface $eventBus;

    public function __construct(
        ClientBuilder $clientBuilder,
        ClientResponseTransformer $responseTransformer,
        ClientResponseValidator $validator,
        MessageBusInterface $eventBus
    ) {
        $this->clientBuilder = $clientBuilder;
        $this->responseTransformer = $responseTransformer;
        $this->validator = $validator;
        $this->eventBus = $eventBus;
    }

    public function __invoke(ApiRequestQuery $query): array
    {
        $client = $this->clientBuilder->getClientForSource(
            type: $query->getSource()->getSourceType(),
            name: $query->getSource()->getExchange()->name
        );

        $response = $client->getRecentTrade(
            ticker: $query->getCoin()->getTicker(),
            category: $query->getCoin()->getCategory()
        );

        $this->validator->validate($response);

        $data = (array) $this->responseTransformer->transform($response, RouteName::recent_trade);

        $this->eventBus->dispatch(new ApiRequestEvent(
            source: $query->getSource(),
            coin: $query->getCoin(),
            data: $data
        ));

        dump($data);

        return $data;
    }
}
