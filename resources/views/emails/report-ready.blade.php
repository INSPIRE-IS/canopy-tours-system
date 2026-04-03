@php
    $greetingName = $name ?: 'there';
@endphp

<p>Hi {{ $greetingName }},</p>

<p>Your {{ $reportLabel }} report is ready to download.</p>

<p>
    <a href="{{ $downloadUrl }}">Download report</a>
</p>

<p>If you did not request this report, you can ignore this email.</p>
