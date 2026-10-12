<?php

namespace Tests\Feature\Clientes;

use App\Models\Cliente;
use App\Models\User;
use App\Support\Catalogo\UsageRegistry;
use App\Support\Clientes\ClienteUsage;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Clientes holds the agency's commercial information, so the authorization
 * matrix and the fiscal validation matter more than the CRUD shape.
 */
class ClientesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        UsageRegistry::flush();
        ClienteUsage::flush();
        $this->seed(CatalogSeeder::class);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('clientes.index'))->assertRedirect(route('login'));
    }

    public function test_a_member_cannot_reach_the_module_or_any_write(): void
    {
        $member = User::factory()->member()->create();
        $cliente = Cliente::factory()->conContactoPrincipal()->create();

        $this->actingAs($member)->get(route('clientes.index'))->assertForbidden();
        $this->actingAs($member)->get(route('clientes.create'))->assertForbidden();
        $this->actingAs($member)->get(route('clientes.show', $cliente))->assertForbidden();
        $this->actingAs($member)->get(route('clientes.edit', $cliente))->assertForbidden();
        $this->actingAs($member)->post(route('clientes.store'), [])->assertForbidden();
        $this->actingAs($member)->patch(route('clientes.update', $cliente), [])->assertForbidden();
        $this->actingAs($member)->post(route('clientes.toggle', $cliente))->assertForbidden();
        $this->actingAs($member)->delete(route('clientes.destroy', $cliente))->assertForbidden();
    }

    public function test_a_member_payload_never_mentions_clientes(): void
    {
        Cliente::factory()->conContactoPrincipal()->create(['nombre' => 'Andina Seguros']);

        $response = $this->actingAs(User::factory()->member()->create())->get(route('dashboard'));

        $this->assertStringNotContainsString('Andina Seguros', $response->getContent() ?: '');
    }

    public function test_an_admin_sees_the_list(): void
    {
        Cliente::factory()->conContactoPrincipal()->create(['nombre' => 'Andina Seguros']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('clientes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('clientes/index')
                ->has('clientes.data', 1)
                ->where('clientes.data.0.nombre', 'Andina Seguros')
                ->where('clientes.data.0.iniciales', 'AS')
                ->where('resumen.total', 1)
            );
    }

    public function test_creating_a_client_derives_its_tag_and_verification_digit(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('clientes.store'), $this->payload())
            ->assertRedirect();

        $cliente = Cliente::query()->firstOrFail();

        $this->assertSame('890903938', $cliente->documento, 'Los puntos y el DV escrito son presentacion.');
        $this->assertSame('8', $cliente->dv, 'El digito de verificacion se deriva, no se toma del formulario.');
        $this->assertSame('TRA', $cliente->tag, 'El tag se genera desde el nombre cuando el formulario lo deja vacio.');
        $this->assertSame(1, $cliente->contactos()->where('is_principal', true)->count());
    }

    public function test_a_wrong_verification_digit_is_rejected(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('clientes.store'), $this->payload(['documento' => '890.903.938-1']))
            ->assertSessionHasErrors(['documento' => 'El dígito de verificación no corresponde al NIT.']);

        $this->assertSame(0, Cliente::query()->count());
    }

    public function test_a_duplicate_document_is_rejected(): void
    {
        Cliente::factory()->conContactoPrincipal()->create(['documento' => '890903938']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('clientes.store'), $this->payload())
            ->assertSessionHasErrors(['documento' => 'Ya existe un cliente con ese documento.']);
    }

    public function test_a_tag_another_client_uses_is_rejected(): void
    {
        Cliente::factory()->conContactoPrincipal()->create(['tag' => 'TRA']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('clientes.store'), $this->payload(['tag' => 'tra']))
            ->assertSessionHasErrors(['tag']);
    }

    public function test_the_generated_tag_avoids_a_collision(): void
    {
        Cliente::factory()->conContactoPrincipal()->create(['nombre' => 'Transportes Aurora', 'tag' => 'TRA']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('clientes.store'), $this->payload());

        $creado = Cliente::query()->where('documento', '890903938')->firstOrFail();

        $this->assertSame('TR2', $creado->tag, 'Dos clientes no pueden compartir el prefijo de numeracion.');
    }

    public function test_a_client_must_carry_exactly_one_principal_contact(): void
    {
        $admin = User::factory()->admin()->create();
        $payload = $this->payload();

        $sinPrincipal = $payload;
        $sinPrincipal['contactos'][0]['is_principal'] = '0';

        $this->actingAs($admin)
            ->post(route('clientes.store'), $sinPrincipal)
            ->assertSessionHasErrors(['contactos' => 'Marca exactamente un contacto principal.']);

        $dosPrincipales = $payload;
        $dosPrincipales['contactos'][] = [
            'nombre' => 'Otro contacto',
            'email' => 'otro@aurora.co',
            'is_principal' => '1',
        ];

        $this->actingAs($admin)
            ->post(route('clientes.store'), $dosPrincipales)
            ->assertSessionHasErrors(['contactos']);

        $this->assertSame(0, Cliente::query()->count());
    }

    public function test_a_regimen_cannot_be_submitted_as_a_document_type(): void
    {
        $regimen = DB::table('catalog_values')->where('system_key', 'responsable-de-iva')->value('id');

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('clientes.store'), $this->payload(['tipo_documento_value_id' => $regimen]))
            ->assertSessionHasErrors(['tipo_documento_value_id']);
    }

    public function test_updating_replaces_the_contact_list(): void
    {
        $cliente = Cliente::factory()->conContactoPrincipal()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('clientes.update', $cliente), $this->payload([
                'documento' => $cliente->documento,
                'tag' => $cliente->tag,
            ]))
            ->assertRedirect(route('clientes.show', $cliente));

        $cliente->refresh();

        $this->assertSame(1, $cliente->contactos()->count());
        $this->assertSame('marcela@aurora.co', $cliente->contactos()->firstOrFail()->email);
    }

    public function test_archiving_keeps_the_client_and_its_history(): void
    {
        $cliente = Cliente::factory()->conContactoPrincipal()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('clientes.toggle', $cliente))
            ->assertRedirect(route('clientes.show', $cliente));

        $this->assertFalse($cliente->fresh()->is_active);
        $this->assertModelExists($cliente);
    }

    public function test_the_estado_filter_narrows_the_list(): void
    {
        Cliente::factory()->conContactoPrincipal()->create(['nombre' => 'Activa SAS']);
        Cliente::factory()->conContactoPrincipal()->inactivo()->create(['nombre' => 'Archivada SAS']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('clientes.index', ['estado' => 'inactivos']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('clientes.data', 1)
                ->where('clientes.data.0.nombre', 'Archivada SAS')
            );
    }

    public function test_the_search_ignores_accents_and_reaches_the_contact(): void
    {
        $cliente = Cliente::factory()->conContactoPrincipal()->create(['nombre' => 'Cafetera del Café']);
        $cliente->contactos()->firstOrFail()->update(['nombre' => 'Marcela Peña', 'email' => 'mpena@cafe.co']);

        $admin = User::factory()->admin()->create();

        foreach (['cafe', 'CAFÉ', 'Pena', 'mpena@cafe.co'] as $termino) {
            $this->actingAs($admin)
                ->get(route('clientes.index', ['buscar' => $termino]))
                ->assertInertia(fn (Assert $page) => $page->has('clientes.data', 1));
        }
    }

    public function test_the_detail_renders_notes_sanitized_on_the_server(): void
    {
        $cliente = Cliente::factory()->conContactoPrincipal()->create([
            'notas' => "**Ojo** con [esto](javascript:alert(1)) y con <script>alert(2)</script>\n- [Portal](https://orbita.co)",
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('clientes.show', $cliente))
            ->assertInertia(function (Assert $page): void {
                $html = $page->toArray()['props']['cliente']['notas_html'];

                $this->assertStringContainsString('<strong>Ojo</strong>', $html);
                $this->assertStringContainsString('href="https://orbita.co"', $html);

                // The rejected link survives as literal text on purpose —
                // nothing the user wrote disappears silently — but it is never
                // an href.
                $this->assertStringNotContainsString('href="javascript:', $html);
                $this->assertStringContainsString('[esto](javascript:alert(1))', $html);

                $this->assertStringNotContainsString('<script>', $html);
                $this->assertStringContainsString('&lt;script&gt;', $html);
            });
    }

    public function test_an_unused_client_is_deleted_with_its_contacts(): void
    {
        $cliente = Cliente::factory()->conContactoPrincipal()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('clientes.destroy', $cliente))
            ->assertRedirect(route('clientes.index'));

        $this->assertModelMissing($cliente);
        $this->assertSame(0, DB::table('cliente_contactos')->count());
    }

    public function test_a_catalog_value_used_by_a_client_can_no_longer_be_deleted(): void
    {
        $cliente = Cliente::factory()->conContactoPrincipal()->create();
        UsageRegistry::flush();

        $this->assertTrue(
            UsageRegistry::isInUse($cliente->tipo_documento_value_id),
            'El registro de uso debe contar al cliente como consumidor del catalogo.'
        );

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('maestros.values.destroy', $cliente->tipo_documento_value_id))
            ->assertRedirect(route('maestros.index'));

        $this->assertDatabaseHas('catalog_values', ['id' => $cliente->tipo_documento_value_id]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'tipo_persona' => 'juridica',
            'tipo_documento_value_id' => DB::table('catalog_values')->where('system_key', 'nit')->value('id'),
            'regimen_value_id' => DB::table('catalog_values')->where('system_key', 'responsable-de-iva')->value('id'),
            'documento' => '890.903.938-8',
            'nombre' => 'Transportes Aurora SAS',
            'tag' => '',
            'ciudad' => 'Medellín',
            'departamento' => 'Antioquia',
            'direccion' => 'Calle 10 # 43-15',
            'notas' => '',
            'is_active' => '1',
            'contactos' => [
                [
                    'nombre' => 'Marcela Peña',
                    'cargo' => 'Gerente',
                    'email' => 'marcela@aurora.co',
                    'telefono' => '+57 300 000 0000',
                    'is_principal' => '1',
                ],
            ],
            ...$overrides,
        ];
    }
}
