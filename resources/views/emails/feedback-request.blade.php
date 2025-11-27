<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #333;
            line-height: 1.6;
        }
        .button {
            background: #0d6efd;
            color: #fff !important;
            padding: 12px 22px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }
        .container {
            max-width: 600px;
            padding: 20px;
        }
    </style>
</head>
<body>
<div class="container">

    <h2>Hi {{ $customerName }},</h2>

    <p>
        Thank you for joining us on the <strong>{{ $tourName }}</strong> 
        @if($tourDate)
            on <strong>{{ \Carbon\Carbon::parse($tourDate)->format('j F Y') }}</strong>.
        @endif
    </p>

    <p>
        We hope you had a memorable experience!  
        Your feedback helps us improve our tours and provide the best possible service to future guests.
    </p>

    <p>
        It will only take **2 minutes** to complete — and it really makes a difference for our team.
    </p>

    <a href="{{ $feedbackLink }}" class="button">Complete Feedback Survey</a>

    <p style="margin-top: 30px;">
        Thank you again for choosing us.  
        We truly appreciate your time and support.
    </p>

    <p>
        Warm regards,<br>
        <strong>Your Tour Company Team</strong>
    </p>

</div>
</body>
</html>
