<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\Prototype;

use App\Domain\PriceOhlcv;
use App\Domain\Store\Adapter\RedisTimeSeries\Sample\RawSample;
use App\Domain\Store\Adapter\RedisTimeSeries\TimeSeriesInterface;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleDuplicatePolicyList;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleFilter;
use App\Domain\TimeFormat;
use App\Domain\TimeFormatTools;
use App\Infra\Old\TickDataBuild\DataMapper\BybitFileSampleColumnsChecker;
use App\Infra\Source\File\BybitCsvFileParser;
use App\Infra\Source\File\BybitFileDecompressor;
use App\Infra\Source\File\BybitFileDownloader;
use App\Tests\Functionnal\Prototype\TsSampling\Domain\SplitSeries;
use App\Tests\Functionnal\Prototype\TsSampling\Domain\TickDataTransformer;
use App\Tests\Functionnal\Prototype\TsSampling\Infra\TickData;
use App\Tests\Functionnal\Prototype\TsSampling\Infra\TsScale;
use Assert\Assert;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertGreaterThanOrEqual;
use function PHPUnit\Framework\assertInstanceOf;

final class AggregationContext implements Context
{
    private array $filenames = [];
    private HttpClientInterface $bybitPublicClient;

    private array $downloadedFiles = [];

    private array $fileContent = [];
    private TickDataTransformer $tickDataTransformer;
    private TimeSeriesInterface $timeSeries;
    private array $keys = [];

    private array $limits = [];

    private \ArrayIterator $resampledAggregate;

    private int $parsedFilesNumber = 0;

    private ?SplitSeries $series = null;

    private array $seriesKeys = [];

    private array $tickData = [];

    private ?TimeFormat $timeFormat = null;

    public function __construct(
        HttpClientInterface $bybitPublicClient,
        TickDataTransformer $tickDataTransformer,
        TimeSeriesInterface $timeSeries
    ) {
        $this->bybitPublicClient = $bybitPublicClient;
        $this->tickDataTransformer = $tickDataTransformer;
        $this->timeSeries = $timeSeries;
    }

    /**
     * @Given I have the following tickData:
     */
    public function iHaveTheFollowingTickdata(TableNode $table)
    {
        foreach ($table->getIterator() as $raw) {
            $this->tickData[] = $raw;
        }
    }

    /**
     * @Then I should remove non mandatory columns
     */
    public function iShouldRemoveNonMandatoryColumns()
    {
        $data = $this->tickData;
        $acceptedKeys = ['timestamp', 'side', 'size', 'price'];
        $newData = [];
        foreach ($data as $sample) {
            $newSample = [];
            foreach ($sample as $key => $value) {
                if (in_array($key, $acceptedKeys)) {
                    $newSample[$key] = $value;
                }
            }
            $newData[] = $newSample;
        }
        $this->tickData = $newData;
    }

    /**
     * @Then I should guess the timestamp format
     */
    public function iShouldGuessTheTimestampFormat()
    {
        $this->timeFormat = null;
        $data = $this->tickData;
        $timeFormat = TimeFormatTools::guessTimeStampFormat($data[0]['timestamp']);
        assertInstanceOf(TimeFormat::class, $timeFormat);
        $this->timeFormat = $timeFormat;
    }

    /**
     * @Then I should check if the mandatory columns are here
     */
    public function iShouldCheckIfTheMandatoryColumnsAreHere()
    {
        $data = $this->tickData[0];
        Assert::lazy()
            ->that($data)
            ->keyExists('timestamp')
            ->keyExists('price')
            ->keyExists('size')
            ->keyExists('side')
            ->verifyNow();
    }

    /**
     * @Then I should sort :arg1 in :arg2 order
     */
    public function iShouldSortInOrder($arg1, $arg2)
    {
        $data = $this->tickData;
        usort($data, function ($a, $b) {
            $tsa = (float) $a['timestamp'];
            $tsb = (float) $b['timestamp'];
            if ($tsa === $tsb) {
                return 0;
            }

            return ($tsa < $tsb) ? -1 : 1;
        });
        $this->tickData = $data;
    }

    /**
     * @Then I should have :arg1 samples
     */
    public function iShouldHaveSamples($arg1)
    {
        assertEquals((int) $arg1, count($this->tickData));
    }

    /**
     * @Given I use the :arg1 interface to check the sample from :arg2 source with :arg3 source type
     */
    public function iUseTheInterfaceToCheckTheSampleFromSourceWithSourceType($arg1, $arg2, $arg3)
    {
        $source = $this->sourceRegistry->getSource(name: $arg2, type: $arg3);
        $columnChecker = new BybitFileSampleColumnsChecker();
    }

    /**
     * @Given I download the :arg1 last files on :arg2 at the slug :arg3 in the :arg4 directory
     */
    public function iDownloadTheFirstFilesOnAtTheSlugInTheDirectory($arg1, $arg2, $arg3, $arg4)
    {
        $downloader = new BybitFileDownloader(
            destinationDir: $arg4,
            bybitPublicClient: $this->bybitPublicClient
        );
        $fileInfos = $downloader->downloadFromHtmlPage(slug: $arg3, options: [
            'filter' => [
                'last' => (int) $arg1,
            ],
        ]);
        $this->downloadedFiles = $fileInfos;
    }

    /**
     * @Given I download the :arg1 :arg2 files on :arg3 at the slug :arg4 in the :arg5 directory
     */
    public function iDownloadTheFilesOnAtTheSlugInTheDirectory($arg1, $arg2, $arg3, $arg4, $arg5)
    {
        $options = [];
        $downloader = new BybitFileDownloader(
            destinationDir: $arg5,
            bybitPublicClient: $this->bybitPublicClient
        );
        if ($arg2 === 'last') {
            $options = [
                'filter' => [
                    'last' => (int) $arg1,
                ],
            ];
        }

        if ($arg2 === 'first') {
            $options = [
                'filter' => [
                    'first' => (int) $arg1,
                ],
            ];
        }

        $fileInfos = $downloader->downloadFromHtmlPage(slug: $arg4, options: $options);
        $this->downloadedFiles = $fileInfos;
    }

    /**
     * @Given I parse the files
     * Here we should not use multiple files parsing in one super-iterator, even if the Downloader can
     */
    public function iParseTheFiles()
    {
        // uncompress the files
        $csvFileInfos = [];
        foreach ($this->downloadedFiles as $fileInfo) {
            $decompressor = new BybitFileDecompressor($fileInfo);
            $csvFileInfos[] = $decompressor->execute();
        }

        $content = new \ArrayIterator();
        $fileParsed = 0;

        foreach ($csvFileInfos as $csvFileInfo) {
            if ($csvFileInfo instanceof \SplFileInfo) {
                $iterator = BybitCsvFileParser::parse($csvFileInfo);
                $iterator->rewind();
                while ($iterator->valid()) {
                    $tick = $iterator->current();
                    $content->append($tick);
                    $iterator->next();
                }
                ++$fileParsed;
            }
        }
        assertGreaterThanOrEqual(1, count($content));
        $content->rewind();
        $this->fileContent = iterator_to_array($content);
        $this->parsedFilesNumber = $fileParsed;
    }

    /**
     * @Given I aggregate the tick data with a :arg1 second step and I split the aggregate into series with labels
     */
    public function iAggregateTheTickDataWithASecondStepAndISplitTheAggregateIntoSeriesWithLabels($arg1)
    {
        // move to aggregator to have split series output
        $transformer = $this->tickDataTransformer;
        $transformer->checkColumns($tickData);

        // sort data
        $transformed = $transformer->chronoSort($tickData);

        // create time scale
        $startDt = $transformed['startTs'];
        $endDt = $transformed['endTs'];
        $this->limits = [(int) floor($startDt) * 1000, (int) floor($endDt) * 1000];

        $timeFormat = TimeFormat::guessTimeStampFormat($tickData[0]['timestamp']);
        $tsScale = $transformer->getTimeScale($tickData, (int) $arg1, $timeFormat);

        // here we don't want ohlcv but o-h-l-c-v series
        // re-sample to 1 second ohlcv
        $aggregate = $transformer->splitResample(tickData: $tickData, tsScale: $tsScale, key: 'BTCUSDT');
        assertInstanceOf(SplitSeries::class, $aggregate);
        $this->series = $aggregate;
    }

    /**
     * @Given i push the series with labels to redis under keys :arg1 with appended label
     */
    public function iPushTheSeriesWithLabelsToRedisUnderKeysWithAppendedLabel($arg1)
    {
        $aggregate = $this->series;
        dd($aggregate);
        $pool = new \ArrayIterator();
        $pool->append($aggregate->getOpen());
        $pool->append($aggregate->getHigh());
        $pool->append($aggregate->getLow());
        $pool->append($aggregate->getClose());
        $pool->append($aggregate->getBuyVolume());
        $pool->append($aggregate->getSellVolume());

        foreach ($pool as $series) {
            $this->timeSeries->deleteSeries($series);
        }

        foreach ($pool as $series) {
            $this->timeSeries->pushSeries($series);
        }
    }

    /**
     * @Then the series should exist under the key :arg1
     */
    public function theSeriesShouldExistUnderTheKey($arg1)
    {
        $keys = [
            $arg1.'open',
            $arg1.'high',
            $arg1.'low',
            $arg1.'close',
            $arg1.'buyVolume',
            $arg1.'sellVolume',
        ];
        foreach ($keys as $key) {
            $info = $this->timeSeries->info($key);
            $numberOfSamples = $info->getTotalSamples();
            assertGreaterThanOrEqual(1000, $numberOfSamples);
        }

        $this->seriesKeys = $keys;
    }

    /**
     * @Given I request the OHLCV data between :arg1 and now
     */
    public function iRequestTheOhlcvDataBetweenAndNow($arg1)
    {
        $result = [];
        $counts = [];

        $ts = strtotime($arg1);
        $to = new \DateTime();

        $dpList = ['open', 'high', 'low', 'close', 'buyVolume', 'sellVolume'];

        foreach ($dpList as $dp) {
            $filter = new SampleFilter('asset', 'BTCUSDT');
            $filter->add('dp', SampleFilter::OP_EQUALS, 'open');
            $serie = $this->timeSeries->multiRangeWithLabels(
                filter: $filter,
                from: $ts * 1000,
                to: $to->getTimestamp() * 1000,
            );

            $result[$dp] = $serie;
            $counts[$dp] = count($serie);
        }
        $lastCount = count($serie);

        $expected = [
            'open' => $lastCount,
            'high' => $lastCount,
            'low' => $lastCount,
            'close' => $lastCount,
            'buyVolume' => $lastCount,
            'sellVolume' => $lastCount,
        ];

        assertEquals($expected, $counts);
    }

    /**
     * @Then I aggregate the tick data with a :arg1 second step and push it to datastore under the key :arg2
     */
    public function iAggregateTheTickDataWithASecondStepAndPushItToDatastoreUnderTheKey($arg1, $arg2)
    {
        $fileContent = $this->fileContent;
        $firstElement = $fileContent[0];

        $timeFormat = TimeFormatTools::guessTimeStampFormat($firstElement['timestamp']);
        $tickData = new TickData(data: $this->fileContent, timeFormat: $timeFormat);

        $tickData->checkColumns();
        $tickData->chronoSort();
        $tsScale = TsScale::createFromTickData($tickData, (int) $arg1);
        $aggregateSample = $this->tickDataTransformer->resample(tickData: $tickData, tsScale: $tsScale);

        // push to ts
        $client = $this->timeSeries;
        $client->create($arg2);

        $keyOpen = $arg2.'_open';
        $keyHigh = $arg2.'_high';
        $keyLow = $arg2.'_low';
        $keyClose = $arg2.'_close';
        $keyBuyVolume = $arg2.'_buyVolume';
        $keySellVolume = $arg2.'_sellVolume';

        foreach ($aggregateSample as $sample) {
            if ($sample !== null) {
                $tsms = (int) $sample->getTsms();

                // split OHLCV to columns
                /** @var PriceOhlcv $sample */
                $rawSample = RawSample::createFromTimestamp(
                    key: $keyOpen,
                    value: $sample->getOpen(),
                    tsms: $tsms,
                );
                $this->writeKey(client: $client, sample: $rawSample);

                $rawSample = RawSample::createFromTimestamp(
                    key: $keyHigh,
                    value: $sample->getHigh(),
                    tsms: $tsms,
                );
                $this->writeKey(client: $client, sample: $rawSample);

                $rawSample = RawSample::createFromTimestamp(
                    key: $keyLow,
                    value: $sample->getLow(),
                    tsms: $tsms,
                );
                $this->writeKey(client: $client, sample: $rawSample);

                $rawSample = RawSample::createFromTimestamp(
                    key: $keyClose,
                    value: $sample->getClose(),
                    tsms: $tsms,
                );
                $this->writeKey(client: $client, sample: $rawSample);

                $rawSample = RawSample::createFromTimestamp(
                    key: $keyBuyVolume,
                    value: $sample->getBuyVolume(),
                    tsms: $tsms,
                );
                $this->writeKey(client: $client, sample: $rawSample);

                $rawSample = RawSample::createFromTimestamp(
                    key: $keySellVolume,
                    value: $sample->getSellVolume(),
                    tsms: $tsms,
                );
                $this->writeKey(client: $client, sample: $rawSample);
            }

            $this->keys = [
                'open' => $keyOpen,
                'high' => $keyOpen,
                'low' => $keyOpen,
                'close' => $keyOpen,
                'buy' => $keyOpen,
                'sell' => $keyOpen,
            ];
        }
    }

    private function writeKey(TimeSeriesInterface $client, RawSample $sample): void
    {
        $existingKey = $client->range($sample->getKey(), $sample->getTsms(), $sample->getTsms());
        if (empty($existingKey[0])) {
            $client->add(rawSample: $sample, duplicatePolicy: SampleDuplicatePolicyList::BLOCK->value);
        }
    }
}
