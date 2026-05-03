<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Doctrine;

use App\Shared\Application\Transaction\TransactionalInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrineTransactional implements TransactionalInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function transactional(callable $operation): mixed
    {
        return $this->entityManager->wrapInTransaction(
            static fn (): mixed => $operation(),
        );
    }
}