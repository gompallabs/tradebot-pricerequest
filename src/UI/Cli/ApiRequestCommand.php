<?php

declare(strict_types=1);

namespace App\UI\Cli;

use App\App\EnvelopeTrait;
use App\App\Query\ApiRequestQuery;
use App\Domain\Coin;
use App\Domain\Source\Category;
use App\Domain\Source\Exchange;
use App\Domain\Source\Source;
use App\Domain\Source\SourceType;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Example use : bin/console app:api:request.
 */
#[AsCommand(name: 'app:api:request')]
final class ApiRequestCommand extends Command
{
    use EnvelopeTrait;

    private MessageBusInterface $queryBus;

    public function __construct(
        MessageBusInterface $queryBus,
        ?string $name = 'app:api:request'
    ) {
        parent::__construct($name);
        $this->queryBus = $queryBus;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('exchange', InputArgument::REQUIRED, 'exchange name?')
            ->addArgument('instrument', InputArgument::REQUIRED, 'instrument ticker?')
            ->addArgument('category', InputArgument::REQUIRED, 'spot or linear?')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $category = Category::tryFrom($input->getArgument('category'));
        $coin = new Coin(
            ticker: $input->getArgument('instrument'),
            category: $category->value
        );
        $exchange = Exchange::tryFrom($input->getArgument('exchange'));

        $source = new Source(
            exchange: $exchange,
            sourceType: SourceType::RestApi
        );

        $this->handle($this->queryBus->dispatch(new ApiRequestQuery(
            source: $source,
            coin: $coin
        )));

        return Command::SUCCESS;
    }
}
