<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\Prototype\TsSampling\Infra;

use App\Domain\TimeFormat;
use App\Tests\Functionnal\Prototype\TsSampling\Domain\TickData as TickDataInterface;
use Assert\Assert;

/**
 * @deprecated
 */
class TickData implements TickDataInterface
{
    private array $tickData;
    private TimeFormat $timeFormat;
    private ?int $flags;
    private string $timeFieldName;

    public function __construct(array $data, TimeFormat $timeFormat, ?int $flags = 0)
    {
        $this->tickData = $data;
        $this->timeFormat = $timeFormat;
        $this->flags = $flags;
        $this->setTimeFieldName($data[0]);
    }

    public function getMinMaxTime(): array
    {
        $startTick = $this->tickData[0];
        $endTick = $this->tickData[count($this->tickData) - 1];

        return [(float) $startTick['timestamp'], (float) $endTick['timestamp']];
    }

    /** This method can consume a lot of memory */
    public function chronoSort(): void
    {
        $tickData = $this->tickData;
        usort($tickData, function ($a, $b) {
            $tsa = (float) $a[$this->getTimeFieldName()];
            $tsb = (float) $b[$this->getTimeFieldName()];
            if ($tsa === $tsb) {
                return 0;
            }

            return ($tsa < $tsb) ? -1 : 1;
        });

        $this->tickData = $tickData;
    }

    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->tickData, $this->getFlags());
    }

    public function count(): int
    {
        return count($this->tickData);
    }

    public function toArray(): array
    {
        return $this->tickData;
    }

    public function checkColumns(): bool
    {
        $data = $this->tickData;
        $sample = $data[0];

        Assert::lazy()
                ->that($sample, 'sample')
                ->isArray('must be an array')
                ->that($sample)
                ->keyExists($this->getTimeFieldName(), 'must have a key named '.$this->timeFieldName)
                ->that($sample)
                ->keyExists('price', 'must have a price key')
                ->that($sample)
                ->keyExists('size', 'must have a size key')
                ->that($sample)
                ->keyExists('side', 'must have a side key')
                ->verifyNow();

        return true;
    }

    private function setTimeFieldName(array $sample): void
    {
        if (array_key_exists('time', $sample)) {
            $this->timeFieldName = 'time';

            return;
        }
        if (array_key_exists('timestamp', $sample)) {
            $this->timeFieldName = 'timestamp';

            return;
        }

        throw new \LogicException('Can not detect time field name in sample in '.__CLASS__.__METHOD__);
    }

    public function getTimeFieldName(): string
    {
        return $this->timeFieldName;
    }

    public function getTickData(): array
    {
        return $this->tickData;
    }

    public function getTimeFormat(): TimeFormat
    {
        return $this->timeFormat;
    }

    public function getFlags(): ?int
    {
        return $this->flags;
    }

    public function setFlags(int $flags): void
    {
        $this->flags = $flags;
    }
}
