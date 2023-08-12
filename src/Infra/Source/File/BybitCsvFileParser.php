<?php

declare(strict_types=1);

namespace App\Infra\Source\File;

use App\Domain\Source\File\FileParserInterface;
use League\Csv\Reader;
use League\Csv\Statement;

/**
 * This parses csv file and returns json.
 * Then the source is deleted from download directory.
 */
final class BybitCsvFileParser implements FileParserInterface
{
    public static function parse(\SplFileInfo $fileInfo): \ArrayIterator
    {
        if ('csv' !== $fileInfo->getExtension()) {
            throw new \LogicException('Expected .csv extension, got '.$fileInfo->getExtension().' in'.__CLASS__);
        }

        $sourceCsv = Reader::createFromPath($fileInfo->getRealPath());
        $sourceCsv->setHeaderOffset(0);
        $statement = new Statement();
        $statement
            ->orderBy(function (array $recordA, array $recordB): bool {
                return $recordA['timestamp'] < $recordB['timestamp'];
            });

        unlink($fileInfo->getRealPath());

        return new \ArrayIterator(
            iterator_to_array(
                $statement->process($sourceCsv)->getIterator())
        );
    }
}
