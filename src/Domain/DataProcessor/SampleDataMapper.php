<?php

declare(strict_types=1);

namespace App\Domain\DataProcessor;

use App\Domain\Source\Source;

interface SampleDataMapper
{
    public function mapToTs(array $sample): \SplFixedArray;

    public function getTimeStampFieldName(): string;

    public function supports(Source $source): bool;

    public function getColumns(): array;
}
