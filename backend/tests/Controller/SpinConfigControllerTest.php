<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional tests for POST /api/spin-configs.
 */
final class SpinConfigControllerTest extends WebTestCase
{
    private function postSpinConfig(mixed $payload): array
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/spin-configs',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        return [
            'status' => $client->getResponse()->getStatusCode(),
            'body'   => json_decode((string) $client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR),
        ];
    }

    public function testCreateSpinConfigHappyPath(): void
    {
        $result = $this->postSpinConfig([
            'name'            => 'Test Config',
            'options'         => ['Alpha', 'Beta', 'Gamma'],
            'removeAfterPick' => false,
            'visualMode'      => 'slow',
        ]);

        self::assertSame(201, $result['status']);

        $body = $result['body'];
        self::assertArrayHasKey('id', $body);
        self::assertArrayHasKey('slug', $body);
        self::assertSame('Test Config', $body['name']);
        self::assertSame(['Alpha', 'Beta', 'Gamma'], $body['options']);
        self::assertFalse($body['removeAfterPick']);
        self::assertSame('slow', $body['visualMode']);
        self::assertArrayHasKey('createdAt', $body);
    }

    public function testCreateSpinConfigDefaults(): void
    {
        $result = $this->postSpinConfig([
            'name'    => 'Minimal Config',
            'options' => ['Only Option'],
        ]);

        self::assertSame(201, $result['status']);

        $body = $result['body'];
        self::assertTrue($body['removeAfterPick']);
        self::assertSame('classic', $body['visualMode']);
    }

    public function testCreateSpinConfigTrimsAndDropsEmptyOptions(): void
    {
        $result = $this->postSpinConfig([
            'name'    => 'Trimmed Config',
            'options' => ['  Hello  ', '', '  ', 'World'],
        ]);

        self::assertSame(201, $result['status']);
        self::assertSame(['Hello', 'World'], $result['body']['options']);
    }

    public function testCreateSpinConfigMissingNameReturns400(): void
    {
        $result = $this->postSpinConfig([
            'options' => ['A', 'B'],
        ]);

        self::assertSame(400, $result['status']);

        $body = $result['body'];
        self::assertSame('validation_failed', $body['error']);
        self::assertArrayHasKey('message', $body);
        self::assertArrayHasKey('name', $body['details']);
    }

    public function testCreateSpinConfigEmptyOptionsReturns400(): void
    {
        $result = $this->postSpinConfig([
            'name'    => 'No Options',
            'options' => [],
        ]);

        self::assertSame(400, $result['status']);

        $body = $result['body'];
        self::assertSame('validation_failed', $body['error']);
        self::assertArrayHasKey('options', $body['details']);
    }

    public function testCreateSpinConfigAllWhitespaceOptionsReturns400(): void
    {
        $result = $this->postSpinConfig([
            'name'    => 'Whitespace Options',
            'options' => ['  ', '   '],
        ]);

        self::assertSame(400, $result['status']);
        self::assertSame('validation_failed', $result['body']['error']);
        self::assertArrayHasKey('options', $result['body']['details']);
    }

    public function testCreateSpinConfigInvalidVisualModeReturns400(): void
    {
        $result = $this->postSpinConfig([
            'name'       => 'Bad Visual Mode',
            'options'    => ['X'],
            'visualMode' => 'turbo',
        ]);

        self::assertSame(400, $result['status']);
        self::assertSame('validation_failed', $result['body']['error']);
        self::assertArrayHasKey('visualMode', $result['body']['details']);
    }

    public function testCreateSpinConfigSlugGeneratedFromName(): void
    {
        $result = $this->postSpinConfig([
            'name'    => 'My Awesome Config',
            'options' => ['Item 1'],
        ]);

        self::assertSame(201, $result['status']);
        self::assertStringStartsWith('my-awesome-config', $result['body']['slug']);
    }
}
