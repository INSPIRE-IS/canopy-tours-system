<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $document['label'] ?? 'Invoice' }} {{ $invoice['number'] ?? '' }}</title>
    <style>
        @page { margin: 30px 40px; }
        body {
            font-family: Arial, sans-serif;
            font-size: 9pt;
            color: #000;
        }
        table { border-collapse: collapse; width: 100%; }
        td, th { padding: 4px 6px; vertical-align: top; }
        .border { border: 1px solid #000; }
        .grey { background: #BBBBBB; }
        .right { text-align: right; }
        .center { text-align: center; }
        .mt-1 { margin-top: 6px; }
        .mt-2 { margin-top: 12px; }
        .logo img { max-height: 50px; }
        .title {
            font-size: 18pt;
            color: #BBBBBB;
            font-weight: bold;
            text-align: right;
        }
        .reg-line {
            margin-left: 18px;
            margin-top: 2px;
            font-size: 7pt;
        }
        .meta-left { width: 70%; padding-right: 8px; }
        .meta-right { width: 30%; }
        .meta-table th { font-weight: bold; }
        .items th { font-weight: bold; }
    </style>
</head>
<body>
    @php($document = $document ?? [])
    <table>
        <tr>
            <td class="logo" style="width:60%;">
                @if(!empty($file_logo))
                    <img src="{{ public_path($file_logo) }}" alt="Logo">
                @endif
            </td>
            <td style="width:40%;">
                <div class="title">{{ strtoupper($document['label'] ?? 'INVOICE') }}</div>
            </td>
        </tr>
    </table>

    <div class="reg-line">
        Reg. {{ $site['reg_no'] ?? '' }}
        @if(!empty($site['vat_no']))
            &nbsp;| VAT No: {{ $site['vat_no'] }}
        @endif
    </div>

    <table class="mt-2">
        <tr>
            <td class="meta-left">
                <table class="meta-table">
                    <tr>
                        <th class="border grey center" style="width:50%;">From:</th>
                        <th class="border grey center">To:</th>
                    </tr>
                    <tr>
                        <td class="border">
                            {{ $site['name'] ?? '' }}<br>
                            {{ $site['address'] ?? '' }}<br>
                            {{ $site['address_code'] ?? '' }}
                        </td>
                        <td class="border">
                            {{ $client['name'] ?? '' }}<br>
                            {{ $client['phone'] ?? '' }}<br>
                            {{ $client['email'] ?? '' }}
                        </td>
                    </tr>
                </table>
            </td>
            <td class="meta-right">
                <table class="meta-table">
                    <tr>
                        <th class="border grey">{{ $document['number_label'] ?? 'Invoice #' }}:</th>
                        <td class="border right">{{ $invoice['number'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <th class="border grey">Booking #:</th>
                        <td class="border right">{{ $booking['ref'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <th class="border grey">Date:</th>
                        <td class="border right">{{ $invoice['date'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <th class="border grey">Amount Due:</th>
                        <td class="border right">{{ number_format(($vat_amount ?? 0) - ($amount_paid ?? 0), 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="items mt-2">
        <thead>
            <tr>
                <th class="border grey center" style="width:40%;">Service</th>
                <th class="border grey center" style="width:15%;">Adults (Qty)</th>
                <th class="border grey center" style="width:15%;">Children (Qty)</th>
                <th class="border grey center" style="width:15%;">Guides (Qty)</th>
                <th class="border grey center" style="width:15%;">Total Cost (ZAR)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($services ?? [] as $service)
                <tr>
                    <td class="border">{{ $service['name'] ?? '' }}</td>
                    <td class="border center">{{ $service['adults'] ?? 0 }}</td>
                    <td class="border center">{{ $service['children'] ?? 0 }}</td>
                    <td class="border center">{{ $service['guides'] ?? 0 }}</td>
                    <td class="border right">{{ number_format($service['total'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="right">Sub-total (Excl VAT)</td>
                <td class="border right">{{ number_format($amount_ex_vat ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="right">VAT - 15.0%</td>
                <td class="border right">{{ number_format($vat ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="right"><strong>Total:</strong></td>
                <td class="border right"><strong>{{ number_format($vat_amount ?? 0, 2) }}</strong></td>
            </tr>
            <tr>
                <td colspan="4" class="right">Amount Paid:</td>
                <td class="border right">{{ number_format($amount_paid ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td colspan="4" class="right grey"><strong>Balance Due:</strong></td>
                <td class="border right grey"><strong>{{ number_format(($vat_amount ?? 0) - ($amount_paid ?? 0), 2) }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <table class="border mt-2">
        <tr>
            <th class="grey" style="width:50%;">Notes</th>
            <th class="grey">Banking Details</th>
        </tr>
        <tr>
            <td>{{ $notes ?? '' }}</td>
            <td>
                <p>Account Owner: {{ $bank['owner'] ?? '' }}</p>
                <p>Account Number: {{ $bank['number'] ?? '' }}</p>
                <p>Account Type: {{ $bank['type'] ?? '' }}</p>
                <p>Bank Name: {{ $bank['name'] ?? '' }}</p>
                <p>Branch Code: {{ $bank['branch_code'] ?? '' }}</p>
                <p>Reference: {{ $booking['ref'] ?? '' }}</p>
            </td>
        </tr>
    </table>
    <p>
    {{ $site['invoice_note'] ?? '' }}
    </p>
</body>
</html>
