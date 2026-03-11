<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests that assert the global JSON error response shape (ARCHITECTURE.md §2.4).
 */
final class ErrorResponseTest extends WebTestCase
{
    public function testUnknownRouteReturns404JsonShape(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/does-not-exist');

        self::assertResponseStatusCodeSame(404);
        self::assertResponseHeaderSame('content-type', 'application/json');

        $body = json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('not_found', $body['error']);
        self::assertArrayHasKey('message', $body);
        self::assertArrayNotHasKey('details', $body);
    }
}
