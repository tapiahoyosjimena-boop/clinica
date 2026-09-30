<?php

namespace App\Domains\Reactivos\Observers;

use App\Domains\Reactivos\Models\Reagent;
use App\Domains\Reactivos\Models\StockMovement;

class ReagentObserver
{
    public function created(Reagent $reagent): void
    {
        if ($reagent->stock_quantity <= 0) {
            return;
        }

        StockMovement::create([
            'reagent_id' => $reagent->id,
            'type' => 'entrada',
            'quantity' => $reagent->stock_quantity,
            'movement_date' => now(),
            'user_id' => auth()->id(),
            'notes' => 'Stock inicial al registrar el reactivo.',
        ]);
    }
}
