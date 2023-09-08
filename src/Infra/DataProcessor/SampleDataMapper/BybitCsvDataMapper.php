<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor\SampleDataMapper;

use App\Domain\DataProcessor\SampleDataMapper;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;

class BybitCsvDataMapper implements SampleDataMapper
{
    public function mapToTs(array $sample): \SplFixedArray
    {
        $result = new \SplFixedArray(5);
        $size = (float) $sample['size'];
        $result[0] = (float) $sample['timestamp'];
        $result[1] = $size;
        $result[2] = (float) $sample['price'];
        $result[3] = $sample['side'] === 'Buy' ? $size : 0;
        $result[4] = $sample['side'] === 'Sell' ? $size : 0;

        return $result;
    }

    public function getTimeStampFieldName(): string
    {
        return 'timestamp';
    }

    public function supports(Source $source): bool
    {
        return $source->getExchange()->name === 'Bybit' && $source->getSourceType() === SourceType::File;
    }

    public function getColumns(): array
    {
        return [
            'timestamp',
            'size',
            'price',
            'buyVolume',
            'sellVolume',
        ];
    }
}
