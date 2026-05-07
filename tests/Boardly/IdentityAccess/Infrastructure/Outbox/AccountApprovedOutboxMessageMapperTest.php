<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Infrastructure\Message\AccountApprovedMessage;
use App\Boardly\IdentityAccess\Infrastructure\Outbox\AccountApprovedOutboxMessageMapper;
use App\Shared\Infrastructure\Outbox\OutboxRecord;
use PHPUnit\Framework\TestCase;

final class AccountApprovedOutboxMessageMapperTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';

    public function testSupportsAccountApprovedOutboxRecord(): void
    {
        self::assertTrue((new AccountApprovedOutboxMessageMapper())->supports($this->outboxRecord()));
    }

    public function testDoesNotSupportAnotherEventType(): void
    {
        self::assertFalse((new AccountApprovedOutboxMessageMapper())->supports(
            $this->outboxRecord(eventType: 'identity_access.account_registered'),
        ));
    }

    public function testMapsAccountApprovedOutboxRecord(): void
    {
        $message = (new AccountApprovedOutboxMessageMapper())->map($this->outboxRecord());

        self::assertInstanceOf(AccountApprovedMessage::class, $message);
        self::assertSame('outbox-record-id', $message->outboxId);
        self::assertSame('event-id', $message->eventId);
        self::assertSame(self::ACCOUNT_ID, $message->accountId);
        self::assertEquals(new \DateTimeImmutable('2026-05-07T09:10:00+00:00'), $message->approvedAt);
    }

    public function testDoesNotExposeCredentialsTokensOrUnsafeData(): void
    {
        $message = (new AccountApprovedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'account_id' => self::ACCOUNT_ID,
            'approved_at' => '2026-05-07T09:10:00+00:00',
            'email' => 'approved@example.com',
            'password' => 'secret',
            'password_hash' => 'hashed-secret',
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'refresh_token_hash' => 'refresh-token-hash',
            'status' => 'active',
        ]));

        $properties = get_object_vars($message);

        self::assertArrayNotHasKey('email', $properties);
        self::assertArrayNotHasKey('password', $properties);
        self::assertArrayNotHasKey('password_hash', $properties);
        self::assertArrayNotHasKey('access_token', $properties);
        self::assertArrayNotHasKey('refresh_token', $properties);
        self::assertArrayNotHasKey('refresh_token_hash', $properties);
        self::assertArrayNotHasKey('status', $properties);
    }

    public function testRejectsMissingAccountId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "account_id" must be a non-empty string');

        (new AccountApprovedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'approved_at' => '2026-05-07T09:10:00+00:00',
        ]));
    }

    public function testRejectsEmptyAccountId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "account_id" must be a non-empty string');

        (new AccountApprovedOutboxMessageMapper())->map($this->outboxRecord(
            aggregateId: null,
            payload: [
                'account_id' => '',
                'approved_at' => '2026-05-07T09:10:00+00:00',
            ],
        ));
    }

    public function testRejectsNonStringAccountId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "account_id" must be a non-empty string');

        (new AccountApprovedOutboxMessageMapper())->map($this->outboxRecord(
            aggregateId: null,
            payload: [
                'account_id' => 123,
                'approved_at' => '2026-05-07T09:10:00+00:00',
            ],
        ));
    }

    public function testRejectsMissingApprovedAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "approved_at" must be a valid date/time string');

        (new AccountApprovedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'account_id' => self::ACCOUNT_ID,
        ]));
    }

    public function testRejectsInvalidApprovedAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "approved_at" must be a valid date/time string');

        (new AccountApprovedOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'account_id' => self::ACCOUNT_ID,
            'approved_at' => 'not-a-date',
        ]));
    }

    public function testRejectsAggregateIdMismatchWhenAggregateIdIsPresent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('aggregateId "different-account-id" must match payload account_id');

        (new AccountApprovedOutboxMessageMapper())->map($this->outboxRecord(aggregateId: 'different-account-id'));
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function outboxRecord(
        string $eventType = 'identity_access.account_approved',
        ?string $aggregateId = self::ACCOUNT_ID,
        ?array $payload = null,
    ): OutboxRecord {
        $now = new \DateTimeImmutable('2026-05-07T10:00:00+00:00');

        return new OutboxRecord(
            id: 'outbox-record-id',
            eventId: 'event-id',
            eventType: $eventType,
            aggregateType: 'identity_access.account',
            aggregateId: $aggregateId,
            payload: $payload ?? [
                'account_id' => self::ACCOUNT_ID,
                'approved_at' => '2026-05-07T09:10:00+00:00',
            ],
            occurredAt: $now,
            availableAt: $now,
            publishedAt: null,
            attempts: 0,
            lastError: null,
            createdAt: $now,
        );
    }
}
