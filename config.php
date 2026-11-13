<?php
declare(strict_types=1);

$env = static fn (string $key, string $default = ''): string => (string) (getenv($key) !== false ? getenv($key) : $default);

return [
    'business' => [
        'name'    => $env('BUSINESS_NAME', 'Brightline Plumbing & Heating'),
        'email'   => $env('BUSINESS_EMAIL', 'hello@example.com'),
        'phone'   => $env('BUSINESS_PHONE', '0000 000000'),
        'address' => $env('BUSINESS_ADDRESS', '1 Example Street, Your Town, AB1 2CD'),
        'vat_number' => $env('VAT_NUMBER', 'GB000000000'),
    ],
    'database' => [
        // sqlite:/path/to/file.sqlite  or  mysql:host=127.0.0.1;dbname=trades;charset=utf8mb4
        'dsn'      => $env('DB_DSN', 'sqlite:' . __DIR__ . '/database/app.sqlite'),
        'user'     => $env('DB_USER'),
        'password' => $env('DB_PASSWORD'),
    ],
    'booking' => [
        // ISO day number (1 = Monday) => [open, close]. Missing days are closed.
        'opening_hours' => [
            1 => ['08:00', '17:00'],
            2 => ['08:00', '17:00'],
            3 => ['08:00', '17:00'],
            4 => ['08:00', '17:00'],
            5 => ['08:00', '17:00'],
            6 => ['09:00', '13:00'],
        ],
        'slot_minutes'      => 30,   // start times offered every 30 minutes
        'min_notice_hours'  => 24,   // no same-day surprises
        'max_days_ahead'    => 60,
        'engineers'         => 1,    // how many jobs can overlap
    ],
    'invoices' => [
        'vat_rate_percent' => 20,
        'payment_terms_days' => 14,
    ],
];
