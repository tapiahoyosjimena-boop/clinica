<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página no encontrada — Clínica Norte</title>
    <link rel="icon" href="{{ asset('images/branding/clinica-norte-favicon.svg') }}" type="image/svg+xml">
    <style>
        :root { --cn-green: #2EB67D; --cn-bg: #F8FAFC; --cn-text: #111827; --cn-muted: #6B7280; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; font-family: ui-sans-serif, system-ui, sans-serif; background: var(--cn-bg); color: var(--cn-text); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; }
        .card { max-width: 420px; background: #fff; border-radius: 12px; padding: 2rem; box-shadow: 0 4px 24px rgba(0,0,0,0.06); border: 1px solid #E5E7EB; text-align: center; }
        .card img { height: 3rem; width: auto; margin-bottom: 1rem; }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        p { color: var(--cn-muted); font-size: 0.9rem; line-height: 1.5; margin: 0 0 1.25rem; }
        .code { font-size: 2.5rem; font-weight: 700; color: var(--cn-green); margin-bottom: 0.25rem; }
        .links { display: flex; flex-wrap: wrap; gap: 0.75rem; justify-content: center; }
        a { color: var(--cn-green); font-weight: 600; text-decoration: none; font-size: 0.875rem; }
        a:hover { text-decoration: underline; }
        .cn-page-visits--minimal { font-size: 0.75rem; color: var(--cn-muted); margin-top: 1rem; }
    </style>
</head>
<body>
    <div class="card">
        <img src="{{ asset('images/branding/clinica-norte-logo.svg') }}" alt="Clínica Norte">
        <div class="code">404</div>
        <h1>Página no encontrada</h1>
        <p>La dirección no existe o fue movida. Verifique la URL o vuelva al inicio.</p>
        <div class="links">
            <a href="{{ url('/admin') }}">Panel de administración</a>
            <a href="{{ url('/paciente/login') }}">Portal del paciente</a>
        </div>
        <x-page-visit-footer variant="minimal" />
    </div>
</body>
</html>
