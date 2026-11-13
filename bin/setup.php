<?php
declare(strict_types=1);

/*
 * Create the database tables, add starter services and an admin account.
 *
 *   php bin/setup.php --email=you@example.com --name="Your Name" --password="a-long-password"
 *   php bin/setup.php ... --demo     # also add sample bookings, quotes and invoices
 */

use App\App;
use App\Database;
use App\Services\BookingService;
use App\ValidationException;

$config = require dirname(__DIR__) . '/bootstrap.php';
$opts = getopt('', ['email:', 'name:', 'password:', 'demo']);

$app = App::fromConfig($config);
Database::migrate($app->db);
echo "✔ Tables ready (" . Database::driver($app->db) . ")\n";

if (!$app->services->active()) {
    foreach ([
        ['Boiler service', 60, 8500, 'Annual safety check and service'],
        ['Boiler repair', 120, 9500, 'Diagnosis and repair, parts extra'],
        ['Leak or blockage', 60, 7500, 'Fix leaks, drips and blocked drains'],
        ['Radiator install', 180, 18000, 'Supply and fit a new radiator'],
        ['Bathroom survey', 30, 0, 'Free visit to measure up and quote'],
    ] as [$name, $minutes, $price, $desc]) {
        $app->services->create($name, $minutes, $price, $desc);
    }
    echo "✔ Starter services added\n";
}

if (isset($opts['email'], $opts['password'])) {
    try {
        $app->auth->createUser($opts['email'], $opts['name'] ?? 'Admin', $opts['password']);
        echo "✔ Admin account created for {$opts['email']}\n";
    } catch (ValidationException $e) {
        fwrite(STDERR, "✘ " . $e->getMessage() . "\n");
        exit(1);
    } catch (PDOException) {
        echo "• Admin {$opts['email']} already exists\n";
    }
} else {
    echo "• No admin created. Re-run with --email=... --password=... to add one.\n";
}

if (isset($opts['demo'])) {
    $now = new DateTimeImmutable();
    $services = $app->services->active();
    $people = [
        ['Sarah Jones', 'CH44 1AB', '12 Victoria Road'], ['Mo Ahmed', 'CH45 2CD', '4 Grove Street'],
        ['Kate Byrne', 'L1 8JQ', '27 Bold Street'], ['Tom Price', 'CH41 5EF', '9 Church Lane'],
    ];
    $day = $now->modify('+2 days');
    foreach ($people as $i => [$name, $pc, $addr]) {
        $service = $services[$i % count($services)];
        while (!($slots = $app->availability->slotsFor($day, (int) $service['duration_minutes'], $now))) {
            $day = $day->modify('+1 day');
        }
        $app->booking->book([
            'service_id' => $service['id'], 'customer_name' => $name, 'email' => strtolower(strtok($name, ' ')) . '@example.com',
            'phone' => '07700 900' . str_pad((string) ($i * 37), 3, '0', STR_PAD_LEFT), 'address' => $addr, 'postcode' => $pc,
            'date' => $slots[0]->format('Y-m-d'), 'time' => $slots[0]->format('H:i'), 'notes' => $i === 0 ? 'Side gate is open.' : '',
        ], $now);
    }
    $q1 = $app->quotes->create(['customer_name' => 'Sarah Jones', 'email' => 'sarah@example.com', 'address' => '12 Victoria Road, CH44 1AB', 'vat_rate' => 20], [
        ['description' => 'Combi boiler replacement (labour)', 'quantity' => 1, 'unit_price_pence' => 65000],
        ['description' => '30kW combi boiler', 'quantity' => 1, 'unit_price_pence' => 145000],
        ['description' => 'Magnetic system filter', 'quantity' => 1, 'unit_price_pence' => 12000],
    ]);
    $app->quotes->setStatus($q1, 'accepted');
    $inv = $app->invoices->invoiceFromQuote($q1, $now->modify('-20 days'));
    $q2 = $app->quotes->create(['customer_name' => 'Kate Byrne', 'email' => 'kate@example.com', 'address' => '27 Bold Street, L1 8JQ', 'vat_rate' => 20], [
        ['description' => 'Replace kitchen radiator', 'quantity' => 1, 'unit_price_pence' => 18000],
        ['description' => 'Thermostatic radiator valves', 'quantity' => 4, 'unit_price_pence' => 2250],
    ]);
    $app->quotes->setStatus($q2, 'accepted');
    $paid = $app->invoices->invoiceFromQuote($q2, $now->modify('-3 days'));
    $app->invoices->markPaid($paid, $now);
    $app->quotes->create(['customer_name' => 'Tom Price', 'email' => 'tom@example.com', 'address' => '9 Church Lane, CH41 5EF', 'vat_rate' => 20], [
        ['description' => 'Bathroom refit (labour, 5 days)', 'quantity' => 5, 'unit_price_pence' => 28000],
    ]);
    echo "✔ Demo data added\n";
}
