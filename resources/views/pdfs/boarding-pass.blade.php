<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Boarding Pass</title>
@php
    $isPdf = isset($pdfMode) && $pdfMode === true;
    function pdf_asset($path) {
        return config('app.url') . $path; 
    }
    
@endphp
    <style>
        @font-face {
            font-family: 'Gotham';
            src: url('{{ asset('fonts/Gotham-Book.otf') }}') format('opentype');
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Gotham';
            src: url('{{ asset('fonts/Gotham-Bold.otf') }}') format('opentype');
            font-weight: 700;
            font-style: normal;
            font-display: swap;
        }

        @font-face {
            font-family: 'Gotham';
            src: url('{{ asset('fonts/Gotham-Black.otf') }}') format('opentype');
            font-weight: 900;
            font-display: swap;
        }

        @page { margin: 0; size: A4; }

        html, body { height: 100%; }

        * { page-break-inside: avoid !important; }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Gotham', Arial, sans-serif !important;
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
            background: #ffffff;
        }

        body {
            font-family: 'Gotham', Arial, sans-serif !important;
            margin: 0;
        }

        .text-24 {
            font-size: 34px;
            font-weight: 900;
            font-family: 'Gotham';
            line-height: 1.4;
            letter-spacing: 1.6px;
            text
        }
        .text-16 {
            font-size: 18px;
            font-weight: 400;
             font-family: 'Gotham';
        }
        .text-14 {
            font-size: 16px;
            font-weight: 400;
             font-family: 'Gotham';
        }
        .text-12 {
            font-size: 14px;
            font-weight: 900;
             font-family: 'Gotham';
        }
        .text-10 {
            font-size: 12px;
            font-weight: 400;
            font-family: 'Gotham';
        }
        .text-8 {
            font-size: 10px;
            font-weight: 400;
             font-family: 'Gotham';
        }
        .ticket-wrapper,
        .main {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .ticket-wrapper {
            width: 100%;
            height: auto;
            background: url('{{ pdf_asset("/images/boarding_pass_assets/header-bg.png") }}') no-repeat center top;
            background-size: cover;
            position: relative;
            z-index: 0;
            padding-bottom: 10px;
            page-break-after: avoid;
            break-after: avoid;
        }

        /* green overlay */
        .ticket-wrapper::before {
             z-index: 0;
        }

        .ticket-content {
            position: relative;
            padding: 25px;
            display: grid;
            grid-template-columns: 3fr 1fr;
            gap: 25px;
            z-index: 5;
        }

        /* .ticket-content::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: calc(64.6% + 6.5px);
            width: 0;
            border-left: 2px dashed #ffffff;
            z-index: 20;
        } */

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
            z-index: 1;
        }

        .left-card {
            height: 260px;
            
        }

        .right-card {
            height: 260px;
        }
        .left-card::after{
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 260px;

            background: url('{{ asset('/images/boarding_pass_assets/tree_transparent.png') }}')
                center bottom no-repeat;
            background-size: contain;

            opacity: 0.35;

            z-index: 0;           /* behind content */
            pointer-events: none;
        }
        .right-card::after {
            content: "";
            position: absolute;
            bottom: -5px;              /* pull it down */
            left: -20px;                /* allow bleed */
            right: -20px;
            height: 320px;              /* THIS controls visible size */

            background: url('{{ asset('/images/boarding_pass_assets/tree_transparent.png') }}')
                center bottom no-repeat;

            background-size: 300%;      /* THIS scales the image */

            opacity: 0.35;
            z-index: 0;
            pointer-events: none;
        }

        .header-text {
            position: absolute;
            width: auto;
            top: -40px;
            left: 0;
            transform: rotate(0deg);
            z-index: 10;
            color: #fff;
            margin-left: 14px;
            text-transform: uppercase;
        }        

        /* wings floating element */
        .wings {
            position: absolute;
            width: 250px;
            top: -35px;
            left: -1;
            right: 0;
            transform: rotate(12deg);
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
            bottom: 20px;
            right: 20px;
            width: 120px;
            height: auto;
            z-index: 5;
            background-size: contain;
        }
        .qr-right {
            position: absolute;
            bottom: -25px;
            left: 20px;
            width: 60px;
            height: auto;
            z-index: 5;
            background-size: contain;
            padding: 10px;
            border-radius: 10px;
            background-color: #fff;
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
            font-weight: 400;
            margin-top: 10px;
            font-family: 'Gotham';
        }

        .value {
            font-size: 14px;
            font-weight: bold;
            font-family: 'Gotham';
        }

        .term-section-header {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            gap: 4px;
        }

        .term-section-icon {
            width: 16px;
            height: 16px;
            object-fit: contain;
        }

        .condition-section-header {
            display: flex;
            align-items: flex-start;
            justify-content: flex-start; /* ← THIS is key */
            gap: 8px;
        }

        .condition-section-icon {
            width: 32px;
            height: 32px;
            object-fit: contain;
            flex-shrink: 0;              /* prevents shrinking */
            margin-top: 2px;             /* optical alignment */
        }

        .condition-section-text {
            text-align: left;
            line-height: 1.4;
        }

        /* Main Body */
        .main {
            position: relative;
            width: 100%;
            padding: 10px 30px 30px 30px;
            z-index: 0;
        }

        /* Watermark background */
        .main::before {
            content: "";
            position: absolute;
            margin-top: 70px;
            inset: 0;
            background: url('{{ asset('/images/boarding_pass_assets/body-bg.png') }}') center top no-repeat;
            background-size: cover;
            opacity: 0.7;          /* <- watermark effect */
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

        .section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 10px;
        }

        .section-icon {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }


        /* Footer */
        .footer {
            width: 100%;
            padding: 20px;
            background-color: #12ac12;
            color:#fff;
            font-size: 12px;
            text-align: center;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <div class="page">

        <div class="ticket-wrapper">
            <div class="ticket-content">

                <!-- LEFT SECTION -->
                <div class="left-card" style="width:98%">

                    <img src="{{ asset('/images/boarding_pass_assets/wings.png') }}" class="wings" >

                    <h2 class="header-text text-24">Boarding Pass</h2>
                    <h2 class="text-10">FLIGHT:</h2>
                    <h1 class="text-24">MAGOEBASKLOOF</h1>
                    <h3 class="text-10">GEORGES VALLEY, R528, 0730</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr);padding:8px 0px">
                        <div style="padding: 16px 20px; border-right: 2px solid #000" >
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

                        

                    <span class="text-10 term-section-header">
                        <img
                            src="{{ asset('/images/boarding_pass_assets/11.png') }}"
                            class="term-section-icon"
                        >
                        <span>PLEASE CHECK IN 15 MINUTES BEFORE THE FLIGHT DEPARTS.</span>
                    </span>

                    <img src="{{ asset('/images/boarding_pass_assets/qr.png') }}" class="qr-left">
                </div>

                <!-- RIGHT SECTION -->
                <div class="right-card">
                    <img src="{{ asset('/images/boarding_pass_assets/logo.png') }}" class="logo" >
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
                    <img src="{{ asset('/images/boarding_pass_assets/qr.png') }}" class="qr-right" >
                    <h3 class="after-text">@canopytourssa</h3>
                </div>

            </div>
            {{-- <div class="ticket-bottom-line"></div> --}}
        </div>
        <div class="main">
            <!-- Dynamic Sections -->
            <div class="grid">
                @foreach($data['sections'] as $section)
                    <div class="cell">
                        <div class="section-header">
                            <h3>{{ strtoupper($section['title']) }}</h3>                            
                            <img
                                src="{{ asset('/images/boarding_pass_assets/' . $loop->iteration . '.png') }}"
                                class="section-icon"
                            >

                        </div>
                        <ul>
                            @foreach($section['items'] as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <div class="footer">
                <span class="text-8 condition-section-header">
                    <img
                        src="{{ asset('/images/boarding_pass_assets/12.png') }}"
                        class="condition-section-icon"
                        alt=""
                    >
                    <span class="condition-section-text">
                        All visitors and participants and participating in any activities and/or transport offered or arranged by Canopy Tours or third parties engaged by
                        Canopy Tours by their signature of this document acknowledge that they have read & understand this indemnity, disclaimer and waiver (‘the
                        Indemnity’) and agree to be bound by it, failing which he/she can choose not to board the transport and/or not to participate in the activities.
                    </span>
                </span>

            </div>            
        </div>
        <!-- FOOTER -->

    </div>
</body>
</html>
