<?php

namespace App\Domain\Store\Adapter\RedisTimeSeries\Factory;

use App\Domain\Store\Adapter\RedisTimeSeries\TimeSeriesInterface;

interface RedisTimeSeriesFactoryInterface
{
    public function getClient(): TimeSeriesInterface;
}
