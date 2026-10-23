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
        .footer { position: absolute; left: 0; right: 0; bottom: 0; height: 36mm; }
        .sign { position: absolute; left: 0; bottom: 0; width: 80mm; text-align: center; }
        .sign img { height: 16mm; }
        .sign .line { border-top: 0.4mm solid #121f3e; margin-top: 1mm; padding-top: 1.5mm; font-size: 10pt; font-weight: bold; }
        .sign .role { font-size: 9pt; color: #5a6782; }
        .stamp { position: absolute; left: 95mm; bottom: 2mm; height: 28mm; }
        .partners { position: absolute; left: 92mm; right: 92mm; bottom: 1mm; text-align: center; }
        .partners img { height: 12mm; margin: 0 3mm; }
        .verify { position: absolute; right: 0; bottom: 0; width: 80mm; text-align: right; }
        .verify img { width: 24mm; height: 24mm; }
        .verify .id { font-size: 9pt; font-weight: bold; margin-top: 1mm; }
        .verify .url { font-size: 7.5pt; color: #5a6782; }
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

        <div class="footer">
            <div class="sign">
                @if($img($config['signature_image'] ?? null))
                    <img src="{{ $img($config['signature_image']) }}" alt="">
                @else
                    <div style="height: 16mm;"></div>
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
                <div class="url">Verify at {{ $verifyUrl }}</div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
