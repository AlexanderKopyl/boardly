<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Shared\Infrastructure\Outbox;

use App\Shared\Infrastructure\Outbox\DoctrineOutbox;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializer;
use App\Shared\Infrastructure\Outbox\OutboxMessage;
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
            ->with(self::isInstanceOf(OutboxMessage::class))
            ->willReturnCallback(static fn (OutboxMessage $message): Envelope => new Envelope($message));
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

    public function testPublishesMultipleRecords(): void
    {
        $connection = $this->connectionLoadingRows([
            $this->row('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c30'),
            $this->row('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c31'),
        ]);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnCallback(static fn (OutboxMessage $message): Envelope => new Envelope($message));
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
            ->willReturnCallback(static function (OutboxMessage $message) use (&$dispatches): Envelope {
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

    public function testMessageWrapperContainsExpectedPersistedEventFields(): void
    {
        $occurredAt = new \DateTimeImmutable('2026-05-03T10:15:30+00:00');
        $connection = $this->connectionLoadingRows([$this->row(self::OUTBOX_ID, $occurredAt)]);
        $bus = $this->createMock(MessageBusInterface::class);

        $bus
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (OutboxMessage $message) use ($occurredAt): bool {
                self::assertSame(self::OUTBOX_ID, $message->outboxId);
                self::assertSame(self::EVENT_ID, $message->eventId);
                self::assertSame('identity_access.account_registered', $message->eventType);
                self::assertSame('identity_access.account', $message->aggregateType);
                self::assertSame(self::ACCOUNT_ID, $message->aggregateId);
                self::assertSame(['account_id' => self::ACCOUNT_ID], $message->payload);
                self::assertEquals($occurredAt, $message->occurredAt);

                return true;
            }))
            ->willReturnCallback(static fn (OutboxMessage $message): Envelope => new Envelope($message));
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
            ->willReturnCallback(static function (OutboxMessage $message) use (&$operations): Envelope {
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

    private function publisher(Connection $connection, MessageBusInterface $bus): OutboxPublisher
    {
        return new OutboxPublisher(new DoctrineOutbox($connection, new OutboxEventSerializer()), $bus);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(string $id, ?\DateTimeImmutable $occurredAt = null): array
    {
        $occurredAt ??= new \DateTimeImmutable('2026-05-03T10:15:30+00:00');

        return [
            'id' => $id,
            'event_id' => self::EVENT_ID,
            'event_type' => 'identity_access.account_registered',
            'aggregate_type' => 'identity_access.account',
            'aggregate_id' => self::ACCOUNT_ID,
            'payload' => ['account_id' => self::ACCOUNT_ID],
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
