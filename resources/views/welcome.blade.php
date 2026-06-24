<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WX Agency — API</title>
    <link rel="shortcut icon" href="https://www.wx.agency/hubfs/WX.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            background: #0a0a0f;
            color: #e2e8f0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(99,102,241,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,0.06) 1px, transparent 1px);
            background-size: 48px 48px;
            z-index: 0;
        }

        .glow {
            position: fixed;
            width: 700px;
            height: 700px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,0.12) 0%, transparent 70%);
            top: -150px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
            pointer-events: none;
        }

        .container {
            position: relative;
            z-index: 1;
            text-align: center;
            padding: 2rem;
            max-width: 560px;
            width: 100%;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(99,102,241,0.12);
            border: 1px solid rgba(99,102,241,0.3);
            color: #a5b4fc;
            font-size: 0.72rem;
            font-weight: 500;
            letter-spacing: 0.09em;
            text-transform: uppercase;
            padding: 5px 14px;
            border-radius: 100px;
            margin-bottom: 2rem;
        }

        .badge::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #6366f1;
            box-shadow: 0 0 8px #6366f1;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        .logo {
            width: 52px;
            height: 52px;
            margin: 0 auto 1.5rem;
            display: block;
        }

        h1 {
            font-size: 2.75rem;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: -0.03em;
            background: linear-gradient(135deg, #fff 40%, #a5b4fc 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        p {
            font-size: 0.97rem;
            color: #94a3b8;
            line-height: 1.75;
            margin-bottom: 2.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #6366f1;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.75rem 1.75rem;
            border-radius: 10px;
            text-decoration: none;
            transition: all 0.2s;
            box-shadow: 0 0 28px rgba(99,102,241,0.3);
        }

        .btn:hover {
            background: #4f46e5;
            transform: translateY(-1px);
            box-shadow: 0 0 36px rgba(99,102,241,0.45);
        }

        footer {
            position: fixed;
            bottom: 2rem;
            font-size: 0.78rem;
            color: #475569;
            z-index: 1;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="glow"></div>

    <div class="container">
        <div class="badge">Internal API Platform</div>
        <img class="logo" src="https://www.wx.agency/hubfs/WX.png" alt="WX">
        <h1>WX Agency<br>API Hub</h1>
        <p>Centralised automation and integration layer for WX Agency tools, HubSpot, Userback, and third-party services.</p>
        <a href="{{ route('login') }}" class="btn">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>
            Sign In
        </a>
    </div>

    <footer>© {{ date('Y') }} WX Agency. Restricted access.</footer>
</body>
</html>
