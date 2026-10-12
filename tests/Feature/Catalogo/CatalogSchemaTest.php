<?php

namespace Tests\Feature\Catalogo;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The catalog is referenced by every transactional table in the product, so its
 * invariants live in Postgres and not only in PHP.
 *
 * These assertions insert RAW rather than through Eloquent on purpose: an enum
 * cast would throw while building the query and the constraint would never be
 * exercised, which is exactly how an untested check constraint rots.
 */
class CatalogSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_value_name_is_unique_per_list_regardless_of_case(): void
    {
        $list = $this->makeList('estados');
        $this->makeValue($list, ['name' => 'Aprobada']);

        $this->expectException(QueryException::class);

        $this->makeValue($list, ['name' => 'APROBADA']);
    }

    public function test_the_same_name_is_allowed_in_a_different_list(): void
    {
        $this->makeValue($this->makeList('estados-cotizacion'), ['name' => 'Aprobada']);
        $this->makeValue($this->makeList('estados-fase'), ['name' => 'Aprobada']);

        $this->assertSame(2, DB::table('catalog_values')->count());
    }

    public function test_a_value_code_is_unique_per_list_regardless_of_case(): void
    {
        $list = $this->makeList('tipos-documento');
        $this->makeValue($list, ['name' => 'NIT', 'code' => 'NIT']);

        $this->expectException(QueryException::class);

        $this->makeValue($list, ['name' => 'Otro', 'code' => 'nit']);
    }

    public function test_a_null_code_does_not_collide(): void
    {
        $list = $this->makeList('etiquetas');
        $this->makeValue($list, ['name' => 'Firmado']);
        $this->makeValue($list, ['name' => 'Vigente']);

        $this->assertSame(2, DB::table('catalog_values')->count());
    }

    public function test_a_system_key_is_unique_per_list(): void
    {
        $list = $this->makeList('estados-factura');
        $this->makeValue($list, ['name' => 'Pagada', 'system_key' => 'pagada', 'is_system' => true]);

        $this->expectException(QueryException::class);

        $this->makeValue($list, ['name' => 'Cancelada', 'system_key' => 'pagada', 'is_system' => true]);
    }

    public function test_a_system_key_is_case_insensitive(): void
    {
        $list = $this->makeList('estados-factura');
        $this->makeValue($list, ['name' => 'Pagada', 'system_key' => 'pagada', 'is_system' => true]);

        $this->expectException(QueryException::class);

        $this->makeValue($list, ['name' => 'Cancelada', 'system_key' => 'PAGADA', 'is_system' => true]);
    }

    public function test_a_list_cannot_hold_two_defaults(): void
    {
        $list = $this->makeList('prioridades');
        $this->makeValue($list, ['name' => 'Media', 'is_default' => true]);

        $this->expectException(QueryException::class);

        $this->makeValue($list, ['name' => 'Alta', 'is_default' => true]);
    }

    public function test_an_unknown_color_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $this->makeValue($this->makeList('estados'), ['name' => 'Aprobada', 'color' => 'fucsia']);
    }

    public function test_an_unknown_icon_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $this->makeValue($this->makeList('tipos-tarea'), ['name' => 'Bug', 'icon' => 'unicorn']);
    }

    public function test_a_system_value_must_carry_its_system_key(): void
    {
        $this->expectException(QueryException::class);

        $this->makeValue($this->makeList('estados'), ['name' => 'Aprobada', 'is_system' => true]);
    }

    public function test_a_system_value_cannot_be_deactivated(): void
    {
        $this->expectException(QueryException::class);

        $this->makeValue($this->makeList('estados'), [
            'name' => 'Aprobada',
            'system_key' => 'aprobada',
            'is_system' => true,
            'is_active' => false,
        ]);
    }

    public function test_a_default_value_cannot_be_inactive(): void
    {
        $this->expectException(QueryException::class);

        $this->makeValue($this->makeList('prioridades'), [
            'name' => 'Media',
            'is_default' => true,
            'is_active' => false,
        ]);
    }

    public function test_a_list_holding_values_cannot_be_deleted(): void
    {
        $list = $this->makeList('estados');
        $this->makeValue($list, ['name' => 'Aprobada']);

        $this->expectException(QueryException::class);

        DB::table('catalog_lists')->where('id', $list)->delete();
    }

    public function test_a_list_key_is_unique_regardless_of_case(): void
    {
        $this->makeList('estados');

        $this->expectException(QueryException::class);

        $this->makeList('ESTADOS');
    }

    private function makeList(string $key): int
    {
        return (int) DB::table('catalog_lists')->insertGetId([
            'key' => $key,
            'group' => 'Pruebas',
            'title' => 'Lista de prueba',
            'flags' => '[]',
            'fields' => '[]',
            'used_in' => '[]',
            'allows_user_values' => true,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeValue(int $listId, array $attributes): void
    {
        DB::table('catalog_values')->insert([
            'list_id' => $listId,
            'flags' => '{}',
            'created_at' => now(),
            'updated_at' => now(),
            ...$attributes,
        ]);
    }
}
