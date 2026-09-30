<?php

namespace App\Domains\Results\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultDetail extends Model
{
    protected $table = 'result_details';

    protected $fillable = [
        'result_id',
        'parameter_name',
        'value',
        'unit',
        'reference_min',
        'reference_max',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class, 'result_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isOutOfCriticalRange(?float $criticalMin = null, ?float $criticalMax = null): bool
    {
        if ($criticalMin === null && $criticalMax === null) {
            return false;
        }

        $val = is_numeric($this->value) ? (float) $this->value : null;
        if ($val === null) {
            return false;
        }

        if ($criticalMin !== null && $val < $criticalMin) {
            return true;
        }

        if ($criticalMax !== null && $val > $criticalMax) {
            return true;
        }

        return false;
    }
}
