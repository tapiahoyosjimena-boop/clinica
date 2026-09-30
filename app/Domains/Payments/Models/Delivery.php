<?php

namespace App\Domains\Payments\Models;

use App\Domains\Patients\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de envío (email/whatsapp) — usado para resultados de lab y comprobantes de pago.
 */
class Delivery extends Model
{
    protected $table = 'deliveries';

    protected $fillable = [
        'result_id',
        'invoice_id',
        'patient_id',
        'channel',
        'sent_at',
        'status',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}
