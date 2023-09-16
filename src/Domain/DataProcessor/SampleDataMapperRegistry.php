<?php

declare(strict_types=1);

namespace App\Domain\DataProcessor;

use App\Domain\Source\Source;

interface SampleDataMapperRegistry
{
    public function mapToTs(Source $source, array $sample): \SplFixedArray;

    public function supports(Source $source): bool;

    public function getMapperForSource(Source $source): ?SampleDataMapper;
}
