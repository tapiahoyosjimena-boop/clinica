<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        (function () {
            try {
                var k = 'cn_portal_theme';
                var m = localStorage.getItem(k) || 'system';
                var d = m === 'dark' || (m === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                document.documentElement.classList.toggle('dark', d);
            } catch (e) {}
        })();
    </script>
    <title>Recuperar contraseña — Portal de {{ $portalLabel }} | Clínica Norte</title>
    @php
        $cnFaviconVer = is_file(public_path('images/branding/clinica-norte-favicon.svg'))
            ? (string) filemtime(public_path('images/branding/clinica-norte-favicon.svg'))
            : '1';
    @endphp
    <link rel="icon" href="{{ asset('images/branding/clinica-norte-favicon.svg') }}?v={{ $cnFaviconVer }}" type="image/svg+xml">
    <style>
        :root {
            --green: #2EB67D;
            --green-dark: #259966;
            --lime: #C8F135;
            --bg: #F8FAFC;
            --white: #ffffff;
            --text: #111827;
            --muted: #6B7280;
            --border: #E5E7EB;
            --danger: #ef4444;
            --radius: 10px;
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Inter, 'Segoe UI', system-ui, sans-serif;
            background: var(--bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .login-box {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }
        .login-logo {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.75rem;
        }
        .login-logo svg { width: 56px; height: 56px; }
        .login-logo .clinic-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--green);
        }
        .login-welcome {
            font-size: 0.9rem;
            color: var(--muted);
            text-align: center;
            margin-top: 0.1rem;
        }
        h2.login-title {
            font-size: 1.3rem;
            font-weight: 700;
            text-align: center;
            color: var(--text);
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            font-size: 0.82rem;
            color: var(--muted);
            text-align: center;
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        .form-group { margin-bottom: 1.1rem; }
        label {
            display: block;
            font-size: 0.82rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.35rem;
        }
        input[type="email"],
        input[type="text"] {
            width: 100%;
            padding: 0.6rem 0.85rem;
            border: 1.5px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            color: var(--text);
            background: var(--white);
            transition: border-color 0.15s;
            outline: none;
        }
        input:focus { border-color: var(--green); }
        input.is-invalid { border-color: var(--danger); }
        .error-msg {
            font-size: 0.78rem;
            color: var(--danger);
            margin-top: 0.25rem;
            line-height: 1.45;
        }
        .btn-login {
            width: 100%;
            padding: 0.7rem;
            background: var(--green);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: background 0.15s;
        }
        .btn-login:hover { background: var(--green-dark); }
        .alert-success {
            background: #d1fae5;
            border: 1px solid #6ee7b7;
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            font-size: 0.82rem;
            color: #065f46;
            margin-bottom: 1.25rem;
            line-height: 1.45;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.1rem;
            font-size: 0.82rem;
            color: var(--green);
            text-decoration: none;
        }
        .back-link:hover { text-decoration: underline; }
        .footer-text {
            margin-top: 1.5rem;
            font-size: 0.75rem;
            color: var(--muted);
            text-align: center;
        }
    </style>
    <x-portal-theme-styles />
</head>
<body>
    <div class="portal-login-theme-slot">
        <x-portal-theme-switcher />
    </div>
    <div class="login-box">
        <div class="login-logo">
            <svg viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="28" cy="28" r="28" fill="#2EB67D"/>
                <rect x="24.5" y="14" width="7" height="28" rx="3.5" fill="white"/>
                <rect x="14" y="24.5" width="28" height="7" rx="3.5" fill="white"/>
                <text text-anchor="end" x="55" y="53" font-size="16" font-family="Inter,sans-serif" font-weight="800" fill="#C8F135">N</text>
            </svg>
            <span class="clinic-name">Clínica Norte S.R.L.</span>
            <span class="login-welcome">Portal de {{ $portalLabel }}</span>
        </div>

        <h2 class="login-title">Recuperar contraseña</h2>
        <p class="login-subtitle">Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.</p>

        @if (session('status'))
            <div class="alert-success" role="alert">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route($sendRoute) }}">
            @csrf

            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    autofocus
                    class="{{ $errors->has('email') ? 'is-invalid' : '' }}"
                    placeholder="tucorreo@ejemplo.com"
                >
                @error('email')
                    <p class="error-msg">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-login">Enviar enlace</button>
        </form>

        <a href="{{ route($loginRoute) }}" class="back-link">← Volver al inicio de sesión</a>

        <p class="footer-text">Clínica Norte S.R.L. — C/ Warnes Esq. Angel Sandoval — 75030069</p>
        <x-page-visit-footer variant="minimal" />
    </div>
    <x-portal-theme-script />
</body>
</html>
