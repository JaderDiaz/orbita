<?php

namespace App\Policies;

use App\Models\CatalogList;
use App\Models\User;

/**
 * Maestros y Listas is administrative configuration.
 *
 * These rows decide tax treatment and project health, so a Miembro del equipo
 * never reaches them — not the screen, and not the writes.
 */
class CatalogListPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, CatalogList $list): bool
    {
        return $user->isAdmin();
    }
}
