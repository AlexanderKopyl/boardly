<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Infrastructure\Outbox\AccountRegisteredOutboxEventSerializer;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class AccountRegisteredOutboxEventSerializerTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';

    public function testSupportsAccountRegistered(): void
    {
        self::assertTrue((new AccountRegisteredOutboxEventSerializer())->supports($this->accountRegistered()));
    }

    public function testSerializesAccountRegisteredMetadata(): void
    {
        $registeredAt = new \DateTimeImmutable('2026-05-03T10:15:30+00:00');
        $createdAt = new \DateTimeImmutable('2026-05-03T10:16:00+00:00');

        $serialized = (new AccountRegisteredOutboxEventSerializer())->serialize(
            $this->accountRegistered($registeredAt, true),
            $createdAt,
        );

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

    public function testSerializesExpectedPayload(): void
    {
        $serialized = (new AccountRegisteredOutboxEventSerializer())->serialize(
            $this->accountRegistered(new \DateTimeImmutable('2026-05-03T10:15:30+00:00'), true),
        );

        self::assertSame([
            'account_id' => self::ACCOUNT_ID,
            'email' => 'registered@example.com',
            'is_system_admin' => true,
            'registered_at' => '2026-05-03T10:15:30+00:00',
        ], $serialized->payload);
    }

    public function testPayloadDoesNotIncludePasswordOrSecurityData(): void
    {
        $serialized = (new AccountRegisteredOutboxEventSerializer())->serialize($this->accountRegistered());

        self::assertArrayNotHasKey('password', $serialized->payload);
        self::assertArrayNotHasKey('password_hash', $serialized->payload);
        self::assertArrayNotHasKey('refresh_token', $serialized->payload);
        self::assertArrayNotHasKey('refresh_token_hash', $serialized->payload);
        self::assertArrayNotHasKey('status', $serialized->payload);
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
