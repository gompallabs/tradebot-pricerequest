<?php

declare(strict_types=1);

namespace App\Infra\Source\File;

use App\Domain\Source\File\FileDownloaderInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * https://public.bybit.com/trading.
 */
final class BybitFileDownloader implements FileDownloaderInterface
{
    private string $backupFolder;
    private string $destinationDir;
    private HttpClientInterface $bybitPublicClient;

    public function __construct(
        string $backupFolder,
        string $destinationDir,
        HttpClientInterface $bybitPublicClient
    ) {
        $this->backupFolder = $backupFolder;
        $this->bybitPublicClient = $bybitPublicClient;
        $this->destinationDir = $destinationDir;
    }

    /**
     * $options = [
     *  'all' => true / false,
     *  'latest' => true / false,
     *  'date' => ?\Datetime,
     *  'filter' => [
     *     'first' => n,
     *     'last' => p
     *  ],
     *  'backup' => true / false.
     */
    public function downloadFromHtmlPage(
        string $slug,
        array $options = []
    ): array {
        // scrap page to have list of files
        $response = $this->bybitPublicClient->request('GET', $slug);
        $html = $response->getContent();
        $crawler = new Crawler($html);
        $fileNames = $crawler->filterXPath('//html/body/ul/li')
            ->each(function (Crawler $node): string {
                return $node->text();
            })
        ;

        // check options to fetch the files to download
        $fileToDownload = [];
        $all = array_key_exists('all', $options) && $options['all'] === true;
        $latest = array_key_exists('latest', $options) && $options['latest'] === true;
        $date = array_key_exists('date', $options) ? $options['date'] : null;
        $filter = array_key_exists('filter', $options);

        if ($filter === true) {
            if (array_key_exists('first', $options['filter'])) {
                $fileToDownload = array_slice($fileNames, 0, $options['filter']['first']);
            }
            if (array_key_exists('last', $options['filter'])) {
                $filelist = array_reverse($fileNames);
                $fileToDownload = array_reverse(array_slice($filelist, 0, $options['filter']['last']));
            }
        }

        if ($all) {
            $fileToDownload = $fileNames;
        }

        if ($latest) {
            $fileToDownload[] = end($fileNames);
        }

        if ($all === false && $date !== null) {
            foreach ($fileNames as $fileName) {
                $strDate = new \DateTime($date);
                $strDate = $strDate->format('Y-m-d');
                $filename = new UnicodeString($fileName);
                if ($filename->containsAny($strDate)) {
                    $fileToDownload[] = $fileName;
                }
            }
        }
        $downloadedFiles = [];
        $destinationDir = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.$this->destinationDir;

        // search for already existing files
        $existingFiles = [];
        foreach ($fileToDownload as $fileName) {
            $finder = new Finder();
            $finder = $finder->in($destinationDir)->files();
            foreach ($finder->getIterator() as $file) {
                if ($file->getFilename() === $fileName) {
                    $downloadedFiles[] = $file;
                    $existingFiles[] = $fileName;
                    break;
                }
            }
        }

        foreach ($fileToDownload as $fileName) {
            if (!in_array($fileName, $existingFiles)) {
                $response = $this->bybitPublicClient->request('GET', $slug.DIRECTORY_SEPARATOR.$fileName);
                $downloadDestination = $destinationDir.DIRECTORY_SEPARATOR.$fileName;

                if ($response->getStatusCode() === 200) {
                    $bin = $response->getContent();
                    file_put_contents($downloadDestination, $bin);
                    if (array_key_exists('backup', $options) && $options['backup'] === true) {
                        $backupDir = dirname(__DIR__, 4).DIRECTORY_SEPARATOR.$this->backupFolder;
                        $backupDestination = $backupDir.DIRECTORY_SEPARATOR.$fileName;

                        // we copy file with Process hackbox because backupDir may be a symlink, not supported in PHP
                        $command = sprintf('cp %s %s', $downloadDestination, $backupDestination);
                        $process = Process::fromShellCommandline($command, getcwd());
                        $process->run();
                        if (!$process->isSuccessful()) {
                            throw new ProcessFailedException($process);
                        }
                    }

                    $finder = new Finder();
                    $finder = $finder->in($destinationDir)->files();
                    foreach ($finder->getIterator() as $file) {
                        if ($file->getFilename() === $fileName) {
                            $downloadedFiles[] = $file;
                            break;
                        }
                    }
                }
            }
        }

        return $downloadedFiles;
    }
}
