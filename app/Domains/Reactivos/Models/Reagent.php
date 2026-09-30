<?php

namespace App\Domains\Reactivos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reagent extends Model
{
    use SoftDeletes;

    protected $table = 'reagents';

    protected $fillable = [
        'name',
        'description',
        'unit',
        'stock_quantity',
        'min_stock',
        'expiration_date',
        'provider_id',
    ];

    protected function casts(): array
    {
        return [
            'stock_quantity' => 'integer',
            'min_stock' => 'integer',
            'expiration_date' => 'date',
        ];
    }

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'reagent_id')->latest('movement_date');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isLowStock(): bool
    {
        return $this->stock_quantity < $this->min_stock;
    }

    public function isExpired(): bool
    {
        return $this->expiration_date !== null && $this->expiration_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiration_date !== null
            && ! $this->isExpired()
            && $this->expiration_date->lte(now()->addDays($days));
    }
}
