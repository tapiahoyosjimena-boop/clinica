<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Comprobante {{ $invoice->invoice_number }}</title>
    @php
        $logoPath = public_path('images/branding/clinica-norte-logo.svg');
        $logoBase64 = is_file($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
        $currency = $bank['currency'] ?? 'BOB';
        $isPaid = $invoice->status === 'pagada';
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
        .text-muted { color: #64748b; }
        .text-green { color: #2EB67D; }
        .text-green-dark { color: #1a8a5a; }
        .fw-bold { font-weight: bold; }

        /* ── Zona 1: Header ── */
        .header-table { width: 100%; margin-bottom: 0; }
        .header-table td { vertical-align: top; padding: 0; }
        .header-logo img { height: 58px; width: auto; display: block; max-width: 260px; }
        .header-brand-name {
            font-size: 21px;
            font-weight: bold;
            color: #2EB67D;
            margin-top: 8px;
        }
        .header-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-top: 5px;
            max-width: 300px;
            line-height: 1.4;
        }
        .header-meta { text-align: right; }
        .header-meta-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .header-meta-number {
            font-size: 17px;
            font-weight: bold;
            color: #1a202c;
            margin-top: 3px;
        }
        .header-meta-date {
            font-size: 11px;
            color: #64748b;
            margin-top: 6px;
        }
        .badge {
            display: inline-block;
            margin-top: 8px;
            padding: 5px 12px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .badge-paid {
            background: #f0faf5;
            color: #1a8a5a;
            border: 1px solid #2EB67D;
        }
        .badge-pending {
            background: #fffbeb;
            color: #92400e;
            border: 1px solid #fbbf24;
        }
        .header-divider {
            width: 100%;
            height: 3px;
            background: #2EB67D;
            margin: 14px 0 18px;
            border: none;
        }

        /* ── Zona 2: Cuerpo ── */
        .info-table { width: 100%; margin-bottom: 18px; }
        .info-table td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px 0 0;
        }
        .info-table td:last-child { padding: 0 0 0 8px; }
        .info-box {
            background: #f8fafc;
            border: 1.5px solid #000000;
            padding: 12px 14px;
        }
        .info-row { margin-bottom: 8px; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .info-value {
            font-size: 13px;
            font-weight: bold;
            color: #1a202c;
            margin-top: 3px;
        }

        .exams-table {
            width: 100%;
            margin-bottom: 16px;
            border: 2px solid #000000;
        }
        .exams-table th {
            background: #2EB67D;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 9px 11px;
            text-align: left;
            border: 1.5px solid #000000;
        }
        .exams-table th.col-price { text-align: right; }
        .exams-table td {
            padding: 9px 11px;
            font-size: 12px;
            border: 1.5px solid #000000;
            color: #1a202c;
        }
        .exams-table tr.row-alt td { background: #f8fafc; }
        .exams-table td.col-price {
            text-align: right;
            font-weight: bold;
        }

        .total-box {
            width: 100%;
            background: #f0faf5;
            border-left: 4px solid #2EB67D;
            padding: 12px 14px;
            margin-bottom: 16px;
        }
        .total-box-table { width: 100%; }
        .total-box-table td { vertical-align: middle; padding: 0; }
        .total-label {
            font-size: 14px;
            font-weight: bold;
            color: #1a202c;
        }
        .total-amount {
            font-size: 20px;
            font-weight: bold;
            color: #2EB67D;
            text-align: right;
        }

        .payment-box {
            width: 100%;
            border-left: 4px solid #2EB67D;
            padding: 12px 14px;
            margin-bottom: 20px;
            background: #ffffff;
            border-top: 1.5px solid #000000;
            border-right: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
        }
        .payment-title {
            font-size: 13px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 8px;
        }
        .payment-line {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 6px;
            line-height: 1.55;
        }
        .payment-line:last-child { margin-bottom: 0; }
        .payment-line strong { color: #1a202c; }

        /* ── Zona 3: Footer ── */
        .footer-divider {
            width: 100%;
            height: 2px;
            background: #2EB67D;
            margin: 8px 0 12px;
            border: none;
        }
        .footer-main {
            text-align: center;
            font-size: 11px;
            color: #1a202c;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .footer-generated {
            text-align: center;
            font-size: 10px;
            color: #64748b;
            margin-bottom: 8px;
        }
        .footer-thanks {
            text-align: center;
            font-size: 12px;
            color: #2EB67D;
            font-weight: bold;
        }
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
                <div class="header-subtitle">Comprobante interno de pago — no constituye factura fiscal</div>
            </td>
            <td class="header-meta" style="width: 45%;">
                <div class="header-meta-label">Comprobante N°</div>
                <div class="header-meta-number">{{ $invoice->invoice_number }}</div>
                <div class="header-meta-date">
                    Fecha de emisión: {{ $invoice->issued_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
                <span class="badge {{ $isPaid ? 'badge-paid' : 'badge-pending' }}">
                    {{ $isPaid ? 'Pagado' : 'Pendiente' }}
                </span>
            </td>
        </tr>
    </table>
    <div class="header-divider"></div>

    {{-- Zona 2 — Cuerpo --}}
    <table class="info-table">
        <tr>
            <td>
                <div class="info-box">
                    <div class="info-row">
                        <div class="info-label">Paciente</div>
                        <div class="info-value">{{ $invoice->order?->patient?->full_name ?? '—' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">N° Orden</div>
                        <div class="info-value">{{ $invoice->order?->order_number ?? '—' }}</div>
                    </div>
                    @if(($invoice->order?->samples ?? collect())->isNotEmpty())
                        <div class="info-row">
                            <div class="info-label">Código(s) de muestra</div>
                            <div class="info-value">
                                {{ $invoice->order->samples->pluck('barcode')->implode(' · ') }}
                            </div>
                        </div>
                    @endif
                </div>
            </td>
            <td>
                <div class="info-box">
                    <div class="info-row">
                        <div class="info-label">N° Comprobante</div>
                        <div class="info-value">{{ $invoice->invoice_number }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Fecha de emisión</div>
                        <div class="info-value">{{ $invoice->issued_at?->format('d/m/Y H:i') ?? '—' }}</div>
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="exams-table">
        <thead>
            <tr>
                <th style="width: 50%;">Examen</th>
                <th style="width: 25%;">Tipo</th>
                <th class="col-price" style="width: 25%;">Precio ({{ $currency }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->order?->exams ?? [] as $index => $exam)
                <tr class="{{ $index % 2 === 1 ? 'row-alt' : '' }}">
                    <td>{{ $exam->name }}</td>
                    <td>{{ $exam->type === 'imagen' ? 'Imagen' : 'Laboratorio' }}</td>
                    <td class="col-price">{{ number_format((float) $exam->price, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-muted" style="text-align: center; padding: 12px;">Sin exámenes registrados</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total-box">
        <table class="total-box-table">
            <tr>
                <td class="total-label">Total:</td>
                <td class="total-amount">
                    {{ number_format((float) $invoice->total_amount, 2, ',', '.') }} {{ $currency }}
                </td>
            </tr>
        </table>
    </div>

    @if($invoice->payments->isNotEmpty())
        <div class="payment-box">
            <div class="payment-title">Pago registrado</div>
            @foreach($invoice->payments as $payment)
                <div class="payment-line">
                    <strong>Método:</strong> {{ $payment->paymentMethod?->name ?? '—' }}
                    · <strong>Monto:</strong> {{ number_format((float) $payment->amount, 2, ',', '.') }} {{ $currency }}
                    · <strong>Fecha:</strong> {{ $payment->paid_at?->format('d/m/Y H:i') ?? '—' }}
                    · <strong>Cobrado por:</strong> {{ $payment->cashier?->name ?? '—' }}
                    @if($payment->receipt_number)
                        · <strong>Ref:</strong> {{ $payment->receipt_number }}
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Zona 3 — Footer --}}
    <div class="footer-divider"></div>
    <div class="footer-main">Clínica Norte S.R.L. · C/ Warnes Esq. Angel Sandoval · 75030069</div>
    <div class="footer-generated">Documento generado el {{ now()->format('d/m/Y H:i') }}</div>
    <div class="footer-thanks">Gracias por su confianza</div>

</body>
</html>
