<?php

namespace App\Actions\Catalogo;

use App\Models\CatalogValue;
use App\Support\Catalogo\CatalogValueInUseException;
use App\Support\Catalogo\UsageRegistry;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Deletes a value that nothing references.
 *
 * Checked twice on purpose. The registry answers instantly and lets the screen
 * offer deactivation instead, but it is derived from the references declared in
 * code, so a module that forgot to declare itself would not be counted. The
 * foreign key is what actually guarantees history survives, and a violation is
 * translated back into the same refusal rather than surfacing as a 500.
 */
class DeleteCatalogValue
{
    /**
     * Postgres raises `restrict_violation` (23001) for an ON DELETE RESTRICT
     * constraint and `foreign_key_violation` (23503) for NO ACTION and for
     * deferred checks. Both mean the same thing here, and assuming only 23503
     * let the refusal surface as a 500 against a real database.
     *
     * @var list<string>
     */
    private const REFERENCE_VIOLATIONS = ['23001', '23503'];

    public function __invoke(CatalogValue $value): void
    {
        if (UsageRegistry::isInUse($value->id)) {
            throw CatalogValueInUseException::for($value);
        }

        try {
            // Wrapped so the rejection cannot poison a surrounding
            // transaction. Postgres aborts the whole transaction on a failed
            // statement, and Laravel turns a nested transaction into a
            // savepoint it can roll back to, leaving the caller's usable.
            DB::transaction(fn () => $value->delete());
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), self::REFERENCE_VIOLATIONS, true)) {
                throw CatalogValueInUseException::for($value);
            }

            throw $exception;
        }
    }
}
