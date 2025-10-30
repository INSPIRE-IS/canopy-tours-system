<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $invoice['number'] ?? '' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 13px; }
        table { border-collapse: collapse; width: 100%; }
        td, th { padding: 4px; vertical-align: top; }
        .border { border: 1px solid black; }
        .no-border { border: none; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .logo { text-align: right; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="logo" style="padding-left:880px;">
        <img src="{{ asset('storage/' . ($file_logo ?? '')) }}" alt="Logo" style="max-height:50px;">
    </div>

    <p>{{ $site['name'] ?? '' }}</p>
    <p>
        {{ $site['reg_no'] ?? '' }}
        <span style="float: right; font-weight: bold;">INVOICE</span>
    </p>

    {{-- Company / Client Info --}}
    <table style="border:1px solid white; border-collapse: collapse; width: 100%;">
        <tr>
            <td style="width:65%;">
                <table class="border" style="width: 80%; height: 85px;">
                    <tr>
                        <td>From:</td>
                        <td>To:</td>
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
            <td>
                <table class="border" style="width: 100%; height: 73px;">
                    <tr>
                        <td>Invoice #:</td>
                        <td>{{ $invoice['number'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Booking #:</td>
                        <td>{{ $booking['ref'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Date:</td>
                        <td>{{ $invoice['date'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td>Amount Due:</td>
                        <td>{{ number_format(($vat_amount ?? 0) - ($amount_paid ?? 0), 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <br>

    {{-- Line Items --}}
    <table class="border">
        <thead>
            <tr>
                <th class="border">Service</th>
                <th class="border">Adults (Qty)</th>
                <th class="border">Children (Qty)</th>
                <th class="border">Guides (Qty)</th>
                <th class="border text-right">Total Cost (ZAR)</th>
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

            <tr>
                <td colspan="2" class="no-border"></td>
                <td class="border text-right" colspan="2">Sub-total (Excl VAT)</td>
                <td class="border text-right">{{ number_format($amount_ex_vat ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="no-border"></td>
                <td class="border text-right">VAT - 15%</td>
                <td class="border text-right">{{ number_format($vat ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="no-border"></td>
                <td class="border text-right">Total</td>
                <td class="border text-right">{{ number_format($vat_amount ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="no-border"></td>
                <td class="border text-right">Amount Paid</td>
                <td class="border text-right">{{ number_format($amount_paid ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="3" class="no-border"></td>
                <td class="border text-right">Balance Due</td>
                <td class="border text-right">{{ number_format(($vat_amount ?? 0) - ($amount_paid ?? 0), 2) }}</td>
            </tr>
        </tbody>
    </table>

    <br>

    {{-- Notes and Banking --}}
    <table class="border">
        <tr>
            <th>NOTES</th>
            <th>Banking Details</th>
        </tr>
        <tr>
            <td style="vertical-align: top;">{{ $notes ?? '' }}</td>
            <td style="vertical-align: top;">
                <p>Account Owner: {{ $bank['owner'] ?? '' }}</p>
                <p>Account Number: {{ $bank['number'] ?? '' }}</p>
                <p>Account Type: {{ $bank['type'] ?? '' }}</p>
                <p>Bank Name: {{ $bank['name'] ?? '' }}</p>
                <p>Branch Code: {{ $bank['branch_code'] ?? '' }}</p>
                <p>Reference: {{ $booking['ref'] ?? '' }}</p>
            </td>
        </tr>
    </table>
</body>
</html>
