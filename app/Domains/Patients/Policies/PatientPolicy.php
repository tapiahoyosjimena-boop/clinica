<?php

namespace App\Domains\Patients\Policies;

use App\Domains\Patients\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('patients.access');
    }

    public function view(User $user, Patient $patient): bool
    {
        return $user->can('patients.access');
    }

    public function create(User $user): bool
    {
        return $user->can('patients.access');
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->can('patients.access');
    }

    public function delete(User $user, Patient $patient): bool
    {
        return self::userMayDeleteOrRestorePatient($user);
    }

    public function deleteAny(User $user): bool
    {
        return self::userMayDeleteOrRestorePatient($user);
    }

    public function restore(User $user, Patient $patient): bool
    {
        return self::userMayDeleteOrRestorePatient($user);
    }

    public function restoreAny(User $user): bool
    {
        return self::userMayDeleteOrRestorePatient($user);
    }

    public function forceDelete(User $user, Patient $patient): bool
    {
        return self::userMayDeleteOrRestorePatient($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return self::userMayDeleteOrRestorePatient($user);
    }

    /** Eliminación / papelera: solo administración y recepción. */
    private static function userMayDeleteOrRestorePatient(User $user): bool
    {
        return $user->can('patients.access')
            && $user->hasAnyRole(['Administrador', 'Recepcionista']);
    }
}
