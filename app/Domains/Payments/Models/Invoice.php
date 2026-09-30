<?php

namespace App\Domains\Payments\Models;

use App\Domains\Orders\Models\Order;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Invoice extends Model
{
    protected $table = 'invoices';

    protected $fillable = [
        'order_id',
        'invoice_number',
        'total_amount',
        'status',
        'issued_at',
        'receipt_pdf_path',
        'libelula_transaction_id',
        'libelula_payment_url',
        'libelula_status',
        'libelula_qr_simple_url',
        'libelula_codigo_recaudacion',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'issued_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Invoice $invoice): void {
            $invoice->loadMissing('payments');
            foreach ($invoice->payments as $payment) {
                $payment->delete();
            }

            if (filled($invoice->receipt_pdf_path)) {
                try {
                    if (Storage::disk('public')->exists($invoice->receipt_pdf_path)) {
                        Storage::disk('public')->delete($invoice->receipt_pdf_path);
                    }
                } catch (\Throwable) {
                    // No impedir el borrado del comprobante si falla el almacenamiento.
                }
            }
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'invoice_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'invoice_id');
    }

    public static function generateInvoiceNumber(): string
    {
        do {
            $number = 'CN-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        } while (static::where('invoice_number', $number)->exists());

        return $number;
    }

    public static function calculateTotalForOrder(Order $order): string
    {
        $order->loadMissing('exams');

        return (string) $order->exams->sum(fn ($exam) => (float) $exam->price);
    }

    /**
     * Crea un comprobante interno en estado pendiente si la orden aún no tiene uno (misma lógica que Crear comprobante en Filament).
     */
    public static function ensurePendingForOrder(Order $order): ?self
    {
        $order = $order->fresh(['invoice', 'exams']);
        if (! $order) {
            return null;
        }
        if ($order->invoice) {
            return $order->invoice;
        }
        if ($order->exams->isEmpty()) {
            return null;
        }

        return static::create([
            'order_id' => $order->id,
            'invoice_number' => static::generateInvoiceNumber(),
            'total_amount' => static::calculateTotalForOrder($order),
            'status' => 'pendiente',
            'issued_at' => now(),
        ]);
    }

    /**
     * Texto multilínea para infolist (exámenes de la orden).
     */
    public function getExamsSummaryAttribute(): string
    {
        $this->loadMissing('order.exams');
        $lines = $this->order?->exams->map(function ($exam) {
            $tipo = $exam->type === 'imagen' ? 'Imagen' : 'Laboratorio';
            $precio = number_format((float) $exam->price, 2, ',', '.').' '.config('clinic_bank.currency', 'BOB');

            return '• '.$exam->name.' ('.$tipo.') — '.$precio;
        }) ?? collect();

        return $lines->implode("\n") ?: '—';
    }

    /**
     * Texto multilínea para infolist (pagos registrados).
     */
    public function getPaymentsSummaryAttribute(): string
    {
        $this->loadMissing('payments.paymentMethod', 'payments.cashier');
        if ($this->payments->isEmpty()) {
            return '—';
        }

        return $this->payments->map(function ($p) {
            $m = $p->paymentMethod?->name ?? '—';
            $a = number_format((float) $p->amount, 2, ',', '.').' '.config('clinic_bank.currency', 'BOB');
            $t = $p->paid_at?->format('d/m/Y H:i') ?? '—';
            $r = $p->receipt_number ?? '—';
            $c = $p->cashier?->name ?? '—';

            return "• {$m} — {$a} — {$t} — Ref: {$r} — Cobrado por: {$c}";
        })->implode("\n");
    }

    public function getQrNoteAttribute(): string
    {
        return 'Placeholder visual en el PDF (sin pasarela).';
    }
}
