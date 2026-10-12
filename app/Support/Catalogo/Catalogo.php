<?php

namespace App\Support\Catalogo;

use App\Models\CatalogValue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Resolves the catalog values the code owns, without a query per check.
 *
 * The standard forbids comparing a catalog value against `name` with a string
 * literal, because the agency renames values freely. Behaviour therefore keys
 * off `system_key`, and this resolver turns a key into the row id once per
 * request so `CatalogValue::matches()` costs nothing.
 *
 * The index is small and changes only when the catalog is edited, so it is
 * cached forever and flushed by the models on save and delete.
 */
class Catalogo
{
    public const CACHE_KEY = 'catalogo:system-keys';

    /**
     * Resolve the row id a code-owned key points at.
     *
     * @throws UnknownSystemKeyException when the value is not seeded
     */
    public static function idFor(SystemKey $key): int
    {
        $index = self::index();
        $slot = self::slot($key);

        return $index[$slot] ?? throw UnknownSystemKeyException::for($key);
    }

    /**
     * Whether a code-owned key resolves at all. For callers that must tolerate
     * a catalog still being seeded, such as a health check.
     */
    public static function has(SystemKey $key): bool
    {
        return isset(self::index()[self::slot($key)]);
    }

    public static function valueFor(SystemKey $key): CatalogValue
    {
        return CatalogValue::query()->findOrFail(self::idFor($key));
    }

    /**
     * Every code-owned key in the catalog, as `list-key:system-key` => row id.
     *
     * @return array<string, int>
     */
    public static function index(): array
    {
        /** @var array<string, int> */
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            /** @var list<object{list_key: string, system_key: string, id: int}> $rows */
            $rows = DB::table('catalog_values')
                ->join('catalog_lists', 'catalog_lists.id', '=', 'catalog_values.list_id')
                ->whereNotNull('catalog_values.system_key')
                ->get([
                    'catalog_values.id',
                    'catalog_values.system_key',
                    'catalog_lists.key as list_key',
                ])
                ->all();

            $index = [];

            foreach ($rows as $row) {
                $index[strtolower($row->list_key.':'.$row->system_key)] = (int) $row->id;
            }

            return $index;
        });
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private static function slot(SystemKey $key): string
    {
        return strtolower($key->listKey().':'.$key->systemKey());
    }
}
