<?php

namespace App\Domains\Payments\Filament\Resources\InvoiceResource\Pages;

use App\Domains\Payments\Filament\Resources\InvoiceResource;
use App\Domains\Payments\Models\Payment;
use App\Domains\Payments\Models\PaymentMethod;
use App\Domains\Payments\Services\AttentionSlipService;
use App\Domains\Payments\Services\LibelulaService;
use App\Domains\Payments\Services\PaymentReceiptService;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Livewire\Attributes\On;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    /** Seteado a true cuando el polling JS detecta que Libélula confirmó el pago. */
    public bool $qrPaymentConfirmed = false;

    // ──────────────────────────────────────────────────────────
    // Livewire listener — el JS del modal QR dispara este evento
    // ──────────────────────────────────────────────────────────

    /**
     * Opción A: al detectar el pago (polling tras QR) solo actualizamos estado y UI.
     * El modal permanece abierto hasta que el usuario pulse Enviar para cerrar y descargar el PDF.
     */
    #[On('qr-payment-confirmed')]
    public function markQrConfirmed(): void
    {
        $this->record->refresh();

        if ($this->qrPaymentConfirmed) {
            return;
        }

        $this->qrPaymentConfirmed = true;

        Notification::make()
            ->title('Pago recibido por Libélula')
            ->body('Pulse «Confirmar» para registrar el comprobante y cerrar esta ventana.')
            ->success()
            ->send();
    }

    // ──────────────────────────────────────────────────────────
    // Acciones de cabecera
    // ──────────────────────────────────────────────────────────

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('descargar_pdf')
                ->label('Descargar comprobante')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn (): bool => (bool) $this->record->receipt_pdf_path
                    && $this->record->status === 'pagada'
                    && ! $this->qrPaymentConfirmed
                )
                ->url(fn (): string => route('payments.invoices.pdf', ['invoice' => $this->record]))
                ->openUrlInNewTab(),

            Actions\Action::make('ficha_atencion')
                ->label('Ficha de Atención')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('info')
                ->visible(fn (): bool => $this->record->status === 'pagada'
                    && ! $this->qrPaymentConfirmed
                    && $this->record->order
                    && app(AttentionSlipService::class)->hasPendingAction($this->record->order)
                )
                ->url(fn (): string => route('payments.attention-slip', ['invoice' => $this->record]))
                ->openUrlInNewTab(),

            Actions\Action::make('registrar_pago')
                ->label(fn (): string => ($this->qrPaymentConfirmed && $this->record->status === 'pagada')
                        ? 'Completar registro QR'
                        : 'Registrar pago'
                )
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (): bool => $this->record->status === 'pendiente' || $this->qrPaymentConfirmed
                )
                ->modalSubmitActionLabel(fn (): string => $this->qrPaymentConfirmed ? 'Confirmar' : 'Enviar'
                )
                ->form([
                    Forms\Components\Placeholder::make('cashier_user')
                        ->label('Registrado por')
                        ->content(fn (): string => auth()->user()?->name ?? '—'),

                    Forms\Components\Select::make('payment_method_id')
                        ->label('Método de pago')
                        ->options(fn () => PaymentMethod::query()
                            ->where('is_active', true)
                            ->orderBy('name')
                            ->pluck('name', 'id'))
                        ->required()
                        ->native(false)
                        ->live(),

                    // ── Botón "Generar QR" ───────────────────────────────
                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('generar_qr')
                            ->label('Generar QR')
                            ->icon('heroicon-o-qr-code')
                            ->color('info')
                            ->visible(fn (Get $get): bool => $this->resolveIsQrMethod($get('payment_method_id'))
                                && ! $this->qrPaymentConfirmed
                            )
                            ->action(function (Get $get): void {
                                $methodId = $get('payment_method_id');

                                if (! $this->resolveIsQrMethod($methodId)) {
                                    return;
                                }

                                $libelula = app(LibelulaService::class);
                                $qrImageUrl = $this->record->libelula_qr_simple_url;

                                // Si no tenemos la imagen del QR, llamamos a Libélula para obtenerla
                                if (! $qrImageUrl) {
                                    try {
                                        $libelula->registrarDeuda($this->record);
                                        $this->record->refresh();
                                        $qrImageUrl = $this->record->libelula_qr_simple_url;
                                    } catch (\Throwable $e) {
                                        Notification::make()
                                            ->title('No se pudo generar el QR')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();

                                        return;
                                    }
                                }

                                if (! $qrImageUrl) {
                                    Notification::make()
                                        ->title('QR no disponible')
                                        ->body('Libélula no devolvió la imagen del QR. Intente de nuevo.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                $statusUrl = route('payments.qr.status', ['invoice' => $this->record->id]);
                                $this->js($this->buildQrModalJs($statusUrl, $qrImageUrl));
                            }),
                    ])
                        ->visible(fn (Get $get): bool => $this->resolveIsQrMethod($get('payment_method_id'))
                            && ! $this->qrPaymentConfirmed
                        ),

                    // ── Mensaje "Pago exitoso" ───────────────────────────
                    Forms\Components\Placeholder::make('qr_success_msg')
                        ->label('')
                        ->content(new HtmlString(
                            '<div class="cn-embedded-text" style="display:flex;align-items:center;gap:8px;color:#16a34a;font-weight:600;font-size:.95rem;">'
                            .'<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20" fill="currentColor">'
                            .'<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>'
                            .'</svg>'
                            .'Pago recibido — pulse «Confirmar» para registrar el comprobante y cerrar</div>'
                        ))
                        ->visible(fn (): bool => $this->qrPaymentConfirmed),

                    // ── Fecha/hora: solo visible para pagos en efectivo ──
                    Forms\Components\DateTimePicker::make('paid_at')
                        ->label('Fecha y hora del pago')
                        ->default(now())
                        ->required(fn (Get $get): bool => ! $this->resolveIsQrMethod($get('payment_method_id')))
                        ->native(false)
                        ->hidden(fn (Get $get): bool => $this->resolveIsQrMethod($get('payment_method_id')))
                        ->helperText('Solo para pagos en efectivo.'),

                    Forms\Components\Placeholder::make('monto')
                        ->label('Monto a registrar')
                        ->content(fn (): string => number_format((float) $this->record->total_amount, 2, ',', '.')
                            .' '.config('clinic_bank.currency', 'BOB')
                        ),
                ])
                ->action(function (array $data): void {
                    $selectedMethod = PaymentMethod::find($data['payment_method_id']);
                    $isQr = $selectedMethod && str_contains(strtolower($selectedMethod->name), 'qr');

                    if ($isQr) {
                        // Verificar directamente en BD — más fiable que la propiedad Livewire,
                        // que puede quedar desactualizada si el snapshot no se rehidrató tras el evento.
                        $freshInvoice = $this->record->fresh();

                        if ($freshInvoice->status !== 'pagada') {
                            Notification::make()
                                ->title('Pago QR pendiente')
                                ->body('Primero genere el QR y espere que el paciente confirme el pago.')
                                ->warning()
                                ->send();

                            throw new Halt;
                        }

                        $this->qrPaymentConfirmed = false;
                        $this->record->refresh();
                        $this->record->load(['payments.paymentMethod', 'payments.cashier', 'order.patient', 'order.exams']);

                        Notification::make()
                            ->title('Pago QR registrado')
                            ->body('El comprobante fue generado correctamente.')
                            ->success()
                            ->send();

                        $this->unmountAction();
                    } else {
                        $this->handleCashPayment($data);
                    }
                }),
            Actions\DeleteAction::make()
                ->label('Borrar comprobante')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn (): bool => InvoiceResource::canDelete($this->record))
                ->requiresConfirmation()
                ->modalHeading('¿Borrar este comprobante?')
                ->modalDescription('Se eliminarán los pagos vinculados y el PDF del comprobante. La orden no se borra; podrá emitirse un comprobante nuevo si aplica.')
                ->successRedirectUrl(fn () => InvoiceResource::getUrl('index')),
        ];
    }

    // ──────────────────────────────────────────────────────────
    // Helpers privados
    // ──────────────────────────────────────────────────────────

    private function resolveIsQrMethod(?string $methodId): bool
    {
        if (! $methodId) {
            return false;
        }
        $method = PaymentMethod::find($methodId);

        return $method && str_contains(strtolower($method->name), 'qr');
    }

    /**
     * Genera el bloque JavaScript del modal QR.
     * Muestra directamente la imagen PNG del QR que entrega Libélula.
     * Sin iframe, sin dependencias externas.
     */
    private function buildQrModalJs(string $statusUrl, string $qrImageUrl): string
    {
        $statusUrlJson = json_encode($statusUrl);
        $qrImageJson = json_encode($qrImageUrl);

        return <<<JS
(function () {
    var existing = document.getElementById('cn-qr-overlay');
    if (existing) existing.remove();

    var overlay = document.createElement('div');
    overlay.id = 'cn-qr-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;box-sizing:border-box;';
    overlay.innerHTML =
        '<div class="cn-qr-modal">' +
        '  <div class="cn-qr-modal__header">' +
        '    <div>' +
        '      <div class="cn-qr-modal__title">Pago con QR — Libélula</div>' +
        '      <div class="cn-qr-modal__subtitle">Escanea el código con tu aplicación bancaria</div>' +
        '    </div>' +
        '    <div class="cn-qr-modal__badge">' +
        '      <span id="cn-qr-dot" style="width:7px;height:7px;background:#f59e0b;border-radius:50%;display:inline-block;"></span>' +
        '      Esperando pago...' +
        '    </div>' +
        '  </div>' +
        '  <div class="cn-qr-modal__body">' +
        '    <img src={$qrImageJson} style="width:260px;height:260px;display:block;margin:0 auto;" alt="Código QR de pago" />' +
        '    <p class="cn-qr-modal__hint">Apunta la cámara al código QR para pagar</p>' +
        '  </div>' +
        '  <div class="cn-qr-modal__footer">' +
        '    <button type="button" id="cn-qr-cancel" class="cn-qr-modal__cancel">Cancelar</button>' +
        '  </div>' +
        '</div>';

    document.body.appendChild(overlay);

    var dot = document.getElementById('cn-qr-dot');
    var dotAnim = setInterval(function () {
        dot.style.opacity = dot.style.opacity === '0.2' ? '1' : '0.2';
    }, 700);

    var pollDone = false;

    var pollTimer = setInterval(function () {
        if (pollDone) return;
        fetch({$statusUrlJson}, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.pagada && !pollDone) {
                pollDone = true;
                clearInterval(pollTimer);
                clearInterval(dotAnim);
                overlay.remove();
                if (window.Livewire) { Livewire.dispatch('qr-payment-confirmed'); }
            }
        })
        .catch(function () {});
    }, 3000);

    document.getElementById('cn-qr-cancel').onclick = function () {
        clearInterval(pollTimer);
        clearInterval(dotAnim);
        overlay.remove();
    };
})();
JS;
    }

    // ──────────────────────────────────────────────────────────
    // Flujo Efectivo (sin cambios respecto al original)
    // ──────────────────────────────────────────────────────────

    private function handleCashPayment(array $data): void
    {
        $receiptService = app(PaymentReceiptService::class);

        try {
            DB::transaction(function () use ($data, $receiptService): void {
                $invoice = $this->record->fresh();
                if ($invoice->status !== 'pendiente') {
                    throw new \RuntimeException('Este comprobante ya no está pendiente.');
                }
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total_amount,
                    'payment_method_id' => $data['payment_method_id'],
                    'paid_at' => $data['paid_at'],
                    'receipt_number' => 'RCP-'.$invoice->id.'-'.strtoupper(Str::random(4)),
                    'cashier_user_id' => auth()->id(),
                ]);
                $invoice->update(['status' => 'pagada']);
                $invoice->refresh();
                $invoice->load(['payments.paymentMethod', 'payments.cashier', 'order.patient', 'order.exams']);
                $receiptService->generateAndStorePdf($invoice);
            });
        } catch (\Throwable $e) {
            Notification::make()
                ->title('No se pudo completar el pago')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->record->refresh();
        $this->record->load(['payments.paymentMethod', 'payments.cashier', 'order.patient', 'order.exams']);

        Notification::make()
            ->title('Pago registrado')
            ->body('Se guardó el pago y se generó el comprobante correctamente.')
            ->success()
            ->send();
    }
}
