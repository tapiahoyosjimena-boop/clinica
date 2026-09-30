<?php

namespace App\Domains\Reactivos\Policies;

use App\Domains\Reactivos\Models\Provider;
use App\Models\User;

class ProviderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('reactivos.access');
    }

    public function view(User $user, Provider $provider): bool
    {
        return $user->can('reactivos.access');
    }

    public function create(User $user): bool
    {
        return $user->can('reactivos.access');
    }

    public function update(User $user, Provider $provider): bool
    {
        return $user->can('reactivos.access');
    }

    public function delete(User $user, Provider $provider): bool
    {
        return $user->can('reactivos.access');
    }

    public function restore(User $user, Provider $provider): bool
    {
        return false;
    }

    public function forceDelete(User $user, Provider $provider): bool
    {
        return false;
    }
}
