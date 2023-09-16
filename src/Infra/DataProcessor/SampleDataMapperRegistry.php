<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor;

use App\Domain\DataProcessor\SampleDataMapper;
use App\Domain\DataProcessor\SampleDataMapperRegistry as SampleDataMapperRegistryInterface;
use App\Domain\Source\Source;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class SampleDataMapperRegistry implements SampleDataMapperRegistryInterface
{
    private array $dataMappers;

    public function __construct(
        #[TaggedIterator('app.sample_data_mapper')] iterable $dataMappers
    ) {
        foreach ($dataMappers as $dataMapper) {
            foreach ($dataMapper as $item) {
                foreach ($item as $subitem) {
                    $this->dataMappers[] = $subitem;
                }
            }
        }
    }

    public function mapToTs(Source $source, array $sample): \SplFixedArray
    {
        $dataMapper = $this->getMapperForSource($source);

        return $dataMapper->mapToTs(sample: $sample);
    }

    public function supports(Source $source): bool
    {
        return true;
    }

    public function getMapperForSource(Source $source): ?SampleDataMapper
    {
        /** @var SampleDataMapper $dataMapper */
        foreach ($this->dataMappers as $dataMapper) {
            if ($dataMapper->supports($source)) {
                return $dataMapper;
            }
        }
        throw new \RuntimeException(sprintf('Missing data mapper for %s of type %s in '.__METHOD__, $source->getExchange()->name, $source->getSourceType()->name));
    }
}
