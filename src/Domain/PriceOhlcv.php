<?php

declare(strict_types=1);

namespace App\Domain;

/**
 * This class is a DTO.
 */
final class PriceOhlcv
{
    private int $tsms;
    private float $open;
    private float $high;
    private float $low;
    private float $close;
    private float $buyVolume;
    private float $sellVolume;

    /**
     * $tsMs is a timestamp in milliseconds.
     * $category means spot or perp (if linear).
     */
    public function __construct(
        int $tsms,
        float $open
    ) {
        $this->tsms = $tsms;
        $this->open = $open;
        $this->high = $open;
        $this->low = $open;
        $this->close = $open;
        $this->buyVolume = 0;
        $this->sellVolume = 0;
    }

    public function addTick(array $tick): void
    {
        $price = (float) $tick['price'];
        if ($price <= $this->low) {
            $this->setLow($price);
        }
        if ($price >= $this->high) {
            $this->setHigh($price);
        }
        $this->addVolume($tick['side'], (float) $tick['size']);
        $this->close = $price;
    }

    public function addTickWithoutLabel(\SplFixedArray $tick): void
    {
        $price = $tick[2];
        if ($price <= $this->low) {
            $this->setLow($price);
        }
        if ($price >= $this->high) {
            $this->setHigh($price);
        }
        $this->addBuyVolume($tick[3]);
        $this->addSellVolume($tick[4]);
        $this->close = $price;
    }

    public function addVolume(string $side, float $size): void
    {
        if ($side === 'Buy') {
            $this->addBuyVolume($size);
        } elseif ($side === 'Sell') {
            $this->addSellVolume($size);
        } else {
            throw new \RuntimeException('Tick side should be Buy or Sell');
        }
    }

    public function addBuyVolume(float $size): void
    {
        $this->buyVolume += $size;
    }

    public function addSellVolume(float $size): void
    {
        $this->sellVolume += $size;
    }

    public function toArray(): array
    {
        return [
            'tsms' => $this->getTsms(),
            'open' => $this->getOpen(),
            'high' => $this->getHigh(),
            'low' => $this->getLow(),
            'close' => $this->getClose(),
            'buyVolume' => $this->getBuyVolume(),
            'sellVolume' => $this->getSellVolume(),
            'totalVolume' => $this->getTotalVolume(),
            'deltaVolume' => $this->getDeltaVolume(),
        ];
    }

    public function getTsms(): float
    {
        return $this->tsms;
    }

    public function getTotalVolume(): float
    {
        return $this->buyVolume + $this->sellVolume;
    }

    public function getDeltaVolume(): float|int
    {
        return $this->buyVolume - $this->sellVolume;
    }

    public function getBuyVolume(): float
    {
        return $this->buyVolume;
    }

    public function getSellVolume(): float
    {
        return $this->sellVolume;
    }

    public function setBuyVolume(float $buyVolume): void
    {
        $this->buyVolume = $buyVolume;
    }

    public function setSellVolume(float $sellVolume): void
    {
        $this->sellVolume = $sellVolume;
    }

    public function getOpen(): float
    {
        return $this->open;
    }

    public function getHigh(): float
    {
        return $this->high;
    }

    public function setHigh(float $high): void
    {
        $this->high = $high;
    }

    public function getLow(): float
    {
        return $this->low;
    }

    public function setLow(float $low): void
    {
        $this->low = $low;
    }

    public function getClose(): float
    {
        return $this->close;
    }

    public function setClose(float $close): void
    {
        $this->close = $close;
    }
}
