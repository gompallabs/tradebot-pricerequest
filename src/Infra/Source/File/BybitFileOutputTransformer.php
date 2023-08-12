<?php

declare(strict_types=1);

namespace App\Infra\Source\File;

use App\Domain\Source\File\FileOutputTransformerInterface;

final class BybitFileOutputTransformer implements FileOutputTransformerInterface
{
    public static function transform(\IteratorAggregate $payload): array
    {
        $tsData = [];
        foreach ($payload as $raw) {
            $tsData[] = [
                'price' => $raw['price'],
                'size' => $raw['size'],
                'side' => $raw['side'],
                'time' => $raw['timestamp'] * 1000, // we work in ms ts
            ];
        }

        return $tsData;
    }
}
