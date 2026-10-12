<?php

namespace App\Support\Clientes;

use App\Models\Cliente;
use RuntimeException;

/**
 * A client cannot be deleted because records point at it.
 *
 * Archiving is what the screen offers instead: the client leaves the active
 * lists and every quote, project and invoice goes on reading the same row,
 * which is what accounting traceability requires.
 */
class ClienteEnUsoException extends RuntimeException
{
    public static function for(Cliente $cliente): self
    {
        return new self(sprintf('Cliente [%s] is referenced by existing records.', $cliente->nombre));
    }
}
