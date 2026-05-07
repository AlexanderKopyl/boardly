<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Console;

use App\Boardly\IdentityAccess\Application\ApproveAccount\ApproveAccountCommand;
use App\Boardly\IdentityAccess\Application\ApproveAccount\ApproveAccountResult;
use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Domain\Exception\AccountAlreadyActive;
use App\Boardly\IdentityAccess\Domain\Exception\AccountNotPendingApproval;
use App\Boardly\IdentityAccess\Domain\Exception\InvalidEmail;
use App\Shared\Application\Bus\CommandBusInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'boardly:identity-access:approve-account',
    description: 'Approves an existing IdentityAccess account.',
)]
final class ApproveAccountConsoleCommand extends Command
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Account email.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $command = new ApproveAccountCommand(
                email: $this->stringArgument($input, 'email'),
            );

            $result = $this->commandBus->dispatch($command);
            if (!$result instanceof ApproveAccountResult) {
                throw new \LogicException('Approve account command bus returned an unexpected result.');
            }
        } catch (AccountNotFound|AccountAlreadyActive|AccountNotPendingApproval|InvalidEmail|\InvalidArgumentException) {
            $output->writeln('<error>Failed to approve account.</error>');

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

    private function writeSuccess(OutputInterface $output, ApproveAccountResult $result): void
    {
        $output->writeln('account approved');
        $output->writeln(sprintf('accountId: %s', $result->accountId()));
        $output->writeln(sprintf('email: %s', $result->email()));
        $output->writeln(sprintf('status: %s', $result->status()));
        $output->writeln(sprintf('approvedAt: %s', $result->approvedAt()->format(\DateTimeInterface::ATOM)));
    }
}
