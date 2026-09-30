<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Resultado — {{ $result->exam?->name }}</title>
    @php
        $logoPath = public_path('images/branding/clinica-norte-logo.svg');
        $logoBase64 = is_file($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        $informeLab = $result->labInformeText();
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #1a202c;
            line-height: 1.45;
            padding: 18px 22px 22px;
        }
        table { border-collapse: collapse; }

        .header-table { width: 100%; }
        .header-table td { vertical-align: top; padding: 0; }
        .header-logo img { height: 52px; width: auto; display: block; max-width: 240px; }
        .header-brand-name {
            font-size: 21px;
            font-weight: bold;
            color: #2EB67D;
            margin-top: 6px;
        }
        .header-subtitle {
            font-size: 11px;
            color: #64748b;
            margin-top: 4px;
        }
        .header-meta { text-align: right; }
        .header-meta-row { font-size: 12px; font-weight: bold; color: #1a202c; margin-top: 4px; }
        .header-meta-row strong { color: #64748b; font-weight: normal; font-size: 10px; text-transform: uppercase; display: block; }
        .header-divider {
            width: 100%;
            height: 3px;
            background: #2EB67D;
            margin: 12px 0 14px;
        }

        .critical-banner {
            width: 100%;
            margin-bottom: 14px;
        }
        .critical-banner-cell {
            background: #f0faf5;
            border: 1px solid #bbf7d0;
            border-left: 4px solid #2EB67D;
            padding: 13px 16px 13px 14px;
        }
        .critical-banner-table { width: 100%; border-collapse: collapse; }
        .critical-badge-cell {
            width: 88px;
            vertical-align: middle;
            padding: 0 8px 0 0;
        }
        .critical-banner-spacer {
            width: 18px;
            padding: 0;
            font-size: 0;
            line-height: 0;
        }
        .critical-content-cell {
            vertical-align: middle;
            padding: 0 0 0 4px;
        }
        .critical-badge-pill {
            width: 100%;
            background: #ffffff;
            border: 1.5px solid #fca5a5;
            text-align: center;
            border-collapse: collapse;
        }
        .critical-badge-pill td {
            text-align: center;
        }
        .critical-icon-cell {
            font-size: 18px;
            font-weight: bold;
            color: #b91c1c;
            line-height: 1;
            padding: 8px 10px 2px;
        }
        .critical-badge-label {
            font-size: 10px;
            font-weight: bold;
            color: #b91c1c;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            line-height: 1.2;
            padding: 0 10px 8px;
        }
        .critical-title {
            font-size: 12px;
            font-weight: bold;
            color: #1a8a5a;
            margin-bottom: 2px;
        }
        .critical-text {
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }
        .critical-text strong {
            color: #b91c1c;
            font-weight: bold;
        }

        .patient-box {
            width: 100%;
            margin-bottom: 16px;
        }
        .patient-box-cell {
            background: #f0faf5;
            border: 1px solid #bbf7d0;
            border-left: 4px solid #2EB67D;
            padding: 14px 16px;
        }
        .patient-box-table { width: 100%; }
        .patient-box-table td {
            width: 50%;
            vertical-align: top;
            padding: 4px 10px 4px 0;
        }
        .patient-box-table td:last-child { padding: 4px 0 4px 10px; }
        .field-label {
            font-size: 10px;
            font-weight: bold;
            color: #1a8a5a;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 3px;
        }
        .field-value {
            font-size: 13px;
            color: #1a202c;
            font-weight: bold;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1a8a5a;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #f0faf5;
        }

        .params-table {
            width: 100%;
            margin-bottom: 16px;
            border: 2px solid #000000;
        }
        .params-table th {
            background: #1a8a5a;
            color: #f1f5f9;
            font-size: 13px;
            font-weight: bold;
            padding: 9px 10px;
            text-align: left;
            border: 1.5px solid #000000;
        }
        .params-table th.col-center { text-align: center; }
        .params-table td {
            font-size: 12px;
            padding: 8px 10px;
            border: 1.5px solid #000000;
            color: #1a202c;
        }
        .params-table tbody td:first-child { font-weight: bold; }
        .params-table tr.row-alt td { background: #f8fafc; }
        .params-table tr.row-alert td { background: #fffafb; }
        .params-table td.val-in-range { color: #1a8a5a; font-weight: bold; }
        .params-table td.val-alert { color: #b91c1c; font-weight: bold; }
        .params-table td.status-ok { color: #2EB67D; font-weight: bold; }
        .params-table td.status-bad { color: #b91c1c; font-weight: bold; }

        .informe-box {
            width: 100%;
            margin-bottom: 18px;
        }
        .informe-box-cell {
            background: #f0faf5;
            border: 1px solid #bbf7d0;
            border-left: 4px solid #2EB67D;
            padding: 12px 14px;
        }
        .informe-title {
            font-size: 13px;
            font-weight: bold;
            color: #1a8a5a;
            margin-bottom: 8px;
        }
        .informe-text {
            font-size: 12px;
            color: #1a202c;
            line-height: 1.55;
            white-space: pre-wrap;
        }

        .footer-divider {
            width: 100%;
            height: 2px;
            background: #2EB67D;
            margin: 10px 0 12px;
        }
        .footer-table { width: 100%; margin-bottom: 10px; }
        .footer-table td {
            width: 50%;
            vertical-align: top;
            font-size: 11px;
            color: #1a202c;
            padding: 0 8px 0 0;
        }
        .footer-table td:last-child { padding: 0 0 0 8px; text-align: right; }
        .footer-label { font-size: 10px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .footer-name { font-size: 12px; font-weight: bold; margin: 4px 0 10px; }
        .footer-legal {
            text-align: center;
            font-size: 10px;
            color: #64748b;
            margin-top: 8px;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    {{-- Zona 1 — Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 58%;">
                @if($logoBase64 !== '')
                    <div class="header-logo">
                        <img src="data:image/svg+xml;base64,{{ $logoBase64 }}" alt="Clínica Norte">
                    </div>
                @endif
                <div class="header-brand-name">Clínica Norte S.R.L.</div>
                <div class="header-subtitle">Informe de Resultados — Laboratorio Clínico</div>
            </td>
            <td class="header-meta" style="width: 42%;">
                <div class="header-meta-row">
                    <strong>N° Orden</strong>
                    {{ $result->order?->order_number ?? '—' }}
                </div>
                <div class="header-meta-row">
                    <strong>Fecha</strong>
                    {{ $result->validated_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    @if($result->is_critical)
        <table class="critical-banner" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="critical-banner-cell">
                    <table class="critical-banner-table">
                <tr>
                    <td class="critical-badge-cell">
                        <table class="critical-badge-pill" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="critical-icon-cell">&#9888;</td>
                            </tr>
                            <tr>
                                <td class="critical-badge-label">Crítico</td>
                            </tr>
                        </table>
                    </td>
                    <td class="critical-banner-spacer">&nbsp;</td>
                    <td class="critical-content-cell">
                        <div class="critical-title">Resultado que requiere atención médica</div>
                        <div class="critical-text">
                            Este informe contiene valores fuera de rango.
                            <strong>Consulte a su médico de inmediato.</strong>
                        </div>
                    </td>
                </tr>
                    </table>
                </td>
            </tr>
        </table>
    @endif

    {{-- Zona 2 — Datos paciente y examen --}}
    <table class="patient-box" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td class="patient-box-cell">
        <table class="patient-box-table">
            <tr>
                <td>
                    <div class="field-label">Paciente</div>
                    <div class="field-value">{{ $result->order?->patient?->full_name ?? '—' }}</div>
                    <div class="field-label" style="margin-top: 10px;">Examen</div>
                    <div class="field-value">{{ $result->exam?->name ?? '—' }}</div>
                </td>
                <td>
                    <div class="field-label">Bioquímico responsable</div>
                    <div class="field-value">{{ $result->responsibleUser?->name ?? '—' }}</div>
                    <div class="field-label" style="margin-top: 10px;">Fecha de validación</div>
                    <div class="field-value">{{ $result->validated_at?->format('d/m/Y H:i') ?? '—' }}</div>
                </td>
            </tr>
        </table>
            </td>
        </tr>
    </table>

    {{-- Zona 3 — Tabla de parámetros --}}
    <div class="section-title">Resultados del análisis</div>
    <table class="params-table">
        <thead>
            <tr>
                <th style="width: 28%;">Parámetro</th>
                <th style="width: 18%;">Resultado</th>
                <th style="width: 12%;">Unidad</th>
                <th style="width: 22%;">Rango de referencia</th>
                <th class="col-center" style="width: 20%;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($result->labParameterDetails() as $index => $detail)
                @php
                    $val = is_numeric($detail->value) ? (float) $detail->value : null;
                    $min = $detail->reference_min !== null ? (float) $detail->reference_min : null;
                    $max = $detail->reference_max !== null ? (float) $detail->reference_max : null;
                    $outOfRef = $val !== null && (($min !== null && $val < $min) || ($max !== null && $val > $max));
                @endphp
                <tr class="{{ $outOfRef ? 'row-alert' : ($index % 2 === 1 ? 'row-alt' : '') }}">
                    <td>{{ $detail->parameter_name }}</td>
                    <td class="{{ $outOfRef ? 'val-alert' : 'val-in-range' }}">{{ $detail->value }}</td>
                    <td>{{ $detail->unit ?? '—' }}</td>
                    <td>
                        @if($min !== null || $max !== null)
                            {{ $min !== null ? rtrim(rtrim(number_format($min, 2, ',', '.'), '0'), ',') : '?' }}
                            –
                            {{ $max !== null ? rtrim(rtrim(number_format($max, 2, ',', '.'), '0'), ',') : '?' }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="col-center {{ $outOfRef ? 'status-bad' : 'status-ok' }}">
                        {{ $outOfRef ? 'Fuera de rango' : 'Normal' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b; padding: 12px;">Sin parámetros registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Zona 4 — Informe clínico --}}
    @if(filled($informeLab))
        <table class="informe-box" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="informe-box-cell">
                    <div class="informe-title">Informe clínico</div>
                    <div class="informe-text">{{ $informeLab }}</div>
                </td>
            </tr>
        </table>
    @endif

    {{-- Zona 5 — Footer --}}
    <div class="footer-divider"></div>
    <table class="footer-table">
        <tr>
            <td>
                <div class="footer-label">Bioquímico responsable</div>
                <div class="footer-name">{{ $result->responsibleUser?->name ?? '—' }}</div>
            </td>
            <td>
                <div class="footer-label">Clínica Norte S.R.L.</div>
                <div class="footer-name" style="font-weight: normal; font-size: 10px;">C/ Warnes Esq. Angel Sandoval</div>
                <div style="font-size: 10px;">Tel. 75030069</div>
            </td>
        </tr>
    </table>
    <div class="footer-legal">
        Documento generado el {{ now()->format('d/m/Y H:i') }} — Este informe es de uso médico confidencial
    </div>

</body>
</html>
