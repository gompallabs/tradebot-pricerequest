<?php

declare(strict_types=1);

namespace App\Domain\DataProcessor;

use App\Domain\Source\Source;

interface SampleChecker
{
    public function check(array $sample);

    public function supports(Source $source);
}
