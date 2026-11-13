<?php
declare(strict_types=1);

namespace App;

/** Synchroniser-token CSRF protection for every POST form. */
final class Csrf
{
    /** @param array<string, mixed> $session */
    public static function token(array &$session): string
    {
        if (empty($session['csrf'])) {
            $session['csrf'] = bin2hex(random_bytes(32));
        }
        return $session['csrf'];
    }

    /** @param array<string, mixed> $session */
    public static function valid(array $session, ?string $submitted): bool
    {
        return !empty($session['csrf']) && is_string($submitted) && hash_equals($session['csrf'], $submitted);
    }
}
