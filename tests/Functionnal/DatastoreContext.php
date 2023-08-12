<?php

declare(strict_types=1);

namespace App\Tests\Functionnal;

use App\Domain\Store\Adapter\RedisTimeSeries\TimeSeriesInterface;
use Behat\Behat\Context\Context;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertIsArray;
use function PHPUnit\Framework\assertLessThan;

final class DatastoreContext implements Context
{
    private TimeSeriesInterface $dataStore;
    private float $start;
    private float $end;

    public function __construct(TimeSeriesInterface $dataStore)
    {
        $this->dataStore = $dataStore;
    }

    /**
     * @Given I have a redis data store up
     */
    public function iHaveARedisDataStoreUp()
    {
        assertInstanceOf(TimeSeriesInterface::class, $this->dataStore);
        $this->dataStore->set(key: 'test', value: 'test', expiration: null);
        $result = $this->dataStore->get('test');
        assertEquals('test', $result);
    }

    /**
     * @Given I start the chronometer
     */
    public function iStartTheChronometer()
    {
        $this->start = microtime(true);
    }

    /**
     * @Given I push the test key :arg1 to redis under that expires after :arg2 seconds
     */
    public function iPushTheTestKeyToRedisUnderThatExpiresAfterSeconds($arg1, $arg2)
    {
        $this->dataStore->set(key: $arg1, value: 'test', expiration: (float) $arg2);
    }

    /**
     * @Given I should be able to retrieve the price array
     */
    public function iShouldBeAbleToRetrieveThePriceArray()
    {
        $testArray = json_decode(
            $this->dataStore->get('test_array'), true
        );
        assertIsArray($testArray);
        assertArrayHasKey('price', $testArray);
        assertEquals('1 USD', $testArray['price']);
    }

    /**
     * @Given I stop the chronometer
     */
    public function iStopTheChronometer()
    {
        $this->end = microtime(true);
    }

    /**
     * @Then I wait :arg2 seconds and can not retrieve the key :arg1
     */
    public function iWaitSecondsAndCanNotRetrieveTheKey2($arg1, $arg2)
    {
        sleep((int) $arg2);
        assertFalse($this->dataStore->get($arg1));
    }

    /**
     * @Then I wait :arg3 seconds and can retrieve the key :arg1 and it has the value :arg2
     */
    public function iWaitSecondsAndCanRetrieveTheKeyAndItHasTheValue($arg1, $arg2, $arg3)
    {
        sleep((int) $arg2);
        $value = $this->dataStore->get($arg1);
        assertEquals($value, $arg2);
    }

    /**
     * @Given the insert should have lasted less than :arg1 ms
     */
    public function theInsertShouldHaveLastedLessThanMs($arg1)
    {
        assertLessThan(100, ($this->end - $this->start) * 1000);
    }
}
