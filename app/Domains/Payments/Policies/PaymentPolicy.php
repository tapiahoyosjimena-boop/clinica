<?php

namespace App\Domains\Payments\Policies;

use App\Domains\Payments\Models\Payment;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->can('payments.access');
    }

    public function view(User $user, Payment $payment): bool
    {
        if (! $user->can('payments.access')) {
            return false;
        }

        $invoice = $payment->invoice ?? $payment->invoice()->first();
        if (! $invoice?->order) {
            return false;
        }

        return ResponsibleClinicalStaffScoping::userMayAccessOrderInPanel($user, $invoice->order);
    }

    public function create(User $user): bool
    {
        return (bool) $user->can('payments.access');
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
