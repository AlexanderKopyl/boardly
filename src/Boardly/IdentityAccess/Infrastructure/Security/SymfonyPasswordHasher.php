<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Security;

use App\Boardly\IdentityAccess\Application\Port\PasswordHasherInterface;
use App\Boardly\IdentityAccess\Application\Port\PasswordVerifierInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

final readonly class SymfonyPasswordHasher implements PasswordHasherInterface, PasswordVerifierInterface
{
    public function __construct(
        private PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {
    }

    public function hash(string $plainPassword): string
    {
        return $this->passwordHasherFactory
            ->getPasswordHasher(PasswordAuthenticatedUserInterface::class)
            ->hash($plainPassword);
    }

    public function verify(string $plainPassword, string $passwordHash): bool
    {
        return $this->passwordHasherFactory
            ->getPasswordHasher(PasswordAuthenticatedUserInterface::class)
            ->verify($passwordHash, $plainPassword);
    }
}
