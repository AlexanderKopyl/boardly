<?php

declare(strict_types=1);

namespace App\Tests\Interfaces\Http\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthCheckControllerTest extends WebTestCase
{
    public function testHealthCheckReturnsOk(): void
    {
        $client = self::createClient();

        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertJsonStringEqualsJsonString(
            '{"status":"ok"}',
            (string) $client->getResponse()->getContent()
        );
    }
}
