<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Group Indemnity - {{ $service->name ?? 'Service' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #222;
            margin: 30px;
        }
        h1, h2 {
            text-align: center;
            margin-bottom: 15px;
        }
        .client-block {
            page-break-after: always;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .info-table td {
            border: 1px solid #ccc;
            padding: 6px 10px;
        }
        .signature {
            margin-top: 40px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            margin-top: 50px;
        }
        .small {
            font-size: 10px;
            color: #555;
        }
    </style>
</head>
<body>
    <h1>Group Indemnity Form</h1>
    <h2>{{ $service->name ?? 'Service Name' }}</h2>

    @foreach($clients as $index => $client)
        <div class="client-block">
            <h3>Participant {{ (int)$index + 1 }}</h3>

            <table class="info-table">
                <tr><td><strong>Full Name:</strong></td><td>{{ $client->full_name ?? '' }}</td></tr>
                <tr><td><strong>ID / Passport:</strong></td><td>{{ $client->id_number ?? '' }}</td></tr>
                <tr><td><strong>Phone:</strong></td><td>{{ $client->phone ?? '' }}</td></tr>
                <tr><td><strong>Email:</strong></td><td>{{ $client->email ?? '' }}</td></tr>
                <tr><td><strong>Date:</strong></td><td>{{ now()->format('d F Y') }}</td></tr>
            </table>

            <p>
                I, the undersigned, acknowledge that I participate in {{ $service->name ?? 'this activity' }}
                at my own risk. I hereby release and indemnify the operators, guides, and associated parties
                from any claims, damages, or injuries that may arise as a result of my participation.
            </p>
            <p>
                I confirm that I am physically fit to take part in this activity and understand all safety instructions provided.
            </p>

            <div class="signature">
                <p>Signature:</p>
                <div class="signature-line"></div>
                <p class="small">(Participant)</p>
                <p>Date: ________________________</p>
            </div>

            <div class="section small" style="margin-top: 50px;">
                <p>Booking Reference: {{ $booking->reference ?? '' }}</p>
            </div>
        </div>
    @endforeach
</body>
</html>
