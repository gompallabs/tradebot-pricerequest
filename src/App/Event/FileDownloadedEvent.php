<?php

declare(strict_types=1);

namespace App\App\Event;

use App\Domain\Coin;
use App\Domain\File;
use App\Domain\Source\Source;

class FileDownloadedEvent
{
    private Source $source;
    private Coin $coin;
    private array $files;

    public function __construct(
        Source $source,
        Coin $coin,
        array $downloadedFiles
    ) {
        $this->source = $source;
        $this->coin = $coin;
        $this->files = $downloadedFiles;
    }

    public function getSource(): Source
    {
        return $this->source;
    }

    public function getCoin(): Coin
    {
        return $this->coin;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function toArray(): array
    {
        return [
            'source' => $this->getSource()->toArray(),
            'coin' => $this->getCoin()->toArray(),
            'files' => array_map(function (File $file) {
                return $file->toArray();
            }, $this->getFiles()),
        ];
    }
}
