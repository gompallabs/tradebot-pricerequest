<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\App\EnvelopeTrait;
use App\App\Query\FileDownloadQuery;
use App\Domain\Coin;
use App\Domain\Source\Category;
use App\Domain\Source\Exchange;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Example use : bin/console app:file:download.
 */
#[AsCommand(name: 'app:file:download')]
final class FileRequestCommand extends Command
{
    use EnvelopeTrait;

    private MessageBusInterface $queryBus;
    private string $backupFolder;

    public function __construct(
        MessageBusInterface $queryBus,
        string $backupFolder,
        ?string $name = 'app:file:download'
    ) {
        parent::__construct($name);
        $this->queryBus = $queryBus;
        $this->backupFolder = $backupFolder;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('exchange', InputArgument::REQUIRED, 'exchange name?')
            ->addArgument('instrument', InputArgument::REQUIRED, 'instrument page name?')
            ->addArgument('category', InputArgument::REQUIRED, 'spot or linear?')
            ->addArgument('all', InputArgument::REQUIRED, 'download all files? [y/n] ')
            ->addArgument('latest', InputArgument::REQUIRED, 'only latest one file? [y/n]')
            ->addArgument('backup', InputArgument::REQUIRED, 'copy to backup dir? [y/n]')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = Category::tryFrom($input->getArgument('category'));

        // options
        $all = $input->getArgument('all') === 'y';
        $latest = $input->getArgument('latest') === 'y';
        $options = [
            'all' => $all,
            'latest' => $latest,
        ];

        if ($input->getArgument('backup') === 'y') {
            $options['backup'] = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.$this->backupFolder;
        }

        $query = new FileDownloadQuery(
            exchange: Exchange::tryFrom($input->getArgument('exchange')),
            coin: new Coin(
                ticker: $input->getArgument('instrument'),
                category: $category->value
            ),
            options: $options
        );

        $response = $this->queryBus->dispatch($query);
        $downloadedFiles = $this->handle($response);

        if (empty($downloadedFiles) === true) {
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
