@php
    $containerMaxWidth = $containerMaxWidth ?? '1000px';
@endphp
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
        --radius: 10px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: Inter, 'Segoe UI', system-ui, sans-serif;
        background: var(--bg);
        color: var(--text);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .pp-header {
        background: var(--white);
        border-bottom: 3px solid var(--green);
        padding: 0 1.5rem;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .pp-brand {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        text-decoration: none;
        color: var(--text);
    }

    .pp-logo { width: 40px; height: 40px; flex-shrink: 0; }

    .pp-brand-name {
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--green);
        line-height: 1.2;
    }
    .pp-brand-sub {
        font-size: 0.7rem;
        color: var(--muted);
        font-weight: 400;
    }

    .pp-user {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.875rem;
    }

    .pp-user-name { color: var(--text); font-weight: 500; }

    .pp-logout {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        background: none;
        border: 1.5px solid var(--border);
        border-radius: 6px;
        padding: 0.35rem 0.75rem;
        font-size: 0.8rem;
        color: var(--muted);
        cursor: pointer;
        text-decoration: none;
        transition: border-color 0.15s, color 0.15s;
    }
    .pp-logout:hover { border-color: #ef4444; color: #ef4444; }

    .pp-bell-wrap { position: relative; flex-shrink: 0; }
    .pp-bell-btn {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border: 1.5px solid var(--border);
        border-radius: 8px;
        background: var(--white);
        color: var(--muted);
        cursor: pointer;
        transition: border-color 0.15s, color 0.15s;
    }
    .pp-bell-btn:hover { border-color: var(--green); color: var(--green); }
    .pp-bell-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 18px;
        height: 18px;
        padding: 0 4px;
        border-radius: 999px;
        background: #ef4444;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        line-height: 18px;
        text-align: center;
    }
    .pp-bell-panel {
        position: absolute;
        right: 0;
        top: calc(100% + 6px);
        width: min(360px, calc(100vw - 2rem));
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        box-shadow: 0 10px 40px rgba(0,0,0,.12);
        z-index: 100;
        max-height: 70vh;
        display: flex;
        flex-direction: column;
    }
    .pp-bell-panel[hidden] { display: none !important; }
    .pp-bell-panel-header {
        padding: 0.65rem 1rem;
        font-weight: 700;
        font-size: 0.85rem;
        border-bottom: 1px solid var(--border);
    }
    .pp-bell-panel-list { overflow-y: auto; max-height: 280px; }
    .pp-bell-item {
        display: block;
        padding: 0.65rem 1rem;
        font-size: 0.78rem;
        color: var(--text);
        text-decoration: none;
        border-bottom: 1px solid var(--border);
    }
    .pp-bell-item:hover { background: #f9fafb; }
    .pp-bell-item.unread { background: #f0fdf4; border-left: 3px solid var(--green); }
    .pp-bell-item time { display: block; color: var(--muted); font-size: 0.7rem; margin-top: 0.2rem; }
    .pp-bell-empty { padding: 1rem; font-size: 0.78rem; color: var(--muted); text-align: center; margin: 0; }
    .pp-bell-panel-footer {
        padding: 0.5rem 0.75rem;
        border-top: 1px solid var(--border);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.35rem;
        justify-content: flex-end;
    }
    .pp-bell-link { color: var(--green); font-weight: 600; text-decoration: none; font-size: 0.72rem; }
    .pp-bell-link:hover { text-decoration: underline; }
    .pp-bell-footer-btn {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: 5px;
        padding: 0.25rem 0.45rem;
        cursor: pointer;
        font-size: 0.72rem;
        color: var(--muted);
    }
    .pp-bell-footer-btn:hover { border-color: var(--green); color: var(--green); }
    .pp-bell-footer-btn.danger { border-color: #fecaca; color: #b91c1c; }
    .pp-bell-footer-btn.danger:hover { border-color: #ef4444; color: #991b1b; }

    .pp-nav {
        background: var(--white);
        border-bottom: 1px solid var(--border);
        padding: 0 1.5rem;
        display: flex;
        align-items: center;
        gap: 0;
        overflow-x: auto;
    }

    .pp-nav a {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.85rem 1.1rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--muted);
        text-decoration: none;
        border-bottom: 3px solid transparent;
        transition: color 0.15s, border-color 0.15s;
        white-space: nowrap;
    }
    .pp-nav a:hover { color: var(--green); }
    .pp-nav a.active {
        color: var(--green);
        border-bottom-color: var(--green);
    }

    .pp-container {
        max-width: {{ $containerMaxWidth }};
        width: 100%;
        margin: 0 auto;
        padding: 2rem 1.5rem;
        flex: 1;
    }

    .pp-page-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--text);
        margin-bottom: 0.25rem;
    }

    .pp-page-sub {
        font-size: 0.875rem;
        color: var(--muted);
        margin-bottom: 1.75rem;
    }

    .dp-stats {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }
    .dp-stat-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 1.25rem 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 1rem;
    }
    .dp-stat-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #dcfce7;
        color: var(--green);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .dp-stat-icon svg { width: 22px; height: 22px; }
    .dp-stat-value {
        font-size: 1.65rem;
        font-weight: 700;
        color: var(--text);
        line-height: 1;
    }
    .dp-stat-label {
        font-size: 0.78rem;
        color: var(--muted);
        margin-top: 0.2rem;
    }

    .pp-card {
        background: var(--white);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
    }

    .pp-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
    .pp-table th {
        background: var(--bg);
        font-weight: 600;
        color: var(--muted);
        text-transform: uppercase;
        font-size: 0.72rem;
        letter-spacing: 0.05em;
        padding: 0.75rem 1rem;
        text-align: left;
        border-bottom: 1px solid var(--border);
    }
    .pp-table td {
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--border);
        color: var(--text);
        vertical-align: middle;
    }
    .pp-table tr:last-child td { border-bottom: none; }
    .pp-table tr:hover td { background: #f9fafb; }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.2rem 0.6rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-green  { background: #dcfce7; color: #166534; }
    .badge-yellow { background: #fef9c3; color: #854d0e; }
    .badge-blue   { background: #dbeafe; color: #1e40af; }
    .badge-gray   { background: #f3f4f6; color: #374151; }
    .badge-red    { background: #fee2e2; color: #991b1b; }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.4rem 0.9rem;
        border-radius: 6px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        border: none;
        transition: opacity 0.15s;
    }
    .btn:hover { opacity: 0.85; }
    .btn-primary { background: var(--green); color: #fff; }
    .btn-outline {
        background: transparent;
        border: 1.5px solid var(--green);
        color: var(--green);
    }
    .btn-sm {
        padding: 0.3rem 0.7rem;
        font-size: 0.75rem;
    }

    .notif-unread { background: #f0fdf4 !important; }
    .notif-unread td { border-left: 3px solid var(--green); }

    .pp-empty {
        padding: 3rem 2rem;
        text-align: center;
        color: var(--muted);
    }
    .pp-empty svg { margin: 0 auto 1rem; display: block; opacity: 0.35; }
    .pp-empty p { font-size: 0.9rem; }

    .pp-pagination { padding: 1rem 1.25rem; border-top: 1px solid var(--border); }
    .pp-pagination nav { display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem; flex-wrap: wrap; }
    .pp-pagination .page-link {
        display: inline-flex;
        align-items: center;
        padding: 0.35rem 0.7rem;
        border-radius: 5px;
        font-size: 0.8rem;
        color: var(--muted);
        text-decoration: none;
        border: 1px solid var(--border);
    }
    .pp-pagination .page-link.active { background: var(--green); color: #fff; border-color: var(--green); }
    .pp-pagination .page-link:hover:not(.active) { border-color: var(--green); color: var(--green); }

    .pp-alert {
        padding: 0.75rem 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }
    .pp-alert--warning {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
    }

    .pp-footer {
        background: var(--white);
        border-top: 1px solid var(--border);
        padding: 1rem 1.5rem;
        text-align: center;
        font-size: 0.78rem;
        color: var(--muted);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.35rem;
    }

    @media (max-width: 640px) {
        .pp-header { padding: 0 1rem; }
        .pp-container { padding: 1.25rem 1rem; }
        .pp-brand-name { font-size: 0.9rem; }
        .pp-user-name { display: none; }
        .pp-table { font-size: 0.8rem; }
        .pp-table th, .pp-table td { padding: 0.65rem 0.6rem; }
        .dp-stats { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 400px) {
        .dp-stats { grid-template-columns: 1fr; }
    }

    .pp-notif-msg-link {
        font-size: 0.875rem;
        color: inherit;
        text-decoration: none;
        border-bottom: 1px dotted var(--muted);
    }
    .pp-notif-msg-link:hover { color: var(--green); border-bottom-color: var(--green); }
    .pp-notif-date-link {
        font-size: 0.8rem;
        color: inherit;
        text-decoration: none;
        white-space: nowrap;
        border-bottom: 1px dotted var(--muted);
    }
    .pp-notif-date-link:hover { color: var(--green); border-bottom-color: var(--green); }
</style>
