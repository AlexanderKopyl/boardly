<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Outbox;

use App\Boardly\IdentityAccess\Infrastructure\Message\AccountRegisteredMessage;
use App\Boardly\IdentityAccess\Infrastructure\Outbox\AccountRegisteredOutboxMessageMapper;
use App\Shared\Infrastructure\Outbox\OutboxRecord;
use PHPUnit\Framework\TestCase;

final class AccountRegisteredOutboxMessageMapperTest extends TestCase
{
    private const string ACCOUNT_ID = '018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d';

    public function testSupportsAccountRegisteredOutboxRecord(): void
    {
        self::assertTrue((new AccountRegisteredOutboxMessageMapper())->supports($this->outboxRecord()));
    }

    public function testDoesNotSupportAnotherEventType(): void
    {
        self::assertFalse((new AccountRegisteredOutboxMessageMapper())->supports(
            $this->outboxRecord(eventType: 'identity_access.account_disabled'),
        ));
    }

    public function testMapsAccountRegisteredOutboxRecord(): void
    {
        $message = (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord());

        self::assertInstanceOf(AccountRegisteredMessage::class, $message);
        self::assertSame('outbox-record-id', $message->outboxId);
        self::assertSame('event-id', $message->eventId);
        self::assertSame(self::ACCOUNT_ID, $message->accountId);
        self::assertEquals(new \DateTimeImmutable('2026-05-03T10:15:30+00:00'), $message->registeredAt);
        self::assertTrue($message->isSystemAdmin);
    }

    public function testIgnoresRawEmailEvenWhenPayloadContainsEmail(): void
    {
        $message = (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'account_id' => self::ACCOUNT_ID,
            'email' => 'registered@example.com',
            'is_system_admin' => true,
            'registered_at' => '2026-05-03T10:15:30+00:00',
        ]));

        self::assertArrayNotHasKey('email', get_object_vars($message));
    }

    public function testDoesNotExposePasswordOrSecurityData(): void
    {
        $message = (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'account_id' => self::ACCOUNT_ID,
            'email' => 'registered@example.com',
            'password' => 'secret',
            'password_hash' => 'hashed-secret',
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'refresh_token_hash' => 'refresh-token-hash',
            'session_state' => 'state',
            'is_system_admin' => true,
            'registered_at' => '2026-05-03T10:15:30+00:00',
        ]));

        $properties = get_object_vars($message);

        self::assertArrayNotHasKey('password', $properties);
        self::assertArrayNotHasKey('password_hash', $properties);
        self::assertArrayNotHasKey('access_token', $properties);
        self::assertArrayNotHasKey('refresh_token', $properties);
        self::assertArrayNotHasKey('refresh_token_hash', $properties);
        self::assertArrayNotHasKey('session_state', $properties);
    }

    public function testRejectsMissingAccountId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "account_id" must be a non-empty string');

        (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'is_system_admin' => true,
            'registered_at' => '2026-05-03T10:15:30+00:00',
        ]));
    }

    public function testRejectsEmptyAccountId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "account_id" must be a non-empty string');

        (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord(
            aggregateId: null,
            payload: [
                'account_id' => '',
                'is_system_admin' => true,
                'registered_at' => '2026-05-03T10:15:30+00:00',
            ],
        ));
    }

    public function testRejectsNonStringAccountId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "account_id" must be a non-empty string');

        (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord(
            aggregateId: null,
            payload: [
                'account_id' => 123,
                'is_system_admin' => true,
                'registered_at' => '2026-05-03T10:15:30+00:00',
            ],
        ));
    }

    public function testRejectsMissingRegisteredAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "registered_at" must be a valid date/time string');

        (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'account_id' => self::ACCOUNT_ID,
            'is_system_admin' => true,
        ]));
    }

    public function testRejectsInvalidRegisteredAt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "registered_at" must be a valid date/time string');

        (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'account_id' => self::ACCOUNT_ID,
            'is_system_admin' => true,
            'registered_at' => 'not-a-date',
        ]));
    }

    public function testRejectsMissingIsSystemAdmin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "is_system_admin" must be a boolean');

        (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'account_id' => self::ACCOUNT_ID,
            'registered_at' => '2026-05-03T10:15:30+00:00',
        ]));
    }

    public function testRejectsNonBooleanIsSystemAdmin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payload field "is_system_admin" must be a boolean');

        (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord(payload: [
            'account_id' => self::ACCOUNT_ID,
            'is_system_admin' => 'true',
            'registered_at' => '2026-05-03T10:15:30+00:00',
        ]));
    }

    public function testRejectsAggregateIdMismatchWhenAggregateIdIsPresent(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('aggregateId "different-account-id" must match payload account_id');

        (new AccountRegisteredOutboxMessageMapper())->map($this->outboxRecord(aggregateId: 'different-account-id'));
    }

    /**
     * @param array<string, mixed>|null $payload
     */
    private function outboxRecord(
        string $eventType = 'identity_access.account_registered',
        ?string $aggregateId = self::ACCOUNT_ID,
        ?array $payload = null,
    ): OutboxRecord {
        $now = new \DateTimeImmutable('2026-05-04T10:00:00+00:00');

        return new OutboxRecord(
            id: 'outbox-record-id',
            eventId: 'event-id',
            eventType: $eventType,
            aggregateType: 'identity_access.account',
            aggregateId: $aggregateId,
            payload: $payload ?? [
                'account_id' => self::ACCOUNT_ID,
                'email' => 'registered@example.com',
                'is_system_admin' => true,
                'registered_at' => '2026-05-03T10:15:30+00:00',
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
