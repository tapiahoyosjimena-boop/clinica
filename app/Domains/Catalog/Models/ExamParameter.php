<?php

namespace App\Domains\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ExamParameter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exam_parameters';

    protected $fillable = [
        'exam_category_id',
        'name',
        'unit',
        'reference_min',
        'reference_max',
        'critical_min',
        'critical_max',
    ];

    protected $casts = [
        'reference_min' => 'integer',
        'reference_max' => 'integer',
        'critical_min' => 'integer',
        'critical_max' => 'integer',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'exam_category_id');
    }

    protected static function booted(): void
    {
        static::saving(function (ExamParameter $parameter): void {
            $category = $parameter->relationLoaded('category')
                ? $parameter->category
                : ExamCategory::query()->find($parameter->exam_category_id);

            if ($category !== null && ! $category->supportsAnalyticalParameters()) {
                throw ValidationException::withMessages([
                    'exam_category_id' => 'Los parámetros analíticos solo aplican a categorías de tipo Laboratorio. Los estudios de imagen se registran como informe libre al cargar resultados.',
                ]);
            }
        });
    }
}
