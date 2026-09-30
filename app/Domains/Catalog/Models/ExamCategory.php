<?php

namespace App\Domains\Catalog\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class ExamCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'exam_categories';

    protected $fillable = [
        'type',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Las categorías de imagen usan informe libre y archivo en resultados;
     * no admiten parámetros analíticos con rangos de referencia.
     */
    public function supportsAnalyticalParameters(): bool
    {
        return $this->type === 'laboratorio';
    }

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function parameters(): HasMany
    {
        return $this->hasMany(ExamParameter::class, 'exam_category_id');
    }

    public function requirements(): HasManyThrough
    {
        return $this->hasManyThrough(
            ExamRequirement::class,
            Exam::class,
            'exam_category_id', // FK en exams → exam_categories
            'exam_id',          // FK en exam_requirements → exams
        );
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'exam_category_id');
    }

    protected static function booted(): void
    {
        static::saving(function (ExamCategory $category): void {
            if (! $category->isDirty('type') || $category->type !== 'imagen') {
                return;
            }

            if ($category->exists && $category->parameters()->exists()) {
                throw ValidationException::withMessages([
                    'type' => 'No puede cambiar a tipo Imagen mientras la categoría tenga parámetros analíticos. Elimínelos primero o mantenga el tipo Laboratorio.',
                ]);
            }
        });
    }
}
