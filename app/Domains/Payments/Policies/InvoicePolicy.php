<?php

namespace App\Domains\Payments\Policies;

use App\Domains\Payments\Models\Invoice;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->can('payments.access');
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if (! $user->can('payments.access')) {
            return false;
        }

        if ($user->hasRole('Administrador') && ! $invoice->order) {
            return true;
        }

        if (! $invoice->order) {
            return false;
        }

        return ResponsibleClinicalStaffScoping::userMayAccessOrderInPanel($user, $invoice->order);
    }

    public function create(User $user): bool
    {
        return (bool) $user->can('payments.access');
    }

    public function update(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return $user->hasRole('Administrador')
            && $user->can('payments.access');
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return false;
    }

    /**
     * Portal del paciente: ver comprobantes de sus órdenes sin payments.access.
     */
    public function viewAsPatient(User $user, Invoice $invoice): bool
    {
        if (! $user->hasRole('Paciente')) {
            return false;
        }

        $patient = $user->patient;
        if (! $patient) {
            return false;
        }

        return (int) $invoice->order?->patient_id === (int) $patient->id;
    }

    public function downloadPdfAsPatient(User $user, Invoice $invoice): bool
    {
        return $this->viewAsPatient($user, $invoice) && $invoice->status === 'pagada';
    }
}
