<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <title>Your Invoice</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #555;
            background: #fff;
        }

        @media only screen and (min-width: 640px) {
            body {
                background: #f6f6f6;
            }
        }

        h1,
        h2,
        h3,
        h4 {
            margin-top: 0;
            color: #333;
        }

        h1 {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        h2 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        h3 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        h4 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 16px;
        }

        p {
            margin-top: 0;
            color: #555;
        }

        p.note {
            color: rgba(51, 51, 51, 0.4);
        }

        .wrapper {
            width: 100%;
        }

        .container {
            width: 80%;
            max-width: 800px;
            margin: 0 auto;
            padding: 0 16px;
        }

        .content {
            border-top: 1px solid #e5e7eb;
            padding-top: 24px;
            margin-top: 24px;
        }

        .invoice {
            margin: 40px 0;
        }

        .invoice-header {
            text-align: center;
        }

        .invoice-logo {
            max-height: 100px;
            margin-bottom: 24px;
        }

        .invoice-body {
            margin-bottom: 24px;
        }

        .invoice-footer {
            text-align: right;
        }

        .invoice-footer p {
            margin-bottom: 0;
        }

        .invoice-footer .note {
            font-size: 12px;
        }

        .invoice-items {
            list-style-type: none;
            margin: 0;
            padding: 0;
        }

        .invoice-items li {
            padding: 8px 0;
            border-top: 1px solid #eee;
            list-style-type: none;
        }

        .invoice-items li:first-child {
            border-top: 0;
        }

        .invoice-items .description {
            width: 30%;
            float: left;
        }

        .invoice-items .qty,
        .invoice-items .price {
            text-align: right;
        }

        .invoice-items .subtotal {
            float: right;
            text-align: right;
        }

        @media only screen and (max-width: 640px) {
            .container {
                width: 100% !important;
                padding: 0 !important;
            }

            .content {
                padding-top: 0 !important;
                margin-top: 24px !important;
                margin-bottom: 24px !important;
            }

            .invoice {
                margin: 40px auto !important;
            }

            .invoice-header {
                text-align: center !important;
            }

            .invoice-logo {
                max-height: 100px !important;
                margin-bottom: 24px !important;
            }

            .invoice-body {
                margin-bottom: 24px !important;
            }

            .invoice-footer {
                text-align: center !important;
            }

            .invoice-footer .note {
                font-size: 12px !important;
            }

            .invoice-items {
                list-style-type: none;
                margin: 0;
                padding: 0;
            }

            .invoice-items li {
                padding: 8px 0;
                border-top: 1px solid #eee;
                list-style-type: none;
            }

            .invoice-items li:first-child {
                border-top: 0;
            }

            .invoice-items .description {
                width: 100%;
                float: left;
            }

            .invoice-items .qty,
            .invoice-items .price {
                text-align: right;
            }

            .invoice-items .subtotal {
                float: right;
                text-align: right;
            }
        }
    </style>
</head>

<body>
    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">
                <table class="container" width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td>
                            <table class="content" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td class="invoice">
                                        <h1>Invoice</h1>
                                        <p class="invoice-number">Invoice #<span
                                                class="invoice-number-value">number</span></p>
                                        <p class="invoice-date">Date: <span class="invoice-date-value">date</span></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="invoice-logo">
                                        <img src="https://canopy-tours.com/logo.png" alt="Logo"
                                            style="max-height:50px;">
                                    </td>
                                </tr>
                                <tr>
                                    <td class="invoice-footer">
                                        <p class="note">Please pay within 7 days.</p>
                                        <p class="note">Thank you for choosing Canopy Tours.</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
