<?php
declare(strict_types=1);

/** Escape a value for safe output in HTML. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function money(int|string $pence): string
{
    return \App\Money::format((int) $pence);
}

function ukdate(string $value, string $format = 'D j M Y'): string
{
    return (new DateTimeImmutable($value))->format($format);
}
