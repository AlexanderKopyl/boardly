<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Outbox\Command;

use App\Shared\Infrastructure\Outbox\OutboxPublisher;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'boardly:outbox:publish',
    description: 'Publishes pending outbox records.',
)]
final class PublishOutboxCommand extends Command
{
    private const int DEFAULT_LIMIT = 100;

    public function __construct(
        private readonly OutboxPublisher $publisher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'limit',
            null,
            InputOption::VALUE_REQUIRED,
            'Maximum number of outbox records to publish.',
            self::DEFAULT_LIMIT,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $limit = $this->parseLimit($input->getOption('limit'));

        if (null === $limit) {
            $output->writeln('<error>The "--limit" option must be an integer greater than or equal to 1.</error>');

            return Command::FAILURE;
        }

        try {
            $result = $this->publisher->publish($limit);
        } catch (\Throwable $exception) {
            $output->writeln(sprintf('<error>Failed to publish outbox records: %s</error>', $exception->getMessage()));

            return Command::FAILURE;
        }

        $output->writeln(sprintf('selected: %d', $result->selected));
        $output->writeln(sprintf('published: %d', $result->published));
        $output->writeln(sprintf('failed: %d', $result->failed));
        $output->writeln(sprintf('skipped: %d', $result->skipped));

        return Command::SUCCESS;
    }

    private function parseLimit(mixed $rawLimit): ?int
    {
        if (!is_int($rawLimit) && !is_string($rawLimit)) {
            return null;
        }

        $limit = filter_var($rawLimit, FILTER_VALIDATE_INT);

        if (false === $limit || $limit < 1) {
            return null;
        }

        return $limit;
    }
}
