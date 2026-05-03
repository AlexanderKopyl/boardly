<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Shared\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Infrastructure\Outbox\AccountRegisteredOutboxEventSerializer;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Infrastructure\Outbox\DoctrineOutbox;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

final class DoctrineOutboxTest extends TestCase
{
    public function testStoreInsertsOneRowPerEventWithoutTransactionControl(): void
    {
        $events = [
            $this->accountRegistered('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d', 'first@example.com'),
            $this->accountRegistered('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2e', 'second@example.com'),
        ];
        $connection = $this->createMock(Connection::class);

        $connection
            ->expects(self::exactly(2))
            ->method('insert')
            ->with(
                'outbox_messages',
                self::callback(static function (array $row): bool {
                    self::assertArrayHasKey('id', $row);
                    self::assertArrayHasKey('event_id', $row);
                    self::assertSame('identity_access.account_registered', $row['event_type']);
                    self::assertSame('identity_access.account', $row['aggregate_type']);
                    self::assertIsString($row['aggregate_id']);
                    self::assertIsArray($row['payload']);
                    self::assertNull($row['published_at']);
                    self::assertSame(0, $row['attempts']);
                    self::assertNull($row['last_error']);

                    return true;
                }),
                self::callback(static function (array $types): bool {
                    self::assertSame(Types::JSON, $types['payload']);
                    self::assertSame(Types::DATETIME_IMMUTABLE, $types['occurred_at']);
                    self::assertSame(Types::DATETIME_IMMUTABLE, $types['available_at']);
                    self::assertSame(Types::DATETIME_IMMUTABLE, $types['created_at']);

                    return true;
                }),
            );
        $connection->expects(self::never())->method('beginTransaction');
        $connection->expects(self::never())->method('commit');
        $connection->expects(self::never())->method('rollBack');

        (new DoctrineOutbox(
            $connection,
            new OutboxEventSerializerRegistry([new AccountRegisteredOutboxEventSerializer()]),
        ))->store($events);
    }

    private function accountRegistered(string $accountId, string $email): AccountRegistered
    {
        return new AccountRegistered(
            AccountId::fromString($accountId),
            Email::fromString($email),
            false,
            new \DateTimeImmutable('2026-05-03T10:15:30+00:00'),
        );
    }
}
