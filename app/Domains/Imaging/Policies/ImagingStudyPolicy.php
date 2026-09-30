<?php

namespace App\Domains\Imaging\Policies;

use App\Domains\Imaging\Models\ImagingStudy;
use App\Models\User;
use App\Support\ResponsibleClinicalStaffScoping;

class ImagingStudyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('imaging.access');
    }

    public function view(User $user, ImagingStudy $study): bool
    {
        return $user->can('imaging.access')
            && ResponsibleClinicalStaffScoping::userMayAccessImagingStudyInPanel($user, $study);
    }

    public function create(User $user): bool
    {
        return $user->can('imaging.access');
    }

    public function update(User $user, ImagingStudy $study): bool
    {
        if (! $user->can('imaging.access')
            || ! ResponsibleClinicalStaffScoping::userMayAccessImagingStudyInPanel($user, $study)) {
            return false;
        }

        // Estudio finalizado: solo administración puede corregir datos desde el panel.
        if ($study->status === 'completado') {
            return $user->hasRole('Administrador');
        }

        return true;
    }

    public function approve(User $user, ImagingStudy $study): bool
    {
        return $user->can('imaging.access')
            && ResponsibleClinicalStaffScoping::userMayAccessImagingStudyInPanel($user, $study)
            && $user->can('imaging.approve');
    }

    /**
     * programado → paciente_presente. Recepcionista (imaging.access) o tecnólogo (imaging.approve).
     */
    public function registrarLlegada(User $user, ImagingStudy $study): bool
    {
        if (! ResponsibleClinicalStaffScoping::userMayAccessImagingStudyInPanel($user, $study)) {
            return false;
        }
        if ($study->status !== 'programado') {
            return false;
        }
        if ($user->hasRole('Recepcionista')) {
            return $user->can('imaging.access');
        }

        return $user->can('imaging.approve')
            && $user->hasRole('Tecnólogo de Imagen');
    }

    /**
     * En programado: recepcionista o tecnólogo con imaging.access.
     * Después (paciente_presente / en_proceso): solo tecnólogo con imaging.approve.
     */
    public function cancelarEstudio(User $user, ImagingStudy $study): bool
    {
        if (! ResponsibleClinicalStaffScoping::userMayAccessImagingStudyInPanel($user, $study)) {
            return false;
        }
        if (in_array($study->status, ['completado', 'cancelado'], true)) {
            return false;
        }
        if ($study->status === 'programado') {
            return $user->can('imaging.access')
                && $user->hasAnyRole(['Recepcionista', 'Tecnólogo de Imagen']);
        }

        return $user->hasRole('Tecnólogo de Imagen')
            && $user->can('imaging.approve');
    }

    /**
     * paciente_presente → en_proceso. Solo tecnólogo de imagen.
     */
    public function iniciarEstudio(User $user, ImagingStudy $study): bool
    {
        if ($study->status !== 'paciente_presente') {
            return false;
        }

        return $this->puedeOperarFlujoTecnologo($user, $study);
    }

    /**
     * en_proceso → completado. Solo tecnólogo de imagen.
     */
    public function completarEstudio(User $user, ImagingStudy $study): bool
    {
        if ($study->status !== 'en_proceso') {
            return false;
        }

        return $this->puedeOperarFlujoTecnologo($user, $study);
    }

    private function puedeOperarFlujoTecnologo(User $user, ImagingStudy $study): bool
    {
        return $user->can('imaging.approve')
            && ResponsibleClinicalStaffScoping::userMayAccessImagingStudyInPanel($user, $study)
            && $user->hasRole('Tecnólogo de Imagen');
    }

    public function delete(User $user, ImagingStudy $study): bool
    {
        return $user->hasRole('Administrador')
            && $user->can('imaging.access')
            && ResponsibleClinicalStaffScoping::userMayAccessImagingStudyInPanel($user, $study);
    }

    public function restore(User $user, ImagingStudy $study): bool
    {
        return false;
    }

    public function forceDelete(User $user, ImagingStudy $study): bool
    {
        return $user->hasRole('Administrador')
            && $user->can('imaging.access')
            && ResponsibleClinicalStaffScoping::userMayAccessImagingStudyInPanel($user, $study);
    }
}
