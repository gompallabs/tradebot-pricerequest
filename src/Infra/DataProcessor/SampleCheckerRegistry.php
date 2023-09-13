<?php

declare(strict_types=1);

namespace App\Infra\DataProcessor;

use App\Domain\DataProcessor\SampleChecker;
use App\Domain\DataProcessor\SampleCheckerRegistry as SampleCheckerRegistryInterface;
use App\Domain\Source\Source;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class SampleCheckerRegistry implements SampleCheckerRegistryInterface
{
    private array $checkers;

    public function __construct(
        #[TaggedIterator('app.sample_checker')] iterable $checkers
    ) {
        foreach ($checkers as $checker) {
            foreach ($checker as $item) {
                $this->checkers[] = $item;
            }
        }
    }

    public function check(Source $source, array $sample)
    {
        $checker = $this->getCheckerForSource($source);

        return $checker->check($sample);
    }

    public function supports(Source $source)
    {
        return true;
    }

    public function getCheckerForSource(Source $source): ?SampleChecker
    {
        /** @var SampleChecker $checker */
        foreach ($this->checkers as $checker) {
            if ($checker->supports($source)) {
                return $checker;
            }
        }
        throw new \RuntimeException(sprintf('Missing checker for %s of type %s in '.__METHOD__, $source->getExchange()->name, $source->getSourceType()->name));
    }
}
