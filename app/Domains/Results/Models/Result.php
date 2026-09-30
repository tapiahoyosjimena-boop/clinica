<?php

namespace App\Domains\Results\Models;

use App\Domains\Catalog\Models\Exam;
use App\Domains\Orders\Models\Order;
use App\Domains\Payments\Models\Delivery;
use App\Domains\Samples\Models\Sample;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Result extends Model
{
    /**
     * Valor de `result_details.parameter_name` para el informe clínico breve en resultados de laboratorio.
     */
    public const PARAMETER_NAME_LAB_INFORME = 'Informe clínico';

    protected $table = 'results';

    protected $fillable = [
        'sample_id',
        'order_id',
        'exam_id',
        'bioquimico_id',
        'validated_at',
        'is_critical',
        'pdf_path',
        'published_to_portal_at',
    ];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
            'published_to_portal_at' => 'datetime',
            'is_critical' => 'boolean',
        ];
    }

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function sample(): BelongsTo
    {
        return $this->belongsTo(Sample::class, 'sample_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class, 'exam_id');
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bioquimico_id');
    }

    public function details(): HasMany
    {
        return $this->hasMany(ResultDetail::class, 'result_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(Delivery::class, 'result_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isLabType(): bool
    {
        return $this->order?->type === 'laboratorio';
    }

    public function isImagingType(): bool
    {
        return $this->order?->type === 'imagen';
    }

    /**
     * Detalles que corresponden a parámetros analíticos (excluye el informe clínico de laboratorio).
     *
     * @return Collection<int, ResultDetail>
     */
    public function labParameterDetails(): Collection
    {
        return $this->details->filter(
            fn (ResultDetail $d) => $d->parameter_name !== self::PARAMETER_NAME_LAB_INFORME
        )->values();
    }

    public function labInformeText(): ?string
    {
        $d = $this->details->firstWhere('parameter_name', self::PARAMETER_NAME_LAB_INFORME);

        return $d?->value;
    }

    /**
     * Indica si el responsable ya completó los valores del examen o el informe.
     * Laboratorio: requiere parámetros analíticos + informe clínico.
     * Imagen: requiere el texto del informe.
     */
    public function hasCompleteData(): bool
    {
        $this->loadMissing('details', 'order');

        if ($this->isLabType()) {
            return $this->labParameterDetails()->isNotEmpty() && filled($this->labInformeText());
        }

        if ($this->isImagingType()) {
            $text = $this->details->firstWhere('parameter_name', 'Informe')?->value;

            return filled($text);
        }

        return false;
    }
}
