<?php

namespace App\Domains\Catalog\Models;

use App\Domains\Imaging\Models\ImagingEquipment;
use App\Domains\Orders\Models\Order;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exam extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exams';

    protected $fillable = [
        'name',
        'exam_category_id',
        'type',
        'imaging_equipment_id',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'exam_category_id');
    }

    public function imagingEquipment(): BelongsTo
    {
        return $this->belongsTo(ImagingEquipment::class, 'imaging_equipment_id');
    }

    /**
     * @param  array<int|string>  $examIds
     */
    public static function validateSelectionForOrder(string $orderType, array $examIds): ?string
    {
        $ids = array_values(array_filter(array_map('intval', $examIds)));
        if ($ids === []) {
            return null;
        }

        $exams = static::query()->whereIn('id', $ids)->get();

        if ($orderType === 'imagen') {
            $imaging = $exams->where('type', 'imagen');
            if ($imaging->count() > 1) {
                return 'En órdenes de imagen solo puede seleccionar un examen de imagen a la vez.';
            }
            if ($imaging->isEmpty()) {
                return 'Debe seleccionar un examen de imagen.';
            }
            if ($exams->where('type', 'laboratorio')->isNotEmpty()) {
                return 'No puede incluir exámenes de laboratorio en una orden de imagen.';
            }
            $exam = $imaging->first();
            if (! $exam->imaging_equipment_id) {
                return 'El examen seleccionado no tiene equipo de imagen asignado en el catálogo. Actualícelo en Catálogo → Exámenes.';
            }
        }

        if ($orderType === 'laboratorio' && $exams->where('type', 'imagen')->isNotEmpty()) {
            return 'No puede incluir exámenes de imagen en una orden de laboratorio.';
        }

        return null;
    }

    /**
     * Equipo de imagen definido en catálogo para una orden con un único examen de imagen.
     *
     * @param  array<int|string>  $examIds
     */
    public static function resolveImagingEquipmentIdForOrder(string $orderType, array $examIds): ?int
    {
        if ($orderType !== 'imagen') {
            return null;
        }
        $ids = array_values(array_filter(array_map('intval', $examIds)));
        if (count($ids) !== 1) {
            return null;
        }
        $exam = static::query()->find($ids[0]);
        if ($exam && $exam->type === 'imagen' && $exam->imaging_equipment_id) {
            return (int) $exam->imaging_equipment_id;
        }

        return null;
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(ExamRequirement::class, 'exam_id');
    }

    public function orders(): BelongsToMany
    {
        return $this->belongsToMany(Order::class, 'order_exam', 'exam_id', 'order_id');
    }
}
