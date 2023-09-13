<?php

declare(strict_types=1);

namespace App\Tests\Functionnal\Prototype;

use App\Domain\Store\Adapter\RedisTimeSeries\Sample\RawSample;
use App\Domain\Store\Adapter\RedisTimeSeries\TimeSeriesInterface;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleDuplicatePolicyList;
use App\Domain\Store\Adapter\RedisTimeSeries\Vo\SampleMetadata;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertGreaterThan;
use function PHPUnit\Framework\assertGreaterThanOrEqual;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertTrue;

final class TimeSeriesContext implements Context
{
    private TimeSeriesInterface $datastore;

    private null|bool|SampleMetadata $metadata = null;

    private array $testSample = [];

    private ?RawSample $last = null;

    private ArrayCollection $tsCollection;

    public function __construct(TimeSeriesInterface $datastore)
    {
        $this->datastore = $datastore;
        $this->tsCollection = new ArrayCollection();
    }

    /**
     * @Given I have a test-sample
     */
    public function iHaveATestSample()
    {
        $data = json_decode($this->datastore->get('test-sample'), true);
        assertGreaterThan(1000, count($data));
        $this->testSample = $data;
    }

    /**
     * @Given I request ts info and have no ts named :arg1
     */
    public function iRequestTsInfoAndHaveNoTsNamed($arg1)
    {
        $this->iRequestTsInfoWithKey($arg1);
        assertFalse($this->metadata);
    }

    /**
     * @Given I request ts info with key :arg1
     */
    public function iRequestTsInfoWithKey($arg1)
    {
        $this->metadata = null;
        $this->metadata = $this->datastore->info($arg1);
    }

    /**
     * @Then I create a TimeSeries in redis with with key :arg1 with expiration :arg2 seconds
     */
    public function iCreateATimeseriesInRedisWithWithKeyWithExpirationSeconds($arg1, $arg2)
    {
        $this->datastore->create(key: $arg1, retentionMs: 600000);
    }

    /**
     * @Then the payload should contain a :arg1 key with :arg2 value
     */
    public function thePayloadShouldContainAKeyWithValue($arg1, $arg2)
    {
        $meta = $this->metadata;
        $accessor = new PropertyAccessor();
        assertTrue($accessor->isReadable($meta, $arg1));
        assertEquals($accessor->getValue($meta, $arg1), $arg2);
    }

    /**
     * @Then the payload should contain a :arg1 key with :arg2 timestamp value
     */
    public function thePayloadShouldContainAKeyWithTimestampValue($arg1, $arg2)
    {
        $targetTime = new \DateTime();
        $targetTime->setTimestamp((int) $arg2);

        $meta = $this->metadata;
        $accessor = new PropertyAccessor();
        assertTrue($accessor->isReadable($meta, $arg1));
        assertEquals($targetTime, $accessor->getValue($meta, $arg1));
    }

    /**
     * @Then the payload should contain a :arg1 key with an empty array value
     */
    public function thePayloadShouldContainAKeyWithAnEmptyArrayValue($arg1)
    {
        $meta = $this->metadata;
        $accessor = new PropertyAccessor();
        assertTrue($accessor->isReadable($meta, $arg1));
        $value = $accessor->getValue($meta, $arg1);
        assertIsArray($value);
        assertEmpty($value);
    }

    /**
     * @Then the payload should contain a :arg1 key with a :arg2 value
     */
    public function thePayloadShouldContainAKeyWithAValue($arg1, $arg2)
    {
        $meta = $this->metadata;
        $accessor = new PropertyAccessor();
        assertTrue($accessor->isReadable($meta, $arg1));
        $value = $accessor->getValue($meta, $arg1);
        if ($arg2 === 'null') {
            assertNull($value);
        } else {
            assertTrue(false);
        }
    }

    /**
     * @Given the ts named :arg1 exists and has no data
     */
    public function theTsNamesExistsAndHasNoData($arg1)
    {
        $info = $this->datastore->info($arg1);
        assertFalse($info);
    }

    /**
     * @Given I add the oldest data point of the test-sample key to the :arg1 ts
     */
    public function iAddTheOldestDataPointOfTheTestSampleKeyToTheTs($arg1)
    {
        $testSample = $this->testSample;
        $datapoint = $testSample[0];
        $this->datastore->add(rawSample: new RawSample($arg1, $datapoint['price'], $datapoint['time']), duplicatePolicy: SampleDuplicatePolicyList::LAST->value);
    }

    /**
     * @Then I should have a datapoint in ts :arg1
     */
    public function iShouldHaveADatapointInTs($arg1): void
    {
        $this->last = null;
        $last = $this->datastore->getLastRaw($arg1);
        assertInstanceOf(RawSample::class, $last);
        $this->last = $last;
    }

    /**
     * @Then I remove the ts :arg1
     */
    public function iRemoveTheTs($arg1)
    {
        $this->datastore->delTs($arg1);
        assertFalse($this->datastore->get($arg1));
    }

    /**
     * @Then the datapoint payload should contain the following properties and values:
     */
    public function theDatapointPayloadShouldContainTheFollowingPropertiesAndValues(PyStringNode $string)
    {
        assertInstanceOf(RawSample::class, $this->last);
        $expected = json_decode(implode('', $string->getStrings()), true);
        $accessor = new PropertyAccessor();
        $last = $this->last;
        foreach ($expected as $key => $value) {
            assertTrue($accessor->isReadable($last, trim($key)));
            assertEquals($accessor->getValue($last, trim($key)), $value);
        }
    }

    /**
     * @Then the ts named :arg1 should have one datapoint
     */
    public function theTsNamedShouldHaveOneDatapoint($arg1)
    {
        $info = $this->datastore->info($arg1);
        assertEquals($info->getChunkCount(), 1);
        $this->last = $this->datastore->getLastRaw($arg1);
    }

    /**
     * @Then every point should exist on the sample :arg1
     */
    public function everyPointShouldExistOnTheSample($arg1)
    {
        $sample = $this->datastore->get($arg1);
        $json = json_decode($sample, true);
        $tsCollection = $this->tsCollection;

        foreach ($tsCollection->getIterator() as $sample) {
            $existingPrices = array_filter($json, function (array $priceArray) use ($sample) {
                /* @var RawSample $sample */
                return
                    (float) $priceArray['price'] === $sample->getValue()
                    && (int) floor($priceArray['time']) === $sample->getTsms()
                ;
            });
            assertGreaterThanOrEqual(1, count($existingPrices));
        }
    }

    /**
     * @Given I request second half of the sample :arg1 on the ts :arg2
     */
    public function iRequestSecondHalfOfTheSampleOnTheTs($arg1, $arg2)
    {
        $json = json_decode($this->datastore->get($arg1), true);
        $tsArray = array_map(function ($priceArray) {
            return (int) $priceArray['time'];
        }, $json);

        \sort($tsArray, SORT_ASC);
        $count = (int) floor(count(array_unique($tsArray)) / 2);
        $rangeTs = array_slice($tsArray, $count);
        $startTs = $rangeTs[0];
        $endTs = end($rangeTs);
        $range = $this->datastore->range($arg2, $startTs, $endTs);
        $rangeData = array_filter($range, function ($data) {
            return $data->getTsms();
        });

        $dataCollection = new ArrayCollection($rangeData);
        $tsCollection = $dataCollection->filter(function (RawSample $rawSample) use ($startTs, $endTs) {
            return $rawSample->getTsms() < $startTs || $rawSample->getTsms() > $endTs;
        });
        assertEquals(0, $tsCollection->count());
        $this->tsCollection = $dataCollection;
    }
}
