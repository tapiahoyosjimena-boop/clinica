<?php

namespace App\Domains\Results\Policies;

use App\Domains\Auth\Support\SystemPermissions;
use App\Domains\Results\Models\Result;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;

class ResultPolicy
{
    public function viewAny(User $user): bool
    {
        return (bool) $user->can('results.access');
    }

    public function view(User $user, Result $result): bool
    {
        if (! $user->can('results.access')) {
            return false;
        }

        return ResponsibleClinicalStaffScoping::userMayAccessResultInPanel($user, $result);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Result $result): bool
    {
        if (! $user->can('results.access')) {
            return false;
        }

        if ($result->validated_at !== null) {
            return false;
        }

        if ($user->hasRole('Paciente') || $user->hasRole('Médico')) {
            return false;
        }

        if ($user->hasRole('Administrador')) {
            return true;
        }

        if ($user->hasAnyRole(['Bioquímico', 'Tecnólogo de Imagen'])) {
            return ResponsibleClinicalStaffScoping::userMayAccessResultInPanel($user, $result);
        }

        return false;
    }

    public function delete(User $user, Result $result): bool
    {
        return false;
    }

    public function restore(User $user, Result $result): bool
    {
        return false;
    }

    public function forceDelete(User $user, Result $result): bool
    {
        return false;
    }

    /**
     * El paciente puede ver/descargar su resultado en el portal cuando ya fue publicado.
     * Funciona con cualquier rol que tenga patient.results.access, no solo "Paciente".
     */
    public function viewAsPatient(User $user, Result $result): bool
    {
        if (! $user->can(SystemPermissions::PATIENT_RESULTS)) {
            return false;
        }

        $patient = $user->patient;
        if (! $patient) {
            return false;
        }

        if ((int) $result->order?->patient_id !== (int) $patient->id) {
            return false;
        }

        return $result->published_to_portal_at !== null
            && $result->validated_at !== null
            && filled($result->pdf_path);
    }
}
