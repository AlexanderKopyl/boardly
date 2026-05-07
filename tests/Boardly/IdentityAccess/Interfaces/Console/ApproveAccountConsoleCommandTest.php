<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Interfaces\Console;

use App\Boardly\IdentityAccess\Application\ApproveAccount\ApproveAccountCommand;
use App\Boardly\IdentityAccess\Application\ApproveAccount\ApproveAccountHandler;
use App\Boardly\IdentityAccess\Application\ApproveAccount\ApproveAccountResult;
use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Interfaces\Console\ApproveAccountConsoleCommand;
use App\Shared\Application\Bus\CommandBusInterface;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ApproveAccountConsoleCommandTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string PASSWORD_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    public function testCommandSendsApproveAccountCommandThroughApplicationBus(): void
    {
        $commandBus = new ApproveAccountConsoleFakeCommandBus($this->approveResult());
        $tester = new CommandTester(new ApproveAccountConsoleCommand($commandBus));

        self::assertSame(Command::SUCCESS, $tester->execute($this->input()));
        self::assertInstanceOf(ApproveAccountCommand::class, $commandBus->dispatchedCommand);
        self::assertSame('Registered.Account@Example.COM', $commandBus->dispatchedCommand->email());
    }

    public function testSuccessfulCommandReturnsSuccessAndPrintsSafeResult(): void
    {
        $tester = new CommandTester(new ApproveAccountConsoleCommand(new ApproveAccountConsoleFakeCommandBus(
            $this->approveResult(),
        )));

        self::assertSame(Command::SUCCESS, $tester->execute($this->input()));

        $output = $tester->getDisplay();
        self::assertStringContainsString('account approved', $output);
        self::assertStringContainsString(sprintf('accountId: %s', self::ACCOUNT_ID), $output);
        self::assertStringContainsString('email: registered.account@example.com', $output);
        self::assertStringContainsString('status: active', $output);
        self::assertStringContainsString('approvedAt: 2026-05-07T09:10:00+00:00', $output);
    }

    public function testExpectedFailureReturnsNonZeroCode(): void
    {
        $tester = new CommandTester(new ApproveAccountConsoleCommand(new ApproveAccountConsoleExpectedFailingCommandBus(
            new AccountNotFound('unsafe account missing token=secret'),
        )));

        self::assertSame(Command::FAILURE, $tester->execute($this->input()));
        self::assertStringContainsString('Failed to approve account.', $tester->getDisplay());
    }

    public function testUnexpectedExceptionIsNotSwallowed(): void
    {
        $tester = new CommandTester(new ApproveAccountConsoleCommand(new ApproveAccountConsoleUnexpectedFailingCommandBus(
            'database connection failed token=secret',
        )));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('database connection failed token=secret');

        $tester->execute($this->input());
    }

    public function testFailureOutputDoesNotExposeSecretsStackTracesEnvValuesOrInfrastructureDetails(): void
    {
        $tester = new CommandTester(new ApproveAccountConsoleCommand(new ApproveAccountConsoleExpectedFailingCommandBus(
            new AccountNotFound(sprintf(
                'unsafe %s token=secret APP_ENV=prod Doctrine\\DBAL\\Connection #0 /var/app',
                self::PASSWORD_HASH,
            )),
        )));

        self::assertSame(Command::FAILURE, $tester->execute($this->input()));

        $output = $tester->getDisplay();
        self::assertStringContainsString('Failed to approve account.', $output);
        self::assertStringNotContainsString(self::PASSWORD_HASH, $output);
        self::assertStringNotContainsString('token=secret', $output);
        self::assertStringNotContainsString('APP_ENV=prod', $output);
        self::assertStringNotContainsString('Doctrine\\DBAL\\Connection', $output);
        self::assertStringNotContainsString('#0 /var/app', $output);
    }

    public function testSuccessOutputDoesNotExposeSecretsTokensEnvValuesOrInfrastructureDetails(): void
    {
        $tester = new CommandTester(new ApproveAccountConsoleCommand(new ApproveAccountConsoleFakeCommandBus(
            $this->approveResult(),
        )));

        self::assertSame(Command::SUCCESS, $tester->execute($this->input()));

        $output = $tester->getDisplay();
        self::assertStringNotContainsString(self::PASSWORD_HASH, $output);
        self::assertStringNotContainsString('token=', $output);
        self::assertStringNotContainsString('APP_ENV=', $output);
        self::assertStringNotContainsString('Doctrine\\DBAL\\Connection', $output);
    }

    public function testCommandDoesNotDependOnApproveAccountHandlerDirectly(): void
    {
        $constructor = (new ReflectionClass(ApproveAccountConsoleCommand::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertCount(1, $constructor->getParameters());
        self::assertSame(CommandBusInterface::class, (string) $constructor->getParameters()[0]->getType());

        foreach ($constructor->getParameters() as $parameter) {
            self::assertNotSame(ApproveAccountHandler::class, (string) $parameter->getType());
        }
    }

    /**
     * @return array<string, string>
     */
    private function input(string $email = 'Registered.Account@Example.COM'): array
    {
        return [
            'email' => $email,
        ];
    }

    private function approveResult(): ApproveAccountResult
    {
        return new ApproveAccountResult(
            accountId: self::ACCOUNT_ID,
            email: 'registered.account@example.com',
            status: 'active',
            approvedAt: new DateTimeImmutable('2026-05-07T09:10:00+00:00'),
        );
    }
}

final class ApproveAccountConsoleFakeCommandBus implements CommandBusInterface
{
    public ?ApproveAccountCommand $dispatchedCommand = null;

    public function __construct(
        private readonly mixed $result,
    ) {
    }

    public function dispatch(object $command): mixed
    {
        if (!$command instanceof ApproveAccountCommand) {
            throw new RuntimeException('Unexpected command type.');
        }

        $this->dispatchedCommand = $command;

        return $this->result;
    }
}

final class ApproveAccountConsoleExpectedFailingCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly \Throwable $exception,
    ) {
    }

    public function dispatch(object $command): mixed
    {
        throw $this->exception;
    }
}

final class ApproveAccountConsoleUnexpectedFailingCommandBus implements CommandBusInterface
{
    public function __construct(
        private readonly string $exceptionMessage,
    ) {
    }

    public function dispatch(object $command): mixed
    {
        throw new RuntimeException($this->exceptionMessage);
    }
}
