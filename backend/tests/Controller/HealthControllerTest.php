<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthControllerTest extends WebTestCase
{
    public function testHealthEndpointReturnsOkStatus(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/health');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertSame(['status' => 'ok'], json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR));
    }
}
