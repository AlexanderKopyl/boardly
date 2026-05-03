<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Shared\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Infrastructure\Outbox\DoctrineOutbox;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineOutboxIntegrationTest extends KernelTestCase
{
    private Connection $connection;
    private DoctrineOutbox $outbox;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()->get(Connection::class);
        $this->outbox = new DoctrineOutbox($this->connection, new OutboxEventSerializer());

        $this->connection->executeQuery('SELECT 1 FROM outbox_messages WHERE 1 = 0');
        $this->connection->executeStatement('DELETE FROM outbox_messages');
    }

    protected function tearDown(): void
    {
        if (isset($this->connection)) {
            $this->connection->close();
        }

        parent::tearDown();
    }

    public function testStoreInsertsAccountRegisteredIntoOutboxMessages(): void
    {
        $registeredAt = new \DateTimeImmutable('2026-05-03T10:15:30+00:00');
        $event = $this->accountRegistered(
            '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d',
            'registered@example.com',
            $registeredAt,
            true,
        );

        $this->outbox->store([$event]);

        $rows = $this->connection->fetchAllAssociative('SELECT * FROM outbox_messages ORDER BY created_at ASC');

        self::assertCount(1, $rows);

        $row = $rows[0];
        self::assertMatchesRegularExpression($this->uuidPattern(), (string) $row['id']);
        self::assertMatchesRegularExpression($this->uuidPattern(), (string) $row['event_id']);
        self::assertSame('identity_access.account_registered', $row['event_type']);
        self::assertSame('identity_access.account', $row['aggregate_type']);
        self::assertSame('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d', $row['aggregate_id']);
        self::assertNull($row['published_at']);
        self::assertSame(0, (int) $row['attempts']);
        self::assertNull($row['last_error']);

        $payload = json_decode((string) $row['payload'], true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($payload);
        ksort($payload);
        $expectedPayload = [
            'account_id' => '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d',
            'email' => 'registered@example.com',
            'is_system_admin' => true,
            'registered_at' => '2026-05-03T10:15:30+00:00',
        ];
        ksort($expectedPayload);
        self::assertSame($expectedPayload, $payload);
        self::assertStringStartsWith('2026-05-03 10:15:30', (string) $row['occurred_at']);
        self::assertStringStartsWith('2026-05-03 10:15:30', (string) $row['available_at']);
        self::assertNotEmpty($row['created_at']);
    }

    public function testStoreInsertsMultipleEvents(): void
    {
        $this->outbox->store([
            $this->accountRegistered('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d', 'first@example.com'),
            $this->accountRegistered('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e', 'second@example.com'),
        ]);

        $rows = $this->connection->fetchAllAssociative(
            'SELECT aggregate_id, payload FROM outbox_messages ORDER BY aggregate_id ASC',
        );

        self::assertCount(2, $rows);
        self::assertSame('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d', $rows[0]['aggregate_id']);
        self::assertSame('first@example.com', json_decode((string) $rows[0]['payload'], true, 512, JSON_THROW_ON_ERROR)['email']);
        self::assertSame('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e', $rows[1]['aggregate_id']);
        self::assertSame('second@example.com', json_decode((string) $rows[1]['payload'], true, 512, JSON_THROW_ON_ERROR)['email']);
    }

    public function testLoadUnpublishedReturnsOnlyUnpublishedAvailableRecordsInDeterministicOrder(): void
    {
        $now = new \DateTimeImmutable('2026-05-03T12:00:00+00:00');
        $first = '10000000-0000-7000-8000-000000000001';
        $second = '10000000-0000-7000-8000-000000000002';

        $this->insertOutboxRecord(
            id: $second,
            aggregateId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e',
            availableAt: new \DateTimeImmutable('2026-05-03T10:00:00+00:00'),
            createdAt: new \DateTimeImmutable('2026-05-03T10:00:02+00:00'),
            payloadEmail: 'second@example.com',
        );
        $this->insertOutboxRecord(
            id: $first,
            aggregateId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d',
            availableAt: new \DateTimeImmutable('2026-05-03T10:00:00+00:00'),
            createdAt: new \DateTimeImmutable('2026-05-03T10:00:01+00:00'),
            payloadEmail: 'first@example.com',
        );
        $this->insertOutboxRecord(
            id: '10000000-0000-7000-8000-000000000003',
            aggregateId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2f',
            availableAt: new \DateTimeImmutable('2026-05-03T13:00:00+00:00'),
            createdAt: new \DateTimeImmutable('2026-05-03T10:00:00+00:00'),
            payloadEmail: 'future@example.com',
        );
        $this->insertOutboxRecord(
            id: '10000000-0000-7000-8000-000000000004',
            aggregateId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c30',
            availableAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
            createdAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
            payloadEmail: 'published@example.com',
            publishedAt: new \DateTimeImmutable('2026-05-03T11:00:00+00:00'),
        );

        $records = $this->outbox->loadUnpublished(10, $now);

        self::assertCount(2, $records);
        self::assertSame([$first, $second], array_map(static fn ($record): string => $record->id, $records));
        self::assertSame('identity_access.account_registered', $records[0]->eventType);
        self::assertSame('identity_access.account', $records[0]->aggregateType);
        self::assertSame('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d', $records[0]->aggregateId);
        self::assertSame('first@example.com', $records[0]->payload['email']);
        self::assertNull($records[0]->publishedAt);
        self::assertSame(0, $records[0]->attempts);
        self::assertNull($records[0]->lastError);
    }

    public function testLoadUnpublishedRespectsLimit(): void
    {
        $now = new \DateTimeImmutable('2026-05-03T12:00:00+00:00');

        $this->insertOutboxRecord(
            id: '10000000-0000-7000-8000-000000000001',
            aggregateId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d',
            availableAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
            createdAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
        );
        $this->insertOutboxRecord(
            id: '10000000-0000-7000-8000-000000000002',
            aggregateId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e',
            availableAt: new \DateTimeImmutable('2026-05-03T09:01:00+00:00'),
            createdAt: new \DateTimeImmutable('2026-05-03T09:01:00+00:00'),
        );

        $records = $this->outbox->loadUnpublished(1, $now);

        self::assertCount(1, $records);
        self::assertSame('10000000-0000-7000-8000-000000000001', $records[0]->id);
    }

    public function testMarkPublishedSetsPublishedAtOnlyForSelectedRow(): void
    {
        $target = '10000000-0000-7000-8000-000000000001';
        $other = '10000000-0000-7000-8000-000000000002';
        $publishedAt = new \DateTimeImmutable('2026-05-03T12:30:00+00:00');

        $this->insertOutboxRecord(
            id: $target,
            aggregateId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d',
            availableAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
            createdAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
        );
        $this->insertOutboxRecord(
            id: $other,
            aggregateId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e',
            availableAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
            createdAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
            payloadEmail: 'other@example.com',
        );

        $this->outbox->markPublished($target, $publishedAt);

        $targetRow = $this->fetchOutboxRow($target);
        $otherRow = $this->fetchOutboxRow($other);

        self::assertStringStartsWith('2026-05-03 12:30:00', (string) $targetRow['published_at']);
        self::assertSame('identity_access.account_registered', $targetRow['event_type']);
        self::assertSame('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d', $targetRow['aggregate_id']);
        self::assertSame('test@example.com', json_decode((string) $targetRow['payload'], true, 512, JSON_THROW_ON_ERROR)['email']);
        self::assertNull($otherRow['published_at']);
    }

    public function testRecordFailureIncrementsAttemptsAndStoresLastErrorWithoutChangingAvailableAtWhenNotProvided(): void
    {
        $id = '10000000-0000-7000-8000-000000000001';

        $this->insertOutboxRecord(
            id: $id,
            aggregateId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d',
            availableAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
            createdAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
            attempts: 2,
        );

        $this->outbox->recordFailure($id, 'Broker unavailable');

        $row = $this->fetchOutboxRow($id);

        self::assertSame(3, (int) $row['attempts']);
        self::assertSame('Broker unavailable', $row['last_error']);
        self::assertStringStartsWith('2026-05-03 09:00:00', (string) $row['available_at']);
        self::assertNull($row['published_at']);
    }

    public function testRecordFailureUpdatesAvailableAtOnlyWhenNextAvailableAtIsProvided(): void
    {
        $id = '10000000-0000-7000-8000-000000000001';
        $nextAvailableAt = new \DateTimeImmutable('2026-05-03T12:45:00+00:00');

        $this->insertOutboxRecord(
            id: $id,
            aggregateId: '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d',
            availableAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
            createdAt: new \DateTimeImmutable('2026-05-03T09:00:00+00:00'),
        );

        $this->outbox->recordFailure($id, 'Retry later', $nextAvailableAt);

        $row = $this->fetchOutboxRow($id);

        self::assertSame(1, (int) $row['attempts']);
        self::assertSame('Retry later', $row['last_error']);
        self::assertStringStartsWith('2026-05-03 12:45:00', (string) $row['available_at']);
        self::assertNull($row['published_at']);
    }

    private function accountRegistered(
        string $accountId,
        string $email,
        ?\DateTimeImmutable $registeredAt = null,
        bool $isSystemAdmin = false,
    ): AccountRegistered {
        return new AccountRegistered(
            AccountId::fromString($accountId),
            Email::fromString($email),
            $isSystemAdmin,
            $registeredAt ?? new \DateTimeImmutable('2026-05-03T10:15:30+00:00'),
        );
    }

    private function uuidPattern(): string
    {
        return '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/';
    }

    private function insertOutboxRecord(
        string $id,
        string $aggregateId,
        \DateTimeImmutable $availableAt,
        \DateTimeImmutable $createdAt,
        string $payloadEmail = 'test@example.com',
        ?\DateTimeImmutable $publishedAt = null,
        int $attempts = 0,
    ): void {
        $this->connection->insert('outbox_messages', [
            'id' => $id,
            'event_id' => str_replace('10000000', '20000000', $id),
            'event_type' => 'identity_access.account_registered',
            'aggregate_type' => 'identity_access.account',
            'aggregate_id' => $aggregateId,
            'payload' => [
                'account_id' => $aggregateId,
                'email' => $payloadEmail,
                'is_system_admin' => false,
                'registered_at' => '2026-05-03T10:15:30+00:00',
            ],
            'occurred_at' => new \DateTimeImmutable('2026-05-03T10:15:30+00:00'),
            'available_at' => $availableAt,
            'published_at' => $publishedAt,
            'attempts' => $attempts,
            'last_error' => null,
            'created_at' => $createdAt,
        ], [
            'id' => Types::GUID,
            'event_id' => Types::GUID,
            'event_type' => Types::STRING,
            'aggregate_type' => Types::STRING,
            'aggregate_id' => Types::STRING,
            'payload' => Types::JSON,
            'occurred_at' => Types::DATETIME_IMMUTABLE,
            'available_at' => Types::DATETIME_IMMUTABLE,
            'published_at' => Types::DATETIME_IMMUTABLE,
            'attempts' => Types::INTEGER,
            'last_error' => Types::TEXT,
            'created_at' => Types::DATETIME_IMMUTABLE,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchOutboxRow(string $id): array
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM outbox_messages WHERE id = :id',
            ['id' => $id],
            ['id' => Types::GUID],
        );

        self::assertIsArray($row);

        return $row;
    }
}
