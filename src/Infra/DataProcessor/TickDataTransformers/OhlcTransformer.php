<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor\TickDataTransformers;

use App\Domain\DataProcessor\TickDataTransformers\TransformerInterface;
use App\Domain\PriceOhlcv;

class OhlcTransformer implements TransformerInterface
{
    private ?TransformerInterface $nextTransformer = null;

    public function transform(\ArrayIterator $tickData): \ArrayIterator
    {
        $current = $tickData->current();
        $tsms = (int) (floor($current[0]) * 1000);
        $candleData = new \ArrayIterator();

        $ohclv = new PriceOhlcv($tsms, $current[2]);
        $ohclv->addBuyVolume($current[3]);
        $ohclv->addSellVolume($current[4]);

        $tickData->next();
        while ($tickData->valid()) {
            $ohclv->addTickWithoutLabel($tickData->current());
            $tickData->next();
        }
        $candleData->append($ohclv);

        if ($this->nextTransformer !== null) {
            return $this->nextTransformer->transform($candleData);
        }

        return $candleData;
    }

    public function setNext(TransformerInterface $transformer): TransformerInterface
    {
        $this->nextTransformer = $transformer;

        return $transformer;
    }
}
