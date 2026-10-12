<?php

namespace App\Support\Catalogo;

use RuntimeException;

/**
 * A code-owned catalog value is missing from the database.
 *
 * This fails loudly on purpose. Returning false instead would let a financial
 * branch silently take the wrong path on a broken or partially seeded install,
 * which is the failure mode the catalog's system keys exist to prevent.
 */
class UnknownSystemKeyException extends RuntimeException
{
    public static function for(SystemKey $key): self
    {
        return new self(sprintf(
            'Catalog value [%s] is not present in list [%s]. Run the catalog seeder.',
            $key->systemKey(),
            $key->listKey(),
        ));
    }
}
