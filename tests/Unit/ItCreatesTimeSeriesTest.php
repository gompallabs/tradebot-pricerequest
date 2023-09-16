<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\Store\Adapter\RedisTimeSeries\TimeSeriesInterface;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\ConnectionParams;
use App\Infra\Store\Adapter\RedisTimeSeries\TimeSeries;
use PHPUnit\Framework\TestCase;

class ItCreatesTimeSeriesTest extends TestCase
{
    public function testItInstanciates()
    {
        $connectionParams = new ConnectionParams(
            getenv('REDIS_HOST'),
            (int) getenv('REDIS_PORT')
        );
        $timeSeries = new TimeSeries(new \Redis(), $connectionParams);
        self::assertInstanceOf(TimeSeriesInterface::class, $timeSeries);
        $value = uniqid(random_bytes(12));
        $timeSeries->set('test', $value, 1);
        self::assertEquals($value, $stored = $timeSeries->get('test'));
    }
}
