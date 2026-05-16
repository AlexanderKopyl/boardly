<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Infrastructure\Persistence\Doctrine\Repository;

use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Mapper\AccountMapper;
use App\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\Repository\DoctrineAccountRepository;
use App\Boardly\Projects\Application\Exception\ProjectNotFound;
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
        self::assertNull($stored->deletedAt());
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
        self::assertNull($stored->deletedAt());
    }

    public function testSaveUpdatesExistingProjectWhenDeleted(): void
    {
        $owner = $this->persistAccount('000000000055', 'delete-owner@example.com');
        $createdAt = new \DateTimeImmutable('2026-05-05T12:00:00+00:00');
        $project = $this->createProject(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000606',
            ownerId: $owner->id(),
            name: 'Project Gamma',
            iconKey: ProjectIconKey::fromString('timeline'),
            createdAt: $createdAt,
        );

        $this->repository->save($project);
        $this->entityManager->flush();

        $deletedAt = new \DateTimeImmutable('2026-05-05T13:00:00+00:00');
        $project->delete($deletedAt);
        $this->repository->save($project);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $stored = $this->repository->find($project->id());

        self::assertInstanceOf(Project::class, $stored);
        self::assertTrue($stored->status()->isDeleted());
        $this->assertSameInstant($deletedAt, $stored->deletedAt());
        $this->assertSameInstant($deletedAt, $stored->updatedAt());
        self::assertNull($stored->archivedAt());
    }

    public function testFindAccessibleActiveByOwnerReturnsOnlyActiveProjectsForThatOwner(): void
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
        $archivedProject = $this->createProject(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000605',
            ownerId: $owner->id(),
            name: 'Archived Project',
            iconKey: ProjectIconKey::fromString('timeline'),
            createdAt: new \DateTimeImmutable('2026-05-05T13:06:00+00:00'),
        );
        $archivedProject->archive(new \DateTimeImmutable('2026-05-05T13:30:00+00:00'));
        $deletedProject = $this->createProject(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000606',
            ownerId: $owner->id(),
            name: 'Deleted Project',
            iconKey: ProjectIconKey::fromString('folder'),
            createdAt: new \DateTimeImmutable('2026-05-05T13:07:00+00:00'),
        );
        $deletedProject->delete(new \DateTimeImmutable('2026-05-05T13:35:00+00:00'));
        $foreignProject = $this->createProject(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000607',
            ownerId: $otherOwner->id(),
            name: 'Foreign Project',
            iconKey: ProjectIconKey::fromString('timeline'),
            createdAt: new \DateTimeImmutable('2026-05-05T13:10:00+00:00'),
        );

        $this->repository->save($firstProject);
        $this->repository->save($secondProject);
        $this->repository->save($archivedProject);
        $this->repository->save($deletedProject);
        $this->repository->save($foreignProject);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $found = $this->repository->findAccessibleActiveByOwner($owner->id());
        $foundIds = array_map(
            static fn (Project $project): string => $project->id()->value(),
            $found,
        );
        $foundNames = array_map(
            static fn (Project $project): string => $project->name()->value(),
            $found,
        );

        self::assertCount(2, $found);
        self::assertSame(
            [
                $secondProject->id()->value(),
                $firstProject->id()->value(),
            ],
            $foundIds,
        );
        self::assertSame(['Second Project', 'First Project'], $foundNames);
    }

    public function testGetAccessibleByIdThrowsForDeletedProject(): void
    {
        $owner = $this->persistAccount('000000000055', 'deleted-owner@example.com');
        $deletedProject = $this->createProject(
            id: '018f3f7a-9e4c-7b2d-9c52-000000000607',
            ownerId: $owner->id(),
            name: 'Deleted Project',
            iconKey: ProjectIconKey::fromString('folder'),
            createdAt: new \DateTimeImmutable('2026-05-05T14:00:00+00:00'),
        );
        $deletedProject->delete(new \DateTimeImmutable('2026-05-05T14:05:00+00:00'));

        $this->repository->save($deletedProject);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $this->expectException(ProjectNotFound::class);
        $this->repository->getAccessibleById($deletedProject->id(), $owner->id());
    }

    public function testProjectDoctrineMappingUsesScalarOwnerAccountIdAndSchemaQualifiedTable(): void
    {
        $metadata = $this->entityManager->getClassMetadata(ProjectEntity::class);
        $schemaManager = $this->entityManager->getConnection()->createSchemaManager();
        $columns = $schemaManager->listTableColumns('projects.projects');
        $foreignKeys = $this->entityManager->getConnection()->createSchemaManager()->listTableForeignKeys('projects.projects');
        $indexes = $schemaManager->listTableIndexes('projects.projects');

        self::assertSame(
            ['id', 'ownerAccountId', 'name', 'iconKey', 'status', 'createdAt', 'updatedAt', 'archivedAt', 'deletedAt', 'version'],
            $metadata->getFieldNames(),
        );
        self::assertSame([], $metadata->getAssociationNames());
        self::assertArrayHasKey('owner_account_id', $columns);
        self::assertArrayHasKey('icon_key', $columns);
        self::assertArrayHasKey('deleted_at', $columns);
        self::assertSame(100, $columns['name']->getLength());
        self::assertSame('folder', $columns['icon_key']->getDefault());
        self::assertArrayHasKey('idx_projects_projects_owner_status', $indexes);
        self::assertArrayHasKey('idx_projects_projects_owner_created_at', $indexes);
        self::assertCount(1, $foreignKeys);
        self::assertSame(['owner_account_id'], $foreignKeys[0]->getLocalColumns());
        self::assertSame(['id'], $foreignKeys[0]->getForeignColumns());
        self::assertSame('accounts', $foreignKeys[0]->getForeignTableName());
        self::assertFalse(
            array_reduce(
                $indexes,
                static fn (bool $carry, \Doctrine\DBAL\Schema\Index $index): bool => $carry || str_contains((string) $index->getName(), 'icon_key'),
                false,
            ),
            'The projects table must not have an icon_key index.',
        );

        $checkConstraintNames = $this->entityManager->getConnection()->fetchFirstColumn(
            <<<'SQL'
SELECT con.conname
FROM pg_constraint con
JOIN pg_class rel ON rel.oid = con.conrelid
JOIN pg_namespace nsp ON nsp.oid = rel.relnamespace
WHERE nsp.nspname = 'projects'
  AND rel.relname = 'projects'
  AND con.contype = 'c'
ORDER BY con.conname
SQL
        );

        self::assertContains('projects_projects_icon_key_format_check', $checkConstraintNames);
        self::assertContains('projects_projects_name_not_blank_check', $checkConstraintNames);
        self::assertContains('projects_projects_status_check', $checkConstraintNames);
        self::assertContains('projects_projects_version_positive_check', $checkConstraintNames);
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

        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO accounts (id, email, password_hash, name, status, is_system_admin, created_at, updated_at, approved_at, rejected_at, disabled_at) VALUES (:id, :email, :passwordHash, :name, :status, :isSystemAdmin, :createdAt, :updatedAt, :approvedAt, :rejectedAt, :disabledAt)',
            [
                'id' => $account->id()->value(),
                'email' => $account->email()->value(),
                'passwordHash' => $account->passwordHash()->value(),
                'name' => $account->name()->value(),
                'status' => $account->status()->value(),
                'isSystemAdmin' => $account->isSystemAdmin(),
                'createdAt' => $account->createdAt()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:sP'),
                'updatedAt' => $account->updatedAt()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:sP'),
                'approvedAt' => $account->approvedAt()?->setTimezone(new \DateTimeZone('UTC'))?->format('Y-m-d H:i:sP'),
                'rejectedAt' => $account->rejectedAt()?->setTimezone(new \DateTimeZone('UTC'))?->format('Y-m-d H:i:sP'),
                'disabledAt' => $account->disabledAt()?->setTimezone(new \DateTimeZone('UTC'))?->format('Y-m-d H:i:sP'),
            ],
            [
                'isSystemAdmin' => \Doctrine\DBAL\ParameterType::BOOLEAN,
            ],
        );

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
