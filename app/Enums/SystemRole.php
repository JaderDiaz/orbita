<?php

namespace App\Enums;

/**
 * The system roles that govern authorization.
 *
 * These are code, not master data. The "Roles del equipo" catalog list
 * (Frontend, Backend, QA, Project manager…) holds JOB TITLES and carries no
 * authorization meaning — conflating the two is how a fourth role ends up
 * bypassing the money boundary.
 */
enum SystemRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';
    case Client = 'client';

    /**
     * Spanish label for the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Propietario',
            self::Admin => 'Administrador',
            self::Member => 'Miembro del equipo',
            self::Client => 'Cliente',
        };
    }

    /**
     * Internal staff, as opposed to an external client.
     */
    public function isStaff(): bool
    {
        return match ($this) {
            self::Owner, self::Admin, self::Member => true,
            self::Client => false,
        };
    }

    /**
     * Full access to finanzas, configuración, facturación and equipo.
     */
    public function isAdministrative(): bool
    {
        return match ($this) {
            self::Owner, self::Admin => true,
            self::Member, self::Client => false,
        };
    }

    /**
     * Can be assigned to projects and log time. An Administrador behaves as a
     * Miembro too, which is an explicit rule of the product.
     */
    public function actsAsMember(): bool
    {
        return $this->isStaff();
    }

    /**
     * Every value, for validation rules and database check constraints.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }
}
