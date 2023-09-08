<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\Infra\Source\File\BybitCsvFileParser;
use App\Infra\Source\File\BybitFileDecompressor;
use App\Tests\Functionnal\Prototype\TsSampling\Infra\TickDataTransformer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

#[AsCommand(name: 'app:bybit:process')]
final class BybitFileImportCommand extends Command
{
    private TickDataTransformer $tickDataTransformer;
    private string $downloadDir;

    public function __construct(
        string $downloadDir,
        TickDataTransformer $tickDataTransformer,
        string $name = null
    ) {
        parent::__construct($name);
        $this->tickDataTransformer = $tickDataTransformer;
        $this->downloadDir = $downloadDir;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $finder = new Finder();
        $downloadDir = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.$this->downloadDir;
        $finder->in($downloadDir)->files();
        $fileToProcess = new \ArrayIterator();
        foreach ($finder->getIterator() as $file) {
            if ($file->getExtension() === 'gz') {
                $fileToProcess->append($file);
            }
        }

        $count = $fileToProcess->count();
        for ($f = 0; $f < $count; ++$f) {
            $fileInfo = $fileToProcess->current();
            $decompressor = new BybitFileDecompressor($fileInfo);
            $csvFileInfo = $decompressor->execute();
            $tickData = BybitCsvFileParser::parse($csvFileInfo);
            $transformer = $this->tickDataTransformer;
            $transformer->checkColumns($tickData);
            $tickData = $transformer->chronoSort($tickData);

            $fileToProcess->next();
        }

        return Command::SUCCESS;
    }
}
