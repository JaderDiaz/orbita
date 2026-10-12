<?php

namespace App\Policies;

use App\Models\CatalogValue;
use App\Models\User;

class CatalogValuePolicy
{
    /**
     * Abilities no role may be granted blindly, the owner included.
     *
     * AppServiceProvider defers these to this policy instead of letting
     * Gate::before hand them to the owner, because a value the code branches on
     * must survive every role. Without the deferral the owner could retire
     * `Pagada` and leave a financial path with nothing to match.
     *
     * @var list<string>
     */
    public const SYSTEM_PROTECTED_ABILITIES = ['deactivate'];

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * A system value can be renamed, recoloured and described freely. Only its
     * retirement and deletion are blocked.
     */
    public function update(User $user, CatalogValue $value): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, CatalogValue $value): bool
    {
        return $user->isAdmin() && ! $value->is_system;
    }

    public function deactivate(User $user, CatalogValue $value): bool
    {
        return $user->isAdmin() && ! $value->is_system;
    }

    public function setDefault(User $user, CatalogValue $value): bool
    {
        return $user->isAdmin() && $value->is_active;
    }

    public function move(User $user, CatalogValue $value): bool
    {
        return $user->isAdmin();
    }
}
