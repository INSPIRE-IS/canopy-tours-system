<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Boarding Pass</title>
@php
    $isPdf = isset($pdfMode) && $pdfMode === true;

    
@endphp
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            width: 100%;
            height: 100%;
            background: #fff;
            padding: 0;
        }

        /* Wrapper */
        .page {
            width: 100%;
            min-height: 100%;
            background: #ffffff;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
        }

        .ticket-wrapper {
            width: 100%;
            height: auto;
            background: url('/images/boarding-pass/header-bg.jpeg') no-repeat center top;
            background-size: cover;
            position: relative;
            z-index: 0;
        }

        /* green overlay */
        .ticket-wrapper::before {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(0, 150, 50, 0.75);
             z-index: -1;
        }

        .ticket-content {
            position: relative;
            padding: 25px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        .ticket-content::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: calc(64.6% + 6.5px); /* Between columns */
            width: 0;
            border-left: 2px dashed #ffffff; /* White dashed line */
            z-index: 20;
        }

        .ticket-bottom-line {
            width: 100%;
            border-bottom: 2px dashed black;
            margin-top: 10px;
        }        

        .left-card,
        .right-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            position: relative;
            margin-top: 40px;
        }

        .left-card {
            height: 260px;
            
        }

        .right-card {
            height: 260px;
        }

        .header-text {
            position: absolute;
            width: 200px;
            top: -30px;
            left: 0;
            right: 0;
            transform: rotate(0deg);
            z-index: 10;
            color: #fff;
            margin-left: 14px;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }        

        /* wings floating element */
        .wings {
            position: absolute;
            width: 200px;
            top: -30px;
            left: -1;
            right: 0;
            transform: rotate(15deg);
            z-index: 10;
        }

        .logo {
            position: absolute;
            width: 90%;
            height: 25%;
            top: -30px;
            left: 0;
            right: 0;
            transform: rotate(0deg);
            z-index: 10;
        }
        /* QR code positioning */
        .qr-left {
            position: absolute;
            bottom: 0px;
            right: 20px;
            width: 90px;
            z-index: 5;
        }
        .qr-right {
            position: absolute;
            bottom: 0px;
            right: 20px;
            width: 90px;
            z-index: 5;
        }

        .after-text {
            position: absolute;
            width: 200px;
            bottom: -16px;
            left: 75px;
            right: 0;
            transform: rotate(0deg);
            z-index: 10;
            color: #FFF;
            margin-left: 14px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }

        .label {
            font-size: 12px;
            color: #6d6d6d;
            margin-top: 10px;
        }

        .value {
            font-size: 16px;
            font-weight: bold;
        }

        /* Main Body */
        .main {
            position: relative;
            width: 100%;
            padding: 30px;
            z-index: 0;
        }

        /* Watermark background */
        .main::before {
            content: "";
            position: absolute;
            margin-top: 70px;
            inset: 0;
            background: url('/images/boarding-pass/body-bg.png') center top no-repeat;
            background-size: cover;
            opacity: 0.05;          /* <- watermark effect */
            z-index: -1;            /* stays behind text */
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
        }

        .cell {
            padding: 16px 20px;
        }
/* -------------------------------
   INNER VERTICAL DIVIDERS
   Column 2 gets border-right
--------------------------------*/
.cell:nth-child(3n + 2) {
    border-right: 1px solid #000;
    height: 80%;
}

/* -------------------------------
   INNER HORIZONTAL DIVIDERS
   Every cell in rows 1 and 2
--------------------------------*/


/* -------------------------------
   LAST COLUMN: add left border
--------------------------------*/
.cell:nth-child(3n + 2) {
    border-left: 1px solid #000;
    height: 80%;
}


        .cell h3 {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .cell ul {
            padding-left: 18px;
        }

        .cell li {
            font-size: 12px;
            margin-bottom: 6px;
            line-height: 1.4;
        }

        /* Footer */
        .footer {
            width: 100%;
            padding: 20px;
            background-color: #12ac12;
            color:#fff;
            font-size: 12px;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="page">

        <div class="ticket-wrapper">
            <div class="ticket-content">

                <!-- LEFT SECTION -->
                <div class="left-card">

                    <img src="/images/boarding-pass/wings.png" class="wings" >

                    <h2 class="header-text">Boarding Pass</h2>
                    <h2 class="sommer">FLIGHT:</h2>
                    <h1 class="sommer">MAGOEBASKLOOF</h1>
                    <h3 class="sommer">GEORGES VALLEY, R528, 0730</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr);">
                        <div style="padding: 16px 20px" >
                            <div class="label">PASSENGER:</div>
                            <div class="value">JOHN DOE</div>
                            <div class="label">SEAT NO:</div>
                            <div class="value">923-251</div>
                        </div>
                        <div style="padding: 16px 20px" >
                            <div class="label">DATE:</div>
                            <div class="value">30 OCT 2025</div>
                            <div class="label">BOARDING TIME:</div>
                            <div class="value">12H30</div>
                        </div>
                    </div>
                    <h5 class="sommer">PLEASE CHECK IN 15 MINUTES BEFORE THE FLIGHT DEPARTS.</h5>
                    <img src="/images/boarding-pass/qr.png" class="qr-left">
                </div>

                <!-- RIGHT SECTION -->
                <div class="right-card">
                    <img src="/images/boarding-pass/logo.png" class="logo" >
                    <div style="margin-top: 30px">
                        <div class="label">PASSENGER:</div>
                        <div class="value">JOHN DOE</div>

                        <div class="label">FLIGHT:</div>
                        <div class="value">MAGOEBASKLOOF</div>

                        <div class="label">DATE:</div>
                        <div class="value">30 OCT 2025</div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr);">
                            <div style="padding: 16px 20px" >
                                <div class="label">TIME:</div>
                                <div class="value">12H30</div>
                            </div>
                            <div style="padding: 16px 20px" >
                                <div class="label">SEAT NO:</div>
                                <div class="value">923-251</div>
                            </div>
                        </div>
                    </div>
                    <img src="/images/boarding-pass/qr.png" class="qr" style="width:60px;">
                    <h3 class="after-text">@canopytourssa</h3>
                </div>

            </div>
            <div class="ticket-bottom-line"></div>
        </div>
        <div class="main">
            <!-- Dynamic Sections -->
            <div class="grid">
                @foreach($data['sections'] as $section)
                    <div class="cell">
                        <h3>{{ strtoupper($section['title']) }}</h3>
                        <ul>
                            @foreach($section['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <h5>
                All visitors and participants and participating in any activities and/or transport offered or arranged by Canopy Tours or 
                third parties engaged by<br>
                Canopy Tours by their signature of this document acknowledge that they have read & understand this indemnity, disclaimer and waiver (‘the<br>
                Indemnity’) and agree to be bound by it, failing which he/she can choose not to board the transport and/or not to participate in the activities.
            </h5>
        </div>

    </div>
</body>
</html>
