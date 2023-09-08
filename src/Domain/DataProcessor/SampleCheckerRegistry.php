<?php

declare(strict_types=1);

namespace App\Domain\DataProcessor;

use App\Domain\Source\Source;

interface SampleCheckerRegistry
{
    public function getCheckerForSource(Source $source): ?SampleChecker;

    public function check(Source $source, array $sample);

    public function supports(Source $source);
}
