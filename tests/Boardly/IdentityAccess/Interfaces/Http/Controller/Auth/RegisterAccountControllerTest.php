<?php

declare(strict_types=1);

namespace App\Tests\Boardly\IdentityAccess\Interfaces\Http\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RegisterAccountControllerTest extends WebTestCase
{
    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
    }

    // --- 201 success ---

    public function testSuccessfulRegistrationReturns201(): void
    {
        $this->postRegister($this->validPayload());

        self::assertResponseStatusCodeSame(201);
    }

    public function testSuccessfulRegistrationReturnsAccountIdAndPendingApprovalStatus(): void
    {
        $this->postRegister($this->validPayload());

        $data = $this->responseData();

        self::assertArrayHasKey('accountId', $data);
        self::assertIsString($data['accountId']);
        self::assertNotEmpty($data['accountId']);
        self::assertSame('pending_approval', $data['status']);
    }

    public function testSuccessfulResponseContainsOnlyAccountIdAndStatus(): void
    {
        $this->postRegister($this->validPayload());

        $data = $this->responseData();

        self::assertSame(['accountId', 'status'], array_keys($data));
    }

    public function testSuccessfulResponseDoesNotExposeSensitiveFields(): void
    {
        $this->postRegister($this->validPayload());

        $data = $this->responseData();

        self::assertArrayNotHasKey('plainPassword', $data);
        self::assertArrayNotHasKey('password', $data);
        self::assertArrayNotHasKey('passwordHash', $data);
        self::assertArrayNotHasKey('accessToken', $data);
        self::assertArrayNotHasKey('refreshToken', $data);
    }

    public function testSuccessfulResponseDoesNotSetCookies(): void
    {
        $this->postRegister($this->validPayload());

        self::assertEmpty($this->client->getResponse()->headers->getCookies());
    }

    // --- 400 Bad Request ---

    public function testMalformedJsonReturns400(): void
    {
        $this->postRegister('{not valid json}');

        self::assertResponseStatusCodeSame(400);

        $data = $this->responseData();

        self::assertSame('invalid_request', $data['error']['code']);
        self::assertSame('Invalid request body.', $data['error']['message']);
    }

    // --- 422 Unprocessable Entity ---

    public function testMissingEmailReturns422(): void
    {
        $payload = $this->validPayload();
        unset($payload['email']);

        $this->postRegister($payload);

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testBlankEmailReturns422(): void
    {
        $this->postRegister($this->validPayload(email: ''));

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testInvalidEmailReturns422(): void
    {
        $this->postRegister($this->validPayload(email: 'not-an-email'));

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testMissingPlainPasswordReturns422(): void
    {
        $payload = $this->validPayload();
        unset($payload['plainPassword']);

        $this->postRegister($payload);

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testBlankPlainPasswordReturns422(): void
    {
        $payload = $this->validPayload();
        $payload['plainPassword'] = '';

        $this->postRegister($payload);

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testTooShortPlainPasswordReturns422(): void
    {
        $payload = $this->validPayload();
        $payload['plainPassword'] = '1234567'; // 7 chars, min is 8

        $this->postRegister($payload);

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testMissingNameReturns422(): void
    {
        $payload = $this->validPayload();
        unset($payload['name']);

        $this->postRegister($payload);

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testBlankNameReturns422(): void
    {
        $payload = $this->validPayload();
        $payload['name'] = '';

        $this->postRegister($payload);

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    public function testTooLongNameReturns422(): void
    {
        $payload = $this->validPayload();
        $payload['name'] = str_repeat('a', 101); // 101 chars, max is 100

        $this->postRegister($payload);

        self::assertResponseStatusCodeSame(422);
        $this->assertValidationFailedError();
    }

    // --- 409 Conflict ---

    public function testDuplicateEmailReturns409(): void
    {
        $email = $this->uniqueEmail();

        $this->postRegister($this->validPayload(email: $email));
        self::assertResponseStatusCodeSame(201);

        $this->postRegister($this->validPayload(email: $email));
        self::assertResponseStatusCodeSame(409);

        $data = $this->responseData();

        self::assertSame('email_already_registered', $data['error']['code']);
        self::assertSame('Email is already registered.', $data['error']['message']);
    }

    // --- Helpers ---

    /** @param array<string, mixed> $payload */
    private function postRegister(array|string $payload): void
    {
        $content = \is_array($payload)
            ? json_encode($payload, JSON_THROW_ON_ERROR)
            : $payload;

        $this->client->request(
            'POST',
            '/api/auth/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
            $content,
        );
    }

    /** @return array<string, string> */
    private function validPayload(?string $email = null): array
    {
        return [
            'email' => $email ?? $this->uniqueEmail(),
            'plainPassword' => 'valid-password-123',
            'name' => 'Test User',
        ];
    }

    private function uniqueEmail(): string
    {
        return sprintf('user+%s@example.com', uniqid('', true));
    }

    /** @return array<string, mixed> */
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

    private function assertValidationFailedError(): void
    {
        $data = $this->responseData();

        self::assertSame('validation_failed', $data['error']['code']);
        self::assertSame('The request payload is invalid.', $data['error']['message']);
        self::assertArrayHasKey('violations', $data['error']);
        self::assertIsArray($data['error']['violations']);
        self::assertNotEmpty($data['error']['violations']);
    }
}
