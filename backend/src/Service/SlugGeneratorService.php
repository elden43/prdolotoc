<?php

namespace App\Service;

class SlugGeneratorService
{
    /**
     * Generate a unique URL-safe slug from a name.
     *
     * @param callable(string): bool $exists Returns true if the given slug is already taken.
     */
    public function generate(string $name, callable $exists): string
    {
        $base = $this->toKebabCase($name);

        if (!$exists($base)) {
            return $base;
        }

        // Collision: append a random 6-char hex suffix
        do {
            $suffix = substr(bin2hex(random_bytes(3)), 0, 6);
            $slug = substr($base, 0, 150) . '-' . $suffix;
        } while ($exists($slug));

        return $slug;
    }

    private function toKebabCase(string $name): string
    {
        // Transliterate non-ASCII characters to ASCII equivalents
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);

        // Lowercase
        $slug = strtolower((string) $slug);

        // Replace non-alphanumeric characters with hyphens
        $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Trim leading/trailing hyphens
        $slug = trim($slug, '-');

        // Truncate to 100 characters to leave room for the suffix
        $slug = substr($slug, 0, 100);

        // Fallback if the name produced an empty slug
        if ($slug === '') {
            $slug = 'config';
        }

        return $slug;
    }
}
