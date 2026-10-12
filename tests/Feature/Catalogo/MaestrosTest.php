<?php

namespace Tests\Feature\Catalogo;

use App\Enums\CatalogColor;
use App\Models\CatalogList;
use App\Models\CatalogValue;
use App\Models\User;
use App\Support\Catalogo\Catalogo;
use App\Support\Catalogo\UsageRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Maestros y Listas is administrative configuration that decides tax treatment,
 * so the authorization matrix matters more than the CRUD shape. Per the testing
 * standard, the CRUD itself is exercised on one representative list only.
 */
class MaestrosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Catalogo::flush();
        UsageRegistry::flush();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('maestros.index'))->assertRedirect(route('login'));
    }

    public function test_a_member_cannot_reach_the_screen_or_any_write(): void
    {
        $member = User::factory()->member()->create();
        $list = $this->makeList();
        $value = $list->values()->firstOrFail();

        $this->actingAs($member)->get(route('maestros.index'))->assertForbidden();
        $this->actingAs($member)->post(route('maestros.values.store', $list), ['name' => 'Nuevo'])->assertForbidden();
        $this->actingAs($member)->patch(route('maestros.values.update', $value), ['name' => 'Otro'])->assertForbidden();
        $this->actingAs($member)->delete(route('maestros.values.destroy', $value))->assertForbidden();
        $this->actingAs($member)->post(route('maestros.values.default', $value))->assertForbidden();
        $this->actingAs($member)->post(route('maestros.values.toggle', $value))->assertForbidden();
        $this->actingAs($member)->post(route('maestros.values.move', $value), ['direction' => 'up'])->assertForbidden();
    }

    public function test_a_client_cannot_reach_the_screen(): void
    {
        $this->actingAs(User::factory()->client()->create())
            ->get(route('maestros.index'))
            ->assertForbidden();
    }

    public function test_an_admin_receives_the_catalog_with_its_palette(): void
    {
        $this->makeList();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('maestros.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('maestros/index')
                ->has('lists', 1)
                ->has('palette', 8)
                ->has('icons', 16)
                ->where('summary', '1 listas · 2 valores. Cambiarlos afecta registros nuevos; los existentes conservan su valor.')
                ->where('lists.0.field_labels', ['Nombre', 'Color del badge', 'Estado final', 'Predeterminado', 'Activo'])
            );
    }

    public function test_the_badge_pair_is_resolved_on_the_server(): void
    {
        $list = $this->makeList();
        $value = $list->values()->where('name', 'Aprobada')->firstOrFail();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('maestros.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('lists.0.values.1.badge', [
                    'background' => CatalogColor::Verde->background(),
                    'foreground' => CatalogColor::Verde->foreground(),
                    'text' => 'Aprobada',
                ])
            );

        $this->assertSame(CatalogColor::Verde, $value->color);
    }

    public function test_an_admin_adds_a_value_at_the_end_of_the_list(): void
    {
        $list = $this->makeList();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('maestros.values.store', $list), [
                'name' => '  Anulada por el cliente  ',
                'color' => CatalogColor::Rojo->value,
                'flag' => true,
            ])
            ->assertRedirect(route('maestros.index'));

        $created = $list->values()->where('name', 'Anulada por el cliente')->firstOrFail();

        $this->assertSame(CatalogColor::Rojo, $created->color);
        $this->assertTrue($created->flag('estado_final'), 'La bandera declarada por la lista debe quedar resuelta en el valor.');
        $this->assertSame(2, $created->position, 'Un valor nuevo se agrega al final.');
        $this->assertNull($created->system_key, 'Un valor creado por el usuario no es del codigo.');
        $this->assertFalse($created->is_system);
    }

    public function test_a_duplicate_name_is_rejected_with_the_prototype_message(): void
    {
        $list = $this->makeList();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('maestros.values.store', $list), ['name' => 'APROBADA'])
            ->assertSessionHasErrors(['name' => 'Ya existe un valor con ese nombre en la lista.']);

        $this->assertSame(2, $list->values()->count());
    }

    public function test_a_duplicate_code_is_rejected(): void
    {
        $list = CatalogList::factory()->withFields(['code'])->create();
        CatalogValue::factory()->for($list, 'list')->create(['name' => 'NIT', 'code' => 'NIT']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('maestros.values.store', $list), ['name' => 'Otro', 'code' => 'nit'])
            ->assertSessionHasErrors(['code' => 'Ya existe un valor con ese código en la lista.']);
    }

    public function test_a_field_the_list_does_not_declare_is_never_stored(): void
    {
        $list = CatalogList::factory()->withFields(['code'])->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('maestros.values.store', $list), [
                'name' => 'Sin color',
                'color' => CatalogColor::Rojo->value,
                'icon' => 'bug',
                'description' => 'No deberia guardarse',
            ]);

        $created = $list->values()->where('name', 'Sin color')->firstOrFail();

        $this->assertNull($created->color, 'Una lista sin campo color no debe guardar color.');
        $this->assertNull($created->icon);
        $this->assertNull($created->description);
    }

    public function test_marking_a_default_clears_the_previous_one(): void
    {
        $list = $this->makeList();
        $borrador = $list->values()->where('name', 'Borrador')->firstOrFail();
        $aprobada = $list->values()->where('name', 'Aprobada')->firstOrFail();

        $this->assertTrue($borrador->is_default);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('maestros.values.default', $aprobada))
            ->assertRedirect(route('maestros.index'));

        $this->assertTrue($aprobada->fresh()->is_default);
        $this->assertFalse($borrador->fresh()->is_default);
        $this->assertSame(1, $list->values()->where('is_default', true)->count());
    }

    public function test_deactivating_a_value_also_clears_its_default(): void
    {
        $list = $this->makeList();
        $borrador = $list->values()->where('name', 'Borrador')->firstOrFail();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('maestros.values.toggle', $borrador))
            ->assertRedirect(route('maestros.index'));

        $borrador->refresh();

        $this->assertFalse($borrador->is_active);
        $this->assertFalse($borrador->is_default, 'Un predeterminado oculto preseleccionaria nada.');
    }

    public function test_a_value_the_code_owns_cannot_be_deactivated_by_anyone(): void
    {
        $list = $this->makeList();
        $protected = CatalogValue::factory()->for($list, 'list')->system('pagada')->create(['name' => 'Pagada']);

        foreach ([User::factory()->owner()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)
                ->post(route('maestros.values.toggle', $protected))
                ->assertForbidden();
        }

        $this->assertTrue($protected->fresh()->is_active);
    }

    public function test_a_value_the_code_owns_cannot_be_deleted_by_anyone(): void
    {
        $list = $this->makeList();
        $protected = CatalogValue::factory()->for($list, 'list')->system('pagada')->create(['name' => 'Pagada']);

        foreach ([User::factory()->owner()->create(), User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)
                ->delete(route('maestros.values.destroy', $protected))
                ->assertForbidden();
        }

        $this->assertModelExists($protected);
    }

    public function test_an_unused_value_is_deleted(): void
    {
        $list = $this->makeList();
        $aprobada = $list->values()->where('name', 'Aprobada')->firstOrFail();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('maestros.values.destroy', $aprobada))
            ->assertRedirect(route('maestros.index'));

        $this->assertModelMissing($aprobada);
    }

    public function test_a_value_in_use_is_refused_with_an_explanation_not_a_crash(): void
    {
        Schema::create('test_cotizaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estado_value_id')->constrained('catalog_values')->restrictOnDelete();
        });

        $list = $this->makeList();
        $aprobada = $list->values()->where('name', 'Aprobada')->firstOrFail();
        DB::table('test_cotizaciones')->insert(['estado_value_id' => $aprobada->id]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('maestros.values.destroy', $aprobada))
            ->assertRedirect(route('maestros.index'));

        $this->assertModelExists($aprobada);
    }

    public function test_moving_a_value_swaps_it_with_its_neighbour(): void
    {
        $list = $this->makeList();
        $borrador = $list->values()->where('name', 'Borrador')->firstOrFail();
        $aprobada = $list->values()->where('name', 'Aprobada')->firstOrFail();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('maestros.values.move', $aprobada), ['direction' => 'up'])
            ->assertRedirect(route('maestros.index'));

        $this->assertSame(0, $aprobada->fresh()->position);
        $this->assertSame(1, $borrador->fresh()->position);
    }

    public function test_moving_the_first_value_up_is_a_no_op(): void
    {
        $list = $this->makeList();
        $borrador = $list->values()->where('name', 'Borrador')->firstOrFail();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('maestros.values.move', $borrador), ['direction' => 'up'])
            ->assertRedirect(route('maestros.index'));

        $this->assertSame(0, $borrador->fresh()->position);
    }

    /**
     * One representative list: it declares a colour and a flag, which is the
     * widest shape the screen renders.
     */
    private function makeList(): CatalogList
    {
        $list = CatalogList::factory()
            ->key('estados-cotizacion-prueba')
            ->withFields(['color'])
            ->withFlag('estado_final', 'Estado final')
            ->create(['title' => 'Estados de cotización', 'group' => 'Comercial']);

        CatalogValue::factory()->for($list, 'list')->isDefault()->create([
            'name' => 'Borrador',
            'color' => CatalogColor::Gris,
            'position' => 0,
        ]);

        CatalogValue::factory()->for($list, 'list')->flags(['estado_final' => true])->create([
            'name' => 'Aprobada',
            'color' => CatalogColor::Verde,
            'position' => 1,
        ]);

        return $list;
    }
}
