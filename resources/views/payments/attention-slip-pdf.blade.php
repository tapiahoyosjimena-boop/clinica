<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ficha de Atención {{ $invoice->invoice_number }}</title>
    @php
        $logoPath = public_path('images/branding/clinica-norte-logo.svg');
        $logoBase64 = is_file($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        $headline = $data['headline'];
        $headlineColors = [
            'pendiente' => ['bg' => '#fffbeb', 'border' => '#fbbf24', 'text' => '#92400e', 'label' => 'Acción pendiente'],
            'listo' => ['bg' => '#f0faf5', 'border' => '#2EB67D', 'text' => '#1a8a5a', 'label' => 'Resultado listo'],
            'en_proceso' => ['bg' => '#eff6ff', 'border' => '#3b82f6', 'text' => '#1e40af', 'label' => 'En proceso'],
            'sin_pendientes' => ['bg' => '#f8fafc', 'border' => '#94a3b8', 'text' => '#475569', 'label' => 'Sin pendientes'],
        ];
        $headlineStyle = $headlineColors[$headline['type']] ?? $headlineColors['sin_pendientes'];
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1a202c;
            line-height: 1.5;
            padding: 20px 24px 24px;
        }
        table { border-collapse: collapse; }
        .header-table { width: 100%; }
        .header-table td { vertical-align: top; padding: 0; }
        .header-logo img { height: 58px; width: auto; display: block; max-width: 260px; }
        .header-brand-name { font-size: 21px; font-weight: bold; color: #2EB67D; margin-top: 8px; }
        .header-subtitle { font-size: 10px; color: #64748b; margin-top: 5px; max-width: 300px; line-height: 1.4; }
        .header-meta { text-align: right; }
        .header-meta-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.04em; }
        .header-meta-number { font-size: 17px; font-weight: bold; color: #1a202c; margin-top: 3px; }
        .header-meta-date { font-size: 11px; color: #64748b; margin-top: 6px; }
        .header-divider { width: 100%; height: 3px; background: #2EB67D; margin: 14px 0 18px; border: none; }

        .info-table { width: 100%; margin-bottom: 16px; }
        .info-table td { width: 50%; vertical-align: top; padding: 0 8px 0 0; }
        .info-table td:last-child { padding: 0 0 0 8px; }
        .info-box { background: #f8fafc; border: 1.5px solid #000000; padding: 12px 14px; }
        .info-row { margin-bottom: 8px; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.03em; }
        .info-value { font-size: 13px; font-weight: bold; color: #1a202c; margin-top: 3px; }

        .headline-box {
            width: 100%;
            border: 1.5px solid {{ $headlineStyle['border'] }};
            background: {{ $headlineStyle['bg'] }};
            padding: 12px 14px;
            margin-bottom: 16px;
        }
        .headline-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: {{ $headlineStyle['text'] }};
            margin-bottom: 6px;
        }
        .headline-message { font-size: 13px; font-weight: bold; color: {{ $headlineStyle['text'] }}; }

        .items-table { width: 100%; margin-bottom: 16px; border: 2px solid #000000; }
        .items-table th {
            background: #2EB67D; color: #ffffff; font-size: 11px; font-weight: bold;
            padding: 8px 10px; text-align: left; border: 1.5px solid #000000;
        }
        .items-table td { padding: 8px 10px; font-size: 11px; border: 1.5px solid #000000; color: #1a202c; vertical-align: top; }
        .items-table tr.row-alt td { background: #f8fafc; }
        .items-instructions { font-size: 10px; color: #64748b; margin-top: 4px; line-height: 1.4; }
        .items-instructions li { margin-left: 12px; }

        .footer-divider { width: 100%; height: 2px; background: #2EB67D; margin: 8px 0 12px; border: none; }
        .footer-main { text-align: center; font-size: 11px; color: #1a202c; font-weight: bold; margin-bottom: 6px; }
        .footer-generated { text-align: center; font-size: 10px; color: #64748b; margin-bottom: 8px; }
        .footer-note { text-align: center; font-size: 10px; color: #64748b; }
    </style>
</head>
<body>

    {{-- Zona 1 — Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 55%;">
                @if($logoBase64 !== '')
                    <div class="header-logo">
                        <img src="data:image/svg+xml;base64,{{ $logoBase64 }}" alt="Clínica Norte">
                    </div>
                @endif
                <div class="header-brand-name">Clínica Norte S.R.L.</div>
                <div class="header-subtitle">Ficha de Atención — presente este documento en recepción</div>
            </td>
            <td class="header-meta" style="width: 45%;">
                <div class="header-meta-label">Orden N°</div>
                <div class="header-meta-number">{{ $order->order_number ?? '—' }}</div>
                <div class="header-meta-date">Generado: {{ now()->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    {{-- Zona 2 — Identificación --}}
    <table class="info-table">
        <tr>
            <td>
                <div class="info-box">
                    <div class="info-row">
                        <div class="info-label">Paciente</div>
                        <div class="info-value">{{ $order->patient?->full_name ?? '—' }}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="info-box">
                    <div class="info-row">
                        <div class="info-label">N° Comprobante</div>
                        <div class="info-value">{{ $invoice->invoice_number }}</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Zona 3 — Mensaje principal --}}
    <div class="headline-box">
        <div class="headline-badge">{{ $headlineStyle['label'] }}</div>
        <div class="headline-message">{{ $headline['message'] }}</div>
    </div>

    {{-- Zona 4 — Detalle por examen --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 28%;">Examen</th>
                <th style="width: 27%;">Estado</th>
                <th style="width: 18%;">Código de muestra</th>
                <th style="width: 27%;">Preparación</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['items'] as $index => $item)
                <tr class="{{ $index % 2 === 1 ? 'row-alt' : '' }}">
                    <td>{{ $item['exam_name'] }}</td>
                    <td>{{ $item['status_label'] }}</td>
                    <td>{{ $item['barcode'] ?? '—' }}</td>
                    <td>
                        @if(!empty($item['instructions']))
                            <ul class="items-instructions">
                                @foreach($item['instructions'] as $instruction)
                                    <li>{{ $instruction }}</li>
                                @endforeach
                            </ul>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; padding:12px;">Sin exámenes registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Zona 5 — Footer --}}
    <div class="footer-divider"></div>
    <div class="footer-main">Clínica Norte S.R.L. · C/ Warnes Esq. Angel Sandoval · 75030069</div>
    <div class="footer-generated">Documento generado el {{ now()->format('d/m/Y H:i') }}</div>
    <div class="footer-note">Este documento no constituye comprobante de pago ni resultado clínico; es una guía de atención.</div>

</body>
</html>
