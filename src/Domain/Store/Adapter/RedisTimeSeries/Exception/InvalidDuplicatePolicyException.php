<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries\Exception;

final class InvalidDuplicatePolicyException extends \InvalidArgumentException
{
}
