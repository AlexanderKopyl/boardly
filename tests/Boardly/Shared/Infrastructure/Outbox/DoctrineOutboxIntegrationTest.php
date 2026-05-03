<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Shared\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Infrastructure\Outbox\DoctrineOutbox;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializer;
use Doctrine\DBAL\Connection;
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
}
