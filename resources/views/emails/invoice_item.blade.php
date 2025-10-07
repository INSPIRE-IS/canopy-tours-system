<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice['invoice']['number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #000; }
        .header {  margin: 0; margin-bottom: 10px; color: #ccc }
        .subheader { font-size: 16px; margin: 0; text-align: left; font-size: 11px; margin-bottom: 20px; }

        table { width: 100%; border-collapse: collapse; border: none;}
        /* th, td { border: 1px solid #000; padding: 6px; font-size: 12px; } */
        th { background: #e5e5e5; text-align: left; }
        .no-border td, .no-border th { border: none; }

        .align-right { text-align: right; }
        .align-left { text-align: left; }

        .totals td { border: 1px solid #000; }
        .totals td.label { font-weight: bold; }

        .highlight { background: #ccc; font-weight: bold; }

        .bank { margin-top: 25px; }
        .bank td { border: none; padding: 2px 0; }
        .bank strong { display: inline-block; width: 120px; }
    </style>
</head>
<body>

    <!-- Header -->
    <table style="width:100%; margin-bottom:5px; border:none;">
        <tr>
            <td style="text-align:left; font-size:20px; font-weight:bold; border:none; color: #ccc; text-transform: uppercase;">
                {{ $invoice['site']['name'] }}
            </td>
            <td style="text-align:right; font-size:20px; font-weight:bold; border:none; color: #ccc;">
                TAX INVOICE
            </td>
        </tr>
    </table>
    <div class="subheader">
        Reg. {{ $invoice['site']['reg_no'] }} &nbsp;&nbsp; VAT: {{ $invoice['site']['vat_no'] }}
    </div>

    <!-- From / To / Invoice Details -->
    <table style="margin-bottom:20px; width: 100%;">
        <tr>
            <td width="50%">
                <table style="border:1px solid black; border-collapse: collapse; width: 100%; height: 85px;">
                    <colgroup>
                        <col style="width: 46.5876%; border:1px solid black;">
                        <col style="width: 53.4894%; border:1px solid black;">
                    </colgroup>
                    <tbody>
                        <tr class="highlight" style="height: 21px; border:1px solid black;">
                            <td style="border-right:1px solid black; text-align: center; font-weight: bold;">From:</td>
                            <td style="text-align: center; font-weight: bold;">To:</td>
                        </tr>
                        <tr style="height: 21px;">
                            <td style="border-right:1px solid black;">{{ $invoice['site']['name'] }}</td>
                            <td>{{ $invoice['client']['name'] }}</td>
                        </tr>
                        <tr style="height: 21px;">
                            <td style="border-right:1px solid black;">{{ $invoice['site']['address'] }}</td>
                            <td>{{ $invoice['client']['phone'] }}</td>
                        </tr>
                        <tr style="height: 10px;">
                            <td style="border-right:1px solid black;">{{ $invoice['site']['address_code'] }}</td>
                            <td>{{ $invoice['client']['email'] }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td width="10%">

            </td>
            <td width="40%">
                <table style="border:1px solid black; border-collapse: collapse; width: 100%; height: 85px;">
                    <tbody>
                        <tr  style="height: 21px; border:1px solid black;">
                            <td class="highlight" style="border-right:1px solid black; text-align: left; font-weight: bold;">Invoice #:</td>
                            <td style="text-align: right;">{{ $invoice['invoice']['number'] }}</td>
                        </tr>
                        <tr  style="height: 21px; border:1px solid black;">
                            <td class="highlight" style="border-right:1px solid black; text-align: left; font-weight: bold;">Proforma #:</td>
                            <td style="text-align: right;">{{ $invoice['invoice']['proforma'] }}</td>
                        </tr>
                        <tr  style="height: 21px; border:1px solid black;">
                            <td class="highlight" style="border-right:1px solid black; text-align: left; font-weight: bold;">Booking #:</td>
                            <td style="text-align: right;">{{ $invoice['invoice']['booking'] }}</td>
                        </tr>
                        <tr  style="height: 21px; border:1px solid black;">
                            <td class="highlight" style="border-right:1px solid black; text-align: left; font-weight: bold;">Date:</td>
                            <td style="text-align: right;">{{ $invoice['invoice']['date'] }}</td>
                        </tr>
                        <tr  style="height: 21px; border:1px solid black;">
                            <td class="highlight" style="border-right:1px solid black; text-align: left; font-weight: bold;">Amount Due:</td>
                            <td style="text-align: right;">R{{ number_format($invoice['invoice']['amount_due'], 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    <!-- Service + Totals -->
    <table style="border:1px solid black; border-collapse: collapse; width: 100%; height: 85px;">
        <thead>
            <tr style="border:1px solid black; ">
                <th style="border-right:1px solid black; text-align: center; font-weight: bold;">Service</th>
                <th style="border-right:1px solid black; text-align: center; font-weight: bold;">Adults (Qty)</th>
                <th style="border-right:1px solid black; text-align: center; font-weight: bold;">Children (Qty)</th>
                <th style="border-right:1px solid black; text-align: center; font-weight: bold;">Guides (Qty)</th>
                <th style="border-right:1px solid black; text-align: center; font-weight: bold;">Total Cost (ZAR)</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border:1px solid black; ">
                <td style="border-right:1px solid #ccc; text-align: center;">{{ $invoice['service']['name'] }}</td>
                <td style="border-right:1px solid #ccc; text-align: center;">{{ $invoice['service']['adults'] }}</td>
                <td style="border-right:1px solid #ccc; text-align: center;">{{ $invoice['service']['children'] }}</td>
                <td style="border-right:1px solid #ccc; text-align: center;">{{ $invoice['service']['guides'] }}</td>
                <td style="text-align: right;">{{ number_format($invoice['invoice']['amount_ex_vat'], 2) }}</td>
            </tr>
            <tr style="border:1px solid black; ">
                <td colspan="4" style="border-right:1px solid #ccc; text-align: left;">Rack Rate Discount:</td>
                <td class="align-right">-{{ number_format($invoice['invoice']['discount'], 2) }}</td>
            </tr>
            <tr style="border:1px solid black; ">
                <!-- Left cell spans multiple rows for contract details -->
                <td rowspan="5" colspan="2" valign="top" style="border:1px solid black; ">
                    <strong>Contract Details:</strong><br>
                    Net Rate<br>
                    100% 30 days before activity to Supplier
                </td>
                <td style="border-right:1px solid #ccc; text-align: right; font-weight: bold;">Sub-total</td>
                <td style="border-right:1px solid #ccc; text-align: right; font-weight: bold;">(Excl VAT)</td>
                <td style=" text-align: left;">{{ number_format($invoice['invoice']['subtotal'], 2) }}</td>
            </tr>
            <tr style="border-bottom:1px solid black;">
                <td style="border-right:1px solid #ccc; text-align: left;"></td>
                <td style="border-right:1px solid #ccc; text-align: right; font-weight: bold;">VAT - 15.0%:</td>
                <td style=" text-align: right;">{{ number_format($invoice['invoice']['vat'], 2) }}</td>
            </tr>
            <tr style="border-bottom:1px solid #ccc;">
                <td style="border-right:1px solid #ccc; text-align: left;"></td>
                <td style="border-right:1px solid #ccc; text-align: right; font-weight: bold;">Total:</td>
                <td style=" text-align: right; font-weight: bold;">{{ number_format($invoice['invoice']['amount_due'], 2) }}</td>
            </tr>
            <tr style="border-bottom:2px solid black;">
                <td style="border-right:1px solid #ccc; text-align: left;"></td>
                <td style="border-right:1px solid #ccc; text-align: right; font-weight: bold;">Amount Paid:</td>
                <td style="text-align: right;">{{ number_format($invoice['invoice']['amount_paid'], 2) }}</td>
            </tr>
            <tr >
                <td class="highlight" style="border-right:1px solid #ccc; text-align: left;"></td>
                <td class="highlight" style="border-right:1px solid #ccc; text-align: right; font-weight: bold;">Balance Due:</td>
                <td class="highlight" style="text-align: right; font-weight: bold;">R{{ number_format($invoice['invoice']['balance_due'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Banking Details -->
    <table class="bank">
        <tr><td><h3>Banking Details</h3></td></tr>
        <tr style="height: 26px;"><td><strong>Account Owner:</strong> {{ $invoice['bank']['account_owner'] }}</td></tr>
        <tr style="height: 26px;"><td><strong>Account Number:</strong> {{ $invoice['bank']['account_no'] }}</td></tr>
        <tr style="height: 26px;"><td><strong>Account Type:</strong> {{ $invoice['bank']['account_type'] }}</td></tr>
        <tr style="height: 26px;"><td><strong>Bank Name:</strong> {{ $invoice['bank']['name'] }}</td></tr>
        <tr style="height: 261px;"><td><strong>Branch Code:</strong> {{ $invoice['bank']['branch_code'] }}</td></tr>
        <tr style="height: 26px;"><td><strong>Reference:</strong> {{ $invoice['invoice']['number'] }}</td></tr>
    </table>
</body>
</html>
