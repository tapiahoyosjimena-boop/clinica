<?php

namespace App\Domains\Imaging\Models;

use App\Domains\Catalog\Models\Exam;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImagingEquipment extends Model
{
    use SoftDeletes;

    protected $table = 'imaging_equipment';

    protected $fillable = [
        'name',
        'type',
        'description',
        'status',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function studies(): HasMany
    {
        return $this->hasMany(ImagingStudy::class, 'equipment_id');
    }

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'imaging_equipment_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'ecógrafo' => 'Ecógrafo',
            'rayos_x' => 'Rayos X',
            'tomógrafo' => 'Tomógrafo',
            'otro' => 'Otro',
            default => ucfirst($this->type),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabel((string) $this->status);
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'disponible' => 'Disponible',
            'mantenimiento' => 'En mantenimiento',
            'fuera_de_servicio' => 'Fuera de servicio',
            default => ucfirst($status),
        };
    }
}
