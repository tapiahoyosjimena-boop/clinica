<?php

namespace App\Domains\Imaging\Policies;

use App\Domains\Imaging\Models\ImagingEquipment;
use App\Models\User;

class ImagingEquipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('imaging.access');
    }

    public function view(User $user, ImagingEquipment $equipment): bool
    {
        return $user->can('imaging.access');
    }

    public function create(User $user): bool
    {
        return $user->can('imaging.access');
    }

    public function update(User $user, ImagingEquipment $equipment): bool
    {
        return $user->can('imaging.access');
    }

    public function delete(User $user, ImagingEquipment $equipment): bool
    {
        return $this->soloAdminConAccesoImagen($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->soloAdminConAccesoImagen($user);
    }

    public function restore(User $user, ImagingEquipment $equipment): bool
    {
        return $this->soloAdminConAccesoImagen($user);
    }

    public function restoreAny(User $user): bool
    {
        return $this->soloAdminConAccesoImagen($user);
    }

    public function forceDelete(User $user, ImagingEquipment $equipment): bool
    {
        return $this->soloAdminConAccesoImagen($user);
    }

    public function forceDeleteAny(User $user): bool
    {
        return $this->soloAdminConAccesoImagen($user);
    }

    private function soloAdminConAccesoImagen(User $user): bool
    {
        return $user->hasRole('Administrador')
            && $user->can('imaging.access');
    }
}
