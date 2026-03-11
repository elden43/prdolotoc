<?php

namespace App\Exception;

use RuntimeException;

/**
 * Thrown when request validation fails.
 * $details maps field name → list of error messages.
 *
 * @phpstan-type DetailsMap array<string, list<string>>
 */
final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, list<string>> $details
     */
    public function __construct(
        private readonly array $details = [],
        string $message = 'Request validation failed.',
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, list<string>>
     */
    public function getDetails(): array
    {
        return $this->details;
    }
}
