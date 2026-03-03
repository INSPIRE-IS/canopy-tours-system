<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingNote;
use App\Models\BookingVoucher;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Team;
use App\Models\TypeInvoice;
use Illuminate\Support\Str;

class RendererService
{
    /**
     * Render a template by replacing {{placeholders}} with data.
     */
    public function invoice(Booking $booking, Customer $customer, ?Invoice $invoice = null): array
    {
        $site = Team::select('full_name', 'reg_no', 'vat_no', 'invoice_note', 'address', 'address_code', 'account_owner', 'account_number', 'account_type', 'bank_name', 'branch_code')->find($booking->team_id);
        $invoiceTypeId = TypeInvoice::where('slug', 'std')->value('id');
        $invoice = $invoice ?: Invoice::with([
            'items',
            'items.season',
            'items.service',
            'items.booking_inventory.booking_item.booking_fee',
        ])
            ->where('booking_id', $booking->id)
            ->when($invoiceTypeId, function ($query) use ($invoiceTypeId) {
                $query->where('type_invoice_id', $invoiceTypeId);
            })
            ->first();

        if (!$invoice) {
            $invoice = Invoice::with([
                'items',
                'items.season',
                'items.service',
                'items.booking_inventory.booking_item.booking_fee',
            ])->where('booking_id', $booking->id)->first();
        }
        if (!$invoice) {
            throw new \RuntimeException('Invoice not found for booking ' . $booking->id);
        }
        $typeSlug = $invoice->typeInvoice?->slug;
        $documentLabel = match ($typeSlug) {
            'quote' => 'Quote',
            'proforma' => 'Proforma Invoice',
            'receipt' => 'Receipt',
            default => 'Invoice',
        };
        $documentNumberLabel = match ($typeSlug) {
            'quote' => 'Quote #',
            'proforma' => 'Proforma #',
            'receipt' => 'Receipt #',
            default => 'Invoice #',
        };
        $emailMessageLines = $documentLabel === 'Quote'
            ? [
                "Hello {$customer->full_name},",
                'Please find your quote attached.',
                "Your booking reference is {$booking->reference}.",
                'Please contact us if you have any questions.',
            ]
            : [
                "Hello {$customer->full_name},",
                'Your booking has been paid.',
                'Thank you for choosing Canopy Tours.',
                "Your booking reference is {$booking->reference}.",
                'Please contact us if you have any questions.',
            ];
        $emailFooterNote = $documentLabel === 'Quote'
            ? 'Thank you for considering Canopy Tours.'
            : 'Please pay within 7 days.';
        $services = $invoice->items->map(function ($item) {
            $fee = $item->booking_inventory?->booking_item?->booking_fee;
            $adultTotal = $fee?->tot_adults ?? ($item->price_adult ?? 0);
            $childTotal = $fee?->tot_children ?? ($item->price_child ?? 0);
            $guideTotal = $fee?->tot_guides ?? ($item->price_guide ?? 0);
            $total = (float) $adultTotal + (float) $childTotal + (float) $guideTotal;
            $inventoryDate = $item->booking_inventory?->date;
            $inventoryTime = $item->booking_inventory?->inventory_time_slot?->start_time;

            return [
                'name' => trim(($item->service?->name ?? '') . ' ' . ($item->season?->name ?? '')),
                'date' => $inventoryDate,
                'time' => $inventoryTime,
                'adults' => $item->pax_adults,
                'children' => $item->pax_children,
                'guides' => $item->pax_guides,
                'price_adult' => $adultTotal,
                'price_child' => $childTotal,
                'price_guide' => $guideTotal,
                'total' => $total,
            ];
        })->values()->toArray();
        $invoiceNote = BookingNote::where('booking_id', $booking->id)
            ->where('type', 'invoice_note')
            ->orderByDesc('created_at')
            ->value('note');
        //dd($services);
        $amountTotal = $invoice->total ?? $booking->grand_tot_due ?? 0;
        $amountExVat = round(($amountTotal / 1.15), 2);
        $vat = round(($amountTotal - $amountExVat), 2);
        $amountPaid = ($booking->tot_paid ?? 0) * 1.15;
        $balanceDue = max(0, $amountTotal - $amountPaid);

        $logoPath = $booking->team->file_logo?->path;
        if ($logoPath && Str::startsWith($logoPath, ['http://', 'https://'])) {
            $parsedPath = parse_url($logoPath, PHP_URL_PATH);
            if (is_string($parsedPath) && str_contains($parsedPath, '/storage/')) {
                $logoPath = ltrim($parsedPath, '/');
            } else {
                $logoPath = null;
            }
        }
        $logoPath = $logoPath ?: 'images/logo.png';

        $data = [
            'document' => [
                'label' => $documentLabel,
                'number_label' => $documentNumberLabel,
                'email_subject' => $documentLabel . ' ' . $invoice->invoice_number,
                'email_message_lines' => $emailMessageLines,
                'email_footer_note' => $emailFooterNote,
            ],
            'site' => [
                'name' => $site?->full_name,
                'reg_no' => $site?->reg_no,
                'vat_no' => $site?->vat_no,
                'address' => $site?->address,
                'address_code' => $site?->address_code,
                'invoice_note' => $site?->invoice_note,
                //'site_logo' => $site->file_logo->path,
                //'file_logo' => $item->service->team->file_logo->path,
            ],
            'client' => [
                'name' => $customer->full_name,
                'phone' => $customer->contact_number,
                'email' => $customer->email_address,
            ],
            'invoice' => [
                'number' => $invoice->invoice_number,
                'date'   => $invoice->invoice_date,
            ],
            'booking' => [
                'ref' => $booking->reference,
            ],
            'services' => $services,
            'file_logo' => $logoPath,
            'amount_ex_vat' => $amountExVat,
            'vat'           => $vat,
            'vat_amount'    => $amountTotal,
            'amount_paid'   => $amountPaid,
            'balance_due'   => $balanceDue,
            'notes'         => $invoiceNote ?: '',
            'bank' => [
                'owner' => $site?->account_owner,
                'number' => $site?->account_number,
                'type'   => $site?->account_type,
                'name'   => $site?->bank_name,
                'branch_code' => $site?->branch_code,
            ],
        ];
        //dd($data);
        // 🔹 Load Blade view with data
        return $data;
    }


    public function voucher(Booking $booking, BookingVoucher $bookingVoucher)
    {

        //dd($voucher);
        $items = $bookingVoucher->items;

        // Build replacements for placeholders
        $replacements = [
            '{{reference}}' => $booking->reference ?? 'REF-000000',
            '{{provider}}' => $bookingVoucher->provider_name ?? 'Canopy Tours',
            '{{service}}' => $bookingVoucher->service_name ?? 'Service Test',
            '{{start_time_and_date}}' => $bookingVoucher->start_time_and_date ?? '12:00 12 Jan 2025',
            '{{start_location}}' => $bookingVoucher->start_location ?? 'Start Location',
            '{{end_location}}' => $bookingVoucher->end_location ?? 'End Location',
            '{{lead_name}}' => $booking->lead_name ?? 'Test Lead',
            '{{lead_email}}' => $booking->lead_email ?? 'test@email.com',
            '{{number_in_party}}' => $booking->number_in_party ?? '8',
            '{{supplier_email}}' => $bookingVoucher->supplier_email ?? 'test@email.com',
            '{{supplier_landline}}' => $bookingVoucher->supplier_landline ?? '123456789',
            '{{supplier_mobile}}' => $bookingVoucher->supplier_mobile ?? '123456789',
        ];

        // Perform replacements in the descriptions before sending to view
        $items->transform(function ($item) use ($replacements, $booking) {
            $item->description = strtr($item->description, $replacements);
            $item->team_logo = $booking->team->file_logo ?? null;
            return $item;
        });

        return $items;
    }
}
