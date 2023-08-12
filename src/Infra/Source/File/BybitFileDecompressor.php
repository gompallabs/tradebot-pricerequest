<?php

declare(strict_types=1);

namespace App\Infra\Source\File;

use App\Domain\Source\File\FileDecompressorInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;

/**
 * Bybit file is a .csv.gz.
 */
final class BybitFileDecompressor implements FileDecompressorInterface
{
    private \SplFileInfo $fileInfo;

    public function __construct(\SplFileInfo $fileInfo)
    {
        $this->fileInfo = $fileInfo;
    }

    public function execute(): ?\SplFileInfo
    {
        $directory = $this->fileInfo->getPath();

        $process = new Process(['gunzip', $this->fileInfo->getPathname()]);
        $process->run();

        $finder = new Finder();
        $finder = $finder->in($directory)->files();

        $csvInfo = null;
        $csvFileName = basename($this->fileInfo->getFilename(), '.gz');

        foreach ($finder->getIterator() as $file) {
            if ($csvFileName === $file->getFilename()) {
                $csvInfo = $file->getFileInfo();
                break;
            }
        }

        return $csvInfo;
    }
}
