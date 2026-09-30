@php
    $logoPath = public_path('images/branding/clinica-norte-logo.svg');
    $logoBase64 = is_readable($logoPath) ? base64_encode((string) file_get_contents($logoPath)) : '';
@endphp
<table class="w100">
    <tr>
        <td style="width: 30%; vertical-align: middle;">
            @if($logoBase64 !== '')
                <img src="data:image/svg+xml;base64,{{ $logoBase64 }}" alt="Clínica Norte" class="header-logo">
            @endif
            <div class="header-brand">Clínica Norte S.R.L.</div>
        </td>
        <td style="width: 70%; vertical-align: middle;" class="header-title">
            {{ $reportTitle }}
        </td>
    </tr>
</table>
<div class="header-rule">&nbsp;</div>
