<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Redirecting to PayFast</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body>
        <p>Redirecting to PayFast, please wait...</p>
        <form id="payfast-form" method="post" action="{{ $payfastUrl }}">
            @foreach ($data as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
        </form>
        <script>
            document.getElementById('payfast-form').submit();
        </script>
    </body>
</html>
