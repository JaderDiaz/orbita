<?php

namespace App\Actions\Catalogo;

use App\Models\CatalogValue;

/**
 * Retires a value or brings it back.
 *
 * Deactivating also clears the default, because a default nobody can pick is a
 * form that silently preselects nothing. The database enforces the same rule.
 */
class ToggleCatalogValueActivation
{
    public function __invoke(CatalogValue $value): void
    {
        $activating = ! $value->is_active;

        $value->update([
            'is_active' => $activating,
            'is_default' => $activating ? $value->is_default : false,
        ]);
    }
}
