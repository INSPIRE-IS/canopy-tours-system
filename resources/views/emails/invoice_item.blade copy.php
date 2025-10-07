<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoice['invoice']['number'] }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #000; }
        h1 { font-size: 18px; margin: 0; }
        h2 { font-size: 16px; margin: 0; }
        .header { text-align: center; margin-bottom: 10px; }
        .subheader { text-align: center; font-size: 11px; margin-bottom: 20px; }

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
    <div class="header">
        <h1>MAGOEBASKLOOF CANOPY TOURS</h1>
        <h2>TAX INVOICE</h2>
    </div>
    <div class="subheader">
        Reg. 2008/018998/07 &nbsp;&nbsp; VAT: 4010252619
    </div>

    <!-- From / To / Invoice Details -->
    <table style="margin-bottom:20px;" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td width="30%" style="border-right:1px solid #e5e5e5;">
                <p class="highlight" width="100%">From:</p>
                <p>Magoebaskloof Canopy Tours</p>
                <p>PO Box 3132</p>
                <p>Tzaneen</p>
                <p>0850</p>
            </td>
            <td width="30%">
                <p class="highlight" width="100%">To:</p>
                <p>{{ $invoice['client']['name'] }}</p>
                <p>{!! nl2br(e($invoice['client']['address'])) !!}</p>
            </td>
            <td width="10%">

            </td>
            <td width="30%">
                <strong>Invoice #:</strong> {{ $invoice['invoice']['number'] }}<br>
                <strong>Proforma #:</strong> {{ $invoice['invoice']['proforma'] }}<br>
                <strong>Booking #:</strong> {{ $invoice['invoice']['booking'] }}<br>
                <strong>Date:</strong> {{ $invoice['invoice']['date'] }}<br>
                <strong>Amount Due:</strong> R{{ number_format($invoice['invoice']['amount_due'], 2) }}
            </td>
        </tr>
    </table>

    <!-- Service + Totals -->
    <table>
        <thead>
            <tr>
                <th>Service</th>
                <th>Adults (Qty)</th>
                <th>Children (Qty)</th>
                <th>Guides (Qty)</th>
                <th>Total Cost (ZAR)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice['service']['name'] }}</td>
                <td class="align-right">{{ $invoice['service']['adults'] }}</td>
                <td class="align-right">{{ $invoice['service']['children'] }}</td>
                <td class="align-right">{{ $invoice['service']['guides'] }}</td>
                <td class="align-right">{{ number_format($invoice['service']['cost'], 2) }}</td>
            </tr>
            <tr>
                <td colspan="4">Rack Rate Discount:</td>
                <td class="align-right">-{{ number_format($invoice['invoice']['discount'], 2) }}</td>
            </tr>
            <tr>
                <!-- Left cell spans multiple rows for contract details -->
                <td rowspan="5" colspan="4" valign="top">
                    <strong>Contract Details:</strong><br>
                    Net Rate<br>
                    100% 30 days before activity to Supplier
                </td>
                <td>Sub-total (Excl VAT): {{ number_format($invoice['invoice']['subtotal'], 2) }}</td>
            </tr>
            <tr>
                <td>VAT - 15.0%: {{ number_format($invoice['invoice']['vat'], 2) }}</td>
            </tr>
            <tr>
                <td>Total: {{ number_format($invoice['invoice']['total'], 2) }}</td>
            </tr>
            <tr>
                <td>Amount Paid: {{ number_format($invoice['invoice']['paid'], 2) }}</td>
            </tr>
            <tr>
                <td class="highlight">Balance Due: R{{ number_format($invoice['invoice']['balance_due'], 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Banking Details -->
    <table class="bank">
        <tr><td><h3>Banking Details</h3></td></tr>
        <tr><td><strong>Account Owner:</strong> Magoebaskloof Canopy Tour</td></tr>
        <tr><td><strong>Account Number:</strong> 4073663376</td></tr>
        <tr><td><strong>Account Type:</strong> Current / Cheque</td></tr>
        <tr><td><strong>Bank Name:</strong> ABSA</td></tr>
        <tr><td><strong>Branch Code:</strong> 630423</td></tr>
        <tr><td><strong>Reference:</strong> {{ $invoice['invoice']['number'] }}</td></tr>
    </table>

</body>
</html>
