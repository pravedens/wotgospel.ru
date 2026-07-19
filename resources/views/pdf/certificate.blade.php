<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Сертификат</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: 'DejaVu Sans', sans-serif;
            background: white;
        }
        .certificate {
            width: 100%;
            height: 100%;
            position: relative;
        }
        .bg-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        .bg-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.3);
            z-index: 1;
        }
        /* Таблица для центрирования */
        .content-table {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }
        .content-table td {
            vertical-align: middle;
            text-align: center;
            padding: 40px 50px;
        }
        .corner {
            position: absolute;
            width: 60px;
            height: 60px;
            border: 2px solid #cbd5e0;
            z-index: 3;
        }
        .corner-tl { top: 20px; left: 20px; border-right: none; border-bottom: none; }
        .corner-tr { top: 20px; right: 20px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: 20px; left: 20px; border-right: none; border-top: none; }
        .corner-br { bottom: 20px; right: 20px; border-left: none; border-top: none; }
        
        .logo { text-align: center; margin-bottom: 10px; }
        .logo img { width: 60px; height: 60px; }
        
        .title { font-size: 42px; font-weight: bold; color: #2b6cb0; letter-spacing: 6px; margin: 5px 0; }
        .subtitle { font-size: 16px; color: #4a5568; letter-spacing: 2px; margin-bottom: 15px; }
        
        .name { font-size: 28px; font-weight: bold; color: #2d3748; border-bottom: 2px dotted #a0aec0; display: inline-block; padding: 0 30px 8px 30px; margin: 15px 0; }
        
        .completed { font-size: 16px; color: #4a5568; margin: 15px 0 5px; }
        .course { font-size: 24px; font-weight: bold; color: #2c5282; margin: 5px 0 20px; }
        
        .church { font-size: 13px; color: #2d3748; line-height: 1.5; margin: 15px 0 5px; }
        .psalm { font-size: 11px; color: #718096; font-style: italic; margin: 5px 0 20px; }
        
        .signatures {
            width: 70%;
            margin: 20px auto 15px;
            overflow: hidden;
        }
        .signature-left {
            float: left;
            width: 45%;
            text-align: center;
        }
        .signature-right {
            float: right;
            width: 45%;
            text-align: center;
        }
        .signature-line {
            width: 80%;
            margin: 0 auto;
            border-top: 1px solid #4a5568;
            margin-bottom: 5px;
        }
        .signature-label { font-size: 11px; color: #718096; }
        .signature-name { font-size: 13px; color: #2d3748; margin-top: 5px; font-weight: bold; }
        
        .qrcode { text-align: center; margin: 10px 0; clear: both; }
        .qrcode img { width: 60px; height: 60px; display: inline-block; }
        .serial { font-size: 9px; color: #a0aec0; margin-top: 5px; }
        
        .clearfix { clear: both; }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    @php
        $bgBase64 = '';
        $logoBase64 = '';
        
        $bgPath = public_path('images/certificate-bg.png');
        $logoPath = public_path('images/icon-512.png');
        
        if (file_exists($bgPath)) {
            $bgData = file_get_contents($bgPath);
            $bgBase64 = 'data:image/png;base64,' . base64_encode($bgData);
        }
        
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($logoData);
        }
    @endphp

    <div class="certificate">
        <div class="bg-image">
            @if($bgBase64)
                <img src="{{ $bgBase64 }}">
            @endif
        </div>
        <div class="overlay"></div>
        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>
        
        <table class="content-table">
            <tr>
                <td>
                    <div class="logo">
                        @if($logoBase64)
                            <img src="{{ $logoBase64 }}">
                        @else
                            <div style="font-size: 40px;">✝️</div>
                        @endif
                    </div>
                    
                    <div class="title">СЕРТИФИКАТ</div>
                    <div class="subtitle">ПОДТВЕРЖДАЕТ, ЧТО</div>
                    
                    <div class="name">{{ $user->full_name }}</div>
                    
                    <div class="completed">успешно завершил(а) курс</div>
                    <div class="course">«{{ strip_tags($course->title) }}»</div>
                    
                    <div class="church">
                        Местная религиозная организация<br>
                        Церковь Христиан Веры Евангельской (пятидесятников)<br>
                        <strong>"СЛОВО ИСТИНЫ"</strong><br>
                        г. Нижний Тагил
                    </div>
                    
                    <div class="psalm">
                        СЛОВО ТВОЕ - СВЕТИЛЬНИК НОГЕ МОЕЙ И СВЕТ СТЕЗЕ МОЕЙ<br>
                        (Пс. 118:105)
                    </div>
                    
                    <div class="signatures">
                        <div class="signature-left">
                            <div class="signature-line"></div>
                            <div class="signature-label">ДАТА</div>
                            <div class="signature-name">{{ date('d.m.Y') }}</div>
                        </div>
                        <div class="signature-right">
                            <div class="signature-line"></div>
                            <div class="signature-label">РУКОВОДИТЕЛЬ</div>
                            <div class="signature-name">Пастор Павел Райн</div>
                        </div>
                        <div class="clearfix"></div>
                    </div>
                    
                    <div class="qrcode">
                        {!! QrCode::size(60)->generate($certificate->getVerificationUrl()) !!}
                    </div>
                    <div class="serial">Серийный номер: {{ $certificate->certificate_uuid }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>