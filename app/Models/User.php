<?php

namespace App\Models;

use App\Enums\SystemRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property SystemRole $system_role
 * @property bool $is_owner
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'system_role', 'is_owner'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'system_role' => SystemRole::class,
            'is_owner' => 'boolean',
        ];
    }

    /**
     * The single protected owner of the installation.
     */
    public function isOwner(): bool
    {
        return $this->system_role === SystemRole::Owner;
    }

    /**
     * Full access: finanzas, configuración, facturación, equipo.
     */
    public function isAdmin(): bool
    {
        return $this->system_role->isAdministrative();
    }

    /**
     * Holds the Miembro del equipo role specifically.
     */
    public function isMember(): bool
    {
        return $this->system_role === SystemRole::Member;
    }

    /**
     * External client, restricted to the portal.
     */
    public function isClient(): bool
    {
        return $this->system_role === SystemRole::Client;
    }

    /**
     * Can be assigned to projects and log time. An Administrador behaves as a
     * Miembro too, so this is wider than isMember().
     */
    public function actsAsMember(): bool
    {
        return $this->system_role->actsAsMember();
    }
}
