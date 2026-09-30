@php
    $cnPortalThemeCssVer = is_file(public_path('css/clinica-norte-portals-theme.css'))
        ? (string) filemtime(public_path('css/clinica-norte-portals-theme.css'))
        : '1';
@endphp
<link rel="stylesheet" href="{{ asset('css/clinica-norte-portals-theme.css') }}?v={{ $cnPortalThemeCssVer }}">
