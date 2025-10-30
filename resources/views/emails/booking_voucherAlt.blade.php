<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Voucher - {{ $items->first()->booking_reference ?? '' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #111;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .container {
            margin: 20px;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #1a202c;
            padding-bottom: 10px;
        }
        .logo img {
            max-height: 60px;
        }
        h1, h2, h3 {
            margin: 0;
            padding: 0;
        }
        h2 {
            margin-top: 10px;
            margin-bottom: 10px;
            font-size: 16px;
            color: #1a202c;
        }
        .voucher-info {
            width: 100%;
            margin-bottom: 20px;
        }
        .voucher-info td {
            padding: 6px;
            vertical-align: top;
        }
        .voucher-info .title {
            font-weight: bold;
            width: 35%;
        }
        .voucher-info .value {
            width: 65%;
        }
        .paragraph {
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 13px;
        }
        table.details {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.details th, table.details td {
            border: 1px solid #e2e8f0;
            padding: 8px;
            text-align: left;
        }
        table.details th {
            background-color: #f7fafc;
            font-weight: bold;
        }
        table.details tr:nth-child(even) td {
            background-color: #f9fafb;
        }
        .highlight {
            background-color: #edf2f7;
            font-weight: bold;
        }
        .footer {
            font-size: 11px;
            color: #555;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="container">

    {{-- Header with Logo and Voucher Title --}}
    <div class="header">
        <table style="width:100%;">
            <tr>
                <td class="logo">
                    @if(isset($items[0]) && isset($items[0]->team_logo))
                        <img src="{{ public_path('storage/' . $items[0]->team_logo) }}" alt="Logo">
                    @endif
                </td>
                <td style="text-align:right;">
                    <h2>Booking Voucher</h2>
                    <p><strong>Reference:</strong> {{ $items->first()->booking_reference ?? 'REF-000' }}</p>
                    <p><strong>Provider:</strong> {{ $items->first()->provider_name ?? 'Provider' }}</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- Paragraph Items --}}
    @foreach($items as $item)
        @if($item->format === 'paragraph')
            <p class="paragraph">{{ $item->description }}</p>
        @endif
    @endforeach

    {{-- Customer & Provider Info --}}
    <h2>Booking Details</h2>
    {{-- <table class="voucher-info">
        <tr>
            <td class="title">Lead Name:</td>
            <td class="value">{{ $items->first()->lead_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="title">Lead Email:</td>
            <td class="value">{{ $items->first()->lead_email ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="title">Number in Party:</td>
            <td class="value">{{ $items->first()->number_in_party ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="title">Start Time & Date:</td>
            <td class="value">{{ $items->first()->start_time_and_date ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="title">Start Location:</td>
            <td class="value">{{ $items->first()->start_location ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="title">End Location:</td>
            <td class="value">{{ $items->first()->end_location ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="title">Supplier Email:</td>
            <td class="value">{{ $items->first()->supplier_email ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="title">Supplier Landline:</td>
            <td class="value">{{ $items->first()->supplier_landline ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="title">Supplier Mobile:</td>
            <td class="value">{{ $items->first()->supplier_mobile ?? 'N/A' }}</td>
        </tr>
    </table> --}}

    {{-- Booking Item Tables --}}
    @if($items->where('format', 'table')->count())
        <table class="details">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    @if($item->format === 'table')
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{!! nl2br(e($item->description)) !!}</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>Thank you for booking with {{ $items->first()->provider_name ?? 'our service' }}. Please contact us if you have any questions regarding this booking.</p>
    </div>

</div>
</body>
</html>
