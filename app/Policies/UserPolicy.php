<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Abilities the owner must never be subject to, not even by itself.
     *
     * AppServiceProvider defers these to this policy instead of letting
     * Gate::before grant them, so the owner cannot lock itself out.
     *
     * @var list<string>
     */
    public const OWNER_PROTECTED_ABILITIES = ['delete', 'block', 'changeRole'];

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $target): bool
    {
        return $user->isAdmin() || $user->is($target);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $target): bool
    {
        return $user->isAdmin() || $user->is($target);
    }

    /**
     * Blocking preserves history; it never deletes. The owner cannot be
     * blocked by anyone, including itself.
     */
    public function block(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $target->isOwner();
    }

    public function changeRole(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $target->isOwner();
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isAdmin() && ! $target->isOwner() && ! $user->is($target);
    }
}
