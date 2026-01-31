<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingVoucher;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Team;

class RendererService
{
    /**
     * Render a template by replacing {{placeholders}} with data.
     */
    public function invoice(Booking $booking, Customer $customer): array
    {
        $site = Team::select('full_name', 'reg_no', 'address', 'address_code', 'account_owner', 'account_number', 'account_type', 'bank_name', 'branch_code')->find($booking->team_id);
        $invoice = Invoice::with('items', 'items.season')->where('booking_id', $booking->id)->first();
        if (!$invoice) {
            throw new \RuntimeException('Invoice not found for booking ' . $booking->id);
        }
        $services = $invoice->items->map(function ($item) {
            $services = [
                'name' => $item->service->name . ' ' . $item->season->name,
                'adults' => $item->pax_adults,
                'children' => $item->pax_children,
                'guides' => $item->pax_guides,
                'price_adult' => $item->price_adult,
                'price_child' => $item->price_child,
                'guides' => $item->pax_guides,
                'total' => $item->price_adult  + $item->price_child  + $item->price_guide,
            ];

            return $services;
        })->unique()->toArray();
        //dd($services);
        $data = [
            'site' => [
                'name' => $site->full_name,
                'reg_no' => $site->reg_no,
                'address' => $site->address,
                'address_code' => $site->address_code,
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
            'file_logo' => $booking->team->file_logo?->path ?? asset('images/logo.png'),
            'amount_ex_vat' => $booking->grand_tot_due,
            'vat'           => $booking->grand_tot_due * 0.15,
            'vat_amount'    => $booking->grand_tot_due * 1.15,
            'amount_paid'   => $booking->tot_paid,
            'balance_due'   => ($booking->grand_tot_due * 1.15) - $booking->tot_paid,
            'notes'         => 'Please pay within 7 days.',
            'bank' => [
                'owner' => $site->account_owner,
                'number' => $site->account_number,
                'type'   => $site->account_type,
                'name'   => $site->bank_name,
                'branch_code' => $site->branch_code,
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
