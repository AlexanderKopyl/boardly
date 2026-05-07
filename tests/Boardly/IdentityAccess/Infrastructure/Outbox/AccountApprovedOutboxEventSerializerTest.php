<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Domain\Event\AccountApproved;
use App\Boardly\IdentityAccess\Infrastructure\Outbox\AccountApprovedOutboxEventSerializer;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\TestCase;

final class AccountApprovedOutboxEventSerializerTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';

    public function testSupportsAccountApproved(): void
    {
        self::assertTrue((new AccountApprovedOutboxEventSerializer())->supports($this->accountApproved()));
    }

    public function testSerializesAccountApprovedMetadata(): void
    {
        $approvedAt = new \DateTimeImmutable('2026-05-07T09:10:00+00:00');
        $createdAt = new \DateTimeImmutable('2026-05-07T09:11:00+00:00');

        $serialized = (new AccountApprovedOutboxEventSerializer())->serialize(
            $this->accountApproved($approvedAt),
            $createdAt,
        );

        self::assertMatchesRegularExpression(
            '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/',
            $serialized->eventId,
        );
        self::assertSame('identity_access.account_approved', $serialized->eventType);
        self::assertSame('identity_access.account', $serialized->aggregateType);
        self::assertSame(self::ACCOUNT_ID, $serialized->aggregateId);
        self::assertSame($approvedAt, $serialized->occurredAt);
        self::assertSame($approvedAt, $serialized->availableAt);
        self::assertSame($createdAt, $serialized->createdAt);
    }

    public function testSerializesExpectedPayload(): void
    {
        $serialized = (new AccountApprovedOutboxEventSerializer())->serialize(
            $this->accountApproved(new \DateTimeImmutable('2026-05-07T09:10:00+00:00')),
        );

        self::assertSame([
            'account_id' => self::ACCOUNT_ID,
            'approved_at' => '2026-05-07T09:10:00+00:00',
        ], $serialized->payload);
    }

    public function testPayloadDoesNotIncludeCredentialsTokensOrUnsafeData(): void
    {
        $serialized = (new AccountApprovedOutboxEventSerializer())->serialize($this->accountApproved());

        self::assertArrayNotHasKey('email', $serialized->payload);
        self::assertArrayNotHasKey('password', $serialized->payload);
        self::assertArrayNotHasKey('password_hash', $serialized->payload);
        self::assertArrayNotHasKey('refresh_token', $serialized->payload);
        self::assertArrayNotHasKey('refresh_token_hash', $serialized->payload);
        self::assertArrayNotHasKey('status', $serialized->payload);
    }

    private function accountApproved(?\DateTimeImmutable $approvedAt = null): AccountApproved
    {
        return new AccountApproved(
            AccountId::fromString(self::ACCOUNT_ID),
            $approvedAt ?? new \DateTimeImmutable('2026-05-07T09:10:00+00:00'),
        );
    }
}
