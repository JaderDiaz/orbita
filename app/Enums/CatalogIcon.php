<?php

namespace App\Enums;

/**
 * The closed set of icons a catalog value may carry.
 *
 * The frontend maps each key to a Lucide component. Keeping the set closed and
 * enforced in the database means a stored key always has a glyph: an unknown
 * key would render nothing at all, which is indistinguishable from a bug.
 */
enum CatalogIcon: string
{
    case Sparkle = 'sparkle';
    case Bug = 'bug';
    case Up = 'up';
    case Down = 'down';
    case Minus = 'minus';
    case Flag = 'flag';
    case File = 'file';
    case Pen = 'pen';
    case Check = 'check';
    case Lock = 'lock';
    case Box = 'box';
    case Card = 'card';
    case Bank = 'bank';
    case Cash = 'cash';
    case Tag = 'tag';
    case Clock = 'clock';

    /**
     * Spanish label for the picker tooltip.
     */
    public function label(): string
    {
        return match ($this) {
            self::Sparkle => 'Destello',
            self::Bug => 'Error',
            self::Up => 'Arriba',
            self::Down => 'Abajo',
            self::Minus => 'Medio',
            self::Flag => 'Bandera',
            self::File => 'Archivo',
            self::Pen => 'Lápiz',
            self::Check => 'Visto',
            self::Lock => 'Candado',
            self::Box => 'Caja',
            self::Card => 'Tarjeta',
            self::Bank => 'Banco',
            self::Cash => 'Efectivo',
            self::Tag => 'Etiqueta',
            self::Clock => 'Reloj',
        };
    }

    /**
     * Shared by validation and by the database check constraint.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $icon): string => $icon->value, self::cases());
    }
}
