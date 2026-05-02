<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Domain\Result;

use App\Boardly\IdentityAccess\Domain\Event\AccountApproved;
use App\Boardly\IdentityAccess\Domain\Event\AccountDisabled;
use App\Boardly\IdentityAccess\Domain\Event\AccountRegistered;
use App\Boardly\IdentityAccess\Domain\Event\AccountRejected;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\Result\AccountApprovalResult;
use App\Boardly\IdentityAccess\Domain\Result\AccountDisableResult;
use App\Boardly\IdentityAccess\Domain\Result\AccountRegistrationResult;
use App\Boardly\IdentityAccess\Domain\Result\AccountRejectionResult;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AccountResultTest extends TestCase
{
    public function testAccountRegistrationResultExposesCreatedEvent(): void
    {
        $event = new AccountRegistered(
            $this->accountId(),
            Email::fromString('user@example.com'),
            false,
            new \DateTimeImmutable('2026-05-02T12:00:00+00:00'),
        );

        $account = Account::register(
            $event->accountId(),
            $event->email(),
            $this->passwordHash(),
            AccountName::fromString('Registered Account'),
            $event->registeredAt(),
        )->account();

        $result = new AccountRegistrationResult($account, $event);

        self::assertSame($account, $result->account());
        self::assertSame($event, $result->event());
    }

    public function testAccountApprovalResultExposesCreatedEvent(): void
    {
        $event = new AccountApproved(
            $this->accountId(),
            new \DateTimeImmutable('2026-05-02T12:01:00+00:00'),
        );

        $result = new AccountApprovalResult($event);

        self::assertSame($event, $result->event());
    }

    public function testAccountRejectionResultExposesCreatedEvent(): void
    {
        $event = new AccountRejected(
            $this->accountId(),
            new \DateTimeImmutable('2026-05-02T12:02:00+00:00'),
        );

        $result = new AccountRejectionResult($event);

        self::assertSame($event, $result->event());
    }

    public function testAccountDisableResultExposesCreatedEvent(): void
    {
        $event = new AccountDisabled(
            $this->accountId(),
            new \DateTimeImmutable('2026-05-02T12:03:00+00:00'),
        );

        $result = new AccountDisableResult($event);

        self::assertSame($event, $result->event());
    }

    /**
     * @param class-string $resultClass
     */
    #[DataProvider('resultClassProvider')]
    public function testResultObjectsExposeOnlyLifecycleEventAccessor(string $resultClass): void
    {
        $reflection = new ReflectionClass($resultClass);
        $publicMethodNames = array_map(
            static fn (\ReflectionMethod $method): string => $method->getName(),
            $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
        );

        sort($publicMethodNames);

        $expectedPublicMethodNames = [AccountRegistrationResult::class === $resultClass ? 'account' : null, '__construct', 'event'];
        $expectedPublicMethodNames = array_values(array_filter($expectedPublicMethodNames));
        sort($expectedPublicMethodNames);

        self::assertSame($expectedPublicMethodNames, $publicMethodNames);
    }

    /**
     * @param class-string $resultClass
     */
    #[DataProvider('resultClassProvider')]
    public function testResultObjectsDoNotExposeCredentialTokenCookieOrHttpAccessors(string $resultClass): void
    {
        $forbiddenAccessors = [
            'password',
            'passwordHash',
            'token',
            'accessToken',
            'refreshToken',
            'jwt',
            'cookie',
            'request',
            'response',
            'httpRequest',
            'httpResponse',
        ];

        foreach ($forbiddenAccessors as $forbiddenAccessor) {
            self::assertFalse(
                method_exists($resultClass, $forbiddenAccessor),
                sprintf('%s must not expose %s().', $resultClass, $forbiddenAccessor),
            );
        }
    }

    /**
     * @param class-string $resultClass
     */
    #[DataProvider('resultClassProvider')]
    public function testResultObjectsDoNotDependOnInfrastructureOrDeliveryLibraries(string $resultClass): void
    {
        $reflection = new ReflectionClass($resultClass);
        $source = file_get_contents((string) $reflection->getFileName());

        self::assertIsString($source);

        $forbiddenDependencyNames = [
            'Symfony',
            'Doctrine',
            'Messenger',
            'Jwt',
            'JWT',
            'Cookie',
            'Redis',
            'OpenSearch',
            'Elasticsearch',
        ];

        foreach ($forbiddenDependencyNames as $forbiddenDependencyName) {
            self::assertStringNotContainsString(
                $forbiddenDependencyName,
                $source,
                sprintf('%s must not depend on %s.', $resultClass, $forbiddenDependencyName),
            );
        }
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function resultClassProvider(): iterable
    {
        yield 'account registration result' => [AccountRegistrationResult::class];
        yield 'account approval result' => [AccountApprovalResult::class];
        yield 'account rejection result' => [AccountRejectionResult::class];
        yield 'account disable result' => [AccountDisableResult::class];
    }

    private function accountId(): AccountId
    {
        return AccountId::fromString('018f3f7a-9e4c-7b2d-9c52-3f8f9b8b4c2d');
    }

    private function passwordHash(): PasswordHash
    {
        return PasswordHash::fromString('$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij');
    }
}
