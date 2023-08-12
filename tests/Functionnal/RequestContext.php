<?php

declare(strict_types=1);

namespace App\Tests\Functionnal;

use App\Domain\Source\Api\Client;
use App\Domain\Source\Api\ClientBuilder;
use App\Domain\Source\Api\ClientResponseTransformer;
use App\Domain\Source\Api\ClientResponseValidator;
use App\Domain\Source\SourceType;
use App\Infra\Source\Api\RouteName;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertLessThan;
use function PHPUnit\Framework\assertNull;

/**
 * This context class contains the definitions of the steps used by the demo
 * feature file. Learn how to get started with Behat and BDD on Behat's website.
 *
 * @see http://behat.org/en/latest/quick_start.html
 */
final class RequestContext implements Context
{
    /** @var Response|null */
    private $builder;

    private $response;

    private Client $client;
    private ClientResponseValidator $responseValidator;
    private ClientResponseTransformer $responseTransformer;

    private array $recenTrades = [];

    public function __construct(
        ClientBuilder $builder,
        ClientResponseValidator $responseValidator,
        ClientResponseTransformer $responseTransformer
    ) {
        $this->builder = $builder;
        $this->responseValidator = $responseValidator;
        $this->responseTransformer = $responseTransformer;
    }

    /**
     * @Given I have a :arg1 client with relevant api keys for :arg2 source
     */
    public function iHaveAClientWithRelevantApiKeysForSource($arg1, $arg2)
    {
        if ('rest api' === $arg1) {
            $type = SourceType::RestApi;
        }
        $client = $this->builder->getClientForSource(type: $type, name: $arg1);
        assertInstanceOf(Client::class, $client);
        $this->client = $client;
    }

    /**
     * @When I send a request to the :arg1 route of :arg2 broker for ticker :arg3
     */
    public function iSendARequestToTheRouteOfBrokerForTicker($arg1, $arg2, $arg3)
    {
        if ('price' === $arg1) {
            $this->response = $this->client->getPrice($arg3);
        }
        if ('recent_trade' === $arg1) {
            $this->response = $this->client->getRecentTrade($arg3, 'linear');
        }
    }

    /**
     * @When I send a request to the :arg1 route of :arg2 broker for ticker :arg3 with parameters:
     */
    public function iSendARequestToTheRouteOfBrokerForTickerWithParameters($arg1, $arg2, $arg3, PyStringNode $params)
    {
        $params = json_decode(implode('', $params->getStrings()), true);
        $from = new \DateTime($params['from']);
        $to = new \DateTime($params['to']);
        if ('price_history' === $arg1) {
            $this->response = $this->client->getHistoricalPrices($arg3, from: $from, to: $to);
        }
    }

    /**
     * @Then the response should be received
     */
    public function theResponseShouldBeReceived(): void
    {
        if (null === $this->response) {
            throw new \RuntimeException('No response received');
        }
    }

    /**
     * @Then the response should be valid
     */
    public function theResponseShouldBeValid()
    {
        assertInstanceOf(ResponseInterface::class, $this->response);
        assertNull($this->responseValidator->validate($this->response));
    }

    /**
     * @Then the response should contain an array with time an price key-value data
     */
    public function theResponseShouldContainAnArrayWithTimeAnPriceKeyValueData()
    {
        $content = $this->responseTransformer->transform($this->response, RouteName::instrument_price);
        assertArrayHasKey('price', $content);
        assertArrayHasKey('time', $content);
    }

    /**
     * @Then the response should contain an array of arrays with time an price key-value data
     */
    public function theResponseShouldContainAnArrayOfArraysWithTimeAnPriceKeyValueData()
    {
        $content = $this->responseTransformer->transform($this->response, RouteName::instrument_history_price);
        assertEquals(1000, count($content));
    }

    /**
     * @Then the response should be an array of the following structures of keys:
     */
    public function theResponseShouldBeAnArrayOfTheFollowingStructuresOfKeys(PyStringNode $string)
    {
        $content = $this->responseTransformer->transform($this->response, RouteName::recent_trade);
        $firstSample = $content[0];
        $targetStructure = json_decode(implode('', $string->getStrings()), true);
        foreach ($targetStructure as $key => $value) {
            assertArrayHasKey($key, $firstSample);
        }
    }

    /**
     * @Then the time of last trade should be close to current time with a maximum :arg1 second delta
     */
    public function theTimeOfLastTradeShouldMeCloseToCurrentTimeWithAMaximumSecondDelta($arg1)
    {
        $content = $this->responseTransformer->transform($this->response, RouteName::recent_trade);
        $firstSample = $content[0];
        $firstTime = $firstSample['timestamp'];
        assertLessThan((int) $arg1 * 1000, time() * 1000 - $firstTime); // we receive time in ms
    }
}
