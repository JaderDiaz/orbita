<?php

namespace App\Support\Clientes;

use Illuminate\Support\Facades\DB;

/**
 * How many records reference each cliente.
 *
 * Same shape as the catalog's UsageRegistry, and for the same reason: the
 * screen has to decide whether to offer deletion or archiving, and a stored
 * counter would go stale exactly when it matters.
 *
 * Empty until Proyectos, Cotizaciones, Facturas and Documentos exist. That is
 * correct rather than a placeholder: nothing references a cliente yet, so every
 * one of them genuinely has zero references, and the registry answers without
 * touching the database at all.
 *
 * Contacts are deliberately absent. They are part of the client and go with it,
 * so they never block a deletion.
 */
class ClienteUsage
{
    /**
     * @var array<int, int>|null
     */
    private static ?array $memo = null;

    /**
     * Transactional columns that reference `clientes.id`.
     *
     * @return list<array{table: string, column: string}>
     */
    public static function references(): array
    {
        return [];
    }

    /**
     * @return array<int, int>
     */
    public static function counts(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        $references = static::references();

        if ($references === []) {
            return self::$memo = [];
        }

        $selects = array_map(
            fn (array $reference): string => sprintf(
                'SELECT %s AS cliente_id FROM %s WHERE %s IS NOT NULL',
                self::identifier($reference['column']),
                self::identifier($reference['table']),
                self::identifier($reference['column']),
            ),
            $references,
        );

        $sql = 'SELECT cliente_id, count(*) AS total FROM ('
            .implode(' UNION ALL ', $selects)
            .') refs GROUP BY cliente_id';

        /** @var list<object{cliente_id: int, total: int}> $rows */
        $rows = DB::select($sql);

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row->cliente_id] = (int) $row->total;
        }

        return self::$memo = $counts;
    }

    public static function countFor(int $clienteId): int
    {
        return self::counts()[$clienteId] ?? 0;
    }

    public static function isInUse(int $clienteId): bool
    {
        return self::countFor($clienteId) > 0;
    }

    public static function flush(): void
    {
        self::$memo = null;
    }

    /**
     * Quotes an identifier this class owns. These never come from user input:
     * they are literals in `references()`.
     */
    private static function identifier(string $name): string
    {
        return '"'.str_replace('"', '', $name).'"';
    }
}
