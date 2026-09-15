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
            background: linear-gradient(135deg, #1a1a2e, #0f3460);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            text-align: center;
            max-width: 380px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        .brand { font-size: 1.8rem; font-weight: 800; color: #1a1a2e; }
        .brand span { color: #4cc9f0; }
        .qr-wrapper {
            background: #f8f9ff;
            border-radius: 16px;
            padding: 20px;
            margin: 25px 0;
            border: 2px dashed #e8f0fe;
        }
        .scan-text {
            color: #6c757d;
            font-size: 0.88rem;
            margin-bottom: 25px;
        }
        .url-badge {
            background: #e8f0fe;
            color: #0f3460;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <div class="qr-card">
        <div class="brand"><span>Keja</span>Pad</div>
        <p style="color:#6c757d; font-size:0.85rem; margin-top:5px;">
            Property Management System
        </p>

        <div class="qr-wrapper">
            {!! $qrcode !!}
        </div>

        <div class="url-badge">
            <i class="bi bi-globe me-1"></i> kejapad.co.ke
        </div>

        <p class="scan-text">
            Scan this QR code to access KejaPad on your phone.
        </p>

        <div class="d-flex gap-2">
            <a href="{{ route('login') }}"
               class="btn btn-dark flex-fill fw-600">
                <i class="bi bi-box-arrow-in-right me-1"></i> Login
            </a>
            <a href="{{ route('register') }}"
               class="btn flex-fill fw-600"
               style="background:#4cc9f0; color:#1a1a2e;">
                <i class="bi bi-person-plus me-1"></i> Register
            </a>
        </div>

        <a href="{{ route('landing') }}"
           class="btn btn-outline-secondary w-100 mt-2 btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back to Home
        </a>
    </div>
</body>
</html>