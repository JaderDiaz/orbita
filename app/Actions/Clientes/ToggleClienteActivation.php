<?php

namespace App\Actions\Clientes;

use App\Models\Cliente;

/**
 * Archives a client or brings it back.
 *
 * Archiving is not a soft delete: the client keeps every record that points at
 * it and simply stops appearing in the active lists, which is what accounting
 * traceability requires.
 */
class ToggleClienteActivation
{
    public function __invoke(Cliente $cliente): void
    {
        $cliente->update(['is_active' => ! $cliente->is_active]);
    }
}
