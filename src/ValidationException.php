<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

/** Carries field => message errors back to the form that caused them. */
final class ValidationException extends RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(implode(' ', $errors));
    }
}
