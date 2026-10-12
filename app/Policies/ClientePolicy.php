<?php

namespace App\Policies;

use App\Models\Cliente;
use App\Models\User;

/**
 * Clientes is commercial information: a Miembro del equipo never reaches it,
 * not the screens and not the writes. The nav already omits the section for
 * that role; this is the enforcement behind it.
 */
class ClientePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Cliente $cliente): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Cliente $cliente): bool
    {
        return $user->isAdmin();
    }

    /**
     * Archiving takes a client out of the active lists and keeps every record
     * that points at it, so it is always available.
     */
    public function archive(User $user, Cliente $cliente): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Cliente $cliente): bool
    {
        return $user->isAdmin();
    }
}
