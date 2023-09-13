<?php

declare(strict_types=1);

namespace App\Infra\Store\Adapter\RedisTimeSeries\Factory;

use App\Domain\Store\Adapter\RedisTimeSeries\Factory\RedisTimeSeriesFactoryInterface;
use App\Domain\Store\Adapter\RedisTimeSeries\TimeSeriesInterface;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\ConnectionParams;
use App\Infra\Store\Adapter\RedisTimeSeries\TimeSeries;

final class RedisTimeSeriesFactory implements RedisTimeSeriesFactoryInterface
{
    private string $host;
    private int $port;

    public function __construct(string $host, string $port)
    {
        $this->host = $host;
        $this->port = (int) $port;
    }

    public function getClient(): TimeSeriesInterface
    {
        return new TimeSeries(
            new \Redis(),
            new ConnectionParams(
                host: $this->host,
                port: $this->port,
            )
        );
    }
}
