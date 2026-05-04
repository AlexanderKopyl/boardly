<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Shared\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Infrastructure\Message\AccountRegisteredMessage;
use App\Boardly\IdentityAccess\Infrastructure\Outbox\AccountRegisteredOutboxMessageMapper;
use App\Shared\Infrastructure\Outbox\DoctrineOutbox;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializerRegistry;
use App\Shared\Infrastructure\Outbox\OutboxMessageMapperInterface;
use App\Shared\Infrastructure\Outbox\OutboxMessageMapperRegistry;
use App\Shared\Infrastructure\Outbox\OutboxPublisher;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class OutboxPublisherTest extends TestCase
{
    private const string OUTBOX_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';
    private const string EVENT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e';
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2f';

    public function testPublishesOneUnpublishedRecordAndMarksItPublished(): void
    {
        $now = new \DateTimeImmutable('2026-05-03T12:00:00+00:00');
        $connection = $this->connectionLoadingRows([$this->row(self::OUTBOX_ID)]);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(AccountRegisteredMessage::class))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));
        $connection
            ->expects(self::once())
            ->method('update')
            ->with(
                'outbox_messages',
                self::callback(static function (array $values) use ($now): bool {
                    self::assertSame($now, $values['published_at']);

                    return true;
                }),
                ['id' => self::OUTBOX_ID],
                self::anything(),
            )
            ->willReturn(1);

        $result = $this->publisher($connection, $bus)->publish(10, $now);

        self::assertSame(1, $result->selected);
        self::assertSame(1, $result->published);
        self::assertSame(0, $result->failed);
        self::assertSame(0, $result->skipped);
    }

    public function testUsesMapperRegistryToMapRecordsBeforeDispatch(): void
    {
        $connection = $this->connectionLoadingRows([$this->row(self::OUTBOX_ID)]);
        $bus = $this->createMock(MessageBusInterface::class);
        $mapper = $this->createMock(OutboxMessageMapperInterface::class);
        $mappedMessage = new \stdClass();

        $mapper
            ->expects(self::once())
            ->method('supports')
            ->willReturn(true);
        $mapper
            ->expects(self::once())
            ->method('map')
            ->willReturn($mappedMessage);
        $bus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::identicalTo($mappedMessage))
            ->willReturn(new Envelope($mappedMessage));
        $connection
            ->expects(self::once())
            ->method('update')
            ->willReturn(1);

        $result = $this->publisher($connection, $bus, new OutboxMessageMapperRegistry([$mapper]))->publish(10, $this->now());

        self::assertSame(1, $result->selected);
        self::assertSame(1, $result->published);
        self::assertSame(0, $result->failed);
        self::assertSame(0, $result->skipped);
    }

    public function testPublishesMultipleMappedRecords(): void
    {
        $connection = $this->connectionLoadingRows([
            $this->row('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c30'),
            $this->row('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c31'),
        ]);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->with(self::isInstanceOf(AccountRegisteredMessage::class))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));
        $connection
            ->expects(self::exactly(2))
            ->method('update')
            ->willReturn(1);

        $result = $this->publisher($connection, $bus)->publish(10, $this->now());

        self::assertSame(2, $result->selected);
        self::assertSame(2, $result->published);
        self::assertSame(0, $result->failed);
        self::assertSame(0, $result->skipped);
    }

    public function testMappingFailureRecordsFailureAndDoesNotMarkPublished(): void
    {
        $now = $this->now();
        $connection = $this->connectionLoadingRows([$this->row(self::OUTBOX_ID, payload: ['registered_at' => '2026-05-03T10:15:30+00:00', 'is_system_admin' => false])]);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus->expects(self::never())->method('dispatch');
        $connection->expects(self::never())->method('update');
        $connection
            ->expects(self::once())
            ->method('executeStatement')
            ->with(
                self::stringContains('UPDATE outbox_messages'),
                self::callback(static function (array $params) use ($now): bool {
                    self::assertSame(self::OUTBOX_ID, $params['id']);
                    self::assertStringContainsString('account_id', $params['last_error']);
                    self::assertEquals($now->modify('+60 seconds'), $params['available_at']);

                    return true;
                }),
                self::anything(),
            )
            ->willReturn(1);

        $result = $this->publisher($connection, $bus)->publish(10, $now);

        self::assertSame(1, $result->selected);
        self::assertSame(0, $result->published);
        self::assertSame(1, $result->failed);
        self::assertSame(0, $result->skipped);
    }

    public function testDispatchFailureRecordsFailureAndDoesNotMarkPublished(): void
    {
        $now = $this->now();
        $connection = $this->connectionLoadingRows([$this->row(self::OUTBOX_ID)]);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus
            ->expects(self::once())
            ->method('dispatch')
            ->willThrowException(new \RuntimeException('Transport unavailable'));
        $connection->expects(self::never())->method('update');
        $connection
            ->expects(self::once())
            ->method('executeStatement')
            ->with(
                self::stringContains('UPDATE outbox_messages'),
                self::callback(static function (array $params) use ($now): bool {
                    self::assertSame(self::OUTBOX_ID, $params['id']);
                    self::assertSame('Transport unavailable', $params['last_error']);
                    self::assertEquals($now->modify('+60 seconds'), $params['available_at']);

                    return true;
                }),
                self::anything(),
            )
            ->willReturn(1);

        $result = $this->publisher($connection, $bus)->publish(10, $now);

        self::assertSame(1, $result->selected);
        self::assertSame(0, $result->published);
        self::assertSame(1, $result->failed);
        self::assertSame(0, $result->skipped);
    }

    public function testMappingFailureDoesNotStopNextRecord(): void
    {
        $firstId = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c50';
        $secondId = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c51';
        $connection = $this->connectionLoadingRows([
            $this->row($firstId, payload: ['registered_at' => '2026-05-03T10:15:30+00:00', 'is_system_admin' => false]),
            $this->row($secondId),
        ]);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(AccountRegisteredMessage::class))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));
        $connection
            ->expects(self::once())
            ->method('executeStatement')
            ->willReturn(1);
        $connection
            ->expects(self::once())
            ->method('update')
            ->with('outbox_messages', self::anything(), ['id' => $secondId], self::anything())
            ->willReturn(1);

        $result = $this->publisher($connection, $bus)->publish(10, $this->now());

        self::assertSame(2, $result->selected);
        self::assertSame(1, $result->published);
        self::assertSame(1, $result->failed);
        self::assertSame(0, $result->skipped);
    }

    public function testDispatchFailureDoesNotStopNextRecord(): void
    {
        $firstId = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c40';
        $secondId = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c41';
        $connection = $this->connectionLoadingRows([$this->row($firstId), $this->row($secondId)]);
        $bus = $this->createMock(MessageBusInterface::class);
        $dispatches = 0;

        $bus
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static function (object $message) use (&$dispatches): Envelope {
                ++$dispatches;

                if (1 === $dispatches) {
                    throw new \RuntimeException('First dispatch failed');
                }

                return new Envelope($message);
            });
        $connection
            ->expects(self::once())
            ->method('executeStatement')
            ->willReturn(1);
        $connection
            ->expects(self::once())
            ->method('update')
            ->with('outbox_messages', self::anything(), ['id' => $secondId], self::anything())
            ->willReturn(1);

        $result = $this->publisher($connection, $bus)->publish(10, $this->now());

        self::assertSame(2, $result->selected);
        self::assertSame(1, $result->published);
        self::assertSame(1, $result->failed);
        self::assertSame(0, $result->skipped);
    }

    public function testLimitLessThanOneReturnsZeroResultAndDoesNotLoadRecords(): void
    {
        $connection = $this->createMock(Connection::class);
        $bus = $this->createMock(MessageBusInterface::class);

        $connection->expects(self::never())->method('createQueryBuilder');
        $connection->expects(self::never())->method('executeQuery');
        $bus->expects(self::never())->method('dispatch');

        $result = $this->publisher($connection, $bus)->publish(0, $this->now());

        self::assertSame(0, $result->selected);
        self::assertSame(0, $result->published);
        self::assertSame(0, $result->failed);
        self::assertSame(0, $result->skipped);
    }

    public function testDispatchesConcreteAccountRegisteredMessageWithMappedEventFields(): void
    {
        $occurredAt = new \DateTimeImmutable('2026-05-03T10:15:30+00:00');
        $connection = $this->connectionLoadingRows([$this->row(self::OUTBOX_ID, $occurredAt)]);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (AccountRegisteredMessage $message) use ($occurredAt): bool {
                self::assertSame(self::OUTBOX_ID, $message->outboxId);
                self::assertSame(self::EVENT_ID, $message->eventId);
                self::assertSame(self::ACCOUNT_ID, $message->accountId);
                self::assertEquals($occurredAt, $message->registeredAt);
                self::assertFalse($message->isSystemAdmin);

                return true;
            }))
            ->willReturnCallback(static fn (object $message): Envelope => new Envelope($message));
        $connection
            ->expects(self::once())
            ->method('update')
            ->willReturn(1);

        $this->publisher($connection, $bus)->publish(10, $this->now());
    }

    public function testMarkPublishedIsCalledOnlyAfterDispatchSucceeds(): void
    {
        $connection = $this->connectionLoadingRows([$this->row(self::OUTBOX_ID)]);
        $bus = $this->createMock(MessageBusInterface::class);
        $operations = [];

        $bus
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $message) use (&$operations): Envelope {
                $operations[] = 'dispatch';

                return new Envelope($message);
            });
        $connection
            ->expects(self::once())
            ->method('update')
            ->willReturnCallback(static function () use (&$operations): int {
                $operations[] = 'markPublished';

                return 1;
            });

        $this->publisher($connection, $bus)->publish(10, $this->now());

        self::assertSame(['dispatch', 'markPublished'], $operations);
    }

    public function testMapsAndDispatchesBeforeMarkingPublished(): void
    {
        $connection = $this->connectionLoadingRows([$this->row(self::OUTBOX_ID)]);
        $bus = $this->createMock(MessageBusInterface::class);
        $mapper = $this->createMock(OutboxMessageMapperInterface::class);
        $mappedMessage = new \stdClass();
        $operations = [];

        $mapper
            ->expects(self::once())
            ->method('supports')
            ->willReturn(true);
        $mapper
            ->expects(self::once())
            ->method('map')
            ->willReturnCallback(static function () use (&$operations, $mappedMessage): object {
                $operations[] = 'map';

                return $mappedMessage;
            });
        $bus
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(static function (object $message) use (&$operations): Envelope {
                $operations[] = 'dispatch';

                return new Envelope($message);
            });
        $connection
            ->expects(self::once())
            ->method('update')
            ->willReturnCallback(static function () use (&$operations): int {
                $operations[] = 'markPublished';

                return 1;
            });

        $this->publisher($connection, $bus, new OutboxMessageMapperRegistry([$mapper]))->publish(10, $this->now());

        self::assertSame(['map', 'dispatch', 'markPublished'], $operations);
    }

    public function testPublisherProductionCodeHasNoIdentityAccessDependency(): void
    {
        $publisherSource = file_get_contents(__DIR__.'/../../../../../src/Shared/Infrastructure/Outbox/OutboxPublisher.php');

        self::assertIsString($publisherSource);
        self::assertStringNotContainsString('IdentityAccess', $publisherSource);
        self::assertStringNotContainsString('AccountRegisteredMessage', $publisherSource);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return Connection&MockObject
     */
    private function connectionLoadingRows(array $rows): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $result = $this->createMock(Result::class);

        $result
            ->expects(self::once())
            ->method('fetchAllAssociative')
            ->willReturn($rows);
        $connection
            ->method('createQueryBuilder')
            ->willReturnCallback(static fn (): QueryBuilder => new QueryBuilder($connection));
        $connection
            ->expects(self::once())
            ->method('executeQuery')
            ->willReturn($result);

        return $connection;
    }

    private function publisher(
        Connection $connection,
        MessageBusInterface $bus,
        ?OutboxMessageMapperRegistry $messageMapperRegistry = null,
    ): OutboxPublisher
    {
        return new OutboxPublisher(
            new DoctrineOutbox($connection, new OutboxEventSerializerRegistry([])),
            $messageMapperRegistry ?? new OutboxMessageMapperRegistry([new AccountRegisteredOutboxMessageMapper()]),
            $bus,
        );
    }

    /**
     * @param array<string, mixed>|null $payload
     *
     * @return array<string, mixed>
     */
    private function row(
        string $id,
        ?\DateTimeImmutable $occurredAt = null,
        ?array $payload = null,
    ): array
    {
        $occurredAt ??= new \DateTimeImmutable('2026-05-03T10:15:30+00:00');
        $payload ??= [
            'account_id' => self::ACCOUNT_ID,
            'registered_at' => $occurredAt->format(\DateTimeInterface::ATOM),
            'is_system_admin' => false,
        ];

        return [
            'id' => $id,
            'event_id' => self::EVENT_ID,
            'event_type' => 'identity_access.account_registered',
            'aggregate_type' => 'identity_access.account',
            'aggregate_id' => self::ACCOUNT_ID,
            'payload' => $payload,
            'occurred_at' => $occurredAt,
            'available_at' => new \DateTimeImmutable('2026-05-03T10:15:30+00:00'),
            'published_at' => null,
            'attempts' => 0,
            'last_error' => null,
            'created_at' => new \DateTimeImmutable('2026-05-03T10:15:30+00:00'),
        ];
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-05-03T12:00:00+00:00');
    }
}
