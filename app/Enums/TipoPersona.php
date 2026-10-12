<?php

namespace App\Enums;

/**
 * Whether a cliente is a company or an individual.
 *
 * This is code, not master data: it decides which document type and régimen the
 * form preselects and which label the name field carries, so a fourth value
 * would have no branch to fall into.
 */
enum TipoPersona: string
{
    case Juridica = 'juridica';
    case Natural = 'natural';

    /**
     * Spanish label for the UI.
     */
    public function label(): string
    {
        return match ($this) {
            self::Juridica => 'Jurídica',
            self::Natural => 'Natural',
        };
    }

    /**
     * The prototype relabels the name field: a company has a razón social, a
     * person has a full name.
     */
    public function nameLabel(): string
    {
        return match ($this) {
            self::Juridica => 'Razón social',
            self::Natural => 'Nombre completo',
        };
    }

    /**
     * Placeholder for the document field, taken from the prototype.
     */
    public function documentPlaceholder(): string
    {
        return match ($this) {
            self::Juridica => '900.000.000-0',
            self::Natural => '1.000.000.000',
        };
    }

    /**
     * Only a NIT carries a verification digit.
     */
    public function expectsVerificationDigit(): bool
    {
        return match ($this) {
            self::Juridica => true,
            self::Natural => false,
        };
    }

    /**
     * Shared by validation and by the database check constraint.
     *
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $tipo): string => $tipo->value, self::cases());
    }
}
