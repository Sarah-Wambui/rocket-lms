{{-- CVH Africa certificate of completion: rendered to an A4 landscape PDF by dompdf (keep CSS simple: no flexbox/grid). --}}
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #121f3e; }
        .page { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; background: #ffffff; }
        .frame-outer { position: absolute; top: 8mm; left: 8mm; right: 8mm; bottom: 8mm; border: 3mm solid #0e2145; }
        .frame-inner { position: absolute; top: 13mm; left: 13mm; right: 13mm; bottom: 13mm; border: 0.6mm solid #5eac49; }
        .band { position: absolute; top: 8mm; left: 8mm; width: 10mm; bottom: 8mm; background: #0170ff; }
        .content { position: absolute; top: 22mm; left: 32mm; right: 24mm; bottom: 20mm; text-align: center; }
        .logo { height: 17mm; }
        .title { margin-top: 9mm; font-size: 30pt; font-weight: bold; letter-spacing: 3pt; color: #0e2145; }
        .subtitle { margin-top: 1mm; font-size: 12pt; letter-spacing: 6pt; color: #5eac49; }
        .intro { margin-top: 9mm; font-size: 12pt; color: #5a6782; }
        .name { margin-top: 3mm; font-size: 28pt; font-weight: bold; color: #0170ff; }
        .name-line { width: 140mm; margin: 2mm auto 0; border-top: 0.4mm solid #cdd5e2; }
        .course-intro { margin-top: 6mm; font-size: 12pt; color: #5a6782; }
        .course { margin-top: 2mm; font-size: 17pt; font-weight: bold; color: #0e2145; }
        .date { margin-top: 4mm; font-size: 11pt; color: #5a6782; }
        /* Footer pieces are positioned from the page top (dompdf mis-handles bottom-anchored boxes). */
        .sign { position: absolute; top: 150mm; left: 40mm; width: 75mm; text-align: center; }
        .sign img { height: 16mm; }
        .sign .gap { height: 16mm; }
        .sign .line { border-top: 0.4mm solid #121f3e; margin-top: 1mm; padding-top: 1.5mm; font-size: 10pt; font-weight: bold; }
        .sign .role { font-size: 9pt; color: #5a6782; }
        .stamp { position: absolute; top: 152mm; left: 128mm; height: 30mm; }
        .partners { position: absolute; top: 166mm; left: 118mm; width: 62mm; text-align: center; }
        .partners img { height: 13mm; margin: 0 2mm; }
        .verify { position: absolute; top: 146mm; right: 30mm; width: 70mm; text-align: center; }
        .verify img { width: 24mm; height: 24mm; }
        .verify .id { font-size: 9pt; font-weight: bold; margin-top: 1mm; }
        .verify .url { font-size: 7pt; color: #5a6782; }
    </style>
</head>
<body>
<div class="page">
    <div class="frame-outer"></div>
    <div class="band"></div>
    <div class="frame-inner"></div>

    <div class="content">
        @if($img($config['logo'] ?? null))
            <img src="{{ $img($config['logo']) }}" class="logo" alt="">
        @endif

        <div class="title">CERTIFICATE</div>
        <div class="subtitle">OF COMPLETION</div>

        <div class="intro">This is to certify that</div>
        <div class="name">{{ $certificate->student_name }}</div>
        <div class="name-line"></div>

        <div class="course-intro">has successfully completed the course</div>
        <div class="course">{{ $certificate->course_title }}</div>
        <div class="date">on {{ $issuedOn }}, offered by {{ $config['issuer'] }}</div>

    </div>

        <div class="sign">
            @if($img($config['signature_image'] ?? null))
                <img src="{{ $img($config['signature_image']) }}" alt="">
            @else
                <div class="gap"></div>
            @endif
            <div class="line">{{ $config['signatory_name'] }}</div>
            <div class="role">{{ $config['signatory_title'] }}</div>
        </div>

        @if($img($config['stamp_image'] ?? null))
            <img src="{{ $img($config['stamp_image']) }}" class="stamp" alt="">
        @elseif(!empty($config['footer_logos']))
            <div class="partners">
                @foreach($config['footer_logos'] as $logo)
                    @if($img($logo) and is_file($img($logo)))
                        <img src="{{ $img($logo) }}" alt="">
                    @endif
                @endforeach
            </div>
        @endif

        <div class="verify">
            <img src="{{ $qr }}" alt="">
            <div class="id">Certificate ID: {{ $certificate->code }}</div>
            <div class="url">Verify at {{ $verifyDisplay }}</div>
        </div>

</div>
</body>
</html>
