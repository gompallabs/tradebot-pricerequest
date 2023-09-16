<?php

declare(strict_types=1);

namespace App\Tests\Functionnal;

use App\Domain\Source\Api\ClientBuilder;
use App\Domain\Source\SourceType;
use App\Infra\Source\Api\BybitApiClient;
use App\Infra\Source\Api\BybitApiClientResponseTransformer;
use App\Infra\Source\Api\BybitApiReponseValidator;
use App\Infra\Source\Api\RouteName;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertGreaterThanOrEqual;
use function PHPUnit\Framework\assertNull;

final class RecentTradesContext implements Context
{
    private ClientBuilder $clientBuilder;
    private ?BybitApiClient $client = null;

    private array|\ArrayIterator $responseContent = [];
    private BybitApiReponseValidator $apiReponseValidator;
    private BybitApiClientResponseTransformer $transformer;

    public function __construct(
        ClientBuilder $clientBuilder,
        BybitApiReponseValidator $apiReponseValidator,
        BybitApiClientResponseTransformer $transformer,
    ) {
        $this->clientBuilder = $clientBuilder;
        $this->apiReponseValidator = $apiReponseValidator;
        $this->transformer = $transformer;
    }

    /**
     * @Given I have a Bybit api client ready
     */
    public function iHaveABybitApiClientReady(): void
    {
        $this->client = $this->clientBuilder->getClientForSource(type: SourceType::RestApi, name: 'Bybit');
    }

    /**
     * @Given I send a request to the :arg1 route of :arg2 broker for ticker :arg3 of category :arg4
     */
    public function iSendARequestToTheRouteOfBrokerForTickerOfCategory($arg1, $arg2, $arg3, $arg4)
    {
        $response = $this->client->getRecentTrade(ticker: $arg3, category: $arg4);
        $error = $this->apiReponseValidator->validate($response);
        assertNull($error);
        $responseContent = $this->transformer->transform($response, RouteName::tryfrom($arg1));
        assertGreaterThanOrEqual(10, $responseContent->count());
        $this->responseContent = $responseContent;
    }

    /**
     * @Then the response should have the following structures of keys:
     */
    public function theResponseShouldHaveTheFollowingStructuresOfKeys(PyStringNode $string)
    {
        $content = $this->responseContent;
        $firstSample = $content[0];
        $targetStructure = json_decode(implode('', $string->getStrings()), true);
        foreach ($targetStructure as $key => $value) {
            assertArrayHasKey($key, $firstSample);
        }
    }
}
