<!DOCTYPE html>
<html>

<head>
    <title>Order</title>

    <style>
        @font-face {
            font-family: 'swz721n';
            src: url('/fonts/swz721n.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Carleton';
            src: url('/fonts/carltonn.ttf') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        body {
            font-family: "swz721n", arial, helvetica;
        }
    </style>
</head>

<body>
    <?php $i = 0; ?>

    <table border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
            <td colspan="3" style='font-family: "Carleton", arial;font-size: 26pt;'><img src="./images/logo.png"
                    Alt="Lakato Logo" style="width:250px;" /></td>
            <td rowspan="2" colspan="2" align="center">
                <strong>ORDER<br />CONFIRMATION<br />{{ $order_no }}</strong>
            </td>
        </tr>
        <tr>
            <td style='font-size: 7pt;'>Reg. No. 1996/012279/07</td>
            <td style='font-size: 7pt;' align="right" colspan="2">PRIVATE BAG X2 BENROSE 2011</td>
        </tr>
        <tr>
            <td style='font-size: 7pt;' valign="top" width="20%">
                JOHANNESBURG
                Unit 10 Droste Park
                Girder Road, Benrose,
                2094
                Tel : (011) 624 -1524
                Fax: (011) 624 -1574
            </td>

            <td style='font-size: 7pt;' align="center" valign="top" width="20%">
                CAPE TOWN
                6 Cilmor Street
                Stikland, Bellville,
                7535
                Tel : (021) 944 - 6900
                Fax: (021) 948 - 0559
            </td style='font-size: 7pt;'>

            <td style='font-size: 7pt;' align="center" valign="top" width="20%">
                PIETERMARITZBURG
                6 Shortts Retreat Street
                Mkondeni, Pietermaritzburg
                3204
                Tel : (033) 386 - 3351
                Fax: (033) 386 - 5215
            </td>

            <td style='font-size: 7pt;' align="right" valign="top" width="20%">
                GQEBERHA
                Erf 117 Greenbushes Business Estate
                Old Cape Road, Gqeberha
                6014
                Tel : (041) 372 - 1238
                Fax: (041) 372 - 1071
            </td>

            <td style='font-size: 7pt;' valign="top" width="20%">
                Branch Details
            </td>
        </tr>
    </table>

    <table border="0" cellpadding="5" cellspacing="0" style="margin-top: 10px;" width="100%">
        <tr>
            <td width="40%" style='font-size: 8pt;'>
                &nbsp;
                <div style="border: 1px solid black; padding: 10px; border-radius: 10px;">
                    {{ $customer->Name }}<br />
                    {{ $customer->SoldToAddr2 }}<br />
                    {{ $customer->SoldToAddr3 }}<br />
                    {{ $customer->SoldToAddr4 }}<br />
                    {{ $customer->SoldToAddr5 }}
                </div>
            </td>

            <td></td>

            <td width="40%" style='font-size: 8pt;'>
                <strong>DELIVER TO:</strong>
                <div style="border: 1px solid black; padding: 10px; border-radius: 10px;">
                    {{ $customer->Name }}<br />
                    {{ $customer->ShipToAddr2 }}<br />
                    {{ $customer->ShipToAddr3 }}<br />
                    {{ $customer->ShipToAddr4 }}<br />
                    {{ $customer->ShipToAddr5 }}
                </div>
            </td>
        </tr>
    </table>

    <table border="1" cellpadding="5" cellspacing="0" style="margin-top: 10px;border-collapse: collapse;"
        width="100%">
        <tr>
            <td align="center" style="background-color: #E3E3E3;font-weight: bold; font-size: 7pt;">Date</td>
            <td align="center" style="background-color: #E3E3E3;font-weight: bold; font-size: 7pt;">Acc. No.</td>
            <td align="center" style="background-color: #E3E3E3;font-weight: bold; font-size: 7pt;">Vat No.</td>
            <td align="center" style="background-color: #E3E3E3;font-weight: bold; font-size: 7pt;">OUR REF.</td>
            <td align="center" style="background-color: #E3E3E3;font-weight: bold; font-size: 7pt;">YOUR REF.</td>
        </tr>
        <tr>
            <td align="center" style="font-size: 8pt;"><?php echo date('Y-m-d H:m'); ?></td>
            <td align="center" style="font-size: 8pt;">{{ $customer_code }}</td>
            <td align="center" style="font-size: 8pt;">{{ $customer->CompanyTaxNumber }}</td>
            <td align="center" style="font-size: 8pt;">{{ $order_no }}</td>
            <td align="center" style="font-size: 8pt;">{{ $po_no }}</td>
        </tr>
    </table>

    <table border="1" cellpadding="5" cellspacing="0" style="margin-top: 10px;border-collapse: collapse;"
        width="100%">
        <tr>
            <td align="center" style="background-color: #E3E3E3;font-weight: bold; font-size: 7pt;">Code</td>
            <td align="center" style="background-color: #E3E3E3;font-weight: bold; font-size: 7pt;">Description</td>
            <td align="center" style="background-color: #E3E3E3;font-weight: bold; font-size: 7pt;">QTY</td>
            <td align="center" style="background-color: #E3E3E3;font-weight: bold; font-size: 7pt;">Price Excl. Vat</td>
            <td align="center" style="background-color: #E3E3E3;font-weight: bold; font-size: 7pt;">Price Incl. Vat</td>
        </tr>

        <?php
        if (!function_exists('parse_nr')) {
            function parse_nr($number, $by, $qty)
            {
                $value = str_replace('R ', '', $number);
                $value = (float) $value;
                $value = $value * $by * $qty;
                return 'R ' . number_format($value, 2, '.', '');
            }
        }
        ?>

        @foreach ($orderItems as $item)
            <tr>
                <td style="font-size: 8pt;">{{ $item['col2'] }}</td> code
                <td style="font-size: 8pt;">{{ $item['col3'] }}<br />{{ $item['col8'] }}</td> desc
                <td style="font-size: 8pt;">{{ $item['col6'] }}</td> qty
                <td style="font-size: 8pt;">
                    <?php
                    $qty = $item['col6']; // Get the quantity
                    ?>
                    @if ($item['col23'] != '')
                        <del>{{ parse_nr($item['col5'], 1, 1) }}</del><br />
                        {{ parse_nr($item['col23'], 1, 1) }}
                    @else
                        {{ parse_nr($item['col5'], 1, 1) }}
                    @endif
                </td>
                <td style="font-size: 8pt;">
                    <?php
                    if ($item['col23'] != '') {
                        echo parse_nr($item['col23'], 1, $qty);
                    } else {
                        echo parse_nr($item['col5'], 1, $qty);
                    }
                    ?>
                </td>
            </tr>
            {{ $i++ }}
        @endforeach

        <tr>
            <td colspan="3">&nbsp;</td>
            <td style="font-size: 8pt;background-color: #E3E3E3;">&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td style="font-size: 8pt;background-color: #E3E3E3;" align='center'><strong>SUB TOTAL</strong></td>
            <td style="font-size: 8pt;" align='center'>{{ $totalPrice }}</td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td style="font-size: 8pt;background-color: #E3E3E3;" align='center'><strong>VAT</strong></td>
            <td style="font-size: 8pt;" align='center'>{{ parse_nr($totalPrice, 0.15, 1) }}</td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td style="font-size: 8pt;background-color: #E3E3E3;" align='center'><strong>TOTAL (ZAR)</strong></td>
            <td style="font-size: 8pt;" align='center'><strong>{{ parse_nr($totalPrice, 1.15, 1) }}</strong></td>
        </tr>
    </table>

    <footer style="position:fixed; bottom:0; width:100%; z-index:1000">
        <hr style="color:#C00000;" />
        <p class="black_footer" style="text-align:center">
            <?php echo Date('Y'); ?> Lakato (PTY) Ltd
        </p>
    </footer>

</body>

</html>
