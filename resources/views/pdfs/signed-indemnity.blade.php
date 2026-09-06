<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Signed Indemnity - {{ $snapshot['booking']['reference'] ?? '' }}</title>
    <style>
        @page {
            margin: 26px 26px 58px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.42;
            color: #222;
        }

        h1, h2, h3 {
            margin: 0 0 8px;
            text-align: center;
        }

        h1 {
            font-size: 18px;
            text-transform: uppercase;
        }

        h2 {
            font-size: 13px;
            text-transform: uppercase;
        }

        h3 {
            font-size: 12px;
            text-align: left;
            margin-top: 16px;
        }

        p {
            margin: 0 0 8px;
        }

        ol {
            margin: 0 0 10px 18px;
            padding: 0;
        }

        li {
            margin-bottom: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        th, td {
            border: 1px solid #d9d9d9;
            padding: 5px 7px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .muted {
            color: #666;
            font-size: 10px;
        }

        .signature-box {
            border: 1px solid #d9d9d9;
            min-height: 125px;
            padding: 12px;
            margin-top: 6px;
        }

        .signature-box img {
            max-height: 115px;
            max-width: 380px;
        }

        .page-break {
            page-break-before: always;
        }

        .page-footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: -38px;
            border-top: 1px solid #d9d9d9;
            padding-top: 6px;
            font-size: 9px;
            color: #555;
        }

        .footer-signature {
            max-height: 22px;
            max-width: 120px;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="page-footer">
        Signed by {{ $snapshot['signer']['name'] ?? '-' }}
        for {{ $snapshot['booking']['reference'] ?? '-' }}
        at {{ $snapshot['signer']['signing_place'] ?? '-' }}
        on {{ $snapshot['audit']['signed_at'] ?? '-' }}
        @if($signatureDataUri)
            | {{ $snapshot['signer']['signature_label'] ?? 'Signature' }}: <img src="{{ $signatureDataUri }}" alt="{{ $snapshot['signer']['signature_label'] ?? 'Signature' }}" class="footer-signature">
        @endif
    </div>

    <h1>{{ $document['title'] ?? 'Digital Indemnity / Disclaimer / Waiver' }}</h1>
    <h2>{{ $snapshot['booking']['service'] ?? 'Activity' }}</h2>

    <table>
        <tr>
            <th>Booking Reference</th>
            <td>{{ $snapshot['booking']['reference'] ?? '-' }}</td>
            <th>Activity Date</th>
            <td>{{ $snapshot['booking']['activity_date'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Participant</th>
            <td>{{ $snapshot['signer']['name'] ?? '-' }}</td>
            <th>Activity Time</th>
            <td>{{ isset($snapshot['booking']['activity_time']) ? substr((string) $snapshot['booking']['activity_time'], 0, 5) : '-' }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $snapshot['signer']['email'] ?? '-' }}</td>
            <th>Phone</th>
            <td>{{ $snapshot['signer']['phone'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Signed At</th>
            <td>{{ $snapshot['audit']['signed_at'] ?? '-' }}</td>
            <th>Signed Place</th>
            <td>{{ $snapshot['signer']['signing_place'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Document Version</th>
            <td>{{ $snapshot['document_version'] ?? '-' }}</td>
            <th>PDF Generated At</th>
            <td>{{ $generatedAt ?? '-' }}</td>
        </tr>
    </table>

    <h3>Definitions</h3>
    @foreach(($document['definitions'] ?? []) as $definition)
        <p>{{ $definition }}</p>
    @endforeach

    <h3>Terms and Conditions</h3>
    <ol>
        @foreach(($document['terms'] ?? []) as $term)
            <li>{{ $term }}</li>
        @endforeach
    </ol>

    <h3>Captured Digital Acceptance</h3>
    <table>
        <tr>
            <th>Agreement Accepted</th>
            <td>{{ collect($answers ?? [])->firstWhere('slug', 'is_agree')['answer'] ?? '-' }}</td>
            <th>Guardian / Parent</th>
            <td>{{ $snapshot['signer']['guardian_name'] ?? 'Not applicable' }}</td>
        </tr>
        <tr>
            <th>Medical Condition</th>
            <td>{{ collect($answers ?? [])->firstWhere('slug', 'medical_id')['answer'] ?? '-' }}</td>
            <th>Medical Details</th>
            <td>{{ collect($answers ?? [])->firstWhere('slug', 'medical_detail')['answer'] ?? '-' }}</td>
        </tr>
        <tr>
            <th>Fit To Walk</th>
            <td>{{ collect($answers ?? [])->firstWhere('slug', 'is_fit')['answer'] ?? '-' }}</td>
            <th>Pregnancy</th>
            <td>{{ collect($answers ?? [])->firstWhere('slug', 'pregnant')['answer'] ?? 'Not applicable' }}</td>
        </tr>
    </table>

    <h3>{{ $snapshot['signer']['signature_label'] ?? 'Signature' }}</h3>
    <div class="signature-box">
        @if($signatureDataUri)
            <img src="{{ $signatureDataUri }}" alt="{{ $snapshot['signer']['signature_label'] ?? 'Signature' }}">
        @else
            <span class="muted">Signature image not available.</span>
        @endif
    </div>

</body>
</html>
