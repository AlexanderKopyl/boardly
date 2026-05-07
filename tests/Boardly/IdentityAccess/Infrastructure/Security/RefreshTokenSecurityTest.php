<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Security;

use App\Boardly\IdentityAccess\Domain\ValueObject\RefreshTokenHash;
use App\Boardly\IdentityAccess\Infrastructure\Security\HmacRefreshTokenHasher;
use App\Boardly\IdentityAccess\Infrastructure\Security\SecureRefreshTokenGenerator;
use PHPUnit\Framework\TestCase;

final class RefreshTokenSecurityTest extends TestCase
{
    private const string HASH_SECRET = 'test-refresh-token-hash-secret-with-32-bytes';

    public function testGeneratorReturnsOpaqueUrlSafeTokens(): void
    {
        $generator = new SecureRefreshTokenGenerator();

        $firstToken = $generator->generate();
        $secondToken = $generator->generate();

        self::assertNotSame('', $firstToken);
        self::assertNotSame($firstToken, $secondToken);
        self::assertGreaterThanOrEqual(43, strlen($firstToken));
        self::assertMatchesRegularExpression('/\A[A-Za-z0-9_-]+\z/', $firstToken);
        self::assertStringNotContainsString('.', $firstToken);
        self::assertStringNotContainsString('=', $firstToken);
    }

    public function testHasherIsDeterministicAndDoesNotExposeRawToken(): void
    {
        $hasher = new HmacRefreshTokenHasher(self::HASH_SECRET);

        $firstHash = $hasher->hash('raw-refresh-token');
        $secondHash = $hasher->hash('raw-refresh-token');
        $differentHash = $hasher->hash('different-refresh-token');

        self::assertInstanceOf(RefreshTokenHash::class, $firstHash);
        self::assertTrue($firstHash->equals($secondHash));
        self::assertFalse($firstHash->equals($differentHash));
        self::assertNotSame('raw-refresh-token', $firstHash->value());
        self::assertStringStartsWith('hmac-sha256:', $firstHash->value());
    }

    public function testHasherRejectsEmptyToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new HmacRefreshTokenHasher(self::HASH_SECRET))->hash('   ');
    }
}
