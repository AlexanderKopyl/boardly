<?php

declare(strict_types=1);

namespace App\Tests\Boardly\Projects\Interfaces\Http\Controller;

use App\Boardly\IdentityAccess\Application\Port\AccessTokenIssuerInterface;
use App\Boardly\IdentityAccess\Application\Port\AccountRepositoryInterface;
use App\Boardly\IdentityAccess\Application\Port\PasswordHasherInterface;
use App\Boardly\IdentityAccess\Domain\Model\Account;
use App\Boardly\IdentityAccess\Domain\ValueObject\AccountName;
use App\Boardly\IdentityAccess\Domain\ValueObject\Email;
use App\Boardly\IdentityAccess\Domain\ValueObject\PasswordHash;
use App\Boardly\Projects\Application\Port\ProjectRepositoryInterface;
use App\Boardly\Projects\Domain\Model\Project;
use App\Boardly\Projects\Domain\ValueObject\ProjectIconKey;
use App\Boardly\Projects\Domain\ValueObject\ProjectName;
use App\Boardly\SharedKernel\Domain\ValueObject\AccountId;
use App\Boardly\SharedKernel\Domain\ValueObject\ProjectId;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Uid\Uuid;

final class ProjectControllerTest extends WebTestCase
{
    private const string PLAIN_PASSWORD = 'Password123!';

    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private AccountRepositoryInterface $accounts;
    private PasswordHasherInterface $passwordHasher;
    private AccessTokenIssuerInterface $accessTokenIssuer;
    private ProjectRepositoryInterface $projects;

    protected function setUp(): void
    {
        $this->setRequiredTestSecrets();

        $this->client = self::createClient();
        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->accounts = $container->get(AccountRepositoryInterface::class);
        $this->passwordHasher = $container->get(PasswordHasherInterface::class);
        $this->accessTokenIssuer = $container->get(AccessTokenIssuerInterface::class);
        $this->projects = $container->get(ProjectRepositoryInterface::class);

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

    public function testCreateProjectReturnsCreatedResponseAndPersistsProject(): void
    {
        $account = $this->persistActiveAccount('projects-create@example.com', 'Projects Create');

        $this->postJson(
            '/api/projects',
            [
                'name' => 'Created Project',
                'iconKey' => 'board',
            ],
            $this->validTokenFor($account->id()),
        );

        self::assertResponseStatusCodeSame(201);

        $data = $this->responseData();
        self::assertSame(['projectId', 'status'], array_keys($data));
        self::assertSame('active', $data['status']);
        self::assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $data['projectId']);

        $project = $this->projects->find(ProjectId::fromString($data['projectId']));
        self::assertInstanceOf(Project::class, $project);
        self::assertSame($account->id()->value(), $project->ownerAccountId()->value());
        self::assertSame('Created Project', $project->name()->value());
        self::assertSame('board', $project->iconKey()->value());
        self::assertTrue($project->status()->isActive());
    }

    public function testCreateProjectWithoutBearerTokenReturns401(): void
    {
        $this->postJson(
            '/api/projects',
            [
                'name' => 'Unauthorized Project',
            ],
        );

        $this->assertUnauthorizedResponse();
    }

    public function testListProjectsReturnsOnlyProjectsOwnedByTheAuthenticatedAccount(): void
    {
        $owner = $this->persistActiveAccount('projects-owner@example.com', 'Projects Owner');
        $otherAccount = $this->persistActiveAccount('projects-other@example.com', 'Projects Other');

        $firstProject = $this->persistProject(
            '018f3f7a-9e4c-7b2d-9c52-000000000701',
            $owner->id(),
            'Owner Alpha',
            'board',
            new \DateTimeImmutable('2026-05-05T10:00:00+00:00'),
        );
        $secondProject = $this->persistProject(
            '018f3f7a-9e4c-7b2d-9c52-000000000702',
            $owner->id(),
            'Owner Beta',
            'timeline',
            new \DateTimeImmutable('2026-05-05T10:05:00+00:00'),
        );
        $this->persistProject(
            '018f3f7a-9e4c-7b2d-9c52-000000000703',
            $otherAccount->id(),
            'Foreign Project',
            'folder',
            new \DateTimeImmutable('2026-05-05T10:10:00+00:00'),
        );

        $this->getJson('/api/projects', $this->validTokenFor($owner->id()));

        self::assertResponseStatusCodeSame(200);

        $data = $this->responseData();
        self::assertArrayHasKey('projects', $data);
        self::assertCount(2, $data['projects']);

        $projects = $data['projects'];
        usort($projects, static fn (array $left, array $right): int => strcmp($left['id'], $right['id']));

        self::assertSame(
            [
                [
                    'id' => $firstProject->id()->value(),
                    'name' => 'Owner Alpha',
                    'iconKey' => 'board',
                    'status' => 'active',
                    'createdAt' => '2026-05-05T10:00:00+00:00',
                ],
                [
                    'id' => $secondProject->id()->value(),
                    'name' => 'Owner Beta',
                    'iconKey' => 'timeline',
                    'status' => 'active',
                    'createdAt' => '2026-05-05T10:05:00+00:00',
                ],
            ],
            $projects,
        );
    }

    public function testGetProjectReturnsDetailsForOwnedProject(): void
    {
        $owner = $this->persistActiveAccount('projects-get-owner@example.com', 'Projects Get Owner');
        $project = $this->persistProject(
            '018f3f7a-9e4c-7b2d-9c52-000000000704',
            $owner->id(),
            'Gettable Project',
            'kanban',
            new \DateTimeImmutable('2026-05-05T11:00:00+00:00'),
        );

        $this->getJson('/api/projects/'.$project->id()->value(), $this->validTokenFor($owner->id()));

        self::assertResponseStatusCodeSame(200);
        self::assertSame(
            [
                'id' => $project->id()->value(),
                'name' => 'Gettable Project',
                'iconKey' => 'kanban',
                'status' => 'active',
                'createdAt' => '2026-05-05T11:00:00+00:00',
                'updatedAt' => '2026-05-05T11:00:00+00:00',
                'archivedAt' => null,
            ],
            $this->responseData(),
        );
    }

    public function testGetProjectForAnotherAccountReturns404(): void
    {
        $owner = $this->persistActiveAccount('projects-get-foreign-owner@example.com', 'Projects Get Owner');
        $otherAccount = $this->persistActiveAccount('projects-get-foreign@example.com', 'Projects Get Foreign');
        $project = $this->persistProject(
            '018f3f7a-9e4c-7b2d-9c52-000000000705',
            $owner->id(),
            'Hidden Project',
            'folder',
            new \DateTimeImmutable('2026-05-05T12:00:00+00:00'),
        );

        $this->getJson('/api/projects/'.$project->id()->value(), $this->validTokenFor($otherAccount->id()));

        self::assertResponseStatusCodeSame(404);
        self::assertSame(
            [
                'error' => [
                    'code' => 'project_not_found',
                    'message' => 'Project not found.',
                ],
            ],
            $this->responseData(),
        );
    }

    public function testArchiveProjectReturnsArchivedPayloadForOwnedProject(): void
    {
        $owner = $this->persistActiveAccount('projects-archive-owner@example.com', 'Projects Archive Owner');
        $project = $this->persistProject(
            '018f3f7a-9e4c-7b2d-9c52-000000000706',
            $owner->id(),
            'Archivable Project',
            'board',
            new \DateTimeImmutable('2026-05-05T13:00:00+00:00'),
        );

        $this->postJson('/api/projects/'.$project->id()->value().'/archive', [], $this->validTokenFor($owner->id()));

        self::assertResponseStatusCodeSame(200);
        $data = $this->responseData();

        self::assertSame($project->id()->value(), $data['projectId']);
        self::assertSame('archived', $data['status']);
        self::assertIsString($data['archivedAt']);
        self::assertNotEmpty($data['archivedAt']);
        self::assertInstanceOf(\DateTimeImmutable::class, new \DateTimeImmutable($data['archivedAt']));
    }

    public function testArchiveProjectForAnotherAccountReturns404(): void
    {
        $owner = $this->persistActiveAccount('projects-archive-foreign-owner@example.com', 'Projects Archive Owner');
        $otherAccount = $this->persistActiveAccount('projects-archive-foreign@example.com', 'Projects Archive Foreign');
        $project = $this->persistProject(
            '018f3f7a-9e4c-7b2d-9c52-000000000707',
            $owner->id(),
            'Hidden Archive Project',
            'folder',
            new \DateTimeImmutable('2026-05-05T14:00:00+00:00'),
        );

        $this->postJson('/api/projects/'.$project->id()->value().'/archive', [], $this->validTokenFor($otherAccount->id()));

        self::assertResponseStatusCodeSame(404);
        self::assertSame(
            [
                'error' => [
                    'code' => 'project_not_found',
                    'message' => 'Project not found.',
                ],
            ],
            $this->responseData(),
        );
    }

    private function setRequiredTestSecrets(): void
    {
        $_ENV['IDENTITY_ACCESS_JWT_SIGNING_SECRET'] = str_repeat('a', 64);
        $_SERVER['IDENTITY_ACCESS_JWT_SIGNING_SECRET'] = str_repeat('a', 64);
        $_ENV['IDENTITY_ACCESS_REFRESH_TOKEN_HASH_SECRET'] = str_repeat('b', 64);
        $_SERVER['IDENTITY_ACCESS_REFRESH_TOKEN_HASH_SECRET'] = str_repeat('b', 64);
    }

    private function persistActiveAccount(string $email, string $name): Account
    {
        $createdAt = new \DateTimeImmutable('2026-05-05T09:00:00+00:00');
        $account = Account::register(
            AccountId::fromString(Uuid::v7()->toRfc4122()),
            Email::fromString($email),
            PasswordHash::fromString($this->passwordHasher->hash(self::PLAIN_PASSWORD)),
            AccountName::fromString($name),
            $createdAt,
        )->account();

        $account->approve($createdAt->modify('+1 minute'));

        $this->accounts->save($account);
        $this->entityManager->flush();
        $this->entityManager->clear();

        return $account;
    }

    private function persistProject(
        string $id,
        AccountId $ownerId,
        string $name,
        string $iconKey,
        \DateTimeImmutable $createdAt,
    ): Project {
        $project = Project::create(
            ProjectId::fromString($id),
            $ownerId,
            ProjectName::fromString($name),
            ProjectIconKey::fromString($iconKey),
            $createdAt,
        )->project();

        $this->projects->save($project);
        $this->entityManager->flush();
        $this->entityManager->clear();

        return $project;
    }

    private function validTokenFor(AccountId $accountId): string
    {
        return $this->accessTokenIssuer
            ->issueForAccount($accountId, (new \DateTimeImmutable('now'))->modify('-1 minute'))
            ->token();
    }

    private function postJson(string $uri, array $payload, ?string $token = null): void
    {
        $this->client->request(
            'POST',
            $uri,
            [],
            [],
            $this->serverVariables($token),
            json_encode($payload, JSON_THROW_ON_ERROR),
        );
    }

    private function getJson(string $uri, ?string $token = null): void
    {
        $this->client->request('GET', $uri, [], [], $this->serverVariables($token));
    }

    /**
     * @return array<string, string>
     */
    private function serverVariables(?string $token = null): array
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_USER_AGENT' => 'Boardly HTTP test',
            'REMOTE_ADDR' => '203.0.113.10',
        ];

        if (null !== $token) {
            $server['HTTP_AUTHORIZATION'] = 'Bearer '.$token;
        }

        return $server;
    }

    /**
     * @return array<string, mixed>
     */
    private function responseData(): array
    {
        $data = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertIsArray($data);

        return $data;
    }

    private function assertUnauthorizedResponse(): void
    {
        self::assertResponseStatusCodeSame(401);
        self::assertSame(
            ['error' => ['code' => 'unauthorized', 'message' => 'Authentication required.']],
            $this->responseData(),
        );
    }
}
