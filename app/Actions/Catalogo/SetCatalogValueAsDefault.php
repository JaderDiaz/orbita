<?php

namespace App\Actions\Catalogo;

use App\Models\CatalogValue;
use Illuminate\Support\Facades\DB;

/**
 * Makes one value the list's default, exclusively.
 *
 * Two rows change, and a partial unique index rejects a second default, so the
 * clear and the set belong in one transaction.
 */
class SetCatalogValueAsDefault
{
    public function __invoke(CatalogValue $value): void
    {
        DB::transaction(function () use ($value): void {
            $value->list->values()
                ->where('is_default', true)
                ->whereKeyNot($value->getKey())
                ->update(['is_default' => false]);

            $value->update(['is_default' => true]);
        });
    }
}
