<?php

namespace App\Domains\Catalog\Policies;

use App\Domains\Catalog\Models\ExamParameter;
use App\Models\User;

class ExamParameterPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('catalog.access');
    }

    public function view(User $user, ExamParameter $examParameter): bool
    {
        return $this->allowsCatalogAccessForParameter($user, $examParameter);
    }

    public function create(User $user): bool
    {
        return $user->can('catalog.access');
    }

    public function update(User $user, ExamParameter $examParameter): bool
    {
        return $this->allowsCatalogAccessForParameter($user, $examParameter);
    }

    public function delete(User $user, ExamParameter $examParameter): bool
    {
        return $this->allowsCatalogAccessForParameter($user, $examParameter);
    }

    public function restore(User $user, ExamParameter $examParameter): bool
    {
        return $this->allowsCatalogAccessForParameter($user, $examParameter);
    }

    protected function allowsCatalogAccessForParameter(User $user, ExamParameter $examParameter): bool
    {
        if (! $user->can('catalog.access')) {
            return false;
        }

        $examParameter->loadMissing('category');

        return $examParameter->category?->supportsAnalyticalParameters() ?? false;
    }

    public function forceDelete(User $user, ExamParameter $examParameter): bool
    {
        return $user->hasRole('Administrador');
    }
}
