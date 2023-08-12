<?php

declare(strict_types=1);

namespace App\Infra\Source\Api;

use App\Domain\Source\Api\ClientResponseValidator;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertNotNull;

class BybitApiReponseValidator implements ClientResponseValidator
{
    public function validate(ResponseInterface $response): string|null
    {
        $content = json_decode($response->getContent(), true);
        assertNotNull($content);
        assertArrayHasKey('retCode', $content);

        $error = null;
        $msg = '';
        if ((int) $content['retCode'] > 0) {
            if (is_array($content)) {
                $msg = array_key_exists('retMsg', $content) ? $content['retMsg'] : '';
                $content = $content['retCode'];
            }
            $error = BybitApiError::getErrorMessage($content).PHP_EOL.$msg;
        }

        return $error;
    }
}
