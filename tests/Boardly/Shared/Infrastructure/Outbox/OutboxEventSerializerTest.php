<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Shared\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\SharedKernel\Domain\Event\DomainEvent;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Shared\Infrastructure\Outbox\OutboxEventSerializer;
use PHPUnit\Framework\TestCase;

final class OutboxEventSerializerTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';

    public function testSerializesAccountRegisteredMetadata(): void
    {
        $registeredAt = new \DateTimeImmutable('2026-05-03T10:15:30+00:00');
        $createdAt = new \DateTimeImmutable('2026-05-03T10:16:00+00:00');

        $serialized = (new OutboxEventSerializer())->serialize($this->accountRegistered($registeredAt), $createdAt);

        self::assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/',
            $serialized->eventId,
        );
        self::assertSame('identity_access.account_registered', $serialized->eventType);
        self::assertSame('identity_access.account', $serialized->aggregateType);
        self::assertSame(self::ACCOUNT_ID, $serialized->aggregateId);
        self::assertSame($registeredAt, $serialized->occurredAt);
        self::assertSame($registeredAt, $serialized->availableAt);
        self::assertSame($createdAt, $serialized->createdAt);
    }

    public function testSerializesAccountRegisteredExplicitPayload(): void
    {
        $registeredAt = new \DateTimeImmutable('2026-05-03T10:15:30+00:00');

        $serialized = (new OutboxEventSerializer())->serialize($this->accountRegistered($registeredAt));

        self::assertSame([
            'account_id' => self::ACCOUNT_ID,
            'email' => 'registered@example.com',
            'is_system_admin' => false,
            'registered_at' => '2026-05-03T10:15:30+00:00',
        ], $serialized->payload);
    }

    public function testPayloadDoesNotIncludePasswordOrSecurityData(): void
    {
        $serialized = (new OutboxEventSerializer())->serialize($this->accountRegistered());

        self::assertArrayNotHasKey('password', $serialized->payload);
        self::assertArrayNotHasKey('password_hash', $serialized->payload);
        self::assertArrayNotHasKey('refresh_token', $serialized->payload);
        self::assertArrayNotHasKey('refresh_token_hash', $serialized->payload);
        self::assertArrayNotHasKey('status', $serialized->payload);
    }

    public function testUnsupportedDomainEventThrowsClearException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported domain event');
        $this->expectExceptionMessage(UnsupportedTestDomainEvent::class);

        (new OutboxEventSerializer())->serialize(new UnsupportedTestDomainEvent());
    }

    private function accountRegistered(
        ?\DateTimeImmutable $registeredAt = null,
        bool $isSystemAdmin = false,
    ): AccountRegistered {
        return new AccountRegistered(
            AccountId::fromString(self::ACCOUNT_ID),
            Email::fromString(' Registered@Example.com '),
            $isSystemAdmin,
            $registeredAt ?? new \DateTimeImmutable('2026-05-03T10:15:30+00:00'),
        );
    }
}

final class UnsupportedTestDomainEvent implements DomainEvent
{
}
