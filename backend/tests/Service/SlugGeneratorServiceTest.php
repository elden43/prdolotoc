<?php

namespace App\Tests\Service;

use App\Service\SlugGeneratorService;
use PHPUnit\Framework\TestCase;

final class SlugGeneratorServiceTest extends TestCase
{
    private SlugGeneratorService $service;

    protected function setUp(): void
    {
        $this->service = new SlugGeneratorService();
    }

    public function testBasicNameProducesKebabCaseSlug(): void
    {
        $slug = $this->service->generate('My Spin Config', static fn () => false);

        self::assertSame('my-spin-config', $slug);
    }

    public function testDuplicateNameGetsSuffix(): void
    {
        $calls = 0;
        // First call (base slug) → taken; second call (slug-XXXXXX) → free
        $exists = static function () use (&$calls): bool {
            ++$calls;
            return $calls === 1; // only the first slug is "taken"
        };

        $slug = $this->service->generate('My Spin Config', $exists);

        self::assertStringStartsWith('my-spin-config-', $slug);
        self::assertMatchesRegularExpression('/^my-spin-config-[0-9a-f]{6}$/', $slug);
    }

    public function testNonAsciiCharactersAreTransliterated(): void
    {
        // Czech "Prďolotoč" – ď → d, č → c via iconv TRANSLIT
        $slug = $this->service->generate('Prďolotoč', static fn () => false);

        // Result should be lowercase ASCII with hyphens only
        self::assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
        self::assertNotEmpty($slug);
    }

    public function testLeadingTrailingSpecialCharsAreTrimmed(): void
    {
        $slug = $this->service->generate('---hello world---', static fn () => false);

        self::assertSame('hello-world', $slug);
    }

    public function testEmptyOrNonAsciiOnlyNameFallsBackToConfig(): void
    {
        // A name that produces nothing printable after transliteration
        $slug = $this->service->generate("\x00\x01\x02", static fn () => false);

        self::assertSame('config', $slug);
    }

    public function testSlugIsTruncatedAtOneHundredCharacters(): void
    {
        $longName = str_repeat('a', 200);

        $slug = $this->service->generate($longName, static fn () => false);

        self::assertSame(100, strlen($slug));
    }

    public function testMultipleCollisionsRetryUntilFree(): void
    {
        $calls = 0;
        // First two slugs are "taken"; third is free
        $exists = static function () use (&$calls): bool {
            ++$calls;
            return $calls <= 2;
        };

        $slug = $this->service->generate('Test', $exists);

        self::assertStringStartsWith('test-', $slug);
        self::assertMatchesRegularExpression('/^test-[0-9a-f]{6}$/', $slug);
    }
}
