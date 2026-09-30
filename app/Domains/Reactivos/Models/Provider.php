<?php

namespace App\Domains\Reactivos\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Provider extends Model
{
    protected $table = 'providers';

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────────

    public function reagents(): HasMany
    {
        return $this->hasMany(Reagent::class, 'provider_id');
    }
}
