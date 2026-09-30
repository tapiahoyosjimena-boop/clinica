@extends('patient-portal.layouts.app')

@section('title', 'Mis Comprobantes')

@section('content')
    <h1 class="pp-page-title">Mis Comprobantes</h1>
    <p class="pp-page-sub">Solo se muestran comprobantes asociados a sus órdenes. La descarga del PDF está disponible cuando el pago fue confirmado.</p>

    <div class="pp-card">
        @if($invoices->isEmpty())
            <div class="pp-empty">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z"/>
                </svg>
                <p>No hay comprobantes registrados para su cuenta.</p>
            </div>
        @else
            <div style="overflow-x:auto;">
                <table class="pp-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>N° Comprobante</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoices as $invoice)
                            <tr>
                                <td style="white-space:nowrap;">
                                    {{ $invoice->issued_at?->format('d/m/Y') ?? '—' }}
                                </td>
                                <td>
                                    <span style="font-weight:600;">{{ $invoice->invoice_number ?? '—' }}</span>
                                </td>
                                <td style="white-space:nowrap;">
                                    {{ number_format((float) $invoice->total_amount, 2, ',', '.') }}
                                    <span style="color:#9CA3AF;font-size:.78rem;">{{ config('clinic_bank.currency', 'BOB') }}</span>
                                </td>
                                <td>
                                    @if($invoice->status === 'pagada')
                                        <span class="badge badge-green">Pagada</span>
                                    @else
                                        <span class="badge badge-yellow">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                                        @if($invoice->status === 'pagada' && $invoice->receipt_pdf_path)
                                            <a href="{{ route('payments.invoices.pdf', $invoice) }}"
                                               class="btn btn-primary"
                                               target="_blank" rel="noopener">
                                                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M3 17a1 1 0 0 1 1-1h12a1 1 0 1 1 0 2H4a1 1 0 0 1-1-1Zm3.293-7.707a1 1 0 0 1 1.414 0L9 10.586V3a1 1 0 1 1 2 0v7.586l1.293-1.293a1 1 0 1 1 1.414 1.414l-3 3a1 1 0 0 1-1.414 0l-3-3a1 1 0 0 1 0-1.414Z" clip-rule="evenodd"/>
                                                </svg>
                                                Descargar
                                            </a>
                                        @else
                                            <span style="color:#9CA3AF;font-size:.8rem;">No disponible</span>
                                        @endif

                                        @if($attentionSlipAvailable[$invoice->id] ?? false)
                                            <a href="{{ route('payments.attention-slip', $invoice) }}"
                                               class="btn"
                                               style="border:1px solid #2EB67D; color:#1a8a5a;"
                                               target="_blank" rel="noopener">
                                                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor">
                                                    <path d="M4 4a2 2 0 0 1 2-2h6.586A2 2 0 0 1 14 2.586L17.414 6A2 2 0 0 1 18 7.414V16a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4Zm9 5a1 1 0 1 0-2 0v.5H9a1 1 0 1 0 0 2h2v.5a1 1 0 1 0 2 0v-.5h2a1 1 0 1 0 0-2h-2V9Z"/>
                                                </svg>
                                                Ficha de Atención
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($invoices->hasPages())
                <div class="pp-pagination">
                    {{ $invoices->links('portals.partials.pagination') }}
                </div>
            @endif
        @endif
    </div>
@endsection
