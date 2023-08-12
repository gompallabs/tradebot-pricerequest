<?php

declare(strict_types=1);

namespace App\Domain\Store\Adapter\RedisTimeSeries\Sample;

class RawSample
{
    protected string $key;

    protected float|string $value;

    /**
     * tsms in milliseconds without decimals
     * if not stated defaults to redis insert dateTime.
     */
    protected ?int $tsms;

    public function __construct(string $key, float|string $value, int $tsms = null)
    {
        $this->key = $key;
        $this->value = $value;
        $this->tsms = $tsms;
    }

    public static function createFromTimestamp(string $key, float|string $value, ?int $tsms): RawSample
    {
        return new self($key, $value, $tsms);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getValue(): float|string
    {
        return $this->value;
    }

    public function getDateTime(): ?\DateTimeInterface
    {
        return TimeStampConverter::dateTimeFromTimestampWithMs($this->tsms);
    }

    public function getTimestampWithMs(): string
    {
        if (null === $this->tsms) {
            return '*';
        }

        return (string) $this->tsms;
    }

    public function getTsms(): int
    {
        return $this->tsms;
    }

    public function toRedisParams(): array
    {
        return [$this->getKey(), $this->getTimestampWithMs(), (string) $this->getValue()];
    }
}
