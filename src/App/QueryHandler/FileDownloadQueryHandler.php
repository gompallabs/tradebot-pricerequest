<?php

declare(strict_types=1);

namespace App\App\QueryHandler;

use App\App\Event\FileDownloadedEvent;
use App\App\Query\FileDownloadQuery;
use App\Domain\File;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;
use App\Infra\Source\File\BybitFileDownloader;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;

#[AsMessageHandler]
class FileDownloadQueryHandler
{
    private BybitFileDownloader $bybitFileDownloader;
    private MessageBusInterface $eventBus;
    private RouterInterface $router;

    public function __construct(
        BybitFileDownloader $bybitFileDownloader,
        MessageBusInterface $eventBus,
        RouterInterface $router
    ) {
        $this->bybitFileDownloader = $bybitFileDownloader;
        $this->eventBus = $eventBus;
        $this->router = $router;
    }

    public function __invoke(FileDownloadQuery $query): array
    {
        if ($query->getExchange()->name !== 'Bybit') {
            throw new \LogicException('Unsupported exchange. Please create client configuration.'.__CLASS__);
        }

        $slug = $this->router->generate(
            name: 'file_download',
            parameters: [
            'symbol' => $query->getCoin()->getTicker(),
            ]
        );

        $downloaded = $this->bybitFileDownloader->downloadFromHtmlPage($slug, $query->getOptions());

        $files = [];
        foreach ($downloaded as $splFileInfo) {
            /* @var \SplFileInfo $splFileInfo */
            $files[] = new File(
                name: $splFileInfo->getFilename(),
                extension: $splFileInfo->getExtension(),
                path: $splFileInfo->getPath()
            );
        }

        $this->eventBus->dispatch(
            new FileDownloadedEvent(
                source: new Source(
                    exchange: $query->getExchange(),
                    sourceType: SourceType::File
                ),
                coin: $query->getCoin(),
                downloadedFiles: $files
            )
        );

        return $files;
    }
}
