<?php

namespace App\Domain\Source\File;

interface FileOutputTransformerInterface
{
    public static function transform(\IteratorAggregate $payload): array;
}
