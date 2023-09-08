<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor;

use App\Domain\DataProcessor\DataProcessor as DataProcessorInterface;
use App\Domain\DataProcessor\SampleDataMapper;
use App\Domain\DataProcessor\SampleDataMapperRegistry as SampleDataMapperRegistryInterface;
use App\Domain\Source\Source;
use App\Domain\TickData;
use App\Domain\TimeFormat;
use App\Domain\TimeFormatTools;

class DataProcessor implements DataProcessorInterface
{
    private ?SampleDataMapper $dataMapper;

    private ?TimeFormat $timeFormat;

    public function __construct(
        private SampleCheckerRegistry $sampleCheckerRegistry,
        private SampleDataMapperRegistryInterface $dataMapperRegistry
    ) {
    }

    public function analyzeSample(array $sample, Source $source): bool
    {
        // throws if check fails
        $this->sampleCheckerRegistry->check($source, $sample);
        $this->dataMapper = $this->dataMapperRegistry->getMapperForSource($source);

        // time format
        $tsKey = $this->dataMapper->getTimeStampFieldName();
        $this->timeFormat = TimeFormatTools::guessTimeStampFormat($sample[$tsKey]);

        return true;
    }

    public function process(array $payload, bool $splitData = false): TickData
    {
        $transformer = new TickDataTransformer(
            dataMapper: $this->dataMapper,
            timeFormat: $this->timeFormat
        );

        return $transformer->transform($payload);
    }
}
