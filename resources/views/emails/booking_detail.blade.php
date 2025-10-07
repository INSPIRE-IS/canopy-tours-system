<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Voucher #{{ $voucher['booking']['reference'] }}</title>
    <style>

    </style>
</head>
<body style="font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #000; line-height: 1.4;">

    <!-- Intro Text -->
    <div style="margin-bottom: 20px; font-size: 12px;">
        This voucher is valid to secure delivery of the service set out below by 
        <strong>{{ $voucher['provider']['name'] }}</strong> 
        on the date and time indicated, to the lead participant and the number of additional participants included here below.
    </div>

    <!-- Booking Details -->
    <h2 style="margin: 5px 0;">Booking Details</h2>
    <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 15px;">
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Reference:</th> 
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['booking']['reference'] }}</td></tr>
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Provider:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['provider']['name'] }}</td></tr>
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Service:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['service']['name'] }}</td></tr>
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Location:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['service']['location'] }}</td></tr>
    </table>

    <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 15px;">
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">What to wear:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{!! nl2br(e($voucher['service']['what_to_wear'])) !!}</td></tr>
    </table>

    <!-- Start / End -->
    <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 15px;">
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Starting Time and Date:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['service']['start'] }}</td></tr>
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Starting Location:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['service']['start_location'] }}</td></tr>
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Ending Location:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['service']['end_location'] }}</td></tr>
    </table>

    <!-- Lead Passenger -->
    <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 15px;">
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Lead Passenger Name:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['lead']['name'] }}</td></tr>
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Lead Passenger Email:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['lead']['email'] }}</td></tr>
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Number in Party:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['lead']['party_size'] }}</td></tr>
    </table>

    <!-- Supplier -->
    <table style="width: 100%; border-collapse: collapse; border: none; margin-bottom: 15px;">
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Supplier Email:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['supplier']['email'] }}</td></tr>
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Supplier Landline:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['supplier']['landline'] }}</td></tr>
        <tr><th style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top; background: #f5f5f5; text-align: left; width: 25%;">Supplier Mobile:</th>
            <td style="border-bottom: 1px solid #000; padding: 6px; vertical-align: top;">{{ $voucher['supplier']['mobile'] }}</td></tr>
    </table>
    <div style="margin-bottom: 20px;"> </div>
</body>
</html>
