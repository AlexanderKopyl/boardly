<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Console;

use App\Boardly\IdentityAccess\Application\CreateSystemAdmin\CreateSystemAdminCommand;
use App\Boardly\IdentityAccess\Application\CreateSystemAdmin\CreateSystemAdminResult;
use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'boardly:identity-access:create-system-admin',
    description: 'Creates a system admin account.',
)]
final class CreateSystemAdminConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'System admin email.')
            ->addArgument('plain-password', InputArgument::REQUIRED, 'System admin plain password.')
            ->addArgument('name', InputArgument::REQUIRED, 'System admin name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $command = new CreateSystemAdminCommand(
                email: $this->stringArgument($input, 'email'),
                plainPassword: $this->stringArgument($input, 'plain-password'),
                name: $this->stringArgument($input, 'name'),
            );

            $result = $this->commandBus->dispatch($command);
            if (!$result instanceof CreateSystemAdminResult) {
                throw new \LogicException(sprintf(
                    'Expected %s from create system admin command bus dispatch.',
                    CreateSystemAdminResult::class,
                ));
            }
        } catch (\Throwable) {
            $output->writeln('<error>Failed to create system admin account.</error>');

            return Command::FAILURE;
        }

        $this->writeSuccess($output, $result);

        return Command::SUCCESS;
    }

    private function stringArgument(InputInterface $input, string $name): string
    {
        $value = $input->getArgument($name);

        if (!is_string($value)) {
            throw new \InvalidArgumentException(sprintf('The "%s" argument must be a string.', $name));
        }

        return $value;
    }

    private function writeSuccess(OutputInterface $output, CreateSystemAdminResult $result): void
    {
        $output->writeln('system admin account created');
        $output->writeln(sprintf('accountId: %s', $result->accountId()));
        $output->writeln(sprintf('email: %s', $result->email()));
        $output->writeln(sprintf('status: %s', $result->status()));
        $output->writeln(sprintf('isSystemAdmin: %s', $result->isSystemAdmin() ? 'true' : 'false'));
    }
}
