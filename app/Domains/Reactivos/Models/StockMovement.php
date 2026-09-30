<?php

namespace App\Domains\Reactivos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    protected $table = 'stock_movements';

    protected $fillable = [
        'reagent_id',
        'order_id',
        'type',
        'quantity',
        'movement_date',
        'user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'movement_date' => 'datetime',
        ];
    }

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function reagent(): BelongsTo
    {
        return $this->belongsTo(Reagent::class, 'reagent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Orders\Models\Order::class, 'order_id');
    }
}
