<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * The navigation is resolved on the SERVER and shared already filtered by role.
 * The client never decides permissions, so these tests assert on the Inertia
 * payload rather than on what the sidebar happens to render.
 */
class NavigationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Section keys a Miembro del equipo must never receive, not even disabled.
     *
     * @var list<string>
     */
    private const ADMIN_ONLY_SECTIONS = [
        'clientes',
        'cotizaciones',
        'facturas',
        'egresos',
        'equipo',
        'usuarios',
        'parametros',
        'maestros',
    ];

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_an_admin_receives_every_section(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.role', 'admin')
                ->has('navigation')
            );

        $keys = $this->navigationKeys($admin);

        foreach (self::ADMIN_ONLY_SECTIONS as $section) {
            $this->assertContains($section, $keys, "El admin deberia ver [{$section}].");
        }

        $this->assertContains('panel', $keys);
        $this->assertContains('proyectos', $keys);
    }

    public function test_the_owner_receives_the_same_sections_as_an_admin(): void
    {
        $ownerKeys = $this->navigationKeys(User::factory()->owner()->create());

        foreach (self::ADMIN_ONLY_SECTIONS as $section) {
            $this->assertContains($section, $ownerKeys);
        }
    }

    public function test_a_member_receives_only_the_operational_sections(): void
    {
        $member = User::factory()->member()->create();

        $keys = $this->navigationKeys($member);

        $this->assertSame(
            ['mi-panel', 'proyectos', 'mi-tiempo', 'documentos'],
            $keys,
            'El nav del miembro debe ser exactamente el del prototipo.',
        );
    }

    public function test_a_member_payload_never_contains_an_admin_section(): void
    {
        $member = User::factory()->member()->create();

        $payload = $this->actingAs($member)
            ->get(route('dashboard'))
            ->getContent();

        foreach (self::ADMIN_ONLY_SECTIONS as $section) {
            $this->assertStringNotContainsString(
                "\"{$section}\"",
                (string) $payload,
                "La seccion [{$section}] se filtro al payload de un miembro.",
            );
        }
    }

    /**
     * Both roles land on the same route; the screen titles itself per role.
     */
    public function test_the_dashboard_titles_itself_per_role(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.role', 'admin'));

        $this->actingAs(User::factory()->member()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('auth.role', 'member'));
    }

    public function test_the_role_is_shared_with_its_spanish_label(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.role', 'member')
                ->where('auth.roleLabel', 'Miembro del equipo')
            );
    }

    /**
     * Flattened section keys from the shared navigation, in order, children
     * included.
     *
     * @return list<string>
     */
    private function navigationKeys(User $user): array
    {
        $groups = $this->actingAs($user)
            ->get(route('dashboard'))
            ->viewData('page')['props']['navigation'];

        $keys = [];

        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $keys[] = $item['key'];

                // Configuración nests its sections as children.
                foreach ($item['children'] ?? [] as $child) {
                    $keys[] = $child['key'];
                }
            }
        }

        return $keys;
    }
}
