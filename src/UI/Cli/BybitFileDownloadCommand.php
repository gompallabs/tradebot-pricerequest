<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\Infra\Source\File\BybitFileDownloader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Example use : bin/console app:bybit:download.
 */
#[AsCommand(name: 'app:bybit:download')]
final class BybitFileDownloadCommand extends Command
{
    private BybitFileDownloader $downloader;
    private string $backupFolder;

    public function __construct(
        BybitFileDownloader $downloader,
        string $backupFolder,
        ?string $name = 'app:bybit:download'
    ) {
        parent::__construct($name);
        $this->downloader = $downloader;
        $this->backupFolder = $backupFolder;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('instrument', InputArgument::REQUIRED, 'instrument page name?')
            ->addArgument('all', InputArgument::REQUIRED, 'download all files? [y/n] ')
            ->addArgument('latest', InputArgument::REQUIRED, 'only latest one file? [y/n]')
            ->addArgument('backup', InputArgument::REQUIRED, 'copy to backup dir? [y/n]')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = 'y' === $input->getArgument('all');
        $latest = 'y' === $input->getArgument('latest');
        $options = [
            'all' => $all,
            'latest' => $latest,
        ];

        if ('y' === $input->getArgument('backup')) {
            $options['backup'] = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.$this->backupFolder;
        }

        $downloadedFiles = $this->downloader->downloadFromHtmlPage(
            slug: '/trading/'.$input->getArgument('instrument'),
            options: $options
        );

        foreach ($downloadedFiles as $file) {
            $output->writeln('<fg=yellow>file </><fg=green>'.$file.'</> successfully downloaded');
        }

        return Command::SUCCESS;
    }
}
