@php
    $cnPortalThemeJsVer = is_file(public_path('js/portal-theme.js'))
        ? (string) filemtime(public_path('js/portal-theme.js'))
        : '1';
@endphp
<script src="{{ asset('js/portal-theme.js') }}?v={{ $cnPortalThemeJsVer }}" defer></script>
