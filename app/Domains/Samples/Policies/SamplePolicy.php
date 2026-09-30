<?php

namespace App\Domains\Samples\Policies;

use App\Domains\Samples\Models\Sample;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;

class SamplePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('samples.access');
    }

    public function view(User $user, Sample $sample): bool
    {
        return $user->can('samples.access')
            && ResponsibleClinicalStaffScoping::userMayAccessSampleInPanel($user, $sample);
    }

    public function create(User $user): bool
    {
        return $user->can('samples.access');
    }

    public function update(User $user, Sample $sample): bool
    {
        if (! $user->can('samples.access')) {
            return false;
        }
        if (! ResponsibleClinicalStaffScoping::userMayAccessSampleInPanel($user, $sample)) {
            return false;
        }
        if ($sample->status === 'procesada' && ! $user->hasRole('Administrador')) {
            return false;
        }

        return true;
    }

    public function reject(User $user, Sample $sample): bool
    {
        if (! $user->can('samples.access')) {
            return false;
        }
        if (! ResponsibleClinicalStaffScoping::userMayAccessSampleInPanel($user, $sample)) {
            return false;
        }
        if ($sample->status !== 'en_analisis') {
            return false;
        }

        return $user->hasRole('Bioquímico')
            && $user->can('samples.reject');
    }

    public function approve(User $user, Sample $sample): bool
    {
        return $user->can('samples.access')
            && ResponsibleClinicalStaffScoping::userMayAccessSampleInPanel($user, $sample)
            && $user->can('samples.approve');
    }

    /**
     * recibida → en_analisis (Aceptar muestra). Solo bioquímico responsable de la orden.
     */
    public function acceptSample(User $user, Sample $sample): bool
    {
        if (! $user->can('samples.access')) {
            return false;
        }
        if (! ResponsibleClinicalStaffScoping::userMayAccessSampleInPanel($user, $sample)) {
            return false;
        }
        if ($sample->status !== 'recibida') {
            return false;
        }

        return $user->hasRole('Bioquímico')
            && $user->can('samples.approve')
            && (int) $sample->order?->responsible_user_id === (int) $user->id;
    }

    /**
     * en_analisis → procesada (Marcar procesada). Solo bioquímico con permiso de aprobación.
     */
    public function processSample(User $user, Sample $sample): bool
    {
        if (! $user->can('samples.access')) {
            return false;
        }
        if (! ResponsibleClinicalStaffScoping::userMayAccessSampleInPanel($user, $sample)) {
            return false;
        }
        if ($sample->status !== 'en_analisis') {
            return false;
        }

        return $user->hasRole('Bioquímico')
            && $user->can('samples.approve');
    }

    public function delete(User $user, Sample $sample): bool
    {
        return $user->hasRole('Administrador');
    }

    public function restore(User $user, Sample $sample): bool
    {
        return false;
    }

    public function forceDelete(User $user, Sample $sample): bool
    {
        return $user->hasRole('Administrador');
    }
}
