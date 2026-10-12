<?php

namespace Tests\Feature\Catalogo;

use App\Enums\Catalogo\EstadoFactura;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Support\Catalogo\Catalogo;
use App\Support\Catalogo\UnknownSystemKeyException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Behaviour keys off `system_key`, never off the display name, because the
 * agency renames catalog values freely and a rename must not change logic.
 */
class CatalogoResolverTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Catalogo::flush();
    }

    public function test_it_resolves_a_code_owned_value(): void
    {
        $value = $this->seedPagada();

        $this->assertSame($value->id, Catalogo::idFor(EstadoFactura::Pagada));
        $this->assertTrue(Catalogo::has(EstadoFactura::Pagada));
    }

    public function test_a_renamed_value_still_resolves(): void
    {
        $value = $this->seedPagada();

        $value->update(['name' => 'Cancelada por el cliente']);

        $this->assertTrue($value->fresh()->matches(EstadoFactura::Pagada));
    }

    public function test_an_unseeded_key_throws_instead_of_returning_false(): void
    {
        $this->assertFalse(Catalogo::has(EstadoFactura::Pagada));

        $this->expectException(UnknownSystemKeyException::class);

        Catalogo::idFor(EstadoFactura::Pagada);
    }

    public function test_a_value_of_another_list_does_not_resolve(): void
    {
        CatalogValue::factory()
            ->for(CatalogList::factory()->key('estados-cotizacion'), 'list')
            ->system('pagada')
            ->create(['name' => 'Pagada']);

        $this->expectException(UnknownSystemKeyException::class);

        Catalogo::idFor(EstadoFactura::Pagada);
    }

    public function test_a_value_without_a_system_key_is_never_mistaken_for_one(): void
    {
        $this->seedPagada();

        $other = CatalogValue::factory()
            ->for(CatalogList::factory()->key('estados-factura-2'), 'list')
            ->create(['name' => 'Pendiente']);

        $this->assertFalse($other->matches(EstadoFactura::Pagada));
    }

    public function test_the_index_is_cached_and_flushed_when_the_catalog_changes(): void
    {
        $value = $this->seedPagada();

        Catalogo::index();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        Catalogo::idFor(EstadoFactura::Pagada);
        $this->assertSame(0, $queries, 'Resolver un system_key con cache caliente no deberia consultar la base.');

        $value->update(['name' => 'Pagada total']);

        $this->assertSame($value->id, Catalogo::idFor(EstadoFactura::Pagada));
    }

    public function test_a_flag_reads_from_the_value(): void
    {
        $value = CatalogValue::factory()
            ->for(CatalogList::factory()->withFlag('saldo_en_cero', 'Saldo en cero'), 'list')
            ->flags(['saldo_en_cero' => true])
            ->create(['name' => 'Pagada']);

        $this->assertTrue($value->flag('saldo_en_cero'));
        $this->assertFalse($value->flag('estado_final'));
    }

    private function seedPagada(): CatalogValue
    {
        return CatalogValue::factory()
            ->for(CatalogList::factory()->key('estados-factura'), 'list')
            ->system('pagada')
            ->create(['name' => 'Pagada']);
    }
}
