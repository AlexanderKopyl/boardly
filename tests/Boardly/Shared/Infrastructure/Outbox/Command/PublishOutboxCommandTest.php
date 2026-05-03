<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Shared\Infrastructure\Outbox\Command;

use App\Shared\Infrastructure\Outbox\Command\PublishOutboxCommand;
use App\Shared\Infrastructure\Outbox\DoctrineOutbox;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializer;
use App\Shared\Infrastructure\Outbox\OutboxMessage;
use App\Shared\Infrastructure\Outbox\OutboxPublisher;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class PublishOutboxCommandTest extends TestCase
{
    public function testPublishesWithDefaultLimit(): void
    {
        $queryBuilder = null;
        $connection = $this->connectionLoadingRows([], $queryBuilder);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $tester = new CommandTester($this->command($connection, $bus));

        self::assertSame(Command::SUCCESS, $tester->execute([]));
        self::assertInstanceOf(QueryBuilder::class, $queryBuilder);
        self::assertSame(100, $queryBuilder->getMaxResults());
    }

    public function testPublishesWithCustomLimit(): void
    {
        $queryBuilder = null;
        $connection = $this->connectionLoadingRows([], $queryBuilder);
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects(self::never())->method('dispatch');

        $tester = new CommandTester($this->command($connection, $bus));

        self::assertSame(Command::SUCCESS, $tester->execute(['--limit' => '25']));
        self::assertInstanceOf(QueryBuilder::class, $queryBuilder);
        self::assertSame(25, $queryBuilder->getMaxResults());
    }

    public function testPrintsSelectedPublishedFailedAndSkippedCounts(): void
    {
        $queryBuilder = null;
        $connection = $this->connectionLoadingRows([$this->row()], $queryBuilder);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(OutboxMessage::class))
            ->willReturnCallback(static fn (OutboxMessage $message): Envelope => new Envelope($message));
        $connection
            ->expects(self::once())
            ->method('update')
            ->willReturn(1);

        $tester = new CommandTester($this->command($connection, $bus));

        self::assertSame(Command::SUCCESS, $tester->execute([]));

        $output = $tester->getDisplay();
        self::assertStringContainsString('selected: 1', $output);
        self::assertStringContainsString('published: 1', $output);
        self::assertStringContainsString('failed: 0', $output);
        self::assertStringContainsString('skipped: 0', $output);
    }

    public function testInvalidZeroLimitFailsAndDoesNotPublish(): void
    {
        $connection = $this->createMock(Connection::class);
        $bus = $this->createMock(MessageBusInterface::class);

        $connection->expects(self::never())->method('createQueryBuilder');
        $bus->expects(self::never())->method('dispatch');

        $tester = new CommandTester($this->command($connection, $bus));

        self::assertSame(Command::FAILURE, $tester->execute(['--limit' => '0']));
        self::assertStringContainsString('The "--limit" option must be an integer greater than or equal to 1.', $tester->getDisplay());
    }

    public function testInvalidNonIntegerLimitFailsAndDoesNotPublish(): void
    {
        $connection = $this->createMock(Connection::class);
        $bus = $this->createMock(MessageBusInterface::class);

        $connection->expects(self::never())->method('createQueryBuilder');
        $bus->expects(self::never())->method('dispatch');

        $tester = new CommandTester($this->command($connection, $bus));

        self::assertSame(Command::FAILURE, $tester->execute(['--limit' => 'not-an-integer']));
        self::assertStringContainsString('The "--limit" option must be an integer greater than or equal to 1.', $tester->getDisplay());
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return Connection&MockObject
     */
    private function connectionLoadingRows(array $rows, ?QueryBuilder &$capturedQueryBuilder): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $result = $this->createMock(Result::class);

        $result
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn($rows);
        $connection
            ->method('createQueryBuilder')
            ->willReturnCallback(static function () use ($connection, &$capturedQueryBuilder): QueryBuilder {
                $capturedQueryBuilder = new QueryBuilder($connection);

                return $capturedQueryBuilder;
            });
        $connection
            ->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result);

        return $connection;
    }

    private function command(Connection $connection, MessageBusInterface $bus): PublishOutboxCommand
    {
        return new PublishOutboxCommand(new OutboxPublisher(
            new DoctrineOutbox($connection, new OutboxEventSerializer()),
            $bus,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function row(): array
    {
        $now = new \DateTimeImmutable('2026-05-03T10:15:30+00:00');

        return [
            'id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d',
            'event_id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e',
            'event_type' => 'identity_access.account_registered',
            'aggregate_type' => 'identity_access.account',
            'aggregate_id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2f',
            'payload' => ['account_id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2f'],
            'occurred_at' => $now,
            'available_at' => $now,
            'published_at' => null,
            'attempts' => 0,
            'last_error' => null,
            'created_at' => $now,
        ];
    }
}
