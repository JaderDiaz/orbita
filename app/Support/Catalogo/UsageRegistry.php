<?php

namespace App\Support\Catalogo;

use Illuminate\Support\Facades\DB;

/**
 * How many records use each catalog value.
 *
 * The prototype stored this as a seeded `uso` number that never updated, so a
 * value created today and used in fifty records still reported "Sin uso" and
 * stayed freely deletable. The count here is derived from the real references
 * instead: one `UNION ALL` over the columns declared below, grouped once.
 *
 * No triggers and no counter columns, per the catalog standard. Adding a module
 * means adding one line to `references()`.
 *
 * The count is memoised per request rather than cached across requests on
 * purpose. It changes on every transactional write, and a stale count is a
 * count that invites deleting a value that is in use. The authoritative guard
 * is the `on delete restrict` foreign key in Postgres; this number only drives
 * what the screen shows and whether it offers to delete or to deactivate.
 */
class UsageRegistry
{
    /**
     * @var array<int, int>|null
     */
    private static ?array $memo = null;

    /**
     * Transactional columns that reference `catalog_values.id`.
     *
     * Adding a module means adding a line here. The count it produces is what
     * tells the Maestros screen whether to offer deleting a value or only
     * deactivating it; the `on delete restrict` foreign key is what actually
     * enforces the answer.
     *
     * @return list<array{table: string, column: string}>
     */
    public static function references(): array
    {
        return [
            ['table' => 'clientes', 'column' => 'tipo_documento_value_id'],
            ['table' => 'clientes', 'column' => 'regimen_value_id'],
        ];
    }

    /**
     * Usage per value id, in one query. Values with no uses are absent.
     *
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
                'SELECT %s AS value_id FROM %s WHERE %s IS NOT NULL',
                self::identifier($reference['column']),
                self::identifier($reference['table']),
                self::identifier($reference['column']),
            ),
            $references,
        );

        $sql = 'SELECT value_id, count(*) AS total FROM ('
            .implode(' UNION ALL ', $selects)
            .') refs GROUP BY value_id';

        /** @var list<object{value_id: int, total: int}> $rows */
        $rows = DB::select($sql);

        $counts = [];

        foreach ($rows as $row) {
            $counts[(int) $row->value_id] = (int) $row->total;
        }

        return self::$memo = $counts;
    }

    public static function countFor(int $valueId): int
    {
        return self::counts()[$valueId] ?? 0;
    }

    public static function isInUse(int $valueId): bool
    {
        return self::countFor($valueId) > 0;
    }

    /**
     * Drops the per-request memo. Needed after a transactional write in the
     * same request, and between tests.
     */
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
