<?php

declare(strict_types=1);

namespace App\Infra\Source\Api;

enum RouteName
{
    case instrument_price;
    case instrument_history_price;
    case recent_trade;
}
