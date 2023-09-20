<?php

declare(strict_types=1);

namespace App\Tests\Functionnal;

use Behat\Behat\Context\Context;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function PHPUnit\Framework\assertGreaterThan;
use function PHPUnit\Framework\assertTrue;

final class FileContext implements Context
{
    private ?HttpClientInterface $client = null;
    private ?string $slug = null;
    private array $filenames = [];
    private \SplFileInfo $fileInfo;

    // ########### 1_download_csvfile ############//
    /**
     * @Given I browse the url :arg1 at the slug :arg2 with the dom crawler
     */
    public function iBrowseTheUrlAtTheSlugWithTheDomCrawler($arg1, $arg2)
    {
        $this->client = HttpClient::createForBaseUri($arg1);
        $response = $this->client->request('GET', $arg2);
        $this->slug = $arg2;
        $html = $response->getContent();
        $crawler = new Crawler($html);
        $this->filenames = $crawler
            ->filterXPath('//html/body/ul/li')
            ->each(function (Crawler $node, $i): string {
                return $node->text();
            })
        ;
    }

    /**
     * @Then I can list the files
     */
    public function iCanListTheFiles()
    {
        assertGreaterThan(0, count($this->filenames));
    }

    /**
     * @Then I download the first file of the list in the :arg1 directory
     */
    public function iDownloadTheFirstFileOfTheListInTheDirectory($arg1)
    {
        $fileName = $this->filenames[0];
        $response = $this->client->request('GET', $this->slug.DIRECTORY_SEPARATOR.$fileName);
        $destinationDir = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$arg1;
        $destinationPath = $destinationDir.$fileName;

        $finder = new Finder();
        $finder = $finder->in($destinationDir)->files();
        foreach ($finder->getIterator() as $file) {
            if (is_string($file->getRealPath())) {
                unlink($file->getRealPath());
            }
        }

        file_put_contents($destinationPath, $response->getContent());

        $finder = new Finder();
        $finder = $finder->in($destinationDir)->files();
        $fileIsDownloaded = false;
        foreach ($finder->getIterator() as $file) {
            if ($file->getFilename() === $fileName) {
                $fileIsDownloaded = true;
                break;
            }
        }
        assertTrue($fileIsDownloaded);
        $this->fileInfo = $file;
    }

    public function getFileInfo(): \SplFileInfo
    {
        return $this->fileInfo;
    }

    // ########### 1_download_csvfile ############//
}
