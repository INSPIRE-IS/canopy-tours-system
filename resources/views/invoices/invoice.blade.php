<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice['number'] ?? '' }}</title>
    <style>
        @page { margin: 30px 40px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111;
        }
        h1, h2, h3, h4, h5, h6, p { margin: 0; padding: 0; }
        table { border-collapse: collapse; width: 100%; }
        td, th { padding: 6px; vertical-align: top; }
        .border { border: 1px solid #000; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .no-border { border: none; }
        .mt-1 { margin-top: 5px; }
        .mt-2 { margin-top: 10px; }
        .mt-3 { margin-top: 15px; }
        .logo img { max-height: 45px; }
        .footer {
            margin-top: 20px;
            font-size: 11px;
            color: #555;
            text-align: center;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <table>
        <tr>
            <td style="width:70%;">
                <strong>{{ $site['name'] ?? '' }}</strong><br>
                {{ $site['address'] ?? '' }}<br>
                {{ $site['address_code'] ?? '' }}<br>
                Reg No: {{ $site['reg_no'] ?? '' }}
            </td>
            <td class="text-right logo">
                @if(!empty($file_logo))
                    <img src="{{ public_path($file_logo) }}" alt="Canopy Tours">
                @endif
                <h2 style="margin-top:10px;">INVOICE</h2>
            </td>
        </tr>
    </table>

    {{-- Invoice Info --}}
    <table class="mt-2" style="width:100%;">
        <tr>
            <td style="width:60%;">
                <table class="border" style="width:100%;">
                    <tr>
                        <th style="width:50%;">From</th>
                        <th>To</th>
                    </tr>
                    <tr>
                        <td>{{ $site['name'] ?? '' }}</td>
                        <td>{{ $client['name'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>{{ $site['address'] ?? '' }}</td>
                        <td>{{ $client['phone'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>{{ $site['address_code'] ?? '' }}</td>
                        <td>{{ $client['email'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
            <td style="padding-left:10px;">
                <table class="border" style="width:100%;">
                    <tr><td>Invoice #:</td><td>{{ $invoice['number'] ?? '' }}</td></tr>
                    <tr><td>Booking #:</td><td>{{ $booking['ref'] ?? '' }}</td></tr>
                    <tr><td>Date:</td><td>{{ $invoice['date'] ?? '' }}</td></tr>
                    <tr><td>Amount Due:</td><td>{{ number_format(($vat_amount ?? 0) - ($amount_paid ?? 0), 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Line Items --}}
    <table class="border mt-3">
        <thead>
            <tr style="background:#f4f4f4;">
                <th class="border">Service</th>
                <th class="border">Adults</th>
                <th class="border">Children</th>
                <th class="border">Guides</th>
                <th class="border text-right">Total (ZAR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($services ?? [] as $service)
                <tr>
                    <td class="border">{{ $service['name'] ?? '' }}</td>
                    <td class="border text-center">{{ $service['adults'] ?? 0 }}</td>
                    <td class="border text-center">{{ $service['children'] ?? 0 }}</td>
                    <td class="border text-center">{{ $service['guides'] ?? 0 }}</td>
                    <td class="border text-right">{{ number_format($service['total'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" class="no-border"></td>
                <td class="border text-right">Sub-total</td>
                <td class="border text-right">{{ number_format($amount_ex_vat ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="no-border"></td>
                <td class="border text-right">VAT (15%)</td>
                <td class="border text-right">{{ number_format($vat ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="no-border"></td>
                <td class="border text-right"><strong>Total</strong></td>
                <td class="border text-right"><strong>{{ number_format($vat_amount ?? 0, 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="3" class="no-border"></td>
                <td class="border text-right">Paid</td>
                <td class="border text-right">{{ number_format($amount_paid ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="no-border"></td>
                <td class="border text-right"><strong>Balance</strong></td>
                <td class="border text-right"><strong>{{ number_format(($vat_amount ?? 0) - ($amount_paid ?? 0), 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    {{-- Notes & Banking --}}
    <table class="border mt-3">
        <tr>
            <th style="width:50%;">Notes</th>
            <th>Banking Details</th>
        </tr>
        <tr>
            <td style="padding:10px;">{{ $notes ?? '' }}</td>
            <td style="padding:10px;">
                <p>Account Owner: {{ $bank['owner'] ?? '' }}</p>
                <p>Account Number: {{ $bank['number'] ?? '' }}</p>
                <p>Account Type: {{ $bank['type'] ?? '' }}</p>
                <p>Bank Name: {{ $bank['name'] ?? '' }}</p>
                <p>Branch Code: {{ $bank['branch_code'] ?? '' }}</p>
                <p>Reference: {{ $booking['ref'] ?? '' }}</p>
            </td>
        </tr>
    </table>

    <div class="footer">
        <p>Thank you for your business!</p>
    </div>
</body>
</html>
