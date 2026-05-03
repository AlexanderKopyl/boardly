<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Repository;

use App\Boardly\IdentityAccess\Application\Exception\AccountNotFound;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountStatus;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Entity\AccountEntity;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper\AccountMapper;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineAccountRepository implements AccountRepositoryInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private AccountMapper $mapper,
    ) {
    }

    public function save(Account $account): void
    {
        $entity = $this->entityManager->find(AccountEntity::class, $account->id()->value());

        if (!$entity instanceof AccountEntity) {
            $this->entityManager->persist($this->mapper->toEntity($account));

            return;
        }

        $this->mapper->updateEntity($account, $entity);
    }

    public function get(AccountId $id): Account
    {
        return $this->find($id) ?? throw AccountNotFound::withId($id);
    }

    public function find(AccountId $id): ?Account
    {
        $entity = $this->entityManager->find(AccountEntity::class, $id->value());

        if (!$entity instanceof AccountEntity) {
            return null;
        }

        return $this->mapper->toDomain($entity);
    }

    public function findByEmail(Email $email): ?Account
    {
        $entity = $this->entityManager
            ->getRepository(AccountEntity::class)
            ->findOneBy(['email' => $email->value()]);

        if (!$entity instanceof AccountEntity) {
            return null;
        }

        return $this->mapper->toDomain($entity);
    }

    public function existsByEmail(Email $email): bool
    {
        $count = $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(account.id)')
            ->from(AccountEntity::class, 'account')
            ->where('account.email = :email')
            ->setParameter('email', $email->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    public function countActiveSystemAdmins(): int
    {
        $count = $this->entityManager
            ->createQueryBuilder()
            ->select('COUNT(account.id)')
            ->from(AccountEntity::class, 'account')
            ->where('account.isSystemAdmin = :isSystemAdmin')
            ->andWhere('account.status = :status')
            ->setParameter('isSystemAdmin', true)
            ->setParameter('status', AccountStatus::active()->value())
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count;
    }
}
