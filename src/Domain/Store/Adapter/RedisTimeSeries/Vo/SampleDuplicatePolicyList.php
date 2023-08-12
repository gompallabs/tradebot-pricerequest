<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries\Vo;

enum SampleDuplicatePolicyList: string
{
    case BLOCK = 'BLOCK';
    case FIRST = 'FIRST';
    case LAST = 'LAST';
    case MIN = 'MIN';
    case MAX = 'MAX';
    case SUM = 'SUM';
}
