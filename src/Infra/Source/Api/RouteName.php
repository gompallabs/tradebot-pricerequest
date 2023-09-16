<?php

declare(strict_types=1);

namespace App\Infra\Source\Api;

enum RouteName: string
{
    case instrument_price = 'instrument_price';
    case instrument_history_price = 'instrument_history_price';
    case recent_trade = 'recent_trade';
}
