<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BookingVoucher;
use App\Models\Service;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class BookingVouchersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $vouchers = BookingVoucher::with(['items', 'service', 'season'])->paginate(10);
        $services = Service::select('id', 'name')->with('seasons')->get();

        return Inertia::render('Admin/BookingVouchers/Index', [
            'vouchers' => $vouchers,
            'services' => $services,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //dd($request->all());
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'season_id' => 'required|exists:seasons,id',
            'name' => 'required|string|max:255',
            // 'team_id' => 'required|exists:teams,id',
            'items' => 'array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.helper' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.disabled' => 'nullable|boolean',
            'items.*.type' => 'nullable|string|max:255',
            'items.*.format' => 'nullable|string|max:255',
        ]);

        $voucher = BookingVoucher::updateOrCreate(
            [
                'service_id' => $data['service_id'],
                'season_id' => $data['season_id'],
                'team_id' => auth()->user()->team_id,
            ],
            [
                'name' => $data['name'],
                // 'created_by' => auth()->id(),
            ]
        );

        foreach ($data['items'] as $item) {
            $voucher->items()->create($item);
        }

        return redirect()->back()->with('success', 'Booking voucher created.');
        // return redirect()->route('admin.booking_vouchers.index')->with('success', 'Booking voucher created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(BookingVoucher $bookingVoucher)
    {
        //
    }

    public function bookingVoucherDownload(BookingVoucher $bookingVoucher)
    {
        // \Debugbar::disable(); // Prevent corruption from Debugbar
        // Ensure the relationship is loaded
        $bookingVoucher->load('items');

        $items = $bookingVoucher->items;
        $booking = null; // assuming you have this relation

        $items = $bookingVoucher->items;
        //dd($items);
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
            // add others as needed
        ];


        // Perform replacements in the descriptions before sending to view
        $items->transform(function ($item) use ($replacements) {
            $item->description = strtr($item->description, $replacements);
            return $item;
        });



        $random = rand(1, 100);
        $pdf = Pdf::loadView('emails.booking_voucher', compact('items'));
        $filePath = 'vouchers/booking_' . $bookingVoucher->id . $random . '.pdf';
        Storage::put($filePath, $pdf->output());
        // Optionally store first
        $path = storage_path('app/' . $filePath);
        //$pdf->save($path);

        //return $pdf->stream('booking-details.pdf');
        //return $pdf->download('booking-details.pdf');
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="booking-voucher.pdf"',
        ]);

        $html = $this->renderTemplate($template->content, $data);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);

        return $pdf->stream("invoice-{$booking->id}.pdf");
        // or ->download("invoice-{$invoice->id}.pdf");

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BookingVoucher $bookingVoucher)
    {
        $data = $request->validate([
            'service_id' => 'required|exists:services,id',
            'season_id' => 'required|exists:seasons,id',
            'name' => 'required|string|max:255',
            // 'team_id' => 'required|exists:teams,id',
            'items' => 'array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.helper' => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.disabled' => 'nullable|boolean',
            'items.*.type' => 'nullable|string|max:255',
            'items.*.format' => 'nullable|string|max:255',
        ]);

        $voucher = BookingVoucher::updateOrCreate(
            [
                'service_id' => $data['service_id'],
                'season_id' => $data['season_id'],
                'team_id' => auth()->user()->team_id,
            ],
            [
                'name' => $data['name'],
                // 'created_by' => auth()->id(),
            ]
        );
        $voucher->items()->delete();

        foreach ($data['items'] as $item) {
            $voucher->items()->create($item);
        }

        return redirect()->back()->with('success', 'Booking voucher created.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BookingVoucher $bookingVoucher)
    {
        //
    }

    private function renderTemplate(string $template, array $data): string
    {
        $placeholders = [
            '{{logo}}'                => $data['logo'] ?? '',
            '{{site_name}}'           => $data['site']['name'] ?? '',
            '{{site_reg_no}}'         => $data['site']['reg_no'] ?? '',
            '{{site_address}}'        => $data['site']['address'] ?? '',
            '{{site_address_code}}'   => $data['site']['address_code'] ?? '',
            '{{client_name}}'         => $data['client']['name'] ?? '',
            '{{client_phone}}'        => $data['client']['phone'] ?? '',
            '{{client_email}}'        => $data['client']['email'] ?? '',
            '{{invoice_number}}'      => $data['invoice']['number'] ?? '',
            '{{invoice_date}}'        => $data['invoice']['date'] ?? '',
            '{{booking_ref}}'         => $data['booking']['ref'] ?? '',
            '{{amount_ex_vat}}'       => $data['amount_ex_vat'] ?? '',
            '{{vat}}'                 => $data['vat'] ?? '',
            '{{amount_paid}}'         => $data['amount_paid'] ?? '',
            '{{balance_due}}'         => $data['balance_due'] ?? '',
            '{{service_name}}'        => $data['service']['name'] ?? '',
            '{{adults}}'              => $data['service']['adults'] ?? '',
            '{{children}}'            => $data['service']['children'] ?? '',
            '{{guides}}'              => $data['service']['guides'] ?? '',
            '{{notes}}'               => $data['notes'] ?? '',
            '{{logo}}'                => $data['logo'] ?? '',
            '{{bank_account_owner}}'  => $data['bank']['owner'] ?? '',
            '{{bank_account_no}}'     => $data['bank']['number'] ?? '',
            '{{bank_account_type}}'   => $data['bank']['type'] ?? '',
            '{{bank_name}}'           => $data['bank']['name'] ?? '',
            '{{branch_code}}'         => $data['bank']['branch_code'] ?? '',
        ];

        $html = strtr($template, $placeholders);

        // Handle dynamic line items if template includes {{lineItems}}
        if (str_contains($html, '{{lineItems}}')) {
            $rows = '';
            foreach ($data['items'] ?? [] as $item) {
                $rows .= "<tr>
                        <td>{$item['description']}</td>
                        <td>{$item['qty']}</td>
                        <td>{$item['price']}</td>
                        <td>{$item['subtotal']}</td>
                    </tr>";
            }
            $html = str_replace('{{lineItems}}', $rows, $html);
        }

        return $html;
    }
}
