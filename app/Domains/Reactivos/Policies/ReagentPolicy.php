<?php

namespace App\Domains\Reactivos\Policies;

use App\Domains\Reactivos\Models\Reagent;
use App\Models\User;

class ReagentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reactivos.access');
    }

    public function view(User $user, Reagent $reagent): bool
    {
        return $user->can('reactivos.access');
    }

    public function create(User $user): bool
    {
        return $user->can('reactivos.access');
    }

    public function update(User $user, Reagent $reagent): bool
    {
        return $user->can('reactivos.access');
    }

    public function delete(User $user, Reagent $reagent): bool
    {
        return $user->can('reactivos.access');
    }

    public function restore(User $user, Reagent $reagent): bool
    {
        return $user->can('reactivos.access');
    }

    public function forceDelete(User $user, Reagent $reagent): bool
    {
        return false;
    }
}
