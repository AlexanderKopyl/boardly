<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\Event;

use App\Boardly\IdentityAccess\Domain\Event\AccountApproved;
use App\Boardly\IdentityAccess\Domain\Event\AccountDisabled;
use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\Event\AccountRejected;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AccountEventTest extends TestCase
{
    public function testAccountRegisteredExposesLifecycleFactPayload(): void
    {
        $accountId = $this->accountId();
        $email = Email::fromString('user@example.com');
        $registeredAt = new \DateTimeImmutable('2026-05-02T12:00:00+00:00');

        $event = new AccountRegistered($accountId, $email, true, $registeredAt);

        self::assertSame($accountId, $event->accountId());
        self::assertSame($email, $event->email());
        self::assertTrue($event->isSystemAdmin());
        self::assertSame($registeredAt, $event->registeredAt());
    }

    public function testAccountApprovedExposesLifecycleFactPayload(): void
    {
        $accountId = $this->accountId();
        $approvedAt = new \DateTimeImmutable('2026-05-02T12:01:00+00:00');

        $event = new AccountApproved($accountId, $approvedAt);

        self::assertSame($accountId, $event->accountId());
        self::assertSame($approvedAt, $event->approvedAt());
    }

    public function testAccountRejectedExposesLifecycleFactPayload(): void
    {
        $accountId = $this->accountId();
        $rejectedAt = new \DateTimeImmutable('2026-05-02T12:02:00+00:00');

        $event = new AccountRejected($accountId, $rejectedAt);

        self::assertSame($accountId, $event->accountId());
        self::assertSame($rejectedAt, $event->rejectedAt());
    }

    public function testAccountDisabledExposesLifecycleFactPayload(): void
    {
        $accountId = $this->accountId();
        $disabledAt = new \DateTimeImmutable('2026-05-02T12:03:00+00:00');

        $event = new AccountDisabled($accountId, $disabledAt);

        self::assertSame($accountId, $event->accountId());
        self::assertSame($disabledAt, $event->disabledAt());
    }

    #[DataProvider('lifecycleEventProvider')]
    public function testLifecycleEventsDoNotExposeCredentialTokenCookieOrHttpAccessors(object $event): void
    {
        $forbiddenAccessors = [
            'password',
            'passwordHash',
            'refreshToken',
            'jwt',
            'cookie',
            'request',
            'httpRequest',
            'doctrineEntity',
        ];

        foreach ($forbiddenAccessors as $forbiddenAccessor) {
            self::assertFalse(
                method_exists($event, $forbiddenAccessor),
                sprintf('%s must not expose %s().', $event::class, $forbiddenAccessor),
            );
        }
    }

    /**
     * @return iterable<string, array{object}>
     */
    public static function lifecycleEventProvider(): iterable
    {
        $accountId = AccountId::fromString('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d');

        yield 'account registered' => [
            new AccountRegistered(
                $accountId,
                Email::fromString('user@example.com'),
                false,
                new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
            ),
        ];

        yield 'account approved' => [
            new AccountApproved($accountId, new \DateTimeImmutable('2026-05-02T12:01:00+00:00')),
        ];

        yield 'account rejected' => [
            new AccountRejected($accountId, new \DateTimeImmutable('2026-05-02T12:02:00+00:00')),
        ];

        yield 'account disabled' => [
            new AccountDisabled($accountId, new \DateTimeImmutable('2026-05-02T12:03:00+00:00')),
        ];
    }

    private function accountId(): AccountId
    {
        return AccountId::fromString('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d');
    }
}
