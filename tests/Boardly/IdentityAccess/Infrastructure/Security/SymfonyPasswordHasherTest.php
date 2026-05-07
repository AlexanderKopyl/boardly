<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Infrastructure\Security;

use App\Boardly\IdentityAccess\Infrastructure\Security\SymfonyPasswordHasher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final class SymfonyPasswordHasherTest extends TestCase
{
    public function testPasswordVerifierAcceptsValidPasswordAndRejectsWrongPassword(): void
    {
        $hasher = new SymfonyPasswordHasher(new PasswordHasherFactory([
            PasswordAuthenticatedUserInterface::class => [
                'algorithm' => 'bcrypt',
                'cost' => 4,
            ],
        ]));

        $passwordHash = $hasher->hash('correct-password');

        self::assertTrue($hasher->verify('correct-password', $passwordHash));
        self::assertFalse($hasher->verify('wrong-password', $passwordHash));
        self::assertNotSame('correct-password', $passwordHash);
    }
}
