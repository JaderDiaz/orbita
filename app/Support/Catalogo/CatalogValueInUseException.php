<?php

namespace App\Support\Catalogo;

use App\Models\CatalogValue;
use RuntimeException;

/**
 * A catalog value cannot be deleted because records reference it.
 *
 * History keeps its value: deactivating takes it out of the pickers while every
 * existing record goes on reading the same row.
 */
class CatalogValueInUseException extends RuntimeException
{
    public static function for(CatalogValue $value): self
    {
        return new self(sprintf('Catalog value [%s] is referenced by existing records.', $value->name));
    }
}
