<?php

namespace App\Actions\Clientes;

use App\Models\Cliente;
use App\Support\Clientes\ClienteEnUsoException;
use App\Support\Clientes\ClienteUsage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a client nothing references. Its contacts go with it.
 *
 * Checked twice, exactly as the catalog's delete is. The registry answers
 * instantly and lets the screen offer archiving instead, but it is derived from
 * references declared in code; the foreign key is what actually guarantees
 * history survives.
 */
class DeleteCliente
{
    /**
     * Postgres raises `restrict_violation` (23001) for an ON DELETE RESTRICT
     * constraint and `foreign_key_violation` (23503) for NO ACTION and deferred
     * checks. Both mean the same thing here.
     *
     * @var list<string>
     */
    private const REFERENCE_VIOLATIONS = ['23001', '23503'];

    public function __invoke(Cliente $cliente): void
    {
        if (ClienteUsage::isInUse($cliente->id)) {
            throw ClienteEnUsoException::for($cliente);
        }

        try {
            // Its own savepoint: a failed statement aborts the surrounding
            // Postgres transaction, so the refusal must not poison the caller's.
            DB::transaction(fn () => $cliente->delete());
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), self::REFERENCE_VIOLATIONS, true)) {
                throw ClienteEnUsoException::for($cliente);
            }

            throw $exception;
        }
    }
}
