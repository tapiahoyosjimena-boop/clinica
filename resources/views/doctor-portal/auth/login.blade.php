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
    <title>Ingresar — Portal Médico | Clínica Norte</title>
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
            margin-bottom: 1.5rem;
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
        input[type="password"],
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
        .password-wrap { position: relative; }
        .password-wrap .password-input { padding-right: 3rem; }
        .password-toggle {
            position: absolute;
            right: 0.1rem;
            top: 0.1rem;
            bottom: 0.1rem;
            width: 2.5rem;
            border: none;
            border-left: 1px solid var(--border);
            background: transparent;
            color: #9ca3af;
            cursor: pointer;
            padding: 0;
            border-radius: 0 7px 7px 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .password-toggle:hover { color: #6b7280; background: #f3f4f6; }
        .password-toggle svg { width: 1.15rem; height: 1.15rem; pointer-events: none; }
        .error-msg { font-size: 0.78rem; color: var(--danger); margin-top: 0.25rem; line-height: 1.45; overflow-wrap: anywhere; }
        .alert-error {
            background: #fee2e2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 0.65rem 0.9rem;
            font-size: 0.82rem;
            color: #991b1b;
            margin-bottom: 1.25rem;
            line-height: 1.45;
            overflow-wrap: anywhere;
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
            <span class="login-welcome">Bienvenido al Portal Médico</span>
        </div>

        <h2 class="login-title">Ingresar</h2>

        @if ($errors->has('portal_login'))
            <div class="alert-error" role="alert">
                {{ $errors->first('portal_login') }}
            </div>
        @endif

        <form method="POST" action="{{ route('doctor.login.submit') }}">
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

            <div class="form-group">
                <label for="password">Contraseña</label>
                <div class="password-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        class="password-input {{ $errors->has('password') ? 'is-invalid' : '' }}"
                        placeholder="••••••••"
                    >
                    <button type="button" class="password-toggle" id="togglePassword" aria-controls="password" aria-label="Mostrar contraseña">
                        <svg id="eyeOpen" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10 3c-4.5 0-8.2 2.94-9.5 7 1.3 4.06 5 7 9.5 7s8.2-2.94 9.5-7c-1.3-4.06-5-7-9.5-7Zm0 11.5A4.5 4.5 0 1 1 10 5a4.5 4.5 0 0 1 0 9.5Zm0-2.5A2 2 0 1 0 10 8a2 2 0 0 0 0 4Z"/>
                        </svg>
                        <svg id="eyeOff" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="display:none;">
                            <path d="M3.28 2.22a.75.75 0 0 0-1.06 1.06l1.46 1.46A10.84 10.84 0 0 0 .5 10c1.3 4.06 5 7 9.5 7 1.96 0 3.8-.56 5.35-1.53l1.37 1.37a.75.75 0 1 0 1.06-1.06L3.28 2.22Zm8.4 8.4-2.3-2.3a2 2 0 0 0 2.3 2.3Zm3.91 2.85A8.9 8.9 0 0 0 19.5 10c-1.3-4.06-5-7-9.5-7-1.5 0-2.93.33-4.22.92l1.7 1.7A4.5 4.5 0 0 1 14.38 12l1.21 1.21Z"/>
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="error-msg">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-login">Ingresar</button>
        </form>

        <a href="{{ route('doctor.password.request') }}" class="portal-forgot-link">¿Olvidaste tu contraseña?</a>

        <p class="footer-text">Clínica Norte S.R.L. — C/ Warnes Esq. Angel Sandoval — 75030069</p>
        <x-page-visit-footer variant="minimal" />
    </div>
    <script>
        (function () {
            const passwordInput = document.getElementById('password');
            const toggleButton  = document.getElementById('togglePassword');
            const eyeOpen       = document.getElementById('eyeOpen');
            const eyeOff        = document.getElementById('eyeOff');
            if (!passwordInput || !toggleButton) return;
            toggleButton.addEventListener('click', function () {
                const isPassword = passwordInput.type === 'password';
                passwordInput.type = isPassword ? 'text' : 'password';
                toggleButton.setAttribute('aria-label', isPassword ? 'Ocultar contraseña' : 'Mostrar contraseña');
                if (eyeOpen && eyeOff) {
                    eyeOpen.style.display = isPassword ? 'none' : 'block';
                    eyeOff.style.display  = isPassword ? 'block' : 'none';
                }
            });
        })();
    </script>
    <x-portal-theme-script />
</body>
</html>
