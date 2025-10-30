<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Indemnity Form - {{ $service->name ?? 'Service' }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #222;
            margin: 30px;
        }
        h1, h2, h3 {
            text-align: center;
            margin-bottom: 10px;
        }
        .section {
            margin-bottom: 20px;
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
            margin-top: 12px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 250px;
            margin-top: 8px;
        }
        .small {
            font-size: 10px;
            color: #555;
        }
    </style>
</head>
<body>
    <h1>Indemnity Form</h1>
    <h2>{{ $service->name ?? 'Service Name' }}</h2>

    <div class="section">
        <table class="info-table">
            <tr><td><strong>Full Name:</strong></td><td>{{ $clients[0]['full_name'] ?? '' }}</td></tr>
            <tr><td><strong>Phone:</strong></td><td>{{ $clients[0]['phone'] ?? '' }}</td></tr>
            <tr><td><strong>Email:</strong></td><td>{{ $clients[0]['email'] ?? '' }}</td></tr>
            <tr><td><strong>Age:</strong></td><td>{{ $clients[0]['id_number'] ?? '' }}</td></tr>
            <tr><td><strong>Gender:</strong></td><td>{{ $clients[0]['gender'] ?? '' }}</td></tr>
            <tr><td><strong>Country:</strong></td><td>{{ $clients[0]['country'] ?? '' }}</td></tr>
            <tr><td><strong>Province:</strong></td><td>{{ $clients[0]['province'] ?? '' }}</td></tr>
            <tr><td><strong>Medical:</strong></td><td>{{ $clients[0]['medical'] ?? '' }}</td></tr>
            <tr><td><strong>Medical Detail:</strong></td><td>{{ $clients[0]['medical_detail'] ?? '' }}</td></tr>
            <tr><td><strong>Fit:</strong></td><td>{{ $clients[0]['is_fit'] ?? '' }}</td></tr>
            <tr><td><strong>Minor:</strong></td><td>{{ $clients[0]['is_minor'] ?? '' }}</td></tr>
        </table>
    </div>

    <div class="section">
        <p>
            I, the undersigned, acknowledge that I participate in {{ $service->name ?? 'this activity' }} entirely at my own risk.
            I hereby release and indemnify the operators, guides, and associated parties from any claims, damages,
            or injuries that may arise as a result of my participation.
        </p>
        <p>
            I confirm that I am physically fit to take part in this activity and understand all safety instructions provided.
        </p>
    </div>

    <div class="signature">
        <p>Signature:</p>
        <img src="{{$clients[0]['signature'] ?? ''}}" alt="Signature" style="max-height:100px;">
        <div class="signature-line"></div>
        <p class="small">(Participant)</p>
        <p>Signature Date: {{ $clients[0]['sign_date']}}</p>
    </div>

    <div class="section small" style="margin-top: 50px;">
        <p>Booking Reference: {{ $booking->reference ?? '' }}</p>
        <p style="font-size: 12px; margin-top: 20px;">
            © {{$year}} {{$service['team']['name']}} — All rights reserved.
        </p>
    </div>
</body>
</html>
