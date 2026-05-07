<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Interfaces\Console;

use App\Boardly\IdentityAccess\Application\CreateSystemAdmin\CreateSystemAdminCommand;
use App\Boardly\IdentityAccess\Application\CreateSystemAdmin\CreateSystemAdminResult;
use App\Boardly\IdentityAccess\Interfaces\Console\CreateSystemAdminConsoleCommand;
use App\Shared\Application\Bus\CommandBusInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class CreateSystemAdminConsoleCommandTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string PASSWORD_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    public function testCommandSendsCreateSystemAdminCommandThroughApplicationBus(): void
    {
        $commandBus = new ConsoleFakeCommandBus($this->createResult());
        $tester = new CommandTester(new CreateSystemAdminConsoleCommand($commandBus));

        self::assertSame(Command::SUCCESS, $tester->execute($this->input()));
        self::assertInstanceOf(CreateSystemAdminCommand::class, $commandBus->dispatchedCommand);
        self::assertSame('System.Admin@Example.COM', $commandBus->dispatchedCommand->email());
        self::assertSame('plain-password', $commandBus->dispatchedCommand->plainPassword());
        self::assertSame('System Admin', $commandBus->dispatchedCommand->name());
    }

    public function testSuccessfulCommandReturnsSuccessAndPrintsSafeResult(): void
    {
        $tester = new CommandTester(new CreateSystemAdminConsoleCommand(new ConsoleFakeCommandBus($this->createResult())));

        self::assertSame(Command::SUCCESS, $tester->execute($this->input()));

        $output = $tester->getDisplay();
        self::assertStringContainsString('system admin account created', $output);
        self::assertStringContainsString(sprintf('accountId: %s', self::ACCOUNT_ID), $output);
        self::assertStringContainsString('email: system.admin@example.com', $output);
        self::assertStringContainsString('status: active', $output);
        self::assertStringContainsString('isSystemAdmin: true', $output);
    }

    public function testFailingCommandReturnsNonZeroCode(): void
    {
        $tester = new CommandTester(new CreateSystemAdminConsoleCommand(new ConsoleFailingCommandBus(
            'duplicate account',
        )));

        self::assertSame(Command::FAILURE, $tester->execute($this->input()));
        self::assertStringContainsString('Failed to create system admin account.', $tester->getDisplay());
    }

    public function testOutputDoesNotExposePlainPassword(): void
    {
        $plainPassword = 'very-secret-plain-password';
        $tester = new CommandTester(new CreateSystemAdminConsoleCommand(new ConsoleFakeCommandBus($this->createResult())));

        self::assertSame(Command::SUCCESS, $tester->execute($this->input(plainPassword: $plainPassword)));

        self::assertStringNotContainsString($plainPassword, $tester->getDisplay());
    }

    public function testFailureOutputDoesNotExposePasswordHashOrUnsafeExceptionData(): void
    {
        $plainPassword = 'plain-password-that-must-not-leak';
        $tester = new CommandTester(new CreateSystemAdminConsoleCommand(new ConsoleFailingCommandBus(sprintf(
            'unsafe %s %s token=secret',
            $plainPassword,
            self::PASSWORD_HASH,
        ))));

        self::assertSame(Command::FAILURE, $tester->execute($this->input(plainPassword: $plainPassword)));

        $output = $tester->getDisplay();
        self::assertStringContainsString('Failed to create system admin account.', $output);
        self::assertStringNotContainsString($plainPassword, $output);
        self::assertStringNotContainsString(self::PASSWORD_HASH, $output);
        self::assertStringNotContainsString('token=secret', $output);
    }

    public function testCommandDoesNotDependOnCreateSystemAdminHandlerDirectly(): void
    {
        $constructor = (new ReflectionClass(CreateSystemAdminConsoleCommand::class))->getConstructor();

        self::assertNotNull($constructor);
        self::assertCount(1, $constructor->getParameters());
        self::assertSame(CommandBusInterface::class, (string) $constructor->getParameters()[0]->getType());
    }

    /**
     * @return array<string, string>
     */
    private function input(
        string $email = 'System.Admin@Example.COM',
        string $plainPassword = 'plain-password',
        string $name = 'System Admin',
    ): array {
        return [
            'email' => $email,
            'plain-password' => $plainPassword,
            'name' => $name,
        ];
    }

    private function createResult(): CreateSystemAdminResult
    {
        return new CreateSystemAdminResult(
            accountId: self::ACCOUNT_ID,
            email: 'system.admin@example.com',
            status: 'active',
            isSystemAdmin: true,
        );
    }
}

final class ConsoleFakeCommandBus implements CommandBusInterface
{
    public ?CreateSystemAdminCommand $dispatchedCommand = null;

    public function __construct(
        private readonly mixed $result,
    ) {
    }

    public function dispatch(object $command): mixed
    {
        if (!$command instanceof CreateSystemAdminCommand) {
            throw new RuntimeException('Unexpected command type.');
        }

        $this->dispatchedCommand = $command;

        return $this->result;
    }
}

final class ConsoleFailingCommandBus implements CommandBusInterface
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
