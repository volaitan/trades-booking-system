<?php
declare(strict_types=1);

namespace Tests;

use App\Money;
use App\Services\QuoteCalculator;
use App\ValidationException;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

final class QuotesAndInvoicesTest extends TestCase
{
    private function makeQuote(string $status = 'accepted'): int
    {
        $id = $this->app->quotes->create(
            ['customer_name' => 'Kate Byrne', 'email' => 'kate@example.com', 'address' => '27 Bold Street', 'vat_rate' => 20],
            [
                ['description' => 'New radiator', 'quantity' => 1, 'unit_price_pence' => 18000],
                ['description' => 'Radiator valves', 'quantity' => 4, 'unit_price_pence' => 2250],
                ['description' => '', 'quantity' => 1, 'unit_price_pence' => 999], // blank rows are ignored
            ]
        );
        $this->app->quotes->setStatus($id, $status);
        return $id;
    }

    public function testMoneyFormattingAndParsing(): void
    {
        $this->assertSame('£1,234.50', Money::format(123450));
        $this->assertSame(4550, Money::parse('45.5'));
        $this->assertSame(120000, Money::parse('£1,200.00'));
        $this->assertSame(7, Money::parse('0.07'));
        $this->expectException(InvalidArgumentException::class);
        Money::parse('12.345');
    }

    public function testTotalsUseWholePenceAndRoundVatHalfUp(): void
    {
        $t = QuoteCalculator::totals([['quantity' => 3, 'unit_price_pence' => 333]], 20);
        $this->assertSame(999, $t['subtotal']);
        $this->assertSame(200, $t['vat']); // 199.8p rounds to 200p
        $this->assertSame(1199, $t['total']);
        $this->assertSame(0, QuoteCalculator::totals([], 20)['total']);
    }

    public function testQuoteStoresItemsAndTotals(): void
    {
        $quote = $this->app->quotes->find($this->makeQuote());
        $this->assertCount(2, $quote['items']);
        $this->assertSame(27000, $quote['totals']['subtotal']);
        $this->assertSame(5400, $quote['totals']['vat']);
        $this->assertSame(32400, $quote['totals']['total']);
        $this->assertMatchesRegularExpression('/^QT-/', $quote['reference']);
    }

    public function testQuoteValidation(): void
    {
        $this->expectException(ValidationException::class);
        $this->app->quotes->create(['customer_name' => '', 'email' => 'bad', 'vat_rate' => 20], []);
    }

    public function testInvoiceCopiesQuoteAndUsesPaymentTerms(): void
    {
        $id = $this->app->invoices->invoiceFromQuote($this->makeQuote(), new DateTimeImmutable('2026-03-01'));
        $inv = $this->app->invoices->find($id, new DateTimeImmutable('2026-03-01'));
        $this->assertSame('INV-2026-0001', $inv['number']);
        $this->assertSame('2026-03-15', $inv['due_date']);
        $this->assertSame(32400, (int) $inv['total_pence']);
        $this->assertCount(2, $inv['items']);
        $this->assertFalse($inv['overdue']);
    }

    public function testInvoiceNumbersAreSequentialPerYear(): void
    {
        $day = new DateTimeImmutable('2026-12-30');
        $this->app->invoices->invoiceFromQuote($this->makeQuote(), $day);
        $second = $this->app->invoices->invoiceFromQuote($this->makeQuote(), $day);
        $third = $this->app->invoices->invoiceFromQuote($this->makeQuote(), new DateTimeImmutable('2027-01-02'));
        $this->assertSame('INV-2026-0002', $this->app->invoices->find($second)['number']);
        $this->assertSame('INV-2027-0001', $this->app->invoices->find($third)['number']);
    }

    public function testOnlyAcceptedQuotesCanBeInvoicedOnce(): void
    {
        $draft = $this->makeQuote('draft');
        try {
            $this->app->invoices->invoiceFromQuote($draft, $this->now());
            $this->fail('Draft quote should not be invoiceable');
        } catch (DomainException $e) {
            $this->assertStringContainsString('accepted', $e->getMessage());
        }

        $accepted = $this->makeQuote();
        $this->app->invoices->invoiceFromQuote($accepted, $this->now());
        $this->expectException(DomainException::class);
        $this->app->invoices->invoiceFromQuote($accepted, $this->now());
    }

    public function testOverdueAndPaymentSummary(): void
    {
        $a = $this->app->invoices->invoiceFromQuote($this->makeQuote(), new DateTimeImmutable('2026-01-01')); // due 15 Jan
        $b = $this->app->invoices->invoiceFromQuote($this->makeQuote(), new DateTimeImmutable('2026-01-20')); // due 3 Feb
        $c = $this->app->invoices->invoiceFromQuote($this->makeQuote(), new DateTimeImmutable('2026-01-25'));
        $this->app->invoices->markPaid($c, new DateTimeImmutable('2026-01-28'));

        $today = new DateTimeImmutable('2026-01-30');
        $this->assertTrue($this->app->invoices->find($a, $today)['overdue']);
        $this->assertFalse($this->app->invoices->find($b, $today)['overdue']);
        $this->assertFalse($this->app->invoices->find($c, $today)['overdue']);

        $summary = $this->app->invoices->summary($today);
        $this->assertSame(64800, $summary['outstanding']);
        $this->assertSame(32400, $summary['overdue']);
        $this->assertSame(32400, $summary['paid_this_month']);
    }
}
