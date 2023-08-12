<?php

namespace App\Domain\Source\Api;

use Symfony\Contracts\HttpClient\ResponseInterface;

interface ClientResponseValidator
{
    public function validate(ResponseInterface $response): string|null;
}
