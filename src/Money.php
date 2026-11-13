<?php
declare(strict_types=1);

namespace App;

use InvalidArgumentException;

/** Money is stored as whole pence (integers) to avoid floating-point rounding errors. */
final class Money
{
    public static function format(int $pence): string
    {
        $sign = $pence < 0 ? '-' : '';
        return $sign . '£' . number_format(abs($pence) / 100, 2);
    }

    /** Parse user input such as "45", "45.5", "£1,200.00" into pence. */
    public static function parse(string $input): int
    {
        $clean = str_replace(['£', ',', ' '], '', trim($input));
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $clean)) {
            throw new InvalidArgumentException("'$input' is not a valid amount");
        }
        [$pounds, $pence] = array_pad(explode('.', $clean), 2, '0');
        return (int) $pounds * 100 + (int) str_pad($pence, 2, '0');
    }
}
