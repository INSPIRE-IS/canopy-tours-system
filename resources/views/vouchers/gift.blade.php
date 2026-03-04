<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Gift Voucher</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; }
        .card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; }
        .row { display: flex; justify-content: space-between; align-items: center; }
        .title { font-size: 20px; font-weight: 700; margin: 0 0 8px; }
        .subtle { color: #6b7280; font-size: 12px; }
        .section { margin-top: 16px; }
        .label { font-weight: 600; font-size: 12px; color: #374151; }
        .value { font-size: 14px; }
        .grid { display: table; width: 100%; margin-top: 8px; }
        .grid-row { display: table-row; }
        .grid-cell { display: table-cell; padding: 4px 0; }
        .logo { max-height: 48px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="row">
            <div>
                <h1 class="title">Gift Voucher</h1>
                <div class="subtle">Voucher Code: {{ $voucher_code }}</div>
            </div>
            @if (!empty($logo_path))
                <img src="{{ $logo_path }}" class="logo" alt="Logo" />
            @endif
        </div>

        <div class="section">
            <div class="label">Recipient</div>
            <div class="value">{{ $voucher->full_name }}</div>
            <div class="subtle">{{ $voucher->email_address }}</div>
        </div>

        <div class="section grid">
            <div class="grid-row">
                <div class="grid-cell label">Service</div>
                <div class="grid-cell value">{{ $service?->name ?? 'Gift Voucher' }}</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell label">Season</div>
                <div class="grid-cell value">{{ $season?->name ?? 'N/A' }}</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell label">Adults</div>
                <div class="grid-cell value">{{ $pax_adults ?? 0 }}</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell label">Children</div>
                <div class="grid-cell value">{{ $pax_children ?? 0 }}</div>
            </div>
            <div class="grid-row">
                <div class="grid-cell label">Amount</div>
                <div class="grid-cell value">R{{ number_format((float) $amount, 2) }}</div>
            </div>
        </div>

        <div class="section">
            <div class="label">Notes</div>
            <div class="value">{{ $voucher->note ?: 'Thank you for choosing Canopy Tours.' }}</div>
        </div>
    </div>
</body>
</html>
