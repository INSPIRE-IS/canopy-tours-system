<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Signed Indemnity</title>
</head>
<body style="font-family: Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #333;">
    <p>Hello {{ $name ?? 'Guest' }},</p>
    <p>Your signed indemnity form is attached.</p>
    <p>Booking reference: <strong>{{ $reference ?? '-' }}</strong></p>
    <p>Thank you for choosing Canopy Tours.</p>
</body>
</html>
