<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KejaPad QR Code</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            background: linear-gradient(135deg, #32145C, #5926C8);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .qr-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: #32145C;
            margin-bottom: 4px;
        }

        .brand span { color: #b7791f; }

        .qr-wrapper {
            background: #f8f9ff;
            border-radius: 16px;
            padding: 20px;
            margin: 25px 0;
            border: 2px dashed rgba(89,38,200,0.08);
        }

        .url-badge {
            background: rgba(89,38,200,0.08);
            color: #5926C8;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
            word-break: break-all;
        }

        .scan-text {
            color: #6c757d;
            font-size: 0.88rem;
            margin-bottom: 25px;
        }

        .steps {
            background: #f8f9ff;
            border-radius: 12px;
            padding: 16px;
            text-align: left;
            margin-bottom: 25px;
        }

        .steps p {
            font-size: 0.8rem;
            font-weight: 600;
            color: #5926C8;
            margin-bottom: 8px;
        }

        .steps ol {
            font-size: 0.8rem;
            color: #555;
            margin: 0;
            padding-left: 18px;
        }

        .steps ol li {
            margin-bottom: 4px;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #5926C8, #4a1ea8);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            width: 100%;
            margin-bottom: 10px;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }

        .btn-primary-custom:hover {
            background: linear-gradient(135deg, #F4C542, #5926C8);
            color: white;
            transform: translateY(-1px);
        }

        .network-info {
            background: rgba(233,155,32,0.14);
            border: 1px solid #ffe0b2;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 20px;
            font-size: 0.8rem;
            color: #e65100;
        }

        .network-info i {
            margin-right: 6px;
        }
    </style>
</head>
<body>
    <div class="qr-card">

        <div class="brand"><span>Keja</span>Pad</div>
        <p style="color:#6c757d; font-size:0.82rem; margin-top:4px; margin-bottom:0;">
            Property Management System
        </p>

        <!-- QR Code -->
        <div class="qr-wrapper">
            {!! $qrcode !!}
        </div>

        <!-- URL being encoded -->
        <div class="url-badge">
            <i class="bi bi-link-45deg me-1"></i>
            {{ $url }}
        </div>

        <!-- Network warning for local development -->
        @if(str_contains($url, '192.168.56.1') || str_contains($url, 'localhost') || str_contains($url, '127.0.0'))
        <div class="network-info">
            <i class="bi bi-wifi"></i>
            <strong>Local network only.</strong>
            Your phone must be connected to the same WiFi as this computer.
        </div>
        @endif

        <!-- How to scan -->
        <div class="steps">
            <p><i class="bi bi-info-circle me-1"></i> How to scan:</p>
            <ol>
                <li>Open your phone camera</li>
                <li>Point it at the QR code</li>
                <li>Tap the link that appears</li>
                <li>KejaPad opens in your browser</li>
            </ol>
        </div>

        <!-- Action buttons -->
        <a href="{{ $url }}" class="btn-primary-custom">
            <i class="bi bi-box-arrow-up-right me-2"></i>
            Open KejaPad
        </a>

        <div class="d-flex gap-2 mt-2">
            <a href="{{ $url }}/login"
               class="btn btn-outline-dark flex-fill btn-sm py-2">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </a>
            <a href="{{ $url }}/register"
               class="btn flex-fill btn-sm py-2"
               style="background:#F4C542; color:#32145C; font-weight:600;">
                <i class="bi bi-person-plus me-1"></i> Register
            </a>
        </div>

        <a href="/"
           class="btn btn-link btn-sm mt-3 text-muted"
           style="font-size:0.8rem;">
            <i class="bi bi-arrow-left me-1"></i> Back to Home
        </a>
    </div>
</body>
</html>