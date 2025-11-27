<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <style>
        body { font-family: Arial, sans-serif; }
        .box { padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .heading { font-size: 20px; margin-bottom: 10px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ccc; padding: 8px; }
    </style>
</head>
<body>

<div class="box">
    <div class="heading">⚠️ Indemnity Flagged for {{ $customer->full_name }}</div>

    <p>The following responses have been flagged for review:</p>

    <table class="table">
        <thead>
        <tr>
            <th>Question</th>
            <th>Answer</th>
        </tr>
        </thead>
        <tbody>
        @foreach($flagged as $item)
            <tr>
                <td>{{ $item['question'] }}</td>
                <td>{{ $item['answer'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <p><strong>Booking ref:</strong> {{ $bookingItem->booking->reference ?? '' }}</p>
    <p><strong>Service:</strong> {{ $bookingItem->service->name ?? '' }}</p>
    <p><strong>Customer ID:</strong> {{ $customer->id }}</p>

</div>

</body>
</html>
