<?php
declare(strict_types=1);

namespace App\Controllers;

use App\App;
use App\Auth;
use App\Csrf;
use App\Money;
use App\ValidationException;
use App\View;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

final class AdminController
{
    public function __construct(private readonly App $app, private readonly View $view)
    {
    }

    // ---------- Auth ----------

    public function loginForm(array $errors = []): string
    {
        return $this->view->render('admin/login', ['title' => 'Sign in', 'errors' => $errors, 'csrf' => Csrf::token($_SESSION)]);
    }

    public function login(): string
    {
        if (!Csrf::valid($_SESSION, $_POST['_csrf'] ?? null)) {
            return $this->loginForm(['email' => 'Your session expired. Please try again.']);
        }
        try {
            if ($this->app->auth->attempt($_SESSION, (string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''), time())) {
                return $this->redirect('/admin');
            }
            return $this->loginForm(['email' => 'Incorrect email or password.']);
        } catch (ValidationException $e) {
            return $this->loginForm($e->errors);
        }
    }

    public function logout(): string
    {
        $this->guard();
        $_SESSION = [];
        session_regenerate_id(true);
        return $this->redirect('/admin/login');
    }

    // ---------- Dashboard ----------

    public function dashboard(): string
    {
        $this->guard();
        $today = new DateTimeImmutable('today');
        return $this->page('admin/dashboard', [
            'title'     => 'Dashboard',
            'todayJobs' => array_filter(
                $this->app->bookings->list(null, $today->format('Y-m-d')),
                static fn ($b) => str_starts_with($b['starts_at'], $today->format('Y-m-d')) && $b['status'] !== 'cancelled'
            ),
            'pending'   => $this->app->bookings->countByStatus('pending'),
            'upcoming'  => array_slice(array_values(array_filter(
                $this->app->bookings->list(null, $today->format('Y-m-d')),
                static fn ($b) => in_array($b['status'], ['pending', 'confirmed'], true)
            )), 0, 8),
            'money'     => $this->app->invoices->summary($today),
        ]);
    }

    // ---------- Bookings ----------

    public function bookings(): string
    {
        $this->guard();
        $status = $_GET['status'] ?? null;
        return $this->page('admin/bookings', [
            'title'    => 'Bookings',
            'bookings' => $this->app->bookings->list($status ?: null, ($_GET['all'] ?? '') ? null : date('Y-m-d')),
            'status'   => $status,
        ]);
    }

    public function bookingStatus(string $id): string
    {
        $this->guardPost();
        try {
            $this->app->bookings->setStatus((int) $id, (string) ($_POST['status'] ?? ''));
            $this->flash('Booking updated.');
        } catch (InvalidArgumentException) {
            $this->flash('Unknown status.', 'error');
        }
        return $this->redirect($_SERVER['HTTP_REFERER'] ?? '/admin/bookings');
    }

    // ---------- Quotes ----------

    public function quotes(): string
    {
        $this->guard();
        return $this->page('admin/quotes', ['title' => 'Quotes', 'quotes' => $this->app->quotes->all()]);
    }

    public function newQuote(array $errors = [], array $old = []): string
    {
        $this->guard();
        $booking = isset($_GET['booking']) ? $this->app->bookings->find((int) $_GET['booking']) : null;
        if ($booking && !$old) {
            $old = [
                'booking_id' => $booking['id'], 'customer_name' => $booking['customer_name'], 'email' => $booking['email'],
                'address' => $booking['address'] . ', ' . $booking['postcode'],
                'items' => [['description' => $booking['service_name'], 'quantity' => 1, 'unit_price' => number_format($booking['price_pence'] / 100, 2, '.', '')]],
            ];
        }
        return $this->page('admin/quote_form', [
            'title' => 'New quote', 'errors' => $errors, 'old' => $old,
            'vatRate' => $this->app->config['invoices']['vat_rate_percent'],
        ]);
    }

    public function createQuote(): string
    {
        $this->guardPost();
        $items = [];
        $errors = [];
        foreach ((array) ($_POST['items'] ?? []) as $row) {
            if (trim((string) ($row['description'] ?? '')) === '') {
                continue;
            }
            try {
                $items[] = [
                    'description' => (string) $row['description'],
                    'quantity' => max(0, (int) ($row['quantity'] ?? 1)),
                    'unit_price_pence' => Money::parse((string) ($row['unit_price'] ?? '0')),
                ];
            } catch (InvalidArgumentException $e) {
                $errors['items'] = $e->getMessage();
            }
        }
        try {
            if ($errors) {
                throw new ValidationException($errors);
            }
            $id = $this->app->quotes->create([
                'customer_name' => (string) ($_POST['customer_name'] ?? ''),
                'email' => (string) ($_POST['email'] ?? ''),
                'address' => (string) ($_POST['address'] ?? ''),
                'notes' => (string) ($_POST['notes'] ?? ''),
                'booking_id' => ($_POST['booking_id'] ?? '') !== '' ? (int) $_POST['booking_id'] : null,
                'vat_rate' => (int) ($_POST['vat_rate'] ?? $this->app->config['invoices']['vat_rate_percent']),
            ], $items);
        } catch (ValidationException $e) {
            return $this->newQuote($e->errors, $_POST);
        }
        $this->flash('Quote created.');
        return $this->redirect("/admin/quotes/$id");
    }

    public function showQuote(string $id): string
    {
        $this->guard();
        $quote = $this->app->quotes->find((int) $id) ?? $this->notFound();
        return $this->page('admin/quote_view', ['title' => 'Quote ' . $quote['reference'], 'quote' => $quote]);
    }

    public function quoteStatus(string $id): string
    {
        $this->guardPost();
        try {
            $this->app->quotes->setStatus((int) $id, (string) ($_POST['status'] ?? ''));
            $this->flash('Quote updated.');
        } catch (InvalidArgumentException) {
            $this->flash('Unknown status.', 'error');
        }
        return $this->redirect("/admin/quotes/$id");
    }

    public function invoiceQuote(string $id): string
    {
        $this->guardPost();
        try {
            $invoiceId = $this->app->invoices->invoiceFromQuote((int) $id, new DateTimeImmutable('today'));
        } catch (DomainException $e) {
            $this->flash($e->getMessage(), 'error');
            return $this->redirect("/admin/quotes/$id");
        }
        $this->flash('Invoice created.');
        return $this->redirect("/admin/invoices/$invoiceId");
    }

    // ---------- Invoices ----------

    public function invoices(): string
    {
        $this->guard();
        $today = new DateTimeImmutable('today');
        return $this->page('admin/invoices', [
            'title' => 'Invoices',
            'invoices' => $this->app->invoices->all($today),
            'money' => $this->app->invoices->summary($today),
        ]);
    }

    public function showInvoice(string $id): string
    {
        $this->guard();
        $invoice = $this->app->invoices->find((int) $id) ?? $this->notFound();
        // Stand-alone, print-ready page: use the browser's "Save as PDF" to send it.
        return $this->view->render('admin/invoice_view', [
            'invoice' => $invoice,
            'business' => $this->app->config['business'],
            'csrf' => Csrf::token($_SESSION),
            'flash' => $this->takeFlash(),
        ], null);
    }

    public function payInvoice(string $id): string
    {
        $this->guardPost();
        $this->app->invoices->markPaid((int) $id, new DateTimeImmutable('today'));
        $this->flash('Invoice marked as paid.');
        return $this->redirect("/admin/invoices/$id");
    }

    // ---------- Helpers ----------

    private function guard(): void
    {
        if (Auth::user($_SESSION) === null) {
            header('Location: /admin/login', true, 303);
            exit;
        }
    }

    private function guardPost(): void
    {
        $this->guard();
        if (!Csrf::valid($_SESSION, $_POST['_csrf'] ?? null)) {
            http_response_code(419);
            exit('Your session expired. Go back, refresh the page and try again.');
        }
    }

    private function page(string $template, array $data): string
    {
        return $this->view->render($template, $data + [
            'user' => Auth::user($_SESSION),
            'csrf' => Csrf::token($_SESSION),
            'flash' => $this->takeFlash(),
        ], 'admin/layout');
    }

    private function redirect(string $to): string
    {
        // Only allow redirects within this site.
        $path = parse_url($to, PHP_URL_PATH) ?: '/admin';
        $query = parse_url($to, PHP_URL_QUERY);
        header('Location: ' . $path . ($query ? "?$query" : ''), true, 303);
        return '';
    }

    private function flash(string $message, string $type = 'success'): void
    {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }

    private function takeFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    private function notFound(): never
    {
        http_response_code(404);
        exit('Not found');
    }
}
