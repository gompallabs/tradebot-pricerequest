<?php

declare(strict_types=1);

namespace App\Tests\Functionnal;

use App\App\Event\ApiRequestEvent;
use App\App\Event\FileDownloadedEvent;
use App\App\Query\ApiRequestQuery;
use App\App\Query\FileDownloadQuery;
use App\App\QueryHandler\ApiRequestQueryHandler;
use App\App\QueryHandler\FileDownloadQueryHandler;
use App\Domain\Coin;
use App\Domain\Source\Exchange;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;
use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

use function PHPUnit\Framework\assertArrayHasKey;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertInstanceOf;
use function PHPUnit\Framework\assertNotEmpty;
use function PHPUnit\Framework\assertObjectHasProperty;

class MessengerContext implements Context
{
    private ApiRequestQueryHandler $apiRequestQueryHandler;
    private FileDownloadQueryHandler $fileDownloadQueryHandler;
    private KernelInterface $kernel;
    private FileDownloadedEvent|ApiRequestEvent|null $message = null;

    public function __construct(
        ApiRequestQueryHandler $apiRequestQueryHandler,
        FileDownloadQueryHandler $fileDownloadQueryHandler,
        KernelInterface $kernel
    ) {
        $this->apiRequestQueryHandler = $apiRequestQueryHandler;
        $this->fileDownloadQueryHandler = $fileDownloadQueryHandler;
        $this->kernel = $kernel;
    }

    // #################################### Messenger Context ############################## //
    /**
     * @Given I download the :arg1 first file for :arg2 instrument :arg3 of :arg4 page
     */
    public function iDownloadTheFirstFileForInstrumentOfPage($arg1, $arg2, $arg3, $arg4)
    {
        // *  'all' => true / false,
        // *  'latest' => true / false,
        // *  'date' => ?\Datetime,
        // *  'filter' => [
        // *     'first' => n,
        // *     'last' => p
        // *    ],
        // *   'backup' => true.
        $downloadedFiles = [];
        $query = new FileDownloadQuery(
            exchange: Exchange::tryFrom($arg4),
            coin: new Coin(ticker: $arg3, category: $arg2),
            options: [
                'all' => false,
                'latest' => false,
                'filter' => [
                    'first' => (int) $arg1,
                ],
                'backup' => true,
            ]
        );

        $downloadedFiles = $this->fileDownloadQueryHandler->__invoke($query);
        assertNotEmpty($downloadedFiles);
    }

    /**
     * @Then a :arg1 event should be dispatched to the :arg2 queue
     */
    public function aEventShouldBeDispatchedToTheQueue($arg1, $arg2)
    {
        $testContainer = $this->kernel->getContainer()->get('test.service_container');
        $transport = $testContainer->get('messenger.transport.processing');
        assertInstanceOf(TransportInterface::class, $transport);
        $queueContent = $transport->get();
        assertCount(1, $queueContent);
        $message = $queueContent[0]->getMessage();
        if ($arg1 === 'FileDownloadedEvent') {
            assertInstanceOf(FileDownloadedEvent::class, $message);
        } elseif ($arg1 === 'ApiRequestEvent') {
            assertInstanceOf(ApiRequestEvent::class, $message);
        } else {
            throw new \RuntimeException();
        }
        $this->message = $message;
    }

    /**
     * @Then the event should contain a coin array with data:
     */
    public function theEventShouldContainACoinArrayWithData(PyStringNode $string)
    {
        $coinArray = json_decode(implode('', $string->getStrings()), true);
        $message = $this->message;
        $coin = $message->getCoin()->toArray();

        assertEquals($coin, $coinArray);
    }

    /**
     * @Then the event should contain the exchange name :arg1
     */
    public function theEventShouldContainTheExchangeName($arg1)
    {
        $message = $this->message;
        assertEquals($arg1, $message->getSource()->getExchange()->name);
    }

    /**
     * @Then the event should contain an array of arrays with the following structure of keys:
     */
    public function theEventShouldContainAnArrayOfArraysWithTheFollowingStructureOfKeys(PyStringNode $string)
    {
        $message = $this->message;
        $data = $message->getData();
        $sample = array_shift($data);
        $inputs = implode('', $string->getStrings());
        $keys = array_keys(json_decode($inputs, true));
        foreach ($keys as $key) {
            assertArrayHasKey($key, $sample);
        }
    }

    /**
     * @Then the event should contain a File entity with properties name, extension and path
     */
    public function theEventShouldContaineAFileEntityWithPropertiesNameExtensionAndPath()
    {
        $message = $this->message;
        $file = $message->getFiles()[0];
        assertObjectHasProperty('name', $file);
        assertObjectHasProperty('extension', $file);
        assertObjectHasProperty('path', $file);
    }

    /**
     * @Given I request the :arg1 for :arg2 instrument :arg3 of :arg4 exchange
     */
    public function iRequestTheForInstrumentOfExchange($arg1, $arg2, $arg3, $arg4)
    {
        $query = new ApiRequestQuery(
            source: new Source(
                exchange: Exchange::tryFrom($arg4),
                sourceType: SourceType::RestApi
            ),
            coin: new Coin(
                ticker: $arg3,
                category: $arg2,
            ),
        );

        $this->apiRequestQueryHandler->__invoke($query);
    }

    // #################################### Messenger Context ############################## //
}
