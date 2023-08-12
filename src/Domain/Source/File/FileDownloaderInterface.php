<?php

namespace App\Domain\Source\File;

interface FileDownloaderInterface
{
    public function downloadFromHtmlPage(
        string $slug,
        array $options = []
    ): array;
}
