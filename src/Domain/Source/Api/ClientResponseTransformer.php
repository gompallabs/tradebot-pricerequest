<?php

namespace App\Domain\Source\Api;

use App\Infra\Source\Api\RouteName;
use Symfony\Contracts\HttpClient\ResponseInterface;

interface ClientResponseTransformer
{
    public function transform(ResponseInterface $response, RouteName $routeName): array|\ArrayIterator;
}
