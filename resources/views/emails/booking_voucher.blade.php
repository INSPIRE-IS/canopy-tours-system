<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Booking Voucher</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #000; }
        table { width: 100%; border-collapse: collapse; border: none; margin-bottom: 15px; margin-top: 10px; }
        th, td { border-bottom: 1px solid #ddd;; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #e2e8f0; }
    </style>
</head>
<body>
    @foreach($items as $item)
        @if($item->format === 'paragraph')
            <p style="margin-bottom: 20px; font-size: 12px; font-weight: bold;">
                {{ $item->description }}
            </p>
        @endif
    @endforeach


    <h2 style="margin: 5px 0;">Booking Details</h2>
    <table>
            @foreach($items as $item)
                @if($item->format === 'table')
                    <tr>
                        <td width="25%">{{ $item->name }}</td>
                        <td width="75%" style="font-weight: bolder;">{!! nl2br(e($item->description)) !!}</td>
                    </tr>
                @endif
            @endforeach
    </table>

</body>
</html>