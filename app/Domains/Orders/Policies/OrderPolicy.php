<?php

namespace App\Domains\Orders\Policies;

use App\Domains\Orders\Models\Order;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('orders.access');
    }

    public function view(User $user, Order $order): bool
    {
        return $user->can('orders.access')
            && ResponsibleClinicalStaffScoping::userMayAccessOrderInPanel($user, $order);
    }

    public function create(User $user): bool
    {
        return $user->can('orders.access');
    }

    public function update(User $user, Order $order): bool
    {
        if (! $user->can('orders.access')) {
            return false;
        }
        if (! ResponsibleClinicalStaffScoping::userMayAccessOrderInPanel($user, $order)) {
            return false;
        }
        if ($order->status === 'completada' && ! $user->hasRole('Administrador')) {
            return false;
        }

        return true;
    }

    /**
     * Cancelar orden: pendiente o en proceso (p. ej. tras pago), si no hay muestras en análisis
     * ni estudios de imagen ya iniciados; usuario con permiso y alcance de la orden.
     */
    public function cancel(User $user, Order $order): bool
    {
        if (! $user->can('orders.access')) {
            return false;
        }
        if (! in_array($order->status, ['pendiente', 'en_proceso'], true)) {
            return false;
        }

        if ($order->hasIrreversibleLabOrImagingProgress()) {
            return false;
        }

        return ResponsibleClinicalStaffScoping::userMayAccessOrderInPanel($user, $order);
    }

    public function delete(User $user, Order $order): bool
    {
        return $user->hasRole('Administrador');
    }

    public function restore(User $user, Order $order): bool
    {
        return $user->hasRole('Administrador');
    }

    public function forceDelete(User $user, Order $order): bool
    {
        return $user->hasRole('Administrador');
    }
}
