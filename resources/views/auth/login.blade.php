<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In — WX Agency</title>
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
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .bg-grid {
            position: fixed;
            inset: 0;
            background-image:
                linear-gradient(rgba(99,102,241,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(99,102,241,0.05) 1px, transparent 1px);
            background-size: 48px 48px;
            z-index: 0;
        }

        .glow {
            position: fixed;
            width: 600px;
            height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,102,241,0.1) 0%, transparent 70%);
            top: -100px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 0;
            pointer-events: none;
        }

        .card {
            position: relative;
            z-index: 1;
            background: rgba(15,15,25,0.85);
            border: 1px solid rgba(99,102,241,0.18);
            border-radius: 20px;
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            backdrop-filter: blur(20px);
            box-shadow: 0 24px 64px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.03);
        }

        .logo-wrap {
            text-align: center;
            margin-bottom: 1.75rem;
        }

        .logo-wrap img {
            width: 44px;
            height: 44px;
            margin-bottom: 1rem;
        }

        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #fff;
            text-align: center;
            margin-bottom: 0.35rem;
        }

        .subtitle {
            font-size: 0.85rem;
            color: #64748b;
            text-align: center;
            margin-bottom: 2rem;
        }

        .alert-error {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #fca5a5;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1.25rem;
        }

        .field {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: #94a3b8;
            margin-bottom: 0.45rem;
            letter-spacing: 0.02em;
        }

        input[type="email"],
        input[type="text"],
        input[type="password"] {
            width: 100%;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            color: #e2e8f0;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            padding: 0.7rem 1rem;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            border-color: rgba(99,102,241,0.6);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
        }

        input::placeholder { color: #475569; }

        .field-error {
            color: #f87171;
            font-size: 0.78rem;
            margin-top: 0.35rem;
        }

        .row-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.82rem;
            color: #64748b;
            cursor: pointer;
        }

        .remember input[type="checkbox"] {
            width: 15px;
            height: 15px;
            accent-color: #6366f1;
            cursor: pointer;
        }

        .forgot {
            font-size: 0.82rem;
            color: #6366f1;
            text-decoration: none;
            transition: color 0.2s;
        }

        .forgot:hover { color: #a5b4fc; }

        .btn-submit {
            width: 100%;
            background: #6366f1;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 0.92rem;
            font-weight: 600;
            padding: 0.8rem;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 0 24px rgba(99,102,241,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-submit:hover {
            background: #4f46e5;
            box-shadow: 0 0 32px rgba(99,102,241,0.45);
            transform: translateY(-1px);
        }

        .btn-submit:active { transform: translateY(0); }

        .spinner {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin 0.7s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        footer {
            position: fixed;
            bottom: 1.5rem;
            left: 50%;
            transform: translateX(-50%);
            font-size: 0.75rem;
            color: #334155;
            z-index: 1;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="glow"></div>

    <div class="card">
        <div class="logo-wrap">
            <img src="https://www.wx.agency/hubfs/WX.png" alt="WX Agency">
            <h1>Welcome back</h1>
            <p class="subtitle">Sign in to your WX Agency account</p>
        </div>

        @if(Session('error'))
        <div class="alert-error">{{ Session('error') }}</div>
        @endif

        @if($errors->any())
        <div class="alert-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf

            <div class="field">
                <label for="email">Email address</label>
                <input type="email" id="email" name="email" placeholder="you@wx.agency" value="{{ old('email') ?? Session::get('email') }}" required autofocus>
                @error('email')
                <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required>
                @error('password')
                <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            <div class="row-meta">
                <label class="remember">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    Remember me
                </label>
                @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="forgot">Forgot password?</a>
                @endif
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">
                <span id="btnText">Sign In</span>
                <div class="spinner" id="spinner"></div>
            </button>
        </form>
    </div>

    <footer>© {{ date('Y') }} WX Agency. All rights reserved.</footer>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function() {
            document.getElementById('btnText').textContent = 'Signing in…';
            document.getElementById('spinner').style.display = 'block';
            document.getElementById('submitBtn').disabled = true;
        });
    </script>
</body>
</html>
