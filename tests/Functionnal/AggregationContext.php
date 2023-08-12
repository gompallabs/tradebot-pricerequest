<?php

declare(strict_types=1);

namespace App\Tests\Functionnal;

use App\Domain\PriceOhlcv;
use App\Domain\Store\Adapter\RedisTimeSeries\Sample\RawSample;
use App\Domain\Store\Adapter\RedisTimeSeries\TimeSeriesInterface;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleAggregationRule;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleDuplicatePolicyList;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleFilter;
use App\Domain\TsSampling\SplitSeries;
use App\Domain\TsSampling\TickDataTransformer;
use App\Infra\Source\File\BybitCsvFileParser;
use App\Infra\Source\File\BybitFileDecompressor;
use App\Infra\Source\File\BybitFileDownloader;
use Behat\Behat\Context\Context;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertGreaterThanOrEqual;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertLessThanOrEqual;

final class AggregationContext implements Context
{
    private array $filenames = [];
    private HttpClientInterface $bybitPublicClient;

    private array $downloadedFiles = [];

    private ?\ArrayIterator $fileContent = null;
    private TickDataTransformer $tickDataTransformer;
    private TimeSeriesInterface $timeSeries;
    private array $keys = [];

    private array $limits = [];

    private \ArrayIterator $resampledAggregate;

    private int $parsedFilesNumber = 0;

    private ?SplitSeries $series = null;

    private array $seriesKeys = [];

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
        if ('last' === $arg2) {
            $options = [
                'filter' => [
                    'last' => (int) $arg1,
                ],
            ];
        }

        if ('first' === $arg2) {
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
        $this->fileContent = $content;
        $this->parsedFilesNumber = $fileParsed;
    }

    /**
     * @Given I aggregate the tick data with a :arg1 second step and I split the aggregate into series with labels
     */
    public function iAggregateTheTickDataWithASecondStepAndISplitTheAggregateIntoSeriesWithLabels($arg1)
    {
        // move to aggregator to have split series output
        $tickData = $this->fileContent;
        $transformer = $this->tickDataTransformer;
        $transformer->checkColumns($tickData);

        // sort data
        $transformed = $transformer->chronoSort($tickData);

        // create time scale
        $startDt = $transformed['startTs'];
        $endDt = $transformed['endTs'];
        $this->limits = [(int) floor($startDt) * 1000, (int) floor($endDt) * 1000];

        $timeFormat = $transformer::guessTimeStampFormat($tickData[0]['timestamp']);
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
        $tickData = $this->fileContent;
        $transformer = $this->tickDataTransformer;
        $transformer->checkColumns($tickData);

        // sort data
        $transformed = $transformer->chronoSort($tickData);

        // create time scale
        $tickData = $transformed['tickData'];
        $startDt = $transformed['startTs'];
        $endDt = $transformed['endTs'];

        $this->limits = [(int) floor($startDt) * 1000, (int) floor($endDt) * 1000];
        $timeFormat = $transformer::guessTimeStampFormat($tickData[0]['timestamp']);
        $tsScale = $transformer->getTimeScale($tickData, (int) $arg1, $timeFormat);

        // re-sample to 1 second ohlcv
        $aggregateSample = $transformer->resample(tickData: $tickData, tsScale: $tsScale);

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
            if (null !== $sample) {
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

    /**
     * @When I fetch the timeseries :arg1 I use redis aggregation to build a :arg2 time series
     */
    public function iFetchTheTimeseriesIUseRedisAggregationToBuildATimeSeries($arg1, $arg2)
    {
        $keys = $this->keys;
        [$startDt, $endDt] = $this->limits;
        // try to fetch range on open
        $keyOpen = $keys['open'];
        $keyHigh = $keys['high'];
        $keyLow = $keys['low'];
        $keyClose = $keys['close'];
        $keyBuy = $keys['buy'];
        $keySell = $keys['sell'];

        $client = $this->timeSeries;

        $open = $client->range(
            key: $keyOpen,
            from: (int) $startDt,
            to: (int) $endDt,
            rule: new SampleAggregationRule(SampleAggregationRule::AGG_FIRST, 10000)
        );
        assertGreaterThanOrEqual($this->parsedFilesNumber * 8000, count($open));
        assertLessThanOrEqual($this->parsedFilesNumber * 9000, count($open));
        $high = $client->range(
            key: $keyHigh,
            from: (int) $startDt,
            to: (int) $endDt,
            rule: new SampleAggregationRule(SampleAggregationRule::AGG_MAX, 10000)
        );
        assertGreaterThanOrEqual($this->parsedFilesNumber * 8000, count($high));
        assertLessThanOrEqual($this->parsedFilesNumber * 9000, count($high));
        $low = $client->range(
            key: $keyLow,
            from: (int) $startDt,
            to: (int) $endDt,
            rule: new SampleAggregationRule(SampleAggregationRule::AGG_MIN, 10000)
        );
        assertGreaterThanOrEqual($this->parsedFilesNumber * 8000, count($low));
        assertLessThanOrEqual($this->parsedFilesNumber * 9000, count($low));

        $close = $client->range(
            key: $keyClose,
            from: (int) $startDt,
            to: (int) $endDt,
            rule: new SampleAggregationRule(SampleAggregationRule::AGG_LAST, 10000)
        );
        assertGreaterThanOrEqual($this->parsedFilesNumber * 8000, count($close));
        assertLessThanOrEqual($this->parsedFilesNumber * 9000, count($close));

        $buy = $client->range(
            key: $keyBuy,
            from: (int) $startDt,
            to: (int) $endDt,
            rule: new SampleAggregationRule(SampleAggregationRule::AGG_SUM, 10000)
        );
        assertGreaterThanOrEqual(8000, count($buy));
        assertLessThanOrEqual(9000, count($buy));

        $sell = $client->range(
            key: $keySell,
            from: (int) $startDt,
            to: (int) $endDt,
            rule: new SampleAggregationRule(SampleAggregationRule::AGG_SUM, 10000)
        );
        assertGreaterThanOrEqual($this->parsedFilesNumber * 8000, count($sell));
        assertLessThanOrEqual($this->parsedFilesNumber * 9000, count($sell));
    }
}
