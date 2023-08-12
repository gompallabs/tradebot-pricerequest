<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries;

/**
 * Adapter for simplified use of PhpRedis.
 */
interface ClientInterface
{
    public function set(string $key, array|string $value, int|float|null $expiration);

    public function get(string $key);

    public function executeCommand(array $params);

    public function unlink(string $key);
}
