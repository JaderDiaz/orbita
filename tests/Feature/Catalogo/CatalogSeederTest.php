<?php

namespace Tests\Feature\Catalogo;

use App\Enums\Catalogo\ColumnaTablero;
use App\Enums\Catalogo\EstadoCotizacion;
use App\Enums\Catalogo\EstadoFactura;
use App\Enums\Catalogo\EstadoFase;
use App\Enums\Catalogo\Modalidad;
use App\Enums\Catalogo\Regimen;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Support\Catalogo\Catalogo;
use App\Support\Catalogo\SystemKey;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The catalog is the foundation every other module holds foreign keys to, so
 * the seed is asserted as data: the counts, the code-owned keys resolving, and
 * re-seeding never reverting the agency's own edits.
 */
class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Every value the code addresses by name.
     *
     * @return list<SystemKey>
     */
    private function codeOwnedKeys(): array
    {
        return [
            EstadoCotizacion::Aprobada,
            EstadoFase::Aceptada,
            ColumnaTablero::Hecho,
            EstadoFactura::Pagada,
            Regimen::ResponsableIva,
            Modalidad::PrecioCerrado,
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        Catalogo::flush();
        $this->seed(CatalogSeeder::class);
    }

    public function test_it_seeds_the_whole_catalog(): void
    {
        $this->assertSame(16, CatalogList::query()->count(), 'El catalogo debe tener 16 listas.');
        $this->assertSame(73, CatalogValue::query()->count(), 'El catalogo debe tener 73 valores.');
        $this->assertSame(7, CatalogList::query()->distinct()->count('group'), 'El catalogo debe tener 7 grupos.');
    }

    public function test_every_code_owned_value_resolves(): void
    {
        foreach ($this->codeOwnedKeys() as $key) {
            $this->assertTrue(
                Catalogo::has($key),
                "El valor [{$key->systemKey()}] de la lista [{$key->listKey()}] debe existir."
            );
        }
    }

    public function test_only_the_code_owned_values_are_protected(): void
    {
        $protected = CatalogValue::query()->where('is_system', true)->count();

        $this->assertCount($protected, $this->codeOwnedKeys());
    }

    public function test_a_protected_value_carries_its_key_and_stays_active(): void
    {
        $values = CatalogValue::query()->where('is_system', true)->get();

        foreach ($values as $value) {
            $this->assertNotNull($value->system_key);
            $this->assertTrue($value->is_active);
        }
    }

    public function test_no_list_holds_more_than_one_default(): void
    {
        $lists = CatalogList::query()->with('values')->get();

        foreach ($lists as $list) {
            $defaults = $list->values->where('is_default', true)->count();

            $this->assertLessThanOrEqual(1, $defaults, "La lista [{$list->key}] tiene mas de un predeterminado.");
        }
    }

    public function test_disciplinas_ships_without_a_default_on_purpose(): void
    {
        $disciplinas = CatalogList::query()->where('key', 'disciplinas')->firstOrFail();

        $this->assertSame(6, $disciplinas->values()->count());
        $this->assertSame(0, $disciplinas->values()->where('is_default', true)->count());
    }

    public function test_a_value_only_stores_the_fields_its_list_declares(): void
    {
        $regimenes = CatalogList::query()->where('key', 'regimenes')->firstOrFail();

        $this->assertFalse($regimenes->usesField('color'));
        $this->assertSame(0, $regimenes->values()->whereNotNull('color')->count(), 'Una lista sin campo color no debe guardar colores.');
        $this->assertSame(4, $regimenes->values()->whereNotNull('code')->count());
    }

    public function test_the_declared_flag_is_resolved_onto_its_values(): void
    {
        $regimenes = CatalogList::query()->where('key', 'regimenes')->firstOrFail();

        $this->assertSame(['key' => 'cobra_iva', 'label' => 'Cobra IVA'], $regimenes->primaryFlag());

        $responsable = Catalogo::valueFor(Regimen::ResponsableIva);
        $noResponsable = $regimenes->values()->where('system_key', 'no-responsable-de-iva')->firstOrFail();

        $this->assertTrue($responsable->flag('cobra_iva'));
        $this->assertFalse($noResponsable->flag('cobra_iva'));
    }

    public function test_reseeding_is_idempotent(): void
    {
        $this->seed(CatalogSeeder::class);

        $this->assertSame(16, CatalogList::query()->count());
        $this->assertSame(73, CatalogValue::query()->count());
    }

    public function test_reseeding_never_reverts_an_edit_the_agency_made(): void
    {
        $value = Catalogo::valueFor(EstadoFactura::Pagada);
        $value->update(['name' => 'Pagada en su totalidad']);

        $this->seed(CatalogSeeder::class);

        $this->assertSame('Pagada en su totalidad', $value->fresh()->name);
        $this->assertSame(73, CatalogValue::query()->count(), 'Resembrar no debe duplicar un valor renombrado.');
    }

    public function test_reseeding_restores_protection_a_write_removed(): void
    {
        $value = Catalogo::valueFor(EstadoFactura::Pagada);
        $value->update(['is_system' => false]);

        $this->seed(CatalogSeeder::class);

        $this->assertTrue($value->fresh()->is_system);
    }

    public function test_team_roles_are_job_titles_and_not_system_roles(): void
    {
        $roles = CatalogList::query()->where('key', 'roles-equipo')->firstOrFail();

        $this->assertSame(7, $roles->values()->count());
        $this->assertSame(
            0,
            $roles->values()->where('is_system', true)->count(),
            'Los roles del equipo son cargos sin significado de autorizacion.'
        );
    }
}
