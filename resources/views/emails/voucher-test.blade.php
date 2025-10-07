<!DOCTYPE html>
<html>
<body>
    <p>Hello,</p>
    <p>Please find your test voucher attached.</p>
    <p>Reference #: {{ $voucher['booking']['reference'] }}</p>
    <p>Date #: {{ $voucher['booking']['date'] }}</p>
    <p>Provider: {{ $voucher['provider']['name'] }}</p>
</body>
</html>