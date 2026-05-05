<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Security;

use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class AuthenticatedAccountUser implements UserInterface
{
    /**
     * @param list<string> $roles
     */
    private function __construct(
        private AccountId $accountId,
        private string $email,
        private string $name,
        private string $status,
        private array $roles,
    ) {
    }

    public static function fromAccount(Account $account): self
    {
        return new self(
            $account->id(),
            $account->email()->value(),
            $account->name()->value(),
            $account->status()->value(),
            ['ROLE_USER'],
        );
    }

    public function accountId(): AccountId
    {
        return $this->accountId;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function getUserIdentifier(): string
    {
        return $this->accountId->value();
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }
}
