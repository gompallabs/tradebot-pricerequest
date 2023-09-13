<?php

namespace App\Tests\Functionnal\Prototype\TsSampling\Domain;

/**
 * @deprecated
 */
interface TickData
{
    public function count(): int;

    public function checkColumns(): bool;

    public function chronoSort(): void;

    public function getIterator(): \ArrayIterator;

    public function getMinMaxTime(): array;

    public function getTimeFieldName(): string;

    public function toArray(): array;
}
