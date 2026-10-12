<?php

namespace App\Enums;

/**
 * The closed palette a catalog value's badge may use.
 *
 * The prototype offered two free colour inputs (`Fondo` and `Texto`), which
 * makes white-on-white reachable. Storing the palette key instead gives a
 * deterministic background/foreground pair and keeps contrast guaranteed.
 * Its seed only ever used these eight entries.
 */
enum CatalogColor: string
{
    case Gris = 'gris';
    case Azul = 'azul';
    case Verde = 'verde';
    case Naranja = 'naranja';
    case Rojo = 'rojo';
    case Morado = 'morado';
    case Turquesa = 'turquesa';
    case Indigo = 'indigo';

    /**
     * Badge background, taken literally from the prototype's `msPal`.
     */
    public function background(): string
    {
        return match ($this) {
            self::Gris => '#f0f0f2',
            self::Azul => '#e8f1ff',
            self::Verde => '#e8f7ee',
            self::Naranja => '#fff3e0',
            self::Rojo => '#ffeceb',
            self::Morado => '#f5edfd',
            self::Turquesa => '#e3f6f9',
            self::Indigo => '#eef0ff',
        };
    }

    /**
     * Badge foreground, taken literally from the prototype's `msPal`.
     */
    public function foreground(): string
    {
        return match ($this) {
            self::Gris => '#48484a',
            self::Azul => '#0a64d6',
            self::Verde => '#1a7f37',
            self::Naranja => '#b25000',
            self::Rojo => '#d70015',
            self::Morado => '#8e3fcf',
            self::Turquesa => '#0f7c8c',
            self::Indigo => '#4b44c9',
        };
    }

    /**
     * Spanish label for the swatch tooltip.
     */
    public function label(): string
    {
        return match ($this) {
            self::Gris => 'Gris',
            self::Azul => 'Azul',
            self::Verde => 'Verde',
            self::Naranja => 'Naranja',
            self::Rojo => 'Rojo',
            self::Morado => 'Morado',
            self::Turquesa => 'Turquesa',
            self::Indigo => 'Índigo',
        };
    }

    /**
     * Shared by validation and by the database check constraint.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $color): string => $color->value, self::cases());
    }
}
