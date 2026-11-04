<?php

namespace Tests\Unit;

use Tests\TestCase;

class WholesalerInvoiceGuestViewTest extends TestCase
{
    public function test_it_shows_the_wholesaler_and_guest_details(): void
    {
        $html = view('invoices.invoice', $this->invoiceData([
            'name' => 'Hyperli',
            'phone' => '',
            'email' => '',
            'guest_name' => 'Jane Guest',
            'guest_phone' => '+27821234567',
            'guest_email' => 'jane@example.com',
        ]))->render();

        $this->assertStringContainsString('Hyperli', $html);
        $this->assertStringContainsString('<strong>Guest:</strong>', $html);
        $this->assertStringContainsString('Jane Guest', $html);
        $this->assertStringContainsString('+27821234567', $html);
        $this->assertStringContainsString('jane@example.com', $html);
    }

    public function test_it_does_not_duplicate_the_guest_label_on_a_normal_invoice(): void
    {
        $html = view('invoices.invoice', $this->invoiceData([
            'name' => 'Jane Guest',
            'phone' => '+27821234567',
            'email' => 'jane@example.com',
            'guest_name' => null,
            'guest_phone' => null,
            'guest_email' => null,
        ]))->render();

        $this->assertStringContainsString('Jane Guest', $html);
        $this->assertStringNotContainsString('<strong>Guest:</strong>', $html);
    }

    private function invoiceData(array $client): array
    {
        return [
            'document' => ['label' => 'Tax Invoice', 'number_label' => 'Tax Invoice #'],
            'site' => ['name' => 'Karkloof Canopy Tour', 'address_lines' => []],
            'client' => $client,
            'invoice' => ['number' => 'INV-2026-000001', 'date' => '2026-11-04'],
            'booking' => ['ref' => 'REF-GUEST1'],
            'services' => [],
            'extras' => [],
            'amount_ex_vat' => 0,
            'vat' => 0,
            'vat_amount' => 0,
            'amount_paid' => 0,
            'notes' => '',
            'bank' => [],
        ];
    }
}
