<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Infrastructure\Persistence\Doctrine\Repository;

use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper\AccountMapper;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Repository\DoctrineAccountRepository;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Entity\ProjectEntity;
use App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Mapper\ProjectMapper;
use App\Boardly\Projects\Infrastructure\Persistence\Doctrine\Repository\DoctrineProjectRepository;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineProjectRepositoryIntegrationTest extends KernelTestCase
{
    private const string PASSWORD_HASH = '$2y$13$zYxwVuTsRqPoNmLkJiHgFe.DcBa9876543210abcdefABCDEFghij';

    private EntityManagerInterface $entityManager;
    private DoctrineProjectRepository $repository;
    private DoctrineAccountRepository $accountRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->repository = new DoctrineProjectRepository($this->entityManager, new ProjectMapper());
        $this->accountRepository = new DoctrineAccountRepository($this->entityManager, new AccountMapper());

        self::assertTrue(
            $this->entityManager->getConnection()->createSchemaManager()->tablesExist(['accounts', 'projects.projects']),
            'The accounts and projects.projects tables must exist. Run doctrine:migrations:migrate --env=test before this test.',
        );

        $this->entityManager->clear();
        $this->entityManager->getConnection()->executeStatement('DELETE FROM projects.projects');
        $this->entityManager->getConnection()->executeStatement('DELETE FROM accounts');
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->isOpen()) {
            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testSaveAndReloadProject(): void
    {
        $owner = $this->persistAccount('000000000051', 'project-owner@example.com');
        $createdAt = new \DateTimeImmutable('2026-05-05T10:00:00+00:00');
        $project = $this->createProject(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000601',
            ownerId: $owner->id(),
            name: 'Project Alpha',
            iconKey: ProjectIconKey::fromString('kanban'),
            createdAt: $createdAt,
        );

        $this->repository->save($project);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $stored = $this->repository->find($project->id());

        self::assertInstanceOf(Project::class, $stored);
        self::assertSame($project->id()->value(), $stored->id()->value());
        self::assertSame($owner->id()->value(), $stored->ownerAccountId()->value());
        self::assertSame('Project Alpha', $stored->name()->value());
        self::assertSame('kanban', $stored->iconKey()->value());
        self::assertTrue($stored->status()->isActive());
        $this->assertSameInstant($createdAt, $stored->createdAt());
        $this->assertSameInstant($createdAt, $stored->updatedAt());
        self::assertNull($stored->archivedAt());
    }

    public function testSaveUpdatesExistingProjectWhenArchived(): void
    {
        $owner = $this->persistAccount('000000000052', 'archive-owner@example.com');
        $createdAt = new \DateTimeImmutable('2026-05-05T11:00:00+00:00');
        $project = $this->createProject(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000602',
            ownerId: $owner->id(),
            name: 'Project Beta',
            iconKey: ProjectIconKey::default(),
            createdAt: $createdAt,
        );

        $this->repository->save($project);
        $this->entityManager->flush();

        $archivedAt = new \DateTimeImmutable('2026-05-05T12:00:00+00:00');
        $project->archive($archivedAt);
        $this->repository->save($project);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $stored = $this->repository->find($project->id());

        self::assertInstanceOf(Project::class, $stored);
        self::assertTrue($stored->status()->isArchived());
        $this->assertSameInstant($archivedAt, $stored->archivedAt());
        $this->assertSameInstant($archivedAt, $stored->updatedAt());
    }

    public function testFindByOwnerReturnsOnlyProjectsForThatOwner(): void
    {
        $owner = $this->persistAccount('000000000053', 'owner@example.com');
        $otherOwner = $this->persistAccount('000000000054', 'other-owner@example.com');

        $firstProject = $this->createProject(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000603',
            ownerId: $owner->id(),
            name: 'First Project',
            iconKey: ProjectIconKey::default(),
            createdAt: new \DateTimeImmutable('2026-05-05T13:00:00+00:00'),
        );
        $secondProject = $this->createProject(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000604',
            ownerId: $owner->id(),
            name: 'Second Project',
            iconKey: ProjectIconKey::fromString('board'),
            createdAt: new \DateTimeImmutable('2026-05-05T13:05:00+00:00'),
        );
        $foreignProject = $this->createProject(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000605',
            ownerId: $otherOwner->id(),
            name: 'Foreign Project',
            iconKey: ProjectIconKey::fromString('timeline'),
            createdAt: new \DateTimeImmutable('2026-05-05T13:10:00+00:00'),
        );

        $this->repository->save($firstProject);
        $this->repository->save($secondProject);
        $this->repository->save($foreignProject);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $found = $this->repository->findByOwner($owner->id());
        $foundIds = array_map(
            static fn (Project $project): string => $project->id()->value(),
            $found,
        );
        $foundNames = array_map(
            static fn (Project $project): string => $project->name()->value(),
            $found,
        );

        sort($foundIds);
        sort($foundNames);

        self::assertCount(2, $found);
        self::assertSame(
            [
                $firstProject->id()->value(),
                $secondProject->id()->value(),
            ],
            $foundIds,
        );
        self::assertSame(['First Project', 'Second Project'], $foundNames);
    }

    public function testProjectDoctrineMappingUsesScalarOwnerAccountIdAndSchemaQualifiedTable(): void
    {
        $metadata = $this->entityManager->getClassMetadata(ProjectEntity::class);
        $columns = $this->entityManager->getConnection()->createSchemaManager()->listTableColumns('projects.projects');
        $foreignKeys = $this->entityManager->getConnection()->createSchemaManager()->listTableForeignKeys('projects.projects');

        self::assertSame(
            ['id', 'ownerAccountId', 'name', 'iconKey', 'status', 'createdAt', 'updatedAt', 'archivedAt', 'version'],
            $metadata->getFieldNames(),
        );
        self::assertSame([], $metadata->getAssociationNames());
        self::assertArrayHasKey('owner_account_id', $columns);
        self::assertArrayHasKey('icon_key', $columns);
        self::assertSame('folder', $columns['icon_key']->getDefault());
        self::assertCount(1, $foreignKeys);
        self::assertSame(['owner_account_id'], $foreignKeys[0]->getLocalColumns());
        self::assertSame(['id'], $foreignKeys[0]->getForeignColumns());
        self::assertSame('accounts', $foreignKeys[0]->getForeignTableName());
    }

    private function persistAccount(string $idSuffix, string $email): Account
    {
        $account = Account::createSystemAdmin(
            AccountId::fromString('018f3f7a-9e4c-7b2d-9c52-'.$idSuffix),
            Email::fromString($email),
            PasswordHash::fromString(self::PASSWORD_HASH),
            AccountName::fromString('Project Owner'),
            new \DateTimeImmutable('2026-05-05T09:00:00+00:00'),
        )->account();

        $this->accountRepository->save($account);
        $this->entityManager->flush();

        return $account;
    }

    private function createProject(
        string $id,
        AccountId $ownerId,
        string $name,
        ProjectIconKey $iconKey,
        \DateTimeImmutable $createdAt,
    ): Project {
        return Project::create(
            ProjectId::fromString($id),
            $ownerId,
            ProjectName::fromString($name),
            $iconKey,
            $createdAt,
        )->project();
    }

    private function assertSameInstant(\DateTimeImmutable $expected, ?\DateTimeImmutable $actual): void
    {
        self::assertNotNull($actual);
        self::assertSame($expected->getTimestamp(), $actual->getTimestamp());
    }
}
