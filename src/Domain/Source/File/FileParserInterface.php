<?php

namespace App\Domain\Source\File;

interface FileParserInterface
{
    public static function parse(\SplFileInfo $fileInfo): \Iterator;
}
